<?php

namespace App\Http\Controllers;

use App\Models\Timesheet;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class TimesheetWebController extends Controller
{
    public function index(): Response
    {
        $today = Carbon::today()->toDateString();

        $timesheets = Timesheet::with(['user', 'geofence'])
            ->latest('clock_in')
            ->paginate(20);

        // Summary Aggregates
        $activeShiftsToday = Timesheet::whereDate('date', $today)
            ->whereNull('clock_out')
            ->count();

        $totalMinutesTracked = Timesheet::sum('shift_duration_minutes') ?: Timesheet::sum('total_minutes');
        $totalHoursTracked = round($totalMinutesTracked / 60, 1);

        $totalCount = Timesheet::count();
        $automatedCount = Timesheet::where('is_automated', true)->count();
        $automatedRatio = $totalCount > 0 ? round(($automatedCount / $totalCount) * 100, 1) : 100.0;

        return Inertia::render('Dispatch/Timesheets/Index', [
            'timesheets' => $timesheets,
            'stats'      => [
                'activeShiftsToday' => $activeShiftsToday,
                'totalHoursTracked' => $totalHoursTracked,
                'automatedRatio'    => $automatedRatio,
            ],
        ]);
    }
}
