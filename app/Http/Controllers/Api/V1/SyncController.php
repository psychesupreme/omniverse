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

        if (in_array('products', $collections)) {
            $data['products'] = $this->syncService->getPullData(\App\Models\Product::class, $lastSyncTimestamp);
        }

        $user = auth()->user();
        if ($user) {
            $activeTimesheet = \App\Models\Timesheet::with('geofence')
                ->where('user_id', $user->id)
                ->whereNull('clock_out')
                ->latest('clock_in')
                ->first();

            $data['active_shift'] = $activeTimesheet ? [
                'id'            => $activeTimesheet->id,
                'geofence_name' => $activeTimesheet->geofence?->name ?? 'Work Site',
                'clock_in'      => ($activeTimesheet->clock_in ?? $activeTimesheet->clock_in_time)?->toIso8601String(),
            ] : null;
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

        $tasks = $data['tasks'] ?? [];
        if (!empty($tasks)) {
            foreach ($tasks as $taskData) {
                $task = \App\Models\Task::find($taskData['id']);
                if ($task) {
                    $updateFields = [
                        'status' => $taskData['status'] ?? $task->status,
                    ];
                    if (isset($taskData['completion_notes'])) {
                        $updateFields['completion_notes'] = $taskData['completion_notes'];
                    }
                    if (($taskData['status'] ?? '') === 'completed') {
                        $updateFields['completed_at'] = now();
                    }
                    if (!empty($taskData['evidence_photos']) && is_array($taskData['evidence_photos'])) {
                        $tenantId = tenant('id') ?? 'default';
                        foreach ($taskData['evidence_photos'] as $idx => $base64Img) {
                            if (!empty($base64Img)) {
                                $imgData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Img));
                                $fileName = "tenants/{$tenantId}/tasks/evidence/{$task->id}_{$idx}_" . time() . ".jpg";
                                \Illuminate\Support\Facades\Storage::disk('public')->put($fileName, $imgData);
                                $updateFields['evidence_photo_path'] = $fileName;
                            }
                        }
                    }
                    $task->update($updateFields);
                }
            }
        }

        $orders = $data['orders'] ?? [];
        $syncedOrderIds = [];
        if (!empty($orders)) {
            \Illuminate\Support\Facades\DB::transaction(function () use ($orders, &$syncedOrderIds) {
                $userId = auth()->id() ?? 1;

                foreach ($orders as $orderData) {
                    $order = \App\Models\Order::updateOrCreate(
                        ['id' => $orderData['id']],
                        [
                            'order_number' => $orderData['order_number'],
                            'user_id'      => $userId,
                            'outlet_id'    => $orderData['outlet_id'] ?? null,
                            'total_amount' => $orderData['total_amount'],
                            'status'       => $orderData['status'] ?? 'pending',
                            'notes'        => $orderData['notes'] ?? null,
                            'placed_at'    => $orderData['placed_at'] ?? now(),
                        ]
                    );

                    if (!empty($orderData['items']) && is_array($orderData['items'])) {
                        foreach ($orderData['items'] as $itemData) {
                            \App\Models\OrderItem::create([
                                'order_id'   => $order->id,
                                'product_id' => $itemData['product_id'],
                                'quantity'   => $itemData['quantity'],
                                'unit_price' => $itemData['unit_price'],
                                'subtotal'   => $itemData['subtotal'],
                            ]);

                            // Deduct product stock quantities
                            $product = \App\Models\Product::find($itemData['product_id']);
                            if ($product) {
                                $product->decrement('stock_quantity', $itemData['quantity']);
                            }
                        }
                    }

                    $syncedOrderIds[] = $order->id;
                }
            });
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Sync processed successfully.',
            'synced_order_ids' => $syncedOrderIds,
            'server_timestamp' => now()->toDateTimeString(),
        ]);
    }
}
