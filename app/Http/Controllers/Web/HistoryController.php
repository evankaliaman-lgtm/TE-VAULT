<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\NotificationLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function notifications(Request $request): View
    {
        return view('notifications.index', ['notifications' => NotificationLog::query()->with('borrowing.asset')->where('recipient_user_id', $request->user()->id)->latest()->paginate(15)->withQueryString()]);
    }

    public function audits(Request $request): View
    {
        $logs = AuditLog::query()->with('actor')->when($request->filled('actor'), fn ($q) => $q->where('actor_user_id', $request->integer('actor')))->when($request->filled('entity'), fn ($q) => $q->where('entity_type', 'like', '%'.$request->string('entity').'%'))->when($request->filled('action'), fn ($q) => $q->where('action', 'like', '%'.$request->string('action').'%'))->when($request->filled('date'), fn ($q) => $q->whereDate('created_at', $request->date('date')))->latest()->paginate(20)->withQueryString();

        return view('audit-logs.index', compact('logs'));
    }
}
