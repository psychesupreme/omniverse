<?php

namespace App\Models;

use App\Casts\PostgisPointCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrackingLog extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tracking_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'user_id',
        'location',
        'speed',
        'recorded_at_mobile',
        'synced_at',
        'version',
        'last_updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'location' => PostgisPointCast::class,
        'recorded_at_mobile' => 'datetime',
        'synced_at' => 'datetime',
        'last_updated_at' => 'datetime',
    ];
}
