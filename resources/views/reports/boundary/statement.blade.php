<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Inventory Boundary Statement — {{ $statement['meta']['company'] }} {{ $statement['meta']['year'] }}</title>
    <style>
        @page { margin: 22mm 16mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; line-height: 1.5; }
        h1 { font-size: 17px; margin: 0 0 2px; color: #1b5e20; }
        h2 { font-size: 12px; margin: 20px 0 7px; color: #1b5e20; border-bottom: 1px solid #cfd8cf; padding-bottom: 4px; }
        .sub { font-size: 10px; color: #666; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #d8dee8; padding: 5px 7px; text-align: left; vertical-align: top; }
        th { background: #f2f6f2; font-weight: bold; font-size: 9.5px; }
        td { font-size: 9.5px; }
        .meta td { border: none; padding: 2px 0; font-size: 10px; }
        .meta td.k { color: #666; width: 34%; }
        .narrative { background: #f7faf7; border-left: 3px solid #2e7d32; padding: 10px 12px; margin-bottom: 6px; }
        .rel-in { color: #1b5e20; font-weight: bold; }
        .rel-out { color: #777; }
        .rel-none { color: #b26a00; }
        .muted { color: #777; }
        .foot { margin-top: 18px; font-size: 8.5px; color: #777; border-top: 1px solid #e2e6ee; padding-top: 8px; }
        .nowrap { white-space: nowrap; }
    </style>
</head>
<body>

@php $m = $statement['meta']; @endphp

<h1>Inventory Boundary Statement</h1>
<div class="sub">{{ $m['company'] }} · Reporting year {{ $m['year'] }} · Version {{ $m['version'] }}</div>

<table class="meta">
    <tr><td class="k">Consolidation approach</td><td>{{ $m['consolidation'] }}</td></tr>
    <tr><td class="k">Reporting year</td><td>{{ $m['year'] }}</td></tr>
    @if($m['country'])<tr><td class="k">Country</td><td>{{ $m['country'] }}</td></tr>@endif
    @if($m['industry'])<tr><td class="k">Sector</td><td>{{ ucwords(str_replace('_', ' ', $m['industry'])) }}</td></tr>@endif
    <tr><td class="k">Boundary determined by</td><td>{{ $m['method'] }}</td></tr>
    @if($m['completed_by'])<tr><td class="k">Approved by</td><td>{{ $m['completed_by'] }}@if($m['completed_at']) on {{ $m['completed_at'] }}@endif</td></tr>@endif
    <tr><td class="k">Statement generated</td><td>{{ $m['generated_on'] }}</td></tr>
</table>

<h2>Boundary description</h2>
<div class="narrative">{{ $statement['narrative'] }}</div>

@if($m['business_description'])
    <p class="muted"><b>Reported business activity:</b> {{ $m['business_description'] }}</p>
@endif

<h2>Scope 3 category screening</h2>
<p class="muted" style="margin-top:-2px">
    The GHG Protocol Corporate Value Chain (Scope 3) Standard requires all fifteen categories to be
    screened for relevance, and every exclusion to be justified. All fifteen are listed below.
</p>
<table>
    <thead>
        <tr>
            <th style="width:5%">#</th>
            <th style="width:24%">Category</th>
            <th style="width:17%">Relevance</th>
            <th style="width:54%">Justification</th>
        </tr>
    </thead>
    <tbody>
        @foreach($statement['scope3_screening'] as $row)
            <tr>
                <td class="nowrap">{{ $row['number'] }}</td>
                <td>{{ $row['name'] }}</td>
                <td class="{{ str_contains($row['status'], 'included') ? 'rel-in' : (str_contains($row['status'], 'excluded') ? 'rel-out' : 'rel-none') }}">
                    {{ $row['status'] }}
                </td>
                <td>
                    {{ $row['reason'] }}
                    @if($row['sources'])<br><span class="muted">Sources: {{ $row['sources'] }}</span>@endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

<h2>Sources included in the inventory ({{ $statement['included']->count() }})</h2>
@if($statement['included']->isEmpty())
    <p class="muted">No sources have been accepted into the boundary.</p>
@else
    <table>
        <thead>
            <tr>
                <th style="width:8%">Scope</th>
                <th style="width:34%">Emission source</th>
                <th style="width:13%">Materiality</th>
                <th style="width:45%">Basis for inclusion</th>
            </tr>
        </thead>
        <tbody>
            @foreach($statement['included'] as $item)
                <tr>
                    <td class="nowrap">
                        {{ $item->scope }}@if($item->scope3Category) · cat {{ $item->scope3Category->sort_order }}@endif
                    </td>
                    <td>{{ $item->suggested_name }}</td>
                    <td>{{ ucfirst($item->materiality) }}</td>
                    <td>{{ $item->rationale ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<h2>Sources excluded, with justification ({{ $statement['excluded']->count() }})</h2>
@if($statement['excluded']->isEmpty())
    <p class="muted">No sources have been excluded from the boundary.</p>
@else
    <table>
        <thead>
            <tr>
                <th style="width:8%">Scope</th>
                <th style="width:34%">Emission source</th>
                <th style="width:58%">Justification for exclusion</th>
            </tr>
        </thead>
        <tbody>
            @foreach($statement['excluded'] as $item)
                <tr>
                    <td class="nowrap">
                        {{ $item->scope }}@if($item->scope3Category) · cat {{ $item->scope3Category->sort_order }}@endif
                    </td>
                    <td>{{ $item->suggested_name }}</td>
                    <td>{{ $item->exclusion_reason ?: 'No justification recorded.' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

@if($statement['deferred']->isNotEmpty())
    <h2>Deferred to a future reporting period ({{ $statement['deferred']->count() }})</h2>
    <table>
        <thead>
            <tr><th style="width:8%">Scope</th><th style="width:42%">Emission source</th><th style="width:50%">Note</th></tr>
        </thead>
        <tbody>
            @foreach($statement['deferred'] as $item)
                <tr>
                    <td class="nowrap">{{ $item->scope }}</td>
                    <td>{{ $item->suggested_name }}</td>
                    <td>{{ $item->rationale ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

@if($statement['coverage']['has_boundary'] && $statement['coverage']['total'] > 0)
    <h2>Data completeness against this boundary</h2>
    <p>
        {{ $statement['coverage']['covered'] }} of {{ $statement['coverage']['total'] }}
        included sources have activity data recorded for {{ $statement['coverage']['year'] }}
        ({{ $statement['coverage']['percent'] }}%).
        @if($statement['coverage']['gaps']->isNotEmpty())
            Sources without data: {{ $statement['coverage']['gaps']->pluck('suggested_name')->take(8)->implode(', ') }}{{ $statement['coverage']['gaps']->count() > 8 ? ', and others' : '' }}.
        @endif
    </p>
@endif

@if(!empty($statement['changes']) && $statement['changes']['has_changes'])
    @php $ch = $statement['changes']; @endphp
    <h2>Changes from the previous boundary ({{ $ch['previous']['year'] }}, version {{ $ch['previous']['version'] }})</h2>
    <p>
        The inventory boundary moved from {{ $ch['previous']['included'] }} to {{ $ch['current']['included'] }}
        included emission sources.
    </p>
    <table>
        <thead>
            <tr><th style="width:22%">Change</th><th style="width:78%">Sources</th></tr>
        </thead>
        <tbody>
            @if($ch['added']->isNotEmpty())
                <tr>
                    <td>Added ({{ $ch['added']->count() }})</td>
                    <td>{{ $ch['added']->pluck('suggested_name')->implode(', ') }}</td>
                </tr>
            @endif
            @if($ch['removed']->isNotEmpty())
                <tr>
                    <td>Removed ({{ $ch['removed']->count() }})</td>
                    <td>{{ $ch['removed']->pluck('suggested_name')->implode(', ') }}</td>
                </tr>
            @endif
            @if($ch['changed']->isNotEmpty())
                <tr>
                    <td>Materiality reassessed ({{ $ch['changed']->count() }})</td>
                    <td>
                        @foreach($ch['changed'] as $c)
                            {{ $c['item']->suggested_name }} ({{ $c['from'] }} → {{ $c['to'] }}){{ ! $loop->last ? '; ' : '' }}
                        @endforeach
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    @if($ch['requires_recalculation'])
        <p class="rel-none"><b>Base-year recalculation considerations</b></p>
        <ul style="margin-top:2px">
            @foreach($ch['reasons'] as $reason)
                <li>{{ $reason }}</li>
            @endforeach
        </ul>
        <p class="muted">
            The GHG Protocol requires the base year to be recalculated where a change in inventory
            boundary is significant against the entity's own significance threshold. The changes above
            are reported for that assessment; the determination itself rests with the reporting entity.
        </p>
    @endif
@endif

<div class="foot">
    Prepared in accordance with the GHG Protocol Corporate Accounting and Reporting Standard and the
    Corporate Value Chain (Scope 3) Accounting and Reporting Standard.
    @if($m['method'] === 'AI-assisted, human-approved')
        Boundary scoping was AI-assisted{{ $m['model'] ? ' (model: '.$m['model'].($m['prompt_version'] ? ', prompt '.$m['prompt_version'] : '').')' : '' }};
        every source listed was reviewed and accepted by a responsible person before adoption.
    @endif
    This statement reflects the boundary decisions recorded in the system at the time of generation.
</div>

</body>
</html>
