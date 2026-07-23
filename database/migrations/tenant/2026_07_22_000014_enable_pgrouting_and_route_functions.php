<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Check if pgrouting extension is available before attempting creation to avoid PostgreSQL transaction abort
        $available = DB::select("SELECT 1 FROM pg_available_extensions WHERE name = 'pgrouting'");
        if (!empty($available)) {
            try {
                DB::statement('CREATE EXTENSION IF NOT EXISTS pgrouting CASCADE;');
            } catch (\Exception $e) {
                logger()->warning('pgrouting extension creation skipped: ' . $e->getMessage());
            }
        }

        // 2. Create spatial SQL function for nearest-neighbor / TSP task sequence optimization using PostGIS
        DB::statement("
            CREATE OR REPLACE FUNCTION get_optimized_task_sequence(
                worker_id_param BIGINT,
                start_lat DOUBLE PRECISION,
                start_lng DOUBLE PRECISION
            )
            RETURNS TABLE (
                task_id BIGINT,
                title VARCHAR,
                outlet_id BIGINT,
                outlet_name VARCHAR,
                latitude DOUBLE PRECISION,
                longitude DOUBLE PRECISION,
                distance_meters DOUBLE PRECISION,
                sequence_order INT
            ) AS $$
            BEGIN
                RETURN QUERY
                WITH start_point AS (
                    SELECT ST_SetSRID(ST_MakePoint(start_lng, start_lat), 4326)::geography AS geom
                ),
                worker_tasks AS (
                    SELECT 
                        t.id AS task_id,
                        t.title,
                        o.id AS outlet_id,
                        o.name AS outlet_name,
                        ST_Y(o.location::geometry) AS latitude,
                        ST_X(o.location::geometry) AS longitude,
                        ST_Distance(o.location, sp.geom) AS distance_meters
                    FROM tasks t
                    JOIN outlets o ON t.outlet_id = o.id
                    CROSS JOIN start_point sp
                    WHERE t.assigned_user_id = worker_id_param
                      AND t.status IN ('pending', 'accepted', 'in_progress')
                )
                SELECT 
                    wt.task_id,
                    wt.title,
                    wt.outlet_id,
                    wt.outlet_name,
                    wt.latitude,
                    wt.longitude,
                    wt.distance_meters,
                    ROW_NUMBER() OVER (ORDER BY wt.distance_meters ASC)::INT AS sequence_order
                FROM worker_tasks wt
                ORDER BY wt.distance_meters ASC;
            END;
            $$ LANGUAGE plpgsql;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP FUNCTION IF EXISTS get_optimized_task_sequence(BIGINT, DOUBLE PRECISION, DOUBLE PRECISION);');
    }
};
