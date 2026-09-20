<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueLog extends Model
{
   protected $fillable = ['queue_entry_id', 'action'];

    public function queueEntry() { return $this->belongsTo(QueueEntry::class); }
}
