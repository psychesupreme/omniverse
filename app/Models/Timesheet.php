<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Timesheet extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'timesheets';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'geofence_id',
        'date',
        'clock_in_time',
        'clock_out_time',
        'clock_in',
        'clock_out',
        'total_minutes',
        'shift_duration_minutes',
        'entry_event_id',
        'exit_event_id',
        'is_automated',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date'                   => 'date',
            'clock_in_time'          => 'datetime',
            'clock_out_time'         => 'datetime',
            'clock_in'               => 'datetime',
            'clock_out'              => 'datetime',
            'total_minutes'          => 'integer',
            'shift_duration_minutes' => 'integer',
            'is_automated'           => 'boolean',
        ];
    }

    /**
     * Relationship with the User model.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship with the Geofence model.
     */
    public function geofence(): BelongsTo
    {
        return $this->belongsTo(Geofence::class, 'geofence_id');
    }

    /**
     * Relationship with entry geofence log event.
     */
    public function entryEvent(): BelongsTo
    {
        return $this->belongsTo(GeofenceLog::class, 'entry_event_id');
    }

    /**
     * Relationship with exit geofence log event.
     */
    public function exitEvent(): BelongsTo
    {
        return $this->belongsTo(GeofenceLog::class, 'exit_event_id');
    }

    /**
     * Check if the user's shift is currently active (clocked in but not clocked out).
     */
    public function isShiftActive(): bool
    {
        $in = $this->clock_in ?? $this->clock_in_time;
        $out = $this->clock_out ?? $this->clock_out_time;

        return !is_null($in) && is_null($out);
    }
}
