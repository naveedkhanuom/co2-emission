@extends('layouts.app')

@push('styles')
<style>
    /* ============================================================
       ANALYTICS & INSIGHTS — polished design language
       (matches the app dashboard: 16px gradient cards, accent bars,
        gradient KPI badges, lift-on-hover)
       ============================================================ */

    /* ---- Page header ---- */
    .analytics-header {
        background: linear-gradient(135deg, #ffffff 0%, #f5f8f5 100%);
        border-radius: 18px;
        padding: 22px 26px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06), 0 4px 16px rgba(0, 0, 0, 0.04);
        border: 1px solid rgba(46, 125, 50, 0.08);
        margin-bottom: 24px;
    }
    .analytics-header .header-icon {
        width: 58px;
        height: 58px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        color: #fff;
        background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%);
        box-shadow: 0 6px 16px rgba(46, 125, 50, 0.28);
        flex-shrink: 0;
    }
    .analytics-header h4 {
        background: linear-gradient(135deg, #1b5e20 0%, #4caf50 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    /* ---- Cards (scoped) ---- */
    .analytics-page .card {
        border: 1px solid rgba(0, 0, 0, 0.05);
        border-radius: 16px;
        background: linear-gradient(135deg, #ffffff 0%, #fafbfa 100%);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06), 0 4px 16px rgba(0, 0, 0, 0.03);
        transition: box-shadow 0.3s ease, transform 0.3s ease;
        overflow: hidden;
    }
    .analytics-page .card:hover {
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.09), 0 10px 28px rgba(0, 0, 0, 0.05);
    }
    .analytics-page .card-header {
        background: transparent !important;
        border-bottom: 1px solid #eef1ef !important;
    }
    .analytics-page .card-header h6 {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 700;
        color: #2c3a30;
    }
    .analytics-page .card-header h6::before {
        content: '';
        width: 4px;
        height: 20px;
        border-radius: 3px;
        background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%);
        flex-shrink: 0;
    }
    .analytics-page .card-header h6 i { display: none; } /* accent bar replaces leading icon */

    /* ---- KPI cards ---- */
    .analytics-kpi {
        position: relative;
        height: 100%;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s ease !important;
    }
    .analytics-kpi::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 4px;
        background: linear-gradient(90deg, transparent, currentColor, transparent);
        opacity: 0;
        transition: opacity 0.3s;
    }
    .analytics-page .analytics-kpi:hover {
        transform: translateY(-6px);
        box-shadow: 0 10px 26px rgba(0, 0, 0, 0.12) !important;
    }
    .analytics-kpi:hover::before { opacity: 1; }
    .analytics-kpi.accent-green  { color: #2e7d32; }
    .analytics-kpi.accent-blue   { color: #0277bd; }
    .analytics-kpi.accent-orange { color: #f57c00; }
    .analytics-kpi.accent-dark   { color: #455a64; }

    .kpi-icon-sm {
        width: 50px;
        height: 50px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: #fff;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.14);
        transition: transform 0.3s ease;
    }
    .analytics-kpi:hover .kpi-icon-sm { transform: scale(1.08) rotate(4deg); }
    .icon-grad-green  { background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%); }
    .icon-grad-blue   { background: linear-gradient(135deg, #0277bd 0%, #03a9f4 100%); }
    .icon-grad-orange { background: linear-gradient(135deg, #f57c00 0%, #ff9800 100%); }
    .icon-grad-dark   { background: linear-gradient(135deg, #455a64 0%, #78909c 100%); }

    .kpi-cap {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.6px;
        text-transform: uppercase;
        color: #8a949c;
        margin-bottom: 2px;
    }
    .kpi-num {
        font-size: 1.85rem;
        font-weight: 800;
        line-height: 1.1;
    }
    .kpi-num.grad-green  { background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
    .kpi-num.grad-blue   { background: linear-gradient(135deg, #0277bd 0%, #03a9f4 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
    .kpi-num.grad-orange { background: linear-gradient(135deg, #f57c00 0%, #ff9800 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
    .kpi-num.grad-dark   { background: linear-gradient(135deg, #37474f 0%, #607d8b 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }

    /* ---- Tabs (pill style) ---- */
    .analytics-tabs {
        background: #ffffff;
        border-radius: 14px;
        padding: 6px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(0, 0, 0, 0.04);
        gap: 4px;
    }
    .analytics-tabs .nav-link {
        color: #6c757d;
        border: none !important;
        border-radius: 10px;
        padding: 9px 18px;
        transition: all 0.2s ease;
    }
    .analytics-tabs .nav-link:hover {
        color: var(--primary-green);
        background: rgba(46, 125, 50, 0.07);
    }
    .analytics-tabs .nav-link.active {
        color: #fff;
        background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%);
        box-shadow: 0 4px 12px rgba(46, 125, 50, 0.28);
    }

    /* ---- Segmented selector buttons ---- */
    .analytics-page .btn-group .btn {
        border-radius: 10px !important;
        font-weight: 600;
        font-size: 0.82rem;
    }
    .analytics-page .btn-group .btn.btn-success {
        background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%);
        border-color: transparent;
        box-shadow: 0 3px 8px rgba(46, 125, 50, 0.22);
    }
    .analytics-page .btn-group { gap: 6px; }

    /* ---- Breadcrumb ---- */
    .breadcrumb-item + .breadcrumb-item::before {
        content: "→";
        color: #adb5bd;
    }
    .breadcrumb-item a {
        color: var(--primary-green);
        text-decoration: none;
        font-weight: 600;
    }
    .breadcrumb-item.active { color: #6c757d; font-weight: 600; }

    /* ---- Tables ---- */
    .analytics-page .table thead th {
        background: #f5f8f5;
        color: #55615a;
        font-weight: 700;
        font-size: 0.78rem;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        border-bottom: none;
    }
    .analytics-page .table tbody tr { transition: background 0.15s ease; }
    .analytics-page .table-hover tbody tr:hover { background: rgba(46, 125, 50, 0.045); }

    /* ---- Distribution / impact bars ---- */
    .hotspot-bar {
        height: 8px;
        border-radius: 6px;
        background: #eceef0;
        overflow: hidden;
    }
    .hotspot-bar-fill {
        height: 100%;
        border-radius: 6px;
        transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }
</style>
@endpush

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="container-fluid py-4 analytics-page">

        {{-- Page Header --}}
        <div class="analytics-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <div class="header-icon"><i class="fas fa-chart-pie"></i></div>
                <div>
                    <h4 class="fw-bold mb-1">Analytics &amp; Insights</h4>
                    <p class="text-muted mb-0 small">Drill-down analysis, intensity metrics, trends, and carbon hotspots</p>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        @include('analytics.partials._filters')

        {{-- Tab Navigation --}}
        <ul class="nav nav-pills analytics-tabs mb-0 d-inline-flex" id="analyticsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-semibold px-4" id="breakdown-tab" data-bs-toggle="tab" data-bs-target="#breakdown" type="button" role="tab">
                    <i class="fas fa-layer-group me-1"></i> Breakdown
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold px-4" id="intensity-tab" data-bs-toggle="tab" data-bs-target="#intensity" type="button" role="tab">
                    <i class="fas fa-balance-scale me-1"></i> Intensity
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold px-4" id="trends-tab" data-bs-toggle="tab" data-bs-target="#trends" type="button" role="tab">
                    <i class="fas fa-chart-line me-1"></i> YoY Trends
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold px-4" id="hotspots-tab" data-bs-toggle="tab" data-bs-target="#hotspots" type="button" role="tab">
                    <i class="fas fa-fire-alt me-1"></i> Hotspots
                </button>
            </li>
        </ul>

        {{-- Tab Content --}}
        <div class="tab-content pt-4" id="analyticsTabContent">
            @include('analytics.partials._breakdown')
            @include('analytics.partials._intensity')
            @include('analytics.partials._yoy_trends')
            @include('analytics.partials._hotspots')
        </div>

    </div>
</div>

{{-- ApexCharts JS --}}
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {

    // =====================================================
    // SHARED STATE & UTILITIES
    // =====================================================
    const COLORS = {
        scope1: '#2e7d32', scope2: '#0277bd', scope3: '#f57c00',
        increase: '#d32f2f', decrease: '#2e7d32', neutral: '#757575',
        chartColors: ['#2e7d32', '#0277bd', '#f57c00', '#7b1fa2', '#c62828', '#00838f', '#4e342e', '#37474f', '#ff6f00', '#1565c0']
    };

    let breakdownChart, intensityScopeChart, intensityTrendChart, yoyOverlayChart, waterfallChart, treemapChart;
    let currentDimension = 'scope';
    let drilldownStack = [];

    function getFilters() {
        return {
            date_range: document.getElementById('filterDateRange').value,
            start_date: document.getElementById('filterStartDate').value,
            end_date: document.getElementById('filterEndDate').value,
            facility: document.getElementById('filterFacility').value,
            department: document.getElementById('filterDepartment').value,
            scope: document.getElementById('filterScope').value,
            category: document.getElementById('filterCategory') ? document.getElementById('filterCategory').value : '',
        };
    }

    function buildQueryString(params) {
        return Object.entries(params).filter(([_, v]) => v).map(([k, v]) => `${k}=${encodeURIComponent(v)}`).join('&');
    }

    function formatNumber(n) {
        if (n === null || n === undefined) return 'N/A';
        if (Math.abs(n) >= 1000000) return (n / 1000000).toFixed(2) + 'M';
        if (Math.abs(n) >= 1000) return (n / 1000).toFixed(2) + 'K';
        return parseFloat(n).toFixed(2);
    }

    // Escape DB-sourced strings (source / facility / department / supplier names)
    // before injecting them into innerHTML, to prevent stored XSS.
    function escapeHtml(value) {
        if (value === null || value === undefined) return '';
        return String(value).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    // =====================================================
    // 1. BREAKDOWN CHARTS
    // =====================================================
    const initialBreakdown = @json($breakdownData);

    function renderBreakdownChart(data) {
        const container = document.getElementById('breakdownChart');
        const noData = document.getElementById('breakdownNoData');

        if (!data || data.length === 0) {
            container.style.display = 'none';
            noData.style.display = 'block';
            renderBreakdownTable([]);
            return;
        }
        container.style.display = 'block';
        noData.style.display = 'none';

        const labels = data.map(d => d.label);
        const values = data.map(d => d.value);
        const colors = data.map((d, i) => {
            if (d.label && d.label.includes('Scope 1')) return COLORS.scope1;
            if (d.label && d.label.includes('Scope 2')) return COLORS.scope2;
            if (d.label && d.label.includes('Scope 3')) return COLORS.scope3;
            return COLORS.chartColors[i % COLORS.chartColors.length];
        });

        const options = {
            series: [{ name: 'Emissions (tCO2e)', data: values }],
            chart: {
                type: 'bar',
                height: 400,
                toolbar: { show: true, tools: { download: true, selection: false, zoom: false, zoomin: false, zoomout: false, pan: false, reset: false } },
                events: {
                    dataPointSelection: function(e, chartCtx, config) {
                        const idx = config.dataPointIndex;
                        const item = data[idx];
                        if (item && currentDimension === 'scope') {
                            drilldownStack.push({ dimension: currentDimension, data: data, label: 'All Emissions' });
                            currentDimension = 'source';
                            loadBreakdown('source', item.raw_value, item.label);
                        } else if (item && currentDimension === 'source') {
                            drilldownStack.push({ dimension: currentDimension, data: data, label: drilldownStack.length > 0 ? drilldownStack[drilldownStack.length - 1].clickedLabel : 'Sources' });
                            loadBreakdown('detail', item.raw_value, item.label);
                        }
                    }
                }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    borderRadius: 6,
                    columnWidth: '60%',
                    distributed: true,
                    dataLabels: { position: 'top' }
                }
            },
            colors: colors,
            dataLabels: {
                enabled: true,
                formatter: function(val) { return formatNumber(val); },
                offsetY: -20,
                style: { fontSize: '11px', colors: ['#333'] }
            },
            xaxis: {
                categories: labels,
                labels: { style: { fontSize: '11px' }, rotate: labels.length > 8 ? -45 : 0, trim: true, maxHeight: 100 }
            },
            yaxis: {
                title: { text: 'Emissions (tCO2e)', style: { fontSize: '12px', color: '#666' } },
                labels: { formatter: function(val) { return formatNumber(val); } }
            },
            tooltip: {
                y: { formatter: function(val) { return val.toFixed(2) + ' tCO2e'; } }
            },
            legend: { show: false },
            grid: { borderColor: '#f1f1f1', strokeDashArray: 4 }
        };

        if (breakdownChart) breakdownChart.destroy();
        breakdownChart = new ApexCharts(container, options);
        breakdownChart.render();

        renderBreakdownTable(data);
    }

    function renderBreakdownTable(data) {
        const tbody = document.getElementById('breakdownTableBody');
        if (!data || data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No data available</td></tr>';
            return;
        }
        tbody.innerHTML = data.map((item, i) => `
            <tr>
                <td class="ps-3 text-muted">${i + 1}</td>
                <td class="fw-semibold">${escapeHtml(item.label)}</td>
                <td class="text-end">${formatNumber(item.value)}</td>
                <td class="text-end">${item.count}</td>
                <td class="text-end">${item.percentage}%</td>
                <td>
                    <div class="hotspot-bar">
                        <div class="hotspot-bar-fill" style="width:${item.percentage}%; background: ${COLORS.chartColors[i % COLORS.chartColors.length]};"></div>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function updateBreadcrumb() {
        const bc = document.getElementById('drilldownBreadcrumb').querySelector('.breadcrumb');
        let html = '<li class="breadcrumb-item"><a href="#" onclick="resetDrilldown(); return false;">All Emissions</a></li>';
        drilldownStack.forEach((item, i) => {
            if (item.clickedLabel) {
                html += `<li class="breadcrumb-item"><a href="#" onclick="drillTo(${i}); return false;">${escapeHtml(item.clickedLabel)}</a></li>`;
            }
        });
        html += `<li class="breadcrumb-item active">${currentDimension === 'scope' ? 'By Scope' : currentDimension === 'source' ? 'By Source' : 'Details'}</li>`;
        bc.innerHTML = html;
    }

    function loadBreakdown(dimension, parent, clickedLabel) {
        if (drilldownStack.length > 0) {
            drilldownStack[drilldownStack.length - 1].clickedLabel = clickedLabel;
        }
        const params = { ...getFilters(), dimension: dimension };
        if (parent) params.parent = parent;

        fetch(`{{ route('analytics.breakdown') }}?${buildQueryString(params)}`)
            .then(r => r.json())
            .then(result => {
                renderBreakdownChart(result.data);
                updateBreadcrumb();
            });
    }

    // Global functions for breadcrumb navigation
    window.resetDrilldown = function() {
        drilldownStack = [];
        currentDimension = 'scope';
        renderBreakdownChart(initialBreakdown);
        updateBreadcrumb();
        document.querySelectorAll('#dimensionSelector .btn').forEach(b => {
            b.classList.toggle('active', b.dataset.dimension === 'scope');
            b.classList.toggle('btn-success', b.dataset.dimension === 'scope');
            b.classList.toggle('btn-outline-success', b.dataset.dimension !== 'scope');
        });
    };

    window.drillTo = function(index) {
        drilldownStack = drilldownStack.slice(0, index + 1);
        const item = drilldownStack[index];
        currentDimension = item.dimension;
        renderBreakdownChart(item.data);
        updateBreadcrumb();
    };

    // Dimension selector buttons
    document.querySelectorAll('#dimensionSelector .btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('#dimensionSelector .btn').forEach(b => {
                b.classList.remove('active', 'btn-success');
                b.classList.add('btn-outline-success');
            });
            this.classList.add('active', 'btn-success');
            this.classList.remove('btn-outline-success');

            drilldownStack = [];
            currentDimension = this.dataset.dimension;
            const params = { ...getFilters(), dimension: currentDimension };
            fetch(`{{ route('analytics.breakdown') }}?${buildQueryString(params)}`)
                .then(r => r.json())
                .then(result => {
                    renderBreakdownChart(result.data);
                    updateBreadcrumb();
                });
        });
    });

    // =====================================================
    // 2. INTENSITY METRICS
    // =====================================================
    const initialIntensity = @json($intensityData);

    function renderIntensity(data) {
        // KPI cards
        document.getElementById('intensityTotal').textContent = formatNumber(data.total_emissions);
        document.getElementById('intensityPerEmployee').textContent = data.per_employee !== null ? formatNumber(data.per_employee) : 'N/A';
        document.getElementById('intensityPerRevenue').textContent = data.per_revenue !== null ? formatNumber(data.per_revenue) : 'N/A';
        document.getElementById('intensityRevenueUnit').textContent = `tCO2e / ${data.currency || '$'}M revenue`;
        document.getElementById('intensityEmployeeCount').textContent = data.employee_count ? data.employee_count.toLocaleString() : 'N/A';
        document.getElementById('intensityRevenueInfo').textContent = data.annual_revenue ? `${data.currency || '$'}${formatNumber(data.annual_revenue)} revenue` : 'Revenue not set';

        // Warning banner
        const warning = document.getElementById('intensityWarning');
        if (!data.employee_count || !data.annual_revenue) {
            warning.style.cssText = '';
            let msg = [];
            if (!data.employee_count) msg.push('employee count');
            if (!data.annual_revenue) msg.push('annual revenue');
            document.getElementById('intensityWarningText').textContent = `Update your company profile with ${msg.join(' and ')} to see all intensity metrics.`;
        } else {
            warning.style.display = 'none';
        }

        // Scope intensity bar chart
        if (data.scope_breakdown && data.scope_breakdown.length > 0) {
            const scopeLabels = data.scope_breakdown.map(s => s.scope);
            const scopeTotals = data.scope_breakdown.map(s => s.total);
            const scopePerEmp = data.scope_breakdown.map(s => s.per_employee || 0);

            const options = {
                series: [
                    { name: 'Total (tCO2e)', data: scopeTotals },
                    ...(data.employee_count ? [{ name: 'Per Employee', data: scopePerEmp }] : [])
                ],
                chart: { type: 'bar', height: 320, toolbar: { show: false } },
                plotOptions: { bar: { horizontal: false, borderRadius: 4, columnWidth: '50%' } },
                colors: [COLORS.scope1, COLORS.scope2],
                xaxis: { categories: scopeLabels },
                yaxis: [
                    { title: { text: 'Total (tCO2e)' }, labels: { formatter: val => formatNumber(val) } },
                    ...(data.employee_count ? [{ opposite: true, title: { text: 'Per Employee' }, labels: { formatter: val => val.toFixed(4) } }] : [])
                ],
                tooltip: { y: { formatter: val => val.toFixed(4) } },
                legend: { position: 'top' },
                grid: { borderColor: '#f1f1f1', strokeDashArray: 4 }
            };

            if (intensityScopeChart) intensityScopeChart.destroy();
            intensityScopeChart = new ApexCharts(document.getElementById('intensityScopeChart'), options);
            intensityScopeChart.render();
        }

        // Monthly intensity trend
        if (data.monthly_trend && data.monthly_trend.length > 0) {
            const months = data.monthly_trend.map(m => m.month);
            const totals = data.monthly_trend.map(m => m.total);
            const perEmp = data.monthly_trend.map(m => m.per_employee || 0);

            const series = [{ name: 'Total Emissions (tCO2e)', data: totals }];
            if (data.employee_count) series.push({ name: 'Per Employee (tCO2e)', data: perEmp });

            const options = {
                series: series,
                chart: { type: 'line', height: 320, toolbar: { show: false } },
                stroke: { width: [3, 2], curve: 'smooth', dashArray: [0, 5] },
                colors: [COLORS.scope1, COLORS.scope3],
                markers: { size: [4, 3] },
                xaxis: { categories: months, labels: { rotate: -45, style: { fontSize: '10px' } } },
                yaxis: [
                    { title: { text: 'Total (tCO2e)' }, labels: { formatter: val => formatNumber(val) } },
                    ...(data.employee_count ? [{ opposite: true, title: { text: 'Per Employee' }, labels: { formatter: val => val.toFixed(4) } }] : [])
                ],
                tooltip: { y: { formatter: val => val.toFixed(4) } },
                legend: { position: 'top' },
                grid: { borderColor: '#f1f1f1', strokeDashArray: 4 }
            };

            if (intensityTrendChart) intensityTrendChart.destroy();
            intensityTrendChart = new ApexCharts(document.getElementById('intensityTrendChart'), options);
            intensityTrendChart.render();
        }

        // Intensity table
        const tbody = document.getElementById('intensityTableBody');
        if (data.scope_breakdown && data.scope_breakdown.length > 0) {
            tbody.innerHTML = data.scope_breakdown.map(s => `
                <tr>
                    <td class="ps-3 fw-semibold">${escapeHtml(s.scope)}</td>
                    <td class="text-end">${formatNumber(s.total)}</td>
                    <td class="text-end">${s.per_employee !== null ? s.per_employee.toFixed(4) : 'N/A'}</td>
                    <td class="text-end">${s.per_revenue !== null ? s.per_revenue.toFixed(4) : 'N/A'}</td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">No data available</td></tr>';
        }
    }

    // =====================================================
    // 3. YEAR-OVER-YEAR TRENDS
    // =====================================================
    const initialYoy = @json($yoyData);
    const initialWaterfall = @json($waterfallData);

    function renderYoY(yoyData, waterfallData) {
        // Summary cards
        const change = yoyData.change || {};
        const changeColor = change.direction === 'increase' ? COLORS.increase : (change.direction === 'decrease' ? COLORS.decrease : COLORS.neutral);

        document.getElementById('yoyTotalChange').innerHTML = `<span style="color:${changeColor}">${change.absolute > 0 ? '+' : ''}${formatNumber(change.absolute)} tCO2e</span>`;
        document.getElementById('yoyTotalChangePct').innerHTML = `<span style="color:${changeColor}">${change.percentage > 0 ? '+' : ''}${change.percentage}% ${change.direction === 'increase' ? '↑' : change.direction === 'decrease' ? '↓' : '→'}</span>`;
        document.getElementById('yoyCurrentTotal').textContent = formatNumber(yoyData.current_period?.total) + ' tCO2e';
        document.getElementById('yoyCurrentRange').textContent = `${yoyData.current_period?.start} — ${yoyData.current_period?.end}`;
        document.getElementById('yoyPreviousTotal').textContent = formatNumber(yoyData.previous_period?.total) + ' tCO2e';
        document.getElementById('yoyPreviousRange').textContent = `${yoyData.previous_period?.start} — ${yoyData.previous_period?.end}`;

        // Overlay chart
        const currentData = yoyData.current_period?.data || [];
        const previousData = yoyData.previous_period?.data || [];
        const overlayNoData = document.getElementById('yoyNoData');
        const overlayContainer = document.getElementById('yoyOverlayChart');

        if (currentData.length === 0 && previousData.length === 0) {
            overlayContainer.style.display = 'none';
            overlayNoData.style.display = 'block';
        } else {
            overlayContainer.style.display = 'block';
            overlayNoData.style.display = 'none';

            // Align labels by using the longer series
            const maxLen = Math.max(currentData.length, previousData.length);
            const labels = [];
            for (let i = 0; i < maxLen; i++) {
                labels.push(currentData[i]?.label || previousData[i]?.label || `Period ${i+1}`);
            }

            const options = {
                series: [
                    { name: 'Current Period', data: currentData.map(d => d.value) },
                    { name: 'Previous Period', data: previousData.map(d => d.value) }
                ],
                chart: { type: 'line', height: 380, toolbar: { show: true } },
                stroke: { width: [3, 2], curve: 'smooth', dashArray: [0, 5] },
                colors: [COLORS.scope1, '#999'],
                markers: { size: [5, 3] },
                fill: {
                    type: ['solid', 'solid'],
                    opacity: [1, 0.6]
                },
                xaxis: { categories: labels, labels: { rotate: -45, style: { fontSize: '10px' } } },
                yaxis: { title: { text: 'Emissions (tCO2e)' }, labels: { formatter: val => formatNumber(val) } },
                tooltip: { y: { formatter: val => val.toFixed(2) + ' tCO2e' } },
                legend: { position: 'top' },
                grid: { borderColor: '#f1f1f1', strokeDashArray: 4 }
            };

            if (yoyOverlayChart) yoyOverlayChart.destroy();
            yoyOverlayChart = new ApexCharts(overlayContainer, options);
            yoyOverlayChart.render();
        }

        // Waterfall chart
        renderWaterfall(waterfallData);
    }

    function renderWaterfall(data) {
        const container = document.getElementById('waterfallChart');
        const noData = document.getElementById('waterfallNoData');
        const tbody = document.getElementById('waterfallTableBody');

        if (!data || data.length === 0) {
            container.style.display = 'none';
            noData.style.display = 'block';
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No comparison data available</td></tr>';
            return;
        }
        container.style.display = 'block';
        noData.style.display = 'none';

        // Build waterfall-style rangeBar data
        let running = 0;
        const categories = [];
        const seriesData = [];
        const colors = [];

        data.forEach(item => {
            categories.push(item.category.length > 20 ? item.category.substring(0, 20) + '...' : item.category);
            const start = running;
            running += item.delta;
            seriesData.push({ x: item.category, y: [Math.min(start, running), Math.max(start, running)] });
            colors.push(item.type === 'increase' ? COLORS.increase : COLORS.decrease);
        });

        const options = {
            series: [{ name: 'Change', data: seriesData }],
            chart: { type: 'rangeBar', height: 380, toolbar: { show: false } },
            plotOptions: {
                bar: { horizontal: true, borderRadius: 3, columnWidth: '70%' }
            },
            colors: [function({ dataPointIndex }) { return colors[dataPointIndex] || COLORS.neutral; }],
            xaxis: { type: 'numeric', labels: { formatter: val => formatNumber(val) }, title: { text: 'tCO2e Change' } },
            yaxis: { labels: { style: { fontSize: '10px' }, maxWidth: 150 } },
            tooltip: {
                custom: function({ seriesIndex, dataPointIndex }) {
                    const item = data[dataPointIndex];
                    return `<div class="px-3 py-2">
                        <strong>${escapeHtml(item.category)}</strong><br>
                        Previous: ${item.previous.toFixed(2)} tCO2e<br>
                        Current: ${item.current.toFixed(2)} tCO2e<br>
                        Change: <span style="color:${item.type === 'increase' ? COLORS.increase : COLORS.decrease}">${item.delta > 0 ? '+' : ''}${item.delta.toFixed(2)} tCO2e</span>
                    </div>`;
                }
            },
            grid: { borderColor: '#f1f1f1' }
        };

        if (waterfallChart) waterfallChart.destroy();
        waterfallChart = new ApexCharts(container, options);
        waterfallChart.render();

        // Waterfall table
        tbody.innerHTML = data.map(item => {
            const dirIcon = item.type === 'increase'
                ? '<span class="badge bg-danger-subtle text-danger"><i class="fas fa-arrow-up"></i> Increase</span>'
                : '<span class="badge bg-success-subtle text-success"><i class="fas fa-arrow-down"></i> Decrease</span>';
            return `
                <tr>
                    <td class="ps-3">${escapeHtml(item.category)}</td>
                    <td class="text-end">${formatNumber(item.previous)}</td>
                    <td class="text-end">${formatNumber(item.current)}</td>
                    <td class="text-end" style="color:${item.type === 'increase' ? COLORS.increase : COLORS.decrease}">${item.delta > 0 ? '+' : ''}${formatNumber(item.delta)}</td>
                    <td class="text-center">${dirIcon}</td>
                </tr>
            `;
        }).join('');
    }

    // Period selector
    document.querySelectorAll('#periodSelector .btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('#periodSelector .btn').forEach(b => {
                b.classList.remove('active', 'btn-success');
                b.classList.add('btn-outline-success');
            });
            this.classList.add('active', 'btn-success');
            this.classList.remove('btn-outline-success');

            const params = { ...getFilters(), period: this.dataset.period };
            fetch(`{{ route('analytics.yoy') }}?${buildQueryString(params)}`)
                .then(r => r.json())
                .then(result => renderYoY(result.yoy, result.waterfall));
        });
    });

    // =====================================================
    // 4. HOTSPOT ANALYSIS
    // =====================================================
    const initialHotspots = @json($hotspotsData);
    const initialTreemap = @json($treemapData);

    function renderHotspots(hotspotsData, treemapData) {
        // Treemap
        const treemapContainer = document.getElementById('treemapChart');
        const treemapNoData = document.getElementById('treemapNoData');

        if (!treemapData || treemapData.length === 0) {
            treemapContainer.style.display = 'none';
            treemapNoData.style.display = 'block';
        } else {
            treemapContainer.style.display = 'block';
            treemapNoData.style.display = 'none';

            const options = {
                series: treemapData,
                chart: { type: 'treemap', height: 420, toolbar: { show: true } },
                legend: { show: true, position: 'top' },
                plotOptions: {
                    treemap: {
                        distributed: false,
                        enableShades: true,
                        shadeIntensity: 0.5
                    }
                },
                tooltip: {
                    y: { formatter: function(val) { return val.toFixed(2) + ' tCO2e'; } }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(text, op) {
                        return [text, formatNumber(op.value) + ' tCO2e'];
                    },
                    style: { fontSize: '11px' }
                }
            };

            if (treemapChart) treemapChart.destroy();
            treemapChart = new ApexCharts(treemapContainer, options);
            treemapChart.render();
        }

        // Hotspots table
        const tbody = document.getElementById('hotspotsTableBody');
        document.getElementById('hotspotCount').textContent = `${hotspotsData.length} contributors`;

        if (!hotspotsData || hotspotsData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No hotspot data available</td></tr>';
            return;
        }

        tbody.innerHTML = hotspotsData.map((item, i) => {
            const scopeColor = item.scope === 'Scope 1' ? COLORS.scope1 : (item.scope === 'Scope 2' ? COLORS.scope2 : COLORS.scope3);
            const barColor = item.percentage > 15 ? COLORS.increase : (item.percentage > 5 ? COLORS.scope3 : COLORS.scope1);
            return `
                <tr>
                    <td class="ps-3 fw-bold text-center">${i + 1}</td>
                    <td class="fw-semibold">${escapeHtml(item.name)}</td>
                    <td><span class="badge" style="background:${scopeColor}; color: white;">${escapeHtml(item.scope)}</span></td>
                    <td class="text-end">${formatNumber(item.value)}</td>
                    <td class="text-end fw-semibold">${item.percentage}%</td>
                    <td class="text-end">${item.cumulative}%</td>
                    <td>
                        <div class="hotspot-bar">
                            <div class="hotspot-bar-fill" style="width:${Math.min(item.percentage * 2, 100)}%; background:${barColor};"></div>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // Hotspot group selector
    document.querySelectorAll('#hotspotGroupSelector .btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('#hotspotGroupSelector .btn').forEach(b => {
                b.classList.remove('active', 'btn-success');
                b.classList.add('btn-outline-success');
            });
            this.classList.add('active', 'btn-success');
            this.classList.remove('btn-outline-success');

            const params = { ...getFilters(), group_by: this.dataset.group };
            fetch(`{{ route('analytics.hotspots') }}?${buildQueryString(params)}`)
                .then(r => r.json())
                .then(result => renderHotspots(result.hotspots, result.treemap));
        });
    });

    // =====================================================
    // FILTER CONTROLS
    // =====================================================
    document.getElementById('filterDateRange').addEventListener('change', function() {
        const custom = document.querySelectorAll('.custom-date-fields');
        custom.forEach(el => el.style.display = this.value === 'custom' ? '' : 'none');
    });

    document.getElementById('btnApplyFilters').addEventListener('click', function() {
        const params = getFilters();
        const qs = buildQueryString(params);
        window.location.href = `{{ route('analytics.index') }}?${qs}`;
    });

    document.getElementById('btnResetFilters').addEventListener('click', function() {
        window.location.href = `{{ route('analytics.index') }}`;
    });

    // =====================================================
    // TAB INITIALIZATION — render charts when tab becomes visible
    // =====================================================
    let tabsInitialized = { breakdown: true, intensity: false, trends: false, hotspots: false };

    document.querySelectorAll('#analyticsTabs button[data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            const target = e.target.getAttribute('data-bs-target').replace('#', '');
            if (target === 'intensity' && !tabsInitialized.intensity) {
                renderIntensity(initialIntensity);
                tabsInitialized.intensity = true;
            } else if (target === 'trends' && !tabsInitialized.trends) {
                renderYoY(initialYoy, initialWaterfall);
                tabsInitialized.trends = true;
            } else if (target === 'hotspots' && !tabsInitialized.hotspots) {
                renderHotspots(initialHotspots, initialTreemap);
                tabsInitialized.hotspots = true;
            }
        });
    });

    // Initialize breakdown tab (default active)
    renderBreakdownChart(initialBreakdown);
});
</script>
@endsection
