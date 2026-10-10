@php $k = $row['kind'] === 'phone' ? '52,211,153' : '56,189,248'; @endphp
<div class="c-top-row a-row" style="--k: {{ $k }}" data-community-top>
    <span class="c-rank {{ $rank <= 3 ? 'c-rank-'.$rank : '' }}">{{ $rank }}</span>
    <span class="c-kind" aria-hidden="true">
        @if ($row['kind'] === 'phone')
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" /></svg>
        @else
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" /></svg>
        @endif
    </span>
    <span class="min-w-0">
        <span class="c-item a-mono" title="{{ $row['item'] }}">{{ $row['item'] }}</span>
        <span class="c-meta"><span class="c-chip">{{ $row['kind'] === 'phone' ? 'Phone' : 'Link' }}</span><span>{{ $row['type'] }}</span></span>
    </span>
    <span class="c-count"><b>{{ $row['count'] }}</b><span><i class="c-ppl">people </i>{{ $row['verb'] }}</span></span>
    <span class="c-bar"><span class="a-grow" style="width: {{ round($row['count'] / $max * 100) }}%; --d: {{ 0.2 + ($rank - 1) * 0.06 }}s"></span></span>
</div>