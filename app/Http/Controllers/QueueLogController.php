<?php
namespace App\Http\Controllers;

use App\Models\QueueLog;

class QueueLogController extends Controller
{
    public function index()
    {
        $logs = QueueLog::with('queueEntry.patient')->latest()->paginate(15);
        return view('queue-logs.index', compact('logs'));
    }

    public function destroy(QueueLog $queueLog)
    {
        $queueLog->delete();
        return redirect()->route('queue-logs.index')->with('success', 'Log entry deleted.');
    }
}