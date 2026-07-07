<?php

namespace App\Models;

use App\Casts\PostgisPolygonCast;
use App\Traits\IsSyncable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Geofence extends Model
{
    use HasFactory, IsSyncable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'geofences';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'name',
        'boundary',
        'version',
        'last_updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'boundary' => PostgisPolygonCast::class,
        'last_updated_at' => 'datetime',
    ];
}
