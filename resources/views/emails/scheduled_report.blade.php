<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: Arial, sans-serif; color: #222; line-height: 1.5;">
    <h2 style="margin-bottom: 4px;">{{ $report->name }}</h2>
    <p style="color:#666; margin-top:0;">Your scheduled GHG emissions report is attached. All figures in tCO₂e.</p>

    <table style="border-collapse: collapse; margin-top: 12px;">
        <tr><td style="padding:4px 12px 4px 0;"><strong>Scope 1</strong></td><td style="text-align:right;">{{ number_format($summary['totals']['scope1'], 2) }}</td></tr>
        <tr><td style="padding:4px 12px 4px 0;"><strong>Scope 2</strong></td><td style="text-align:right;">{{ number_format($summary['totals']['scope2'], 2) }}</td></tr>
        <tr><td style="padding:4px 12px 4px 0;"><strong>Scope 3</strong></td><td style="text-align:right;">{{ number_format($summary['totals']['scope3'], 2) }}</td></tr>
        <tr><td style="padding:6px 12px 4px 0; border-top:1px solid #ccc;"><strong>Total</strong></td><td style="text-align:right; border-top:1px solid #ccc;"><strong>{{ number_format($summary['totals']['total'], 2) }} tCO₂e</strong></td></tr>
    </table>

    <p style="color:#666; font-size: 12px; margin-top: 16px;">
        Covering {{ number_format($summary['record_count']) }} emission records &middot; generated {{ $summary['generated_on'] }}.
    </p>
</body>
</html>
