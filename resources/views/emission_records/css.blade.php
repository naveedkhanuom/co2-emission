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
            --gray-50: #f8f9fa;
            --gray-100: #f1f3f4;
            --gray-200: #e8eaed;
            --gray-600: #5f6368;
            --gray-800: #3c4043;
            --warning-orange: #f57c00;
            --danger-red: #d32f2f;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--gray-50);
            color: var(--gray-800);
        }
        
        
        /* Main Content */
        #content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        /* Top Navigation */
        .top-navbar {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 15px 20px;
            position: sticky;
            top: 0;
            z-index: 999;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        /* Entry Modes */
        .entry-mode-card {
            background: white;
            border-radius: 10px;
            padding: 20px 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            height: 100%;
            border: 2px solid transparent;
        }
        
        .entry-mode-card:hover {
            transform: translateY(-3px);
            border-color: var(--primary-green);
            box-shadow: 0 6px 20px rgba(46, 125, 50, 0.1);
        }
        
        .entry-mode-card.active {
            border-color: var(--primary-green);
            background-color: rgba(46, 125, 50, 0.05);
        }
        
        .entry-mode-card h4 {
            font-size: 1.1rem;
            margin: 15px 0 10px 0;
            font-weight: 600;
            color: var(--gray-800);
        }
        
        .entry-mode-card p {
            font-size: 0.85rem;
            margin-bottom: 12px;
            line-height: 1.4;
        }
        
        .entry-mode-icon {
            font-size: 36px;
            color: var(--primary-green);
            margin-bottom: 12px;
        }
        
        .entry-mode-card .badge {
            font-size: 0.75rem;
            padding: 4px 10px;
        }
        
        /* Form Container */
        .form-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        
        /* Form Header */
        .form-header {
            padding-bottom: 20px;
            border-bottom: 2px solid var(--gray-200);
            margin-bottom: 30px;
        }
        
        .form-steps-indicator {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .form-steps-indicator .step {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--gray-200);
            color: var(--gray-600);
            font-size: 0.875rem;
            transition: all 0.3s;
        }
        
        .form-steps-indicator .step.active {
            background: var(--primary-green);
            color: white;
        }
        
        /* Form Sections */
        .form-section {
            margin-bottom: 40px;
            padding: 25px;
            background: var(--gray-50);
            border-radius: 12px;
            border: 1px solid var(--gray-200);
            transition: all 0.3s;
        }
        
        .form-section:hover {
            border-color: var(--primary-green);
            box-shadow: 0 2px 8px rgba(46, 125, 50, 0.1);
        }
        
        .form-section-header {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--gray-200);
        }
        
        .section-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-green);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        
        .section-title-content {
            flex: 1;
        }
        
        .form-section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 5px;
            display: flex;
            align-items: center;
        }
        
        .section-description {
            color: var(--gray-600);
            font-size: 0.9rem;
            margin: 0;
        }
        
        .form-section-body {
            padding-top: 10px;
        }
        
        .form-section-title i {
            margin-right: 10px;
            color: var(--primary-blue);
        }
        
        /* Form Field Wrapper */
        .form-field-wrapper {
            position: relative;
        }
        
        .form-field-wrapper .form-label {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .form-field-wrapper .form-label i {
            font-size: 0.9rem;
            margin-right: 6px;
            color: var(--gray-600);
        }
        
        /* Form Labels */
        .form-label {
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 8px;
        }
        
        .required-label::after {
            content: " *";
            color: var(--danger-red);
        }
        
        /* Form Controls */
        .form-control, .form-select {
            border: 1.5px solid var(--gray-300);
            border-radius: 8px;
            padding: 10px 15px;
            transition: all 0.3s;
            font-size: 0.95rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.25rem rgba(46, 125, 50, 0.15);
            outline: none;
        }
        
        .form-control:invalid, .form-select:invalid {
            border-color: var(--danger-red);
        }
        
        .form-control.is-invalid, .form-select.is-invalid {
            border-color: var(--danger-red);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 3.6 .4.4.4-.4m0 4.8-.4-.4-.4.4'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
        
        /* Calculation Display */
        .calculation-display, .calculation-display-enhanced {
            background: linear-gradient(135deg, rgba(3, 169, 244, 0.1) 0%, rgba(46, 125, 50, 0.1) 100%);
            border-radius: 12px;
            padding: 20px;
            margin-top: 10px;
            border: 2px solid var(--primary-blue);
            box-shadow: 0 2px 8px rgba(3, 169, 244, 0.1);
        }
        
        .calculation-display-enhanced {
            text-align: center;
        }
        
        .calculation-formula {
            font-family: 'Courier New', monospace;
            font-size: 0.95rem;
            color: var(--gray-700);
            margin-bottom: 10px;
        }
        
        .calculation-formula-small {
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            color: var(--gray-600);
        }
        
        .calculated-result-large {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-green);
            margin-top: 10px;
        }
        
        .calculated-result-large .result-unit {
            font-size: 1rem;
            color: var(--gray-600);
            font-weight: 500;
            margin-left: 8px;
        }
        
        /* Quick Entry Table */
        .quick-entry-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        .table-controls {
            padding: 15px;
            background-color: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
        }
        
        .entry-row {
            transition: all 0.3s;
        }
        
        .entry-row:hover {
            background-color: rgba(46, 125, 50, 0.05);
        }
        
        .entry-row.editing {
            background-color: rgba(3, 169, 244, 0.1);
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            background-color: var(--gray-100);
            color: var(--gray-600);
            transition: all 0.2s;
        }
        
        .action-btn:hover {
            background-color: var(--gray-200);
        }
        
        .save-btn:hover {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--light-green);
        }
        
        .edit-btn:hover {
            background-color: rgba(3, 169, 244, 0.1);
            color: var(--light-blue);
        }
        
        .delete-btn:hover {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        /* Recent Entries */
        .recent-entry-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 15px;
            border-left: 4px solid var(--primary-green);
        }
        
        .recent-entry-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        
        /* Saved Drafts */
        .draft-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 15px;
            border-left: 4px solid var(--warning-orange);
        }
        
        /* Scope Badges */
        .scope-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .scope-1 {
            background-color: rgba(46, 125, 50, 0.1);
            color: var(--primary-green);
        }
        
        .scope-2 {
            background-color: rgba(3, 169, 244, 0.1);
            color: var(--light-blue);
        }
        
        .scope-3 {
            background-color: rgba(121, 85, 72, 0.1);
            color: #795548;
        }
        
        /* Help Text / Field Help */
        .help-text, .field-help {
            color: var(--gray-600);
            font-size: 0.85rem;
            margin-top: 8px;
            display: flex;
            align-items: flex-start;
            line-height: 1.5;
        }
        
        .field-help {
            background: white;
            padding: 8px 12px;
            border-radius: 6px;
            border-left: 3px solid var(--primary-blue);
        }
        
        .field-help i {
            color: var(--primary-blue);
            margin-right: 6px;
            margin-top: 2px;
            font-size: 0.75rem;
        }
        
        /* Calculation Mode Selector */
        .calculation-mode-selector {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .calc-option {
            flex: 1;
            padding: 25px 20px;
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
            position: relative;
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }
        
        .calc-option:hover {
            border-color: var(--primary-green);
            background-color: rgba(46, 125, 50, 0.03);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.1);
        }
        
        .calc-option.active {
            border-color: var(--primary-green);
            background: linear-gradient(135deg, rgba(46, 125, 50, 0.1) 0%, rgba(46, 125, 50, 0.05) 100%);
            box-shadow: 0 4px 16px rgba(46, 125, 50, 0.15);
        }
        
        .calc-option-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: var(--gray-100);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--primary-green);
            flex-shrink: 0;
            transition: all 0.3s;
        }
        
        .calc-option.active .calc-option-icon {
            background: var(--primary-green);
            color: white;
        }
        
        .calc-option-content {
            flex: 1;
        }
        
        .calc-option-content h6 {
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 5px;
        }
        
        .calc-option-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        
        /* Form Actions Wrapper */
        .form-actions-wrapper {
            margin-top: 40px;
            padding-top: 25px;
            border-top: 2px solid var(--gray-200);
            background: var(--gray-50);
            padding: 25px;
            border-radius: 12px;
        }
        
        .form-actions-wrapper .btn-lg {
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .form-actions-wrapper .btn-lg:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        /* Enhanced Form Controls */
        .form-control-lg, .form-select-lg {
            padding: 12px 16px;
            font-size: 1rem;
            border-radius: 8px;
            border: 1.5px solid var(--gray-300);
            transition: all 0.3s;
        }
        
        .form-control-lg:focus, .form-select-lg:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.25rem rgba(46, 125, 50, 0.15);
            transform: translateY(-1px);
        }
        
        /* Input Group Enhancements */
        .input-group-lg .form-control,
        .input-group-lg .form-select {
            padding: 12px 16px;
        }
        
        .input-group-text {
            background: var(--gray-100);
            border-color: var(--gray-300);
            font-weight: 500;
            color: var(--gray-700);
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            #sidebarCollapse {
                display: block;
            }
            
            #sidebar {
                margin-left: -250px;
            }
            
            #content {
                margin-left: 0;
                padding: 15px;
            }
            
            #sidebar.active {
                margin-left: 0;
            }
            
            #content.active {
                margin-left: 250px;
            }
            
            .action-buttons {
                flex-wrap: wrap;
            }
            
            .form-section {
                padding: 20px 15px;
            }
            
            .form-section-header {
                flex-direction: column;
                gap: 15px;
            }
            
            .calculation-mode-selector {
                flex-direction: column;
            }
            
            .calc-option {
                flex-direction: row;
            }
            
            .form-steps-indicator {
                display: none;
            }
            
            .form-header {
                text-align: center;
            }
            
            .form-actions-wrapper .d-flex {
                flex-direction: column;
                gap: 10px !important;
            }
            
            .form-actions-wrapper .btn-lg {
                width: 100%;
            }
        }
    </style>