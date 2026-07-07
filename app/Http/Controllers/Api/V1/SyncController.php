<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PullSyncRequest;
use App\Http\Requests\Api\V1\PushSyncRequest;
use App\Models\Outlet;
use App\Models\TrackingLog;
use App\Services\SyncService;
use Illuminate\Http\JsonResponse;

class SyncController extends Controller
{
    /**
     * The sync service instance.
     */
    protected SyncService $syncService;

    /**
     * Create a new controller instance.
     */
    public function __construct(SyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Handle pull sync request (Server to Mobile).
     */
    public function pull(PullSyncRequest $request): JsonResponse
    {
        $lastSyncTimestamp = $request->input('last_sync_timestamp');
        $collections = $request->input('collections', []);

        $data = [];

        if (in_array('outlets', $collections)) {
            $data['outlets'] = $this->syncService->getPullData(Outlet::class, $lastSyncTimestamp);
        }

        if (in_array('tracking_logs', $collections)) {
            $data['tracking_logs'] = $this->syncService->getPullData(TrackingLog::class, $lastSyncTimestamp);
        }

        return response()->json([
            'data' => $data,
            'server_timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Handle push sync request (Mobile to Server).
     */
    public function push(PushSyncRequest $request): JsonResponse
    {
        $data = $request->input('data', []);

        $outlets = $data['outlets'] ?? [];
        if (!empty($outlets)) {
            $this->syncService->processPush(Outlet::class, $outlets);
        }

        $trackingLogs = $data['tracking_logs'] ?? [];
        if (!empty($trackingLogs)) {
            $this->syncService->processPush(TrackingLog::class, $trackingLogs);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Sync processed successfully.',
            'server_timestamp' => now()->toDateTimeString(),
        ]);
    }
}
