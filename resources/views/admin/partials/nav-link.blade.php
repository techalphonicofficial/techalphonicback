{{-- One sidebar link. In collapsed (rail) mode only the icon shows; the label becomes the tooltip. --}}
<a href="{{ $i['href'] }}" class="a-nav-link {{ $i['active'] ? 'active' : '' }}" title="{{ $i['label'] }}" @if($i['active']) aria-current="page" @endif>
    <x-icon :name="$i['icon']" class="size-4 shrink-0 {{ $i['active'] ? 'text-teal' : 'text-slate-500' }}" />
    <span class="a-rail-hide flex-1 truncate">{{ $i['label'] }}</span>
    @if($i['label'] === 'Leads' && $newLeads)
        <span class="a-rail-hide rounded-full bg-coral/90 px-1.5 text-[0.625rem] font-bold leading-4 text-white">{{ $newLeads }}</span>
    @endif
</a>
