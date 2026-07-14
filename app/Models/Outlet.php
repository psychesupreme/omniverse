<?php

namespace App\Models;

use App\Casts\PostgisPointCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Outlet extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'outlets';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'name',
        'phone',
        'email',
        'address',
        'status',
        'location',
        'version',
        'last_updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'location'        => PostgisPointCast::class,
        'last_updated_at' => 'datetime',
    ];

    /**
     * Get the interaction logs associated with this outlet.
     */
    public function interactionLogs(): HasMany
    {
        return $this->hasMany(InteractionLog::class, 'outlet_id');
    }

    /**
     * Get the tasks associated with this outlet.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'outlet_id');
    }
}
