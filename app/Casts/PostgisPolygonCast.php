<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PostgisPolygonCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<int, array{lat: float, lng: float}>|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if (is_null($value)) {
            return null;
        }

        // Handle raw hex EWKB from PostgreSQL (e.g., '0103000020E6100000...')
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

            // Next 4 bytes: Number of rings
            $numRings = unpack($isLittle ? 'V' : 'N', substr($binary, $offset, 4))[1];
            $offset += 4;

            $rings = [];
            for ($r = 0; $r < $numRings; $r++) {
                // Next 4 bytes: Number of points in this ring
                $numPoints = unpack($isLittle ? 'V' : 'N', substr($binary, $offset, 4))[1];
                $offset += 4;

                $points = [];
                for ($p = 0; $p < $numPoints; $p++) {
                    $coords = unpack($isLittle ? 'dlongitude/dlatitude' : 'dlongitude/dlatitude', substr($binary, $offset, 16));
                    $offset += 16;
                    $points[] = [
                        'lat' => (float) $coords['latitude'],
                        'lng' => (float) $coords['longitude'],
                    ];
                }
                $rings[] = $points;
            }

            return $rings[0] ?? null;
        }

        // Handle WKT string (e.g., 'POLYGON((lng1 lat1, lng2 lat2, ...))' or with SRID prefix)
        if (is_string($value) && preg_match('/POLYGON\s*\(\s*\(\s*(.*?)\s*\)\s*\)/i', $value, $matches)) {
            $coordsStr = $matches[1];
            $pointsStr = explode(',', $coordsStr);
            $points = [];
            foreach ($pointsStr as $pointStr) {
                $pointStr = trim($pointStr);
                $parts = preg_split('/\s+/', $pointStr);
                if (count($parts) >= 2) {
                    $points[] = [
                        'lat' => (float) $parts[1],
                        'lng' => (float) $parts[0],
                    ];
                }
            }
            return $points;
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

        if (is_array($value)) {
            $coords = $value;
            if (empty($coords)) {
                return null;
            }

            // Close the ring if first and last elements do not match
            $first = $coords[0];
            $last = end($coords);
            if ($first['lat'] !== $last['lat'] || $first['lng'] !== $last['lng']) {
                $coords[] = $first;
            }

            $points = [];
            foreach ($coords as $coord) {
                $points[] = $coord['lng'] . ' ' . $coord['lat'];
            }

            $wkt = 'SRID=4326;POLYGON((' . implode(',', $points) . '))';
            return DB::raw("ST_GeogFromText('{$wkt}')");
        }

        return $value;
    }
}
