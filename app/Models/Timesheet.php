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
        'date',
        'clock_in_time',
        'clock_out_time',
        'total_minutes',
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
            'date'           => 'date',
            'clock_in_time'  => 'datetime',
            'clock_out_time' => 'datetime',
            'total_minutes'  => 'integer',
        ];
    }

    /**
     * Relationship with the User model.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
