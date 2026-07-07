<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PostgisPointCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     * @return array{latitude: float, longitude: float}|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if (is_null($value)) {
            return null;
        }

        // Handle raw hex EWKB from PostgreSQL (e.g. '0101000020E6100000...')
        if (is_string($value) && preg_match('/^[0-9a-fA-F]+$/', $value)) {
            $binary = hex2bin($value);
            
            // Unpack byte order
            $byteOrder = unpack('C', $binary[0])[1];
            $isLittle = ($byteOrder === 1);
            
            $typeFormat = $isLittle ? 'V' : 'N';
            $type = unpack($typeFormat, substr($binary, 1, 4))[1];
            
            // Check if SRID is present (0x20000000 flag)
            $hasSrid = ($type & 0x20000000) !== 0;
            $offset = 5;
            if ($hasSrid) {
                $offset += 4; // Skip SRID integer
            }
            
            $coords = unpack($isLittle ? 'dlongitude/dlatitude' : 'dlongitude/dlatitude', substr($binary, $offset, 16));
            
            return [
                'latitude' => (float) $coords['latitude'],
                'longitude' => (float) $coords['longitude'],
            ];
        }

        // Handle WKT string (e.g., 'POINT(lng lat)')
        if (is_string($value) && preg_match('/POINT\s*\(\s*(-?\d+\.?\d*)\s+(-?\d+\.?\d*)\s*\)/i', $value, $matches)) {
            return [
                'latitude' => (float) $matches[2],
                'longitude' => (float) $matches[1],
            ];
        }

        return null;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (is_null($value)) {
            return null;
        }

        if (is_array($value) && isset($value['latitude']) && isset($value['longitude'])) {
            return DB::raw("ST_GeogFromText('SRID=4326;POINT(" . $value['longitude'] . " " . $value['latitude'] . ")')");
        }

        return $value;
    }
}
