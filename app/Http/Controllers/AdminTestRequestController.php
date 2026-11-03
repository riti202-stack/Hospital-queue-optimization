<?php

namespace App\Http\Controllers;

use App\Models\TestRequest;
use App\Notifications\TestRequestUpdated;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminTestRequestController extends Controller
{
    private array $tabs = [
        'new'       => ['New requests', ['pending']],
        'to-book'   => ['Accepted — to book', ['accepted']],
        'waiting'   => ['Waiting for patient', ['suggested']],
        'scheduled' => ['Booked', ['scheduled']],
        'finished'  => ['Finished', ['completed', 'cancelled']],
    ];

    public function index(Request $request)
    {
        $tab = array_key_exists($request->tab, $this->tabs) ? $request->tab : 'new';

        $counts = [];
        foreach ($this->tabs as $key => [, $statuses]) {
            $counts[$key] = TestRequest::whereIn('status', $statuses)->count();
        }

        $requests = TestRequest::with('patient')->withCount('items')
            ->whereIn('status', $this->tabs[$tab][1])
            ->orderBy($tab === 'scheduled' ? 'scheduled_at' : 'created_at', $tab === 'finished' ? 'desc' : 'asc')
            ->paginate(20)->withQueryString();

        return view('admin.test-requests.index', ['tabs' => $this->tabs, 'tab' => $tab, 'counts' => $counts, 'requests' => $requests]);
    }

    public function show(TestRequest $testRequest)
    {
        $r = $testRequest->load('items.labTest', 'patient', 'doctor.user');

        // The time we are trying to book: accepted suggestion, current booking, or the patient's choice
        $target = $r->status === 'accepted' && $r->suggested_at
            ? $r->suggested_at
            : ($r->scheduled_at ?? $r->preferred_at);

        $targetSlot = TestRequest::slotStart($target);
        $targetUsed = TestRequest::bookedInSlot($targetSlot, $r->id);
        $targetFree = $targetUsed < TestRequest::SLOT_CAPACITY && TestRequest::isOpen($targetSlot) && $targetSlot->isFuture();
        $nearest    = TestRequest::nearestFree($target, $r->id);

        $bookAt = $targetFree ? $targetSlot : ($nearest ?? $targetSlot);

        // Load of every slot on the booking day
        $daySlots = [];
        for ($t = $bookAt->copy()->setTime(TestRequest::OPEN_HOUR, 0); $t->hour < TestRequest::CLOSE_HOUR; $t->addMinutes(TestRequest::SLOT_MINUTES)) {
            $daySlots[] = ['time' => $t->copy(), 'used' => TestRequest::bookedInSlot($t, $r->id)];
        }

        $defaultRoom = $r->room_no ?: $r->items->map(fn ($i) => $i->labTest?->room_no)->filter()->unique()->join(', ');

        $apology = 'Dear ' . ($r->patient->name ?? 'patient') . ', we are sorry — the time you asked for ('
            . $r->preferred_at->format('D, d M \a\t h:i A') . ') is fully booked because of high demand. '
            . ($nearest ? 'The nearest free time is ' . $nearest->format('D, d M \a\t h:i A') . '. ' : '')
            . 'Please accept it if it suits you, or suggest another time.';

        return view('admin.test-requests.show', compact(
            'r', 'target', 'targetSlot', 'targetUsed', 'targetFree', 'nearest', 'bookAt', 'daySlots', 'defaultRoom', 'apology'
        ));
    }

    /** Book the slot (date, time, room, doctor) */
    public function book(Request $request, TestRequest $testRequest)
    {
        abort_unless(in_array($testRequest->status, ['pending', 'accepted', 'scheduled']), 403);

        $data = $request->validate([
            'date'         => 'required|date',
            'time'         => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'room_no'      => 'required|string|max:100',
            'conducted_by' => 'required|string|max:150',
        ]);

        $at = Carbon::parse($data['date'] . ' ' . $data['time']);
        if ($at->isPast() || ! TestRequest::isOpen($at)) {
            return back()->withInput()->withErrors(['time' => 'Choose a future time inside lab hours (Sat–Thu, 8 AM – 6 PM).']);
        }

        $used = TestRequest::bookedInSlot(TestRequest::slotStart($at), $testRequest->id);
        if ($used >= TestRequest::SLOT_CAPACITY) {
            $n = TestRequest::nearestFree($at, $testRequest->id);
            return back()->withInput()->withErrors(['time' =>
                "That slot is full ({$used}/" . TestRequest::SLOT_CAPACITY . ').'
                . ($n ? ' Nearest free slot: ' . $n->format('D, d M h:i A') . '. Book that, or send the patient a suggestion.' : '')]);
        }

        $testRequest->update([
            'status'       => 'scheduled',
            'scheduled_at' => $at,
            'room_no'      => $data['room_no'],
            'conducted_by' => $data['conducted_by'],
        ]);

        $this->notify($testRequest, 'scheduled');

        return redirect()->route('admin.test-requests.index', ['tab' => 'scheduled'])
            ->with('success', "{$testRequest->code} booked for " . $at->format('D, d M h:i A') . '. The patient has been notified.');
    }

    /** Can't book: apologise and suggest another time */
    public function suggest(Request $request, TestRequest $testRequest)
    {
        abort_unless(in_array($testRequest->status, ['pending', 'accepted']), 403);

        $data = $request->validate([
            'date'          => 'required|date',
            'time'          => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'admin_message' => 'required|string|max:1000',
        ]);

        $at = Carbon::parse($data['date'] . ' ' . $data['time']);
        if ($at->isPast() || ! TestRequest::isOpen($at)) {
            return back()->withInput()->withErrors(['suggest_time' => 'The suggested time must be in the future and inside lab hours.']);
        }

        $testRequest->update([
            'status'        => 'suggested',
            'suggested_at'  => $at,
            'admin_message' => $data['admin_message'],
        ]);

        $this->notify($testRequest, 'suggested');

        return redirect()->route('admin.test-requests.index', ['tab' => 'waiting'])
            ->with('success', "Suggestion sent to the patient for {$testRequest->code}.");
    }

    public function complete(TestRequest $testRequest)
    {
        abort_unless($testRequest->status === 'scheduled', 403);
        $testRequest->update(['status' => 'completed']);

        return back()->with('success', "{$testRequest->code} marked as done.");
    }

    public function cancel(TestRequest $testRequest)
    {
        abort_unless(! in_array($testRequest->status, ['completed', 'cancelled']), 403);
        $testRequest->update(['status' => 'cancelled']);

        return back()->with('success', "{$testRequest->code} cancelled.");
    }

    private function notify(TestRequest $r, string $kind): void
    {
        try {
            $r->loadMissing('patient');
            $r->patient?->notify(new TestRequestUpdated($r, $kind));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}