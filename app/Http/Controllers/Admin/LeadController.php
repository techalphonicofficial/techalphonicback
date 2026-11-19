<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LeadUpdateRequest;
use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Lead::class);

        $query = $this->scoped($request)->with('assignee:id,name')->latest();
        if ($s = $request->query('status')) {
            $query->where('status', $s);
        }
        if ($q = trim((string) $request->query('q'))) {
            $like = '%'.addcslashes($q, '%_\\').'%';
            $query->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like)
                ->orWhere('phone', 'like', $like)->orWhere('company', 'like', $like));
        }
        if ($service = $request->query('service')) {
            $query->where('service', $service);
        }
        if ($from = $request->date('from')) {
            $query->where('created_at', '>=', $from->startOfDay());
        }
        if ($to = $request->date('to')) {
            $query->where('created_at', '<=', $to->endOfDay());
        }

        return view('admin.leads.index', [
            'items' => $query->paginate(30)->withQueryString(),
            'counts' => $this->scoped($request)->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status'),
            'services' => Lead::whereNotNull('service')->distinct()->orderBy('service')->pluck('service'),
        ]);
    }

    public function show(int $id)
    {
        $lead = Lead::with(['assignee', 'leadNotes.user'])->findOrFail($id);
        $this->authorize('view', $lead);

        return view('admin.leads.show', [
            'lead' => $lead,
            'users' => User::permission('leads.view')->orWhereHas('roles', fn ($q) => $q->where('name', 'Super Admin'))->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function update(LeadUpdateRequest $request, int $id)
    {
        $lead = Lead::findOrFail($id);
        $this->authorize('update', $lead);
        $data = $request->validated();

        if ($data['status'] === LeadStatus::Contacted->value && ! $lead->contacted_at) {
            $lead->contacted_at = now();
        }
        $lead->fill(collect($data)->except('note')->all())->save();

        if (! empty($data['note'])) {
            $lead->leadNotes()->create(['user_id' => $request->user()->id, 'note' => $data['note']]);
        }
        ActivityLog::record('lead_updated', $lead, 'Lead updated', ['status' => $data['status']]);

        return back()->with('success', 'Lead updated.');
    }

    public function destroy(int $id)
    {
        $lead = Lead::findOrFail($id);
        $this->authorize('delete', $lead);
        $lead->delete();
        ActivityLog::record('lead_deleted', $lead);

        return redirect()->route('admin.leads.index')->with('success', 'Lead deleted.');
    }

    public function export(Request $request)
    {
        $this->authorize('export', Lead::class);
        ActivityLog::record('leads_exported', null, 'Leads CSV exported');

        return response()->streamDownload(function () use ($request) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Date', 'Name', 'Phone', 'Email', 'Company', 'Service', 'Location', 'Budget', 'Website', 'Message', 'Status', 'Assigned to', 'Source page']);
            $this->scoped($request)->with('assignee:id,name')->orderBy('id')
                ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
                ->chunk(500, function ($rows) use ($out) {
                    foreach ($rows as $l) {
                        // Prefix formula characters so spreadsheet apps don't execute them (CSV injection).
                        $safe = fn ($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'".$v : $v;
                        fputcsv($out, array_map($safe, [$l->id, $l->created_at->format('Y-m-d H:i'), $l->name, $l->phone, $l->email, $l->company,
                            $l->service, $l->location, $l->budget, $l->website, $l->message, $l->status->label(), $l->assignee?->name, $l->source_page]));
                    }
                });
            fclose($out);
        }, 'leads-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function scoped(Request $request)
    {
        $user = $request->user();

        return Lead::query()->when(! $user->can('leads.view_all'), fn ($q) => $q->where(fn ($w) => $w->whereNull('assigned_to')->orWhere('assigned_to', $user->id)));
    }
}
