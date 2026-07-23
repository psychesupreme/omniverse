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

        // Fetch the latest tracking log per user in a single query (avoids N+1)
        $latestLogSubquery = TrackingLog::selectRaw('DISTINCT ON (user_id) user_id, latitude, longitude')
            ->orderBy('user_id')
            ->orderByDesc('recorded_at_mobile');

        $latestLogs = [];
        try {
            foreach ($latestLogSubquery->get() as $log) {
                $latestLogs[$log->user_id] = [
                    'latitude'  => $log->latitude,
                    'longitude' => $log->longitude,
                ];
            }
        } catch (\Exception $e) {
            // If tracking_logs table is empty or query fails, proceed with empty locations
        }

        $workers = User::all(['id', 'name', 'email'])->map(function ($user) use ($latestLogs) {
            return [
                'id'       => $user->id,
                'name'     => $user->name,
                'email'    => $user->email,
                'location' => $latestLogs[$user->id] ?? null,
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
