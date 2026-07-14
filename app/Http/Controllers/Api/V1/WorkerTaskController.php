<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskStatusUpdateRequest;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WorkerTaskController extends Controller
{
    /**
     * Display a listing of tasks assigned to the authenticated field agent.
     */
    public function index(): JsonResponse
    {
        $userId = auth()->id();

        $tasks = Task::with('outlet')
            ->where('assigned_user_id', $userId)
            ->orderBy('scheduled_for', 'asc')
            ->get();

        return response()->json($tasks);
    }

    /**
     * Update the status of an assigned task.
     */
    public function updateStatus(TaskStatusUpdateRequest $request, Task $task): JsonResponse
    {
        // Access check: Ensure the task is assigned to the authenticated user
        if ($task->assigned_user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized. This task is not assigned to you.',
            ], 403);
        }

        $validated = $request->validated();

        try {
            $updateData = [
                'status' => $validated['status'],
            ];

            if ($validated['status'] === 'completed') {
                $updateData['completed_at'] = now();
            }

            if (isset($validated['evidence_photo_path'])) {
                $updateData['evidence_photo_path'] = $validated['evidence_photo_path'];
            }

            $task->update($updateData);

            return response()->json([
                'message' => 'Task status updated successfully.',
                'task'    => $task->load('outlet'),
            ]);

        } catch (\Exception $e) {
            Log::error('Worker task status update failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while updating the task status.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
