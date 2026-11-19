<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless($request->user()->can('activity.view'), 403);

        $items = ActivityLog::with('user:id,name')
            ->when($request->query('action'), fn ($q, $a) => $q->where('action', $a))
            ->when($request->integer('user'), fn ($q, $u) => $q->where('user_id', $u))
            ->latest('created_at')->paginate(50)->withQueryString();

        return view('admin.activity.index', [
            'items' => $items,
            'actions' => ActivityLog::distinct()->orderBy('action')->pluck('action'),
        ]);
    }
}
