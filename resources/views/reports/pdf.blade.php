<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #222; font-size: 12px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .muted { color: #666; font-size: 11px; }
        .meta td { padding: 2px 8px 2px 0; font-size: 11px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 14px; }
        table.data th, table.data td { border: 1px solid #ddd; padding: 6px 8px; }
        table.data th { background: #f3f4f6; text-align: left; font-size: 11px; }
        table.data td.num, table.data th.num { text-align: right; }
        .totals td { padding: 6px 8px; border: 1px solid #ddd; }
        .totals .label { background: #f9fafb; font-weight: bold; }
        .grand { background: #eef2ff; font-weight: bold; }
        .section { margin-top: 22px; font-size: 14px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="muted">GHG Emissions Report &middot; all figures in tCO₂e</div>

    <table class="meta" style="margin-top:10px;">
        <tr><td><strong>Period:</strong></td><td>{{ $period ?? 'All periods' }}</td></tr>
        <tr><td><strong>Facility:</strong></td><td>{{ $facility ?? 'All facilities' }}</td></tr>
        <tr><td><strong>Department:</strong></td><td>{{ $department ?? 'All departments' }}</td></tr>
        <tr><td><strong>Records:</strong></td><td>{{ number_format($record_count) }}</td></tr>
        <tr><td><strong>Generated:</strong></td><td>{{ $generated_on }}</td></tr>
    </table>

    <div class="section">Emissions by Scope</div>
    <table class="data totals">
        <tr><td class="label">Scope 1 — Direct</td><td class="num">{{ number_format($totals['scope1'], 2) }}</td></tr>
        <tr><td class="label">Scope 2 — Indirect, energy (location-based)</td><td class="num">{{ number_format($totals['scope2'], 2) }}</td></tr>
        @if(isset($totals['scope2_market']))
        <tr><td class="label">Scope 2 — Indirect, energy (market-based)</td><td class="num">{{ number_format($totals['scope2_market'], 2) }}</td></tr>
        @endif
        <tr><td class="label">Scope 3 — Value chain</td><td class="num">{{ number_format($totals['scope3'], 2) }}</td></tr>
        <tr class="grand"><td>Total (location-based Scope 2)</td><td class="num">{{ number_format($totals['total'], 2) }} tCO₂e</td></tr>
        @if(isset($totals['total_market']))
        <tr class="grand"><td>Total (market-based Scope 2)</td><td class="num">{{ number_format($totals['total_market'], 2) }} tCO₂e</td></tr>
        @endif
    </table>

    <div class="section">Breakdown by Source</div>
    @if(count($by_source) > 0)
        <table class="data">
            <thead>
                <tr>
                    <th>Source</th>
                    <th>Scope</th>
                    <th class="num">Records</th>
                    <th class="num">tCO₂e</th>
                </tr>
            </thead>
            <tbody>
                @foreach($by_source as $row)
                    <tr>
                        <td>{{ $row['source'] }}</td>
                        <td>Scope {{ $row['scope'] }}</td>
                        <td class="num">{{ number_format($row['records']) }}</td>
                        <td class="num">{{ number_format($row['co2e'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="muted">No emission records for this report's scope.</div>
    @endif
</body>
</html>
