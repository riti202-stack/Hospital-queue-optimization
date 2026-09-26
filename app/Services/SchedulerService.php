<?php
namespace App\Services;

use App\Models\QueueEntry;
use Carbon\Carbon;

class SchedulerService
{
    protected array $urgencyWeights = [
        'emergency' => 100,
        'urgent'    => 50,
        'routine'   => 10,
    ];

    protected float $agingRate = 0.5;

    public function calculatePriority(QueueEntry $entry): float
    {
        $baseWeight = $this->urgencyWeights[$entry->urgency_level] ?? 0;
        $minutesWaited = Carbon::parse($entry->checked_in_at)->diffInMinutes(now());

        return $baseWeight + ($minutesWaited * $this->agingRate);
    }

    public function refreshAllPriorities(): void
    {
        $waiting = QueueEntry::where('status', 'waiting')->get();

        foreach ($waiting as $entry) {
            $entry->priority_score = $this->calculatePriority($entry);
            $entry->save();
        }
    }

    public function getOrderedQueue(?int $departmentId = null)
    {
        $this->refreshAllPriorities();

        $query = QueueEntry::with(['patient', 'doctor.user', 'department'])
            ->where('status', 'waiting')
            ->orderByDesc('priority_score');

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        return $query->get();
    }
}