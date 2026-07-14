<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TaskController extends Controller
{
    /**
     * Display a listing of tasks with filters and eager loaded relations.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Task::with(['outlet', 'assignedUser']);

        if ($request->filled('assigned_user_id')) {
            $query->where('assigned_user_id', $request->input('assigned_user_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $tasks = $query->latest('scheduled_for')->paginate(15);

        return response()->json($tasks);
    }

    /**
     * Store a newly created task in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'outlet_id'        => ['required', 'exists:outlets,id'],
            'assigned_user_id' => ['required', 'exists:users,id'],
            'title'            => ['required', 'string', 'max:255'],
            'scheduled_for'    => ['required', 'date'],
            'description'      => ['nullable', 'string'],
        ]);

        try {
            $task = Task::create([
                'outlet_id'        => $validated['outlet_id'],
                'assigned_user_id' => $validated['assigned_user_id'],
                'title'            => $validated['title'],
                'scheduled_for'    => $validated['scheduled_for'],
                'description'      => $validated['description'] ?? null,
                'status'           => 'pending',
            ]);

            return response()->json([
                'message' => 'Task created and dispatched successfully.',
                'task'    => $task->load(['outlet', 'assignedUser']),
            ], 201);

        } catch (\Exception $e) {
            Log::error('Task creation failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while creating the task.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified task.
     */
    public function show(Task $task): JsonResponse
    {
        return response()->json($task->load(['outlet', 'assignedUser']));
    }

    /**
     * Update the specified task in storage.
     */
    public function update(Request $request, Task $task): JsonResponse
    {
        $validated = $request->validate([
            'outlet_id'        => ['sometimes', 'required', 'exists:outlets,id'],
            'assigned_user_id' => ['sometimes', 'required', 'exists:users,id'],
            'title'            => ['sometimes', 'required', 'string', 'max:255'],
            'scheduled_for'    => ['sometimes', 'required', 'date'],
            'description'      => ['nullable', 'string'],
            'status'           => ['sometimes', 'required', 'string', 'in:pending,accepted,in_progress,completed,cancelled'],
        ]);

        try {
            $task->update($validated);

            return response()->json([
                'message' => 'Task updated successfully.',
                'task'    => $task->load(['outlet', 'assignedUser']),
            ]);

        } catch (\Exception $e) {
            Log::error('Task update failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while updating the task.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified task from storage.
     */
    public function destroy(Task $task): JsonResponse
    {
        try {
            $task->delete();
            return response()->json(['message' => 'Task deleted successfully.']);
        } catch (\Exception $e) {
            Log::error('Task deletion failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while deleting the task.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
