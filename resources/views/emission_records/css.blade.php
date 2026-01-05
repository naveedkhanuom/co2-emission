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
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            height: 100%;
            border: 2px solid transparent;
        }
        
        .entry-mode-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary-green);
            box-shadow: 0 8px 25px rgba(46, 125, 50, 0.1);
        }
        
        .entry-mode-card.active {
            border-color: var(--primary-green);
            background-color: rgba(46, 125, 50, 0.05);
        }
        
        .entry-mode-icon {
            font-size: 48px;
            color: var(--primary-green);
            margin-bottom: 20px;
        }
        
        /* Form Container */
        .form-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        
        /* Form Sections */
        .form-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .form-section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-green);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .form-section-title i {
            margin-right: 10px;
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
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            padding: 10px 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.25rem rgba(46, 125, 50, 0.25);
        }
        
        /* Calculation Display */
        .calculation-display {
            background-color: var(--gray-50);
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
            border-left: 4px solid var(--primary-blue);
        }
        
        .calculation-formula {
            font-family: monospace;
            font-size: 1rem;
            color: var(--gray-800);
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
        
        /* Help Text */
        .help-text {
            color: var(--gray-600);
            font-size: 0.875rem;
            margin-top: 5px;
        }
        
        /* Calculation Mode Toggle */
        .calculation-mode {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .calc-option {
            flex: 1;
            text-align: center;
            padding: 15px;
            border: 2px solid var(--gray-200);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .calc-option:hover {
            border-color: var(--primary-green);
            background-color: rgba(46, 125, 50, 0.05);
        }
        
        .calc-option.active {
            border-color: var(--primary-green);
            background-color: rgba(46, 125, 50, 0.1);
        }
        
        /* Mobile Toggle */
        @media (max-width: 768px) {
            #sidebarCollapse {
                display: block;
            }
            
            #sidebar {
                margin-left: -250px;
            }
            
            #content {
                margin-left: 0;
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
        }
    </style>