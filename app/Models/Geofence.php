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
        'description',
        'boundary',
        'area',
        'is_active',
        'version',
        'last_updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'boundary'        => PostgisPolygonCast::class,
        'area'            => PostgisPolygonCast::class,
        'is_active'       => 'boolean',
        'last_updated_at' => 'datetime',
    ];

    /**
     * Boot function to automatically keep boundary and area in sync.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($geofence) {
            // Keep dynamic UUID generation if not set
            if (empty($geofence->id)) {
                $geofence->id = (string) \Illuminate\Support\Str::uuid();
            }

            // Sync boundary and area for backward compatibility by inspecting raw attributes
            $attributes = $geofence->getAttributes();

            if (array_key_exists('area', $attributes) && !array_key_exists('boundary', $attributes)) {
                $geofence->attributes['boundary'] = $attributes['area'];
            } elseif (array_key_exists('boundary', $attributes) && !array_key_exists('area', $attributes)) {
                $geofence->attributes['area'] = $attributes['boundary'];
            }
        });
    }
}
