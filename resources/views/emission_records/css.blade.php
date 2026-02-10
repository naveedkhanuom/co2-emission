<!-- Flatpickr CSS for date picker -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<!-- Select2 CSS for enhanced dropdowns -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
    :root {
        --primary-green: #2e7d32;
        --light-green: #4caf50;
        --dark-green: #1b5e20;
        --primary-blue: #0277bd;
        --light-blue: #03a9f4;
        --gray-50: #f8faf9;
        --gray-100: #f1f5f4;
        --gray-200: #e2e8e6;
        --gray-300: #c5d0cc;
        --gray-500: #647870;
        --gray-600: #5f6368;
        --gray-800: #2d3331;
        --warning-orange: #ed6c02;
        --danger-red: #d32f2f;
        --radius-sm: 8px;
        --radius-md: 12px;
        --radius-lg: 16px;
        --radius-xl: 20px;
        --shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
        --shadow-md: 0 4px 12px rgba(0,0,0,0.06);
        --shadow-lg: 0 8px 24px rgba(0,0,0,0.08);
    }

    /* Page context */
    #content {
        margin-left: 250px;
        padding: 24px;
        transition: all 0.3s;
        min-height: 100vh;
        background: linear-gradient(180deg, var(--gray-50) 0%, #fff 120px);
    }

    /* Page header (emission records) */
    .emission-records-page-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--gray-800);
        margin-bottom: 4px;
        letter-spacing: -0.02em;
    }
    .emission-records-page-subtitle {
        font-size: 0.9375rem;
        color: var(--gray-500);
        margin-bottom: 0;
    }

    /* Entry mode strip */
    .entry-mode-strip {
        margin-bottom: 28px;
    }
    .entry-mode-strip .strip-label {
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--gray-500);
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 12px;
    }
    .entry-mode-card {
        background: #fff;
        border-radius: var(--radius-lg);
        padding: 24px 20px;
        box-shadow: var(--shadow-sm);
        text-align: center;
        cursor: pointer;
        transition: all 0.25s ease;
        height: 100%;
        border: 2px solid var(--gray-200);
        position: relative;
        overflow: hidden;
    }
    .entry-mode-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: var(--primary-green);
        opacity: 0;
        transition: opacity 0.25s ease;
    }
    .entry-mode-card:hover {
        transform: translateY(-4px);
        border-color: var(--gray-300);
        box-shadow: var(--shadow-md);
    }
    .entry-mode-card.active {
        border-color: var(--primary-green);
        background: linear-gradient(135deg, rgba(46, 125, 50, 0.06) 0%, #fff 60%);
        box-shadow: 0 0 0 1px rgba(46, 125, 50, 0.2), var(--shadow-md);
    }
    .entry-mode-card.active::before {
        opacity: 1;
    }
    .entry-mode-card h4 {
        font-size: 1.125rem;
        margin: 14px 0 6px 0;
        font-weight: 600;
        color: var(--gray-800);
    }
    .entry-mode-card p {
        font-size: 0.8125rem;
        margin-bottom: 10px;
        line-height: 1.45;
        color: var(--gray-500);
    }
    .entry-mode-icon {
        width: 52px;
        height: 52px;
        margin: 0 auto 8px;
        border-radius: var(--radius-md);
        background: var(--gray-100);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: var(--primary-green);
        transition: all 0.25s ease;
    }
    .entry-mode-card.active .entry-mode-icon {
        background: var(--primary-green);
        color: #fff;
    }
    .entry-mode-card .badge {
        font-size: 0.6875rem;
        padding: 4px 10px;
        font-weight: 600;
        border-radius: 6px;
    }

    /* Form container */
    .form-container {
        background: #fff;
        border-radius: var(--radius-xl);
        padding: 32px;
        box-shadow: var(--shadow-md);
        margin-bottom: 32px;
        border: 1px solid var(--gray-200);
    }
    .form-header {
        padding-bottom: 20px;
        border-bottom: 1px solid var(--gray-200);
        margin-bottom: 28px;
    }
    .form-header h4 {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--gray-800);
        margin-bottom: 4px;
    }
    .form-header .text-muted {
        font-size: 0.875rem;
    }
    .form-steps-indicator {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    .form-steps-indicator .step {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--gray-200);
        color: var(--gray-500);
        font-size: 0.75rem;
        transition: all 0.25s ease;
    }
    .form-steps-indicator .step.active {
        background: var(--primary-green);
        color: #fff;
    }

    /* Form sections */
    .form-section {
        margin-bottom: 32px;
        padding: 24px;
        background: var(--gray-50);
        border-radius: var(--radius-lg);
        border: 1px solid var(--gray-200);
        transition: all 0.25s ease;
    }
    .form-section:hover {
        border-color: var(--gray-300);
    }
    .form-section-header {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 1px solid var(--gray-200);
    }
    .section-number {
        width: 36px;
        height: 36px;
        border-radius: var(--radius-md);
        background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1rem;
        flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(46, 125, 50, 0.3);
    }
    .section-title-content { flex: 1; }
    .form-section-title {
        font-size: 1.0625rem;
        font-weight: 600;
        color: var(--gray-800);
        margin-bottom: 4px;
        display: flex;
        align-items: center;
    }
    .form-section-title i {
        margin-right: 8px;
        color: var(--primary-blue);
        opacity: 0.9;
    }
    .section-description {
        color: var(--gray-500);
        font-size: 0.8125rem;
        margin: 0;
        line-height: 1.4;
    }
    .form-section-body { padding-top: 8px; }

    /* Form fields */
    .form-field-wrapper {
        position: relative;
        margin-bottom: 4px;
    }
    .form-field-wrapper .form-label {
        display: flex;
        align-items: center;
        margin-bottom: 8px;
        font-size: 0.9375rem;
    }
    .form-field-wrapper .form-label i {
        font-size: 0.8rem;
        margin-right: 6px;
        color: var(--gray-500);
    }
    .form-label {
        font-weight: 600;
        color: var(--gray-800);
    }
    .required-label::after {
        content: " *";
        color: var(--danger-red);
        font-weight: 600;
    }
    .form-control, .form-select {
        border: 1.5px solid var(--gray-300);
        border-radius: var(--radius-sm);
        padding: 10px 14px;
        transition: all 0.2s ease;
        font-size: 0.9375rem;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--primary-green);
        box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.12);
        outline: none;
    }
    .form-control.is-invalid, .form-select.is-invalid {
        border-color: var(--danger-red);
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23d32f2f'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 3.6 .4.4.4-.4m0 4.8-.4-.4-.4.4'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 12px center;
    }

    /* Field help – subtle, not competing with inputs */
    .field-help {
        color: var(--gray-500);
        font-size: 0.8125rem;
        margin-top: 6px;
        display: flex;
        align-items: flex-start;
        line-height: 1.4;
        padding: 6px 10px;
        border-radius: var(--radius-sm);
        background: rgba(255,255,255,0.7);
        border-left: 2px solid var(--gray-300);
    }
    .field-help i {
        color: var(--primary-blue);
        margin-right: 6px;
        margin-top: 2px;
        font-size: 0.7rem;
        opacity: 0.9;
    }

    /* Calculation mode selector */
    .calculation-mode-selector {
        display: flex;
        gap: 16px;
        margin-bottom: 20px;
    }
    .calc-option {
        flex: 1;
        padding: 20px 18px;
        border: 2px solid var(--gray-200);
        border-radius: var(--radius-lg);
        cursor: pointer;
        transition: all 0.25s ease;
        background: #fff;
        position: relative;
        display: flex;
        align-items: flex-start;
        gap: 14px;
    }
    .calc-option:hover {
        border-color: var(--primary-green);
        background: rgba(46, 125, 50, 0.03);
        transform: translateY(-2px);
        box-shadow: var(--shadow-sm);
    }
    .calc-option.active {
        border-color: var(--primary-green);
        background: linear-gradient(135deg, rgba(46, 125, 50, 0.08) 0%, rgba(46, 125, 50, 0.02) 100%);
        box-shadow: 0 0 0 1px rgba(46, 125, 50, 0.15);
    }
    .calc-option-icon {
        width: 44px;
        height: 44px;
        border-radius: var(--radius-md);
        background: var(--gray-100);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        color: var(--primary-green);
        flex-shrink: 0;
        transition: all 0.25s ease;
    }
    .calc-option.active .calc-option-icon {
        background: var(--primary-green);
        color: #fff;
    }
    .calc-option-content { flex: 1; }
    .calc-option-content h6 {
        font-weight: 600;
        color: var(--gray-800);
        margin-bottom: 4px;
        font-size: 0.9375rem;
    }
    .calc-option-content .small {
        font-size: 0.8125rem;
        color: var(--gray-500);
    }
    .calc-option-badge {
        position: absolute;
        top: 10px;
        right: 10px;
    }

    /* Calculation result – hero block */
    .calculation-display-enhanced {
        text-align: center;
        background: linear-gradient(135deg, rgba(46, 125, 50, 0.08) 0%, rgba(3, 169, 244, 0.06) 100%);
        border-radius: var(--radius-lg);
        padding: 20px;
        border: 1px solid rgba(46, 125, 50, 0.2);
        margin-top: 8px;
    }
    .calculation-formula-small {
        font-family: 'SF Mono', 'Consolas', monospace;
        font-size: 0.8125rem;
        color: var(--gray-500);
        margin-bottom: 8px;
    }
    .calculated-result-large {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--primary-green);
        margin-top: 4px;
        letter-spacing: -0.02em;
    }
    .calculated-result-large .result-unit {
        font-size: 0.9375rem;
        color: var(--gray-500);
        font-weight: 500;
        margin-left: 6px;
    }

    /* Form actions */
    .form-actions-wrapper {
        margin-top: 32px;
        padding: 24px;
        border-top: 1px solid var(--gray-200);
        background: var(--gray-50);
        border-radius: var(--radius-lg);
        border: 1px solid var(--gray-200);
    }
    .form-actions-wrapper .btn-lg {
        padding: 12px 24px;
        font-weight: 600;
        border-radius: var(--radius-sm);
        transition: all 0.2s ease;
        font-size: 0.9375rem;
    }
    .form-actions-wrapper .btn-success {
        background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
        border: none;
        box-shadow: 0 2px 8px rgba(46, 125, 50, 0.3);
    }
    .form-actions-wrapper .btn-success:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(46, 125, 50, 0.35);
    }
    .form-actions-wrapper .btn-outline-secondary:hover,
    .form-actions-wrapper .btn-outline-primary:hover {
        transform: translateY(-1px);
    }

    /* Input groups */
    .input-group-lg .form-control,
    .input-group-lg .form-select {
        padding: 10px 14px;
        font-size: 0.9375rem;
    }
    .input-group-text {
        background: var(--gray-100);
        border-color: var(--gray-300);
        font-weight: 500;
        color: var(--gray-600);
        font-size: 0.875rem;
    }

    /* Quick entry */
    .quick-entry-header {
        margin-bottom: 20px;
    }
    .quick-entry-header h4 {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--gray-800);
        margin-bottom: 4px;
    }
    .quick-entry-tip {
        padding: 14px 18px;
        border-radius: var(--radius-md);
        background: rgba(3, 169, 244, 0.06);
        border: 1px solid rgba(3, 169, 244, 0.2);
        font-size: 0.875rem;
        color: var(--gray-700);
    }
    .quick-entry-table {
        background: #fff;
        border-radius: var(--radius-lg);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--gray-200);
    }
    .quick-entry-table .table {
        margin-bottom: 0;
    }
    .quick-entry-table .table thead th {
        background: var(--gray-50);
        color: var(--gray-600);
        font-weight: 600;
        font-size: 0.8125rem;
        text-transform: none;
        padding: 12px 10px;
        border-bottom: 1px solid var(--gray-200);
    }
    .quick-entry-table .table tbody td {
        padding: 10px;
        vertical-align: middle;
        font-size: 0.875rem;
    }
    .entry-row {
        transition: background 0.2s ease;
    }
    .entry-row:hover {
        background: rgba(46, 125, 50, 0.04);
    }
    .entry-row.editing {
        background: rgba(3, 169, 244, 0.06);
    }
    .table-controls {
        padding: 14px 18px;
        background: var(--gray-50);
        border-top: 1px solid var(--gray-200);
        font-size: 0.875rem;
    }

    /* Action buttons in table */
    .action-buttons { display: flex; gap: 6px; }
    .action-btn {
        width: 32px;
        height: 32px;
        border-radius: var(--radius-sm);
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        background: var(--gray-100);
        color: var(--gray-600);
        transition: all 0.2s ease;
    }
    .action-btn:hover {
        background: var(--gray-200);
    }
    .save-btn:hover {
        background: rgba(46, 125, 50, 0.12);
        color: var(--primary-green);
    }
    .delete-btn:hover {
        background: rgba(211, 47, 47, 0.1);
        color: var(--danger-red);
    }

    /* Template cards */
    .template-card {
        border-radius: var(--radius-lg) !important;
        border: 2px solid var(--gray-200) !important;
        transition: all 0.25s ease !important;
        cursor: pointer !important;
    }
    .template-card:hover {
        border-color: var(--primary-green) !important;
        transform: translateY(-3px);
        box-shadow: var(--shadow-md) !important;
        background: rgba(46, 125, 50, 0.03) !important;
    }
    .template-card .card-body {
        padding: 20px 16px !important;
    }
    .template-card .card-title {
        font-size: 1rem;
        font-weight: 600;
        color: var(--gray-800);
    }
    .template-card .card-text {
        font-size: 0.8125rem;
        color: var(--gray-500);
    }

    /* Scope badges */
    .scope-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .scope-1 { background: rgba(46, 125, 50, 0.12); color: var(--primary-green); }
    .scope-2 { background: rgba(3, 169, 244, 0.12); color: var(--primary-blue); }
    .scope-3 { background: rgba(121, 85, 72, 0.12); color: #795548; }

    /* Select2 overrides to match design */
    .select2-container--default .select2-selection--single {
        border: 1.5px solid var(--gray-300);
        border-radius: var(--radius-sm);
        min-height: 46px;
        padding: 8px 12px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 44px;
    }
    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: var(--primary-green);
        box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.12);
    }

    /* Modals – match page design */
    #customSourceModal .modal-content,
    #successModal .modal-content {
        border-radius: var(--radius-lg);
        border: 1px solid var(--gray-200);
        box-shadow: var(--shadow-lg);
    }
    #customSourceModal .modal-header,
    #successModal .modal-header {
        border-bottom: 1px solid var(--gray-200);
        padding: 18px 24px;
    }
    #customSourceModal .modal-body,
    #successModal .modal-body {
        padding: 24px;
    }
    #customSourceModal .modal-footer,
    #successModal .modal-footer {
        border-top: 1px solid var(--gray-200);
        padding: 16px 24px;
    }
    #successModal .btn-success {
        background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
        border: none;
        font-weight: 600;
    }

    /* Optional: "Other" source input group */
    #emissionSourceOtherWrapper .form-control {
        border-radius: var(--radius-sm);
    }

    /* Mobile */
    @media (max-width: 768px) {
        #content { padding: 16px; }
        .form-container { padding: 20px; }
        .form-section { padding: 18px; }
        .entry-mode-card { padding: 18px 14px; }
        .entry-mode-card h4 { font-size: 1rem; }
        .calculation-mode-selector { flex-direction: column; }
        .calc-option { flex-direction: row; }
        .form-steps-indicator { display: none; }
        .form-actions-wrapper .d-flex {
            flex-direction: column;
            gap: 10px !important;
        }
        .form-actions-wrapper .btn-lg { width: 100%; }
    }
</style>
