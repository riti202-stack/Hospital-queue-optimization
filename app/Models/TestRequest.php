<?php

namespace App\Models;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;

class TestRequest extends Model
{
    // lab capacity rules -change here 

    const SLOT_MINUTES = 30;
    const SLOT_CAPACITY = 3;
    const OPEN_HOUR = 8; //8:00 AM
    const CLOSE_HOUR = 18 ; //6:00 PM

    protected $fillable =[
        'patient_id','doctor_id','status','preferred_at','suggested_at','scheduled_at','room_no','conducted_by','total_amount','patient_note','admin_message','dependent_id',

    ];

    protected $casts = [
        'preferred_at' => 'datetime',
        'suggested_at' => 'datetime',
        'scheduled_at' => 'datetime',

    ];

    public function patient()
    {
        return $this->belongsTo(User::class,'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function items()
    {
        return $this->hasMany(TestRequestItem::class);
    }

    public function getCodeAttribute(): string
    {
        return 'TR-' . str_pad($this->id, 5, '0', STR_PAD_LEFT);
    }

    /** [label, badge class] */
    public function statusBadge(): array
    {
        return match ($this->status) {
            'pending'   => ['Waiting for admin', 'bg-warning text-dark'],
            'suggested' => ['New time suggested', 'bg-info text-dark'],
            'accepted'  => ['Patient accepted — to book', 'bg-primary'],
            'scheduled' => ['Booked', 'bg-success'],
            'completed' => ['Done', 'bg-dark'],
            default     => ['Cancelled', 'bg-secondary'],
        };
    }

    // ===== Slot helpers =====

    public static function slotStart(Carbon $t): Carbon
    {
        $m = intdiv($t->minute, self::SLOT_MINUTES) * self::SLOT_MINUTES;
        return $t->copy()->setTime($t->hour, $m, 0);
    }

    /** Lab open: Saturday–Thursday, OPEN_HOUR to CLOSE_HOUR */
    public static function isOpen(Carbon $t): bool
    {
        return ! $t->isFriday() && $t->hour >= self::OPEN_HOUR && $t->hour < self::CLOSE_HOUR;
    }

    public static function bookedInSlot(Carbon $slot, ?int $exceptId = null): int
    {
        return static::where('status', 'scheduled')
            ->where('scheduled_at', '>=', $slot)
            ->where('scheduled_at', '<', $slot->copy()->addMinutes(self::SLOT_MINUTES))
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->count();
    }

    /** Nearest free slot: same day closest first (before or after), then the following days */
    public static function nearestFree(Carbon $preferred, ?int $exceptId = null): ?Carbon
    {
        $pref = static::slotStart($preferred);

        for ($day = 0; $day < 7; $day++) {
            $date = $pref->copy()->startOfDay()->addDays($day);
            $slots = [];
            for ($t = $date->copy()->setTime(self::OPEN_HOUR, 0); $t->hour < self::CLOSE_HOUR; $t->addMinutes(self::SLOT_MINUTES)) {
                $slots[] = $t->copy();
            }

            if ($day === 0) {
                usort($slots, fn ($a, $b) => abs($a->diffInMinutes($pref, false)) <=> abs($b->diffInMinutes($pref, false)));
            }

            foreach ($slots as $s) {
                if (! static::isOpen($s) || $s->lte(now())) continue;
                if (static::bookedInSlot($s, $exceptId) < self::SLOT_CAPACITY) return $s;
            }
        }

        return null;
    }




}