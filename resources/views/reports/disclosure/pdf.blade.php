<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $frameworkLabel }} — {{ $data['meta']['year'] }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #222; font-size: 12px; }
        h1 { font-size: 18px; margin: 0 0 2px; }
        .muted { color: #666; font-size: 11px; }
        .meta td { padding: 2px 8px 2px 0; font-size: 11px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 12px; }
        table.data th, table.data td { border: 1px solid #ddd; padding: 6px 8px; }
        table.data th { background: #f3f4f6; text-align: left; font-size: 11px; }
        table.data td.num, table.data th.num { text-align: right; }
        .ref { color: #374151; font-weight: bold; font-size: 10px; white-space: nowrap; }
        .section { margin-top: 18px; font-size: 13px; font-weight: bold; }
        .note { margin-top: 16px; font-size: 10px; color: #777; border-top: 1px solid #eee; padding-top: 8px; }
    </style>
</head>
<body>
    <h1>{{ $frameworkLabel }}</h1>
    <div class="muted">{{ $data['meta']['company'] }} &middot; Reporting year {{ $data['meta']['year'] }} &middot; figures in tCO₂e</div>

    <table class="meta" style="margin-top:10px;">
        <tr><td><strong>GWP basis:</strong></td><td>{{ $data['meta']['gwp_label'] }}</td></tr>
        <tr><td><strong>Consolidation:</strong></td><td>{{ $data['meta']['consolidation'] }}</td></tr>
        <tr><td><strong>Records:</strong></td><td>{{ number_format($data['meta']['record_count']) }}</td></tr>
        <tr><td><strong>Generated:</strong></td><td>{{ $data['meta']['generated_on'] }}</td></tr>
    </table>

    <div class="section">Disclosure Datapoints</div>
    <table class="data">
        <thead>
            <tr>
                <th style="width:16%;">Reference</th>
                <th>Datapoint</th>
                <th class="num" style="width:18%;">Value</th>
                <th style="width:14%;">Unit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datapoints as $dp)
            <tr>
                <td class="ref">{{ $dp['ref'] }}</td>
                <td>{{ $dp['label'] }}</td>
                <td class="num">{{ is_numeric($dp['value']) ? number_format($dp['value'], 2) : $dp['value'] }}</td>
                <td>{{ $dp['unit'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if(in_array($framework, ['gri_305', 'cdp']) && count($data['scope3_by_category']))
    <div class="section">Scope 3 by GHG Protocol Category</div>
    <table class="data">
        <thead><tr><th style="width:8%;">#</th><th>Category</th><th class="num" style="width:20%;">tCO₂e</th></tr></thead>
        <tbody>
            @foreach($data['scope3_by_category'] as $cat)
            <tr>
                <td>{{ $cat['number'] ?: '—' }}</td>
                <td>{{ $cat['name'] }}</td>
                <td class="num">{{ number_format($cat['co2e'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="note">
        This export maps the organisation's GHG inventory to {{ $frameworkLabel }} emission datapoints. It is a
        preparation aid, not a filed disclosure or assurance statement. Scope 2 is dual-reported (location- and
        market-based) per the GHG Protocol Scope 2 Guidance. Figures should be reviewed before submission.
    </div>
</body>
</html>
