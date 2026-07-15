<?php

namespace App\Http\Controllers;

use App\Models\Outlet;
use App\Models\Task;
use App\Models\TrackingLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DispatcherController extends Controller
{
    /**
     * Display the dispatch dashboard.
     */
    public function index(): Response
    {
        $outlets = Outlet::all(['id', 'name', 'location', 'status']);

        // Fetch workers (users) along with their last known coordinates from tracking logs
        $workers = User::all(['id', 'name', 'email'])->map(function ($user) {
            $latestLog = TrackingLog::where('user_id', $user->id)
                ->latest('recorded_at_mobile')
                ->first();

            return [
                'id'       => $user->id,
                'name'     => $user->name,
                'email'    => $user->email,
                'location' => $latestLog ? $latestLog->location : null,
            ];
        });

        // Fetch today's tasks with relation mappings
        $tasks = Task::with(['outlet', 'assignedUser'])
            ->whereDate('scheduled_for', now()->toDateString())
            ->latest()
            ->get();

        return Inertia::render('Dispatch/Index', [
            'tenant_id' => tenant('id'),
            'outlets'   => $outlets,
            'workers'   => $workers,
            'tasks'     => $tasks,
        ]);
    }
}
