<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tasks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'outlet_id',
        'assigned_user_id',
        'title',
        'description',
        'status',
        'scheduled_for',
        'completed_at',
        'completion_notes',
        'evidence_photo_path',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'scheduled_for' => 'datetime',
        'completed_at'  => 'datetime',
    ];

    /**
     * Get the outlet associated with this task.
     */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'outlet_id');
    }

    /**
     * Get the user assigned to this task.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
}
