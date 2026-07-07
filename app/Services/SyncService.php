<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SyncService
{
    /**
     * Process pushed records from mobile client using LWW logic.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     */
    public function processPush(string $modelClass, array $records): void
    {
        DB::transaction(function () use ($modelClass, $records) {
            foreach ($records as $record) {
                // Find existing record, including soft deleted ones
                $existingRecord = $modelClass::withTrashed()->find($record['id']);

                if (!$existingRecord) {
                    // Create the record
                    $newRecord = $modelClass::create($record);
                    
                    // If the payload indicates it was deleted, apply soft delete
                    if (isset($record['deleted_at']) && !is_null($record['deleted_at'])) {
                        $newRecord->delete();
                    }
                } else {
                    // Compare timestamps using LWW
                    $incomingTime = Carbon::parse($record['last_updated_at']);
                    $existingTime = Carbon::parse($existingRecord->last_updated_at);

                    if ($incomingTime->gt($existingTime)) {
                        // Restore if it was soft-deleted but incoming is not deleted
                        if ($existingRecord->trashed() && (!isset($record['deleted_at']) || is_null($record['deleted_at']))) {
                            $existingRecord->restore();
                        }

                        // Update the record
                        $existingRecord->update($record);

                        // If incoming payload has deleted_at, soft delete it
                        if (isset($record['deleted_at']) && !is_null($record['deleted_at'])) {
                            $existingRecord->delete();
                        }
                    }
                }
            }
        });

        if (!empty($records)) {
            \App\Events\SyncProcessed::dispatch(
                tenant('id'),
                [
                    'model' => class_basename($modelClass),
                    'count' => count($records),
                ]
            );
        }
    }

    /**
     * Retrieve records modified since last sync for pulling to client.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     */
    public function getPullData(string $modelClass, ?string $lastSyncTimestamp)
    {
        if (is_null($lastSyncTimestamp)) {
            return $modelClass::withTrashed()->get();
        }

        return $modelClass::withTrashed()
            ->where('last_updated_at', '>', $lastSyncTimestamp)
            ->get();
    }
}
