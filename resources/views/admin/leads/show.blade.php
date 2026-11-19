@extends('admin.layouts.app')

@section('title', 'Lead: '.$lead->name)

@section('content')
@php $wa = 'https://wa.me/'.preg_replace('/\D/', '', $lead->phone); @endphp
<a href="{{ route('admin.leads.index') }}" class="mb-4 inline-block text-sm text-slate-500 hover:text-ink">← All leads</a>
<div class="grid gap-6 xl:grid-cols-[1fr_360px]">
    <div class="grid content-start gap-6">
        <div class="a-card">
            <div class="a-card-h"><p class="a-card-t">{{ $lead->name }}</p><x-admin.status-badge :status="$lead->status" /></div>
            <dl class="grid gap-x-6 gap-y-4 p-5 sm:grid-cols-2">
                @foreach(['Phone' => $lead->phone, 'Email' => $lead->email, 'Company' => $lead->company, 'Service' => $lead->service, 'Location' => $lead->location, 'Budget' => $lead->budget, 'Website' => $lead->website, 'Form' => $lead->form_name, 'Received' => $lead->created_at->format('d M Y, h:i A'), 'First contacted' => $lead->contacted_at?->format('d M Y, h:i A')] as $k => $v)
                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $k }}</dt><dd class="mt-0.5 break-words text-ink">{{ $v ?: '—' }}</dd></div>
                @endforeach
                <div class="sm:col-span-2"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Source page</dt><dd class="mt-0.5 break-all text-sm">
                    {{-- Only link http(s) URLs: source_page is visitor-supplied. --}}
                    @if(preg_match('#^https?://#i', (string) $lead->source_page))<a href="{{ $lead->source_page }}" target="_blank" rel="noopener noreferrer nofollow" class="text-brand">{{ $lead->source_page }}</a>@else{{ $lead->source_page ?: '—' }}@endif
                </dd></div>
                @if($lead->utm)<div class="sm:col-span-2"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Campaign</dt><dd class="mt-1 flex flex-wrap gap-1">@foreach($lead->utm as $k => $v)<span class="a-badge bg-slate-100 text-slate-600">{{ $k }}: {{ $v }}</span>@endforeach</dd></div>@endif
            </dl>
            @if($lead->message)
                <div class="border-t border-line p-5"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Message</p><p class="mt-2 whitespace-pre-line text-ink">{{ $lead->message }}</p></div>
            @endif
            <div class="flex flex-wrap gap-2 border-t border-line p-4">
                <a href="tel:{{ preg_replace('/[^\d+]/', '', $lead->phone) }}" class="a-btn a-btn-primary"><x-icon name="phone" class="size-4" /> Call</a>
                <a href="{{ $wa }}" target="_blank" rel="noopener" class="a-btn a-btn-light"><x-icon name="whatsapp" class="size-4" /> WhatsApp</a>
                @if($lead->email)<a href="mailto:{{ $lead->email }}" class="a-btn a-btn-light"><x-icon name="mail" class="size-4" /> Email</a>@endif
            </div>
        </div>

        <div class="a-card">
            <div class="a-card-h"><p class="a-card-t">Activity notes</p></div>
            <ul class="divide-y divide-line">
                @forelse($lead->leadNotes as $n)
                    <li class="p-4"><p class="whitespace-pre-line text-sm text-ink">{{ $n->note }}</p><p class="mt-1 text-xs text-slate-500">{{ $n->user?->name ?? 'System' }} · {{ $n->created_at->diffForHumans() }}</p></li>
                @empty
                    <li class="p-6 text-center text-sm text-slate-500">No notes yet.</li>
                @endforelse
            </ul>
        </div>
        <p class="text-xs text-slate-400">IP {{ $lead->ip }} · {{ \Illuminate\Support\Str::limit($lead->user_agent, 120) }}</p>
    </div>

    <aside class="grid content-start gap-4">
        @can('update', $lead)
            <form method="POST" action="{{ route('admin.leads.update', $lead->id) }}" class="a-card">
                @csrf @method('PUT')
                <div class="a-card-h"><p class="a-card-t">Update lead</p></div>
                <div class="a-card-b grid gap-3">
                    <x-admin.field type="select" name="status" label="Status" :value="$lead->status->value" :options="\App\Enums\LeadStatus::options()" :empty="false" />
                    <x-admin.field type="select" name="assigned_to" label="Assigned to" :value="$lead->assigned_to" :options="$users" empty="Unassigned" />
                    <x-admin.field type="textarea" name="notes" label="Summary notes" :value="$lead->notes" rows="3" />
                    <x-admin.field type="textarea" name="note" label="Add activity note" rows="2" placeholder="e.g. Called, sent proposal…" />
                    <button class="a-btn a-btn-primary">Save</button>
                </div>
            </form>
        @endcan
        @can('delete', $lead)
            <form method="POST" action="{{ route('admin.leads.destroy', $lead->id) }}" onsubmit="return confirm('Delete this lead?')">@csrf @method('DELETE')<button class="a-btn a-btn-danger w-full">Delete lead</button></form>
        @endcan
    </aside>
</div>
@endsection
