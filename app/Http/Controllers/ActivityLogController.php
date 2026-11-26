<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->canViewAuditLogs(), 403);

        $logs = $this->filtered($request)
            ->with('user')
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('activity-logs.index', [
            'logs' => $logs,
            'modules' => $this->distinctValues('module'),
            'actions' => $this->distinctValues('action'),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()->canViewAuditLogs(), 403);

        $filename = 'bargo-activity-log-'.now()->format('Ymd-His').'.csv';

        $rows = $this->filtered($request)->with('user')->latest()->get();

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['When', 'User', 'Action', 'Module', 'Description', 'IP']);

            foreach ($rows as $log) {
                fputcsv($out, [
                    $log->created_at?->toDateTimeString(),
                    $log->user?->name,
                    $log->action,
                    $log->module,
                    $log->description,
                    $log->ip_address,
                ]);
            }

            fclose($out);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function filtered(Request $request)
    {
        return ActivityLog::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('description', 'like', $term)
                        ->orWhere('action', 'like', $term)
                        ->orWhere('module', 'like', $term);
                });
            })
            ->when($request->filled('module'), fn ($q) => $q->where('module', $request->string('module')))
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->string('action')))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')));
    }

    private function distinctValues(string $column)
    {
        return ActivityLog::query()
            ->whereNotNull($column)
            ->distinct()
            ->orderBy($column)
            ->pluck($column);
    }
}
