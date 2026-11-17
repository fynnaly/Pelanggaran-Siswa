<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /** Hanya admin & kesiswaan yang boleh membaca jejak aktivitas. */
    private const VIEWER_ROLES = ['admin', 'kesiswaan'];

    public function index(Request $request)
    {
        $this->authorizeViewer();

        $validated = $request->validate([
            'action' => ['nullable', 'string', 'max:80'],
            'q' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $action = $validated['action'] ?? null;
        $q = trim($validated['q'] ?? '');
        $from = $validated['from'] ?? null;
        $to = $validated['to'] ?? null;

        $logs = ActivityLog::with('user')
            ->when($action, fn ($query) => $query->where('action', $action))
            ->when($q !== '', fn ($query) => $query->where(function ($where) use ($q) {
                $where->where('description', 'like', "%{$q}%")
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$q}%"));
            }))
            ->when($from, fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('created_at', '<=', $to))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $actions = ActivityLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $todayCount = ActivityLog::whereDate('created_at', today())->count();
        $importIssues = ActivityLog::where('action', 'like', '%import%')
            ->get()
            ->filter(fn (ActivityLog $log) => ($log->properties['skipped'] ?? 0) > 0
                || ($log->properties['status'] ?? 'ok') === 'failed')
            ->count();

        return view('activity-logs.index', compact(
            'logs',
            'actions',
            'action',
            'q',
            'from',
            'to',
            'todayCount',
            'importIssues',
        ));
    }

    public function show(ActivityLog $activityLog)
    {
        $this->authorizeViewer();

        $activityLog->load('user');

        return view('activity-logs.show', compact('activityLog'));
    }

    private function authorizeViewer(): void
    {
        abort_unless(
            request()->user()?->hasAnyRole(self::VIEWER_ROLES),
            403,
            'Anda tidak memiliki izin melihat log aktivitas.',
        );
    }
}
