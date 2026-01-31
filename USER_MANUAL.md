# GHG Emissions Management System - User Manual

**Version:** 1.0  
**Last Updated:** January 2026  
**System:** Laravel 11 GHG Emissions Management Platform

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Getting Started](#2-getting-started)
3. [User Roles and Permissions](#3-user-roles-and-permissions)
4. [Company Management](#4-company-management)
5. [Data Entry - Emission Records](#5-data-entry---emission-records)
6. [Scope 3 Management](#6-scope-3-management)
7. [Reports and Analytics](#7-reports-and-analytics)
8. [Targets and Goals](#8-targets-and-goals)
9. [Data Import and Export](#9-data-import-and-export)
10. [Settings and Configuration](#10-settings-and-configuration)
11. [Troubleshooting](#11-troubleshooting)
12. [Appendix](#12-appendix)

---

## 1. Introduction

### 1.1 What is the GHG Emissions Management System?

The GHG Emissions Management System is a comprehensive platform designed to help organizations track, manage, and report their greenhouse gas (GHG) emissions. The system supports:

- **Multi-Company Architecture**: Manage multiple companies from a single platform
- **Scope 1, 2, and 3 Tracking**: Complete coverage of all emission scopes
- **Compliance Reporting**: Generate reports aligned with GHG Protocol standards
- **Data Quality Management**: Track and improve data quality over time
- **Target Setting**: Set and monitor emission reduction targets

### 1.2 Key Features

- ✅ Emission record management (manual entry and bulk import)
- ✅ Scope 3 supplier management and surveys
- ✅ Site, facility, and department organization
- ✅ Emission sources and factors library
- ✅ Custom reports and templates
- ✅ Target tracking and goal management
- ✅ Data quality dashboard
- ✅ Utility bill OCR processing
- ✅ Import history tracking
- ✅ Multi-company switching

### 1.3 System Requirements

**Minimum Requirements:**
- Modern web browser (Chrome, Firefox, Safari, Edge - latest versions)
- Internet connection
- Screen resolution: 1280x720 or higher

**Recommended:**
- Screen resolution: 1920x1080 or higher
- JavaScript enabled
- Cookies enabled

---

## 2. Getting Started

### 2.1 First Login

1. Navigate to the login page
2. Enter your email address and password
3. Click "Login"
4. If you don't have an account, contact your system administrator

### 2.2 Dashboard Overview

After logging in, you'll see the main dashboard with:

- **Quick Stats**: Total emissions, targets, data quality score
- **Recent Activity**: Latest emission records entered
- **Charts**: Visual representation of emissions by scope
- **Navigation Menu**: Access to all system features

### 2.3 Company Selection

If you have access to multiple companies:

1. Click the company switcher in the top navigation
2. Select the company you want to work with
3. All data will be filtered to that company

### 2.4 Navigation

The main navigation menu includes:

- **Home**: Dashboard
- **Emission Records**: Data entry and management
- **Scope 3**: Scope 3 emissions and suppliers
- **Reports**: Generate and view reports
- **Targets**: Set and track emission targets
- **Settings**: System configuration
- **Users**: User management (Admin only)

---

## 3. User Roles and Permissions

### 3.1 Available Roles

The system supports role-based access control with the following default roles:

**Super Admin**
- Full system access
- Can manage all companies
- Can create and manage users
- Can configure system settings

**Admin**
- Full access to assigned company
- Can manage company users
- Can configure company settings
- Can view and edit all data

**Manager**
- Can view and edit emission records
- Can generate reports
- Can manage targets
- Cannot delete records

**User**
- Can enter emission records
- Can view own records
- Limited report access
- Cannot delete records

**Viewer**
- Read-only access
- Can view reports and dashboards
- Cannot create or edit data

### 3.2 Permission Management

**To Manage Roles (Admin only):**

1. Navigate to **Users** → **Roles**
2. Click "Create Role" or edit existing role
3. Assign permissions to the role
4. Save changes

**To Assign Roles to Users:**

1. Navigate to **Users** → **Users**
2. Click "Edit" on a user
3. Select the appropriate role
4. Save changes

---

## 4. Company Management

### 4.1 Creating a Company

**Note:** Only Super Admins can create companies.

1. Navigate to **Companies** → **Create Company**
2. Fill in company details:
   - Company Name (required)
   - Company Code (unique identifier)
   - Industry Type
   - Country
   - Address
   - Contact Information
   - Logo (optional)
3. Configure settings:
   - Currency
   - Timezone
   - Fiscal Year Start
   - Reporting Standards
   - Enabled Scopes (1, 2, 3)
4. Click "Save"

### 4.2 Editing Company Information

1. Navigate to **Companies**
2. Click on the company you want to edit
3. Click "Edit"
4. Update information
5. Click "Save"

### 4.3 Company Settings

Access company-specific settings:

1. Navigate to **Companies** → Select Company → **Settings**
2. Configure:
   - Default emission factors
   - Data quality thresholds
   - Notification preferences
   - Report templates
   - Custom fields

---

## 5. Data Entry - Emission Records

### 5.1 Overview

Emission records are the core of the system. Each record represents a single emission event or measurement period.

### 5.2 Single Entry Form

**To enter a single emission record:**

1. Navigate to **Emission Records** → **New Entry**
2. Fill in the form:

   **Basic Information:**
   - **Entry Date**: Date of the emission (required)
   - **Facility**: Select or enter facility name (required)
   - **Site**: Select site (optional, if available)
   - **Department**: Select department (optional)
   - **Scope**: Select 1, 2, or 3 (required)

   **Emission Details:**
   - **Emission Source**: Select from dropdown (required)
   - **Activity Data**: Enter the quantity (e.g., 1000 kWh) (required)
   - **Unit**: Automatically populated based on source
   - **Emission Factor**: Auto-filled, can be edited (required)
   - **CO₂e Value**: Auto-calculated (Activity × Factor) (required)

   **Additional Information:**
   - **Confidence Level**: Low, Medium, or High (required)
   - **Data Source**: Manual, Import, API, Meter, Invoice, Estimate (required)
   - **Notes**: Optional comments
   - **Status**: Active or Draft

   **Scope 3 Specific Fields** (only shown when Scope 3 is selected):
   - **Scope 3 Category**: Select from 15 GHG Protocol categories
   - **Supplier**: Select supplier (if applicable)
   - **Calculation Method**: Activity-based, Spend-based, or Hybrid
   - **Data Quality**: Primary, Secondary, or Estimated
   - **Spend Amount**: For spend-based calculations
   - **Spend Currency**: Currency code (e.g., USD, EUR)

   **Supporting Documents:**
   - Click "Upload Documents"
   - Select files (PDF, images, Excel files)
   - Maximum file size: 10MB per file
   - Multiple files can be uploaded

3. Click "Save" or "Save as Draft"

### 5.3 Quick Entry (Bulk Entry)

**To enter multiple records quickly:**

1. Navigate to **Emission Records** → **Quick Entry**
2. Click "Add Row" to add a new entry row
3. Fill in the required fields for each row:
   - Entry Date
   - Facility
   - Scope
   - Emission Source
   - CO₂e Value
   - Confidence Level
   - Data Source
4. Click "Save All" to save all entries at once
5. Validation errors will be shown per row if any

### 5.4 Scope Entry Mode

**To enter emissions by scope:**

1. Navigate to **Emission Records** → **Scope Entry**
2. Select the scope tab (Scope 1, 2, or 3)
3. Fill in the form (same fields as single entry)
4. The emission source dropdown will be filtered by scope

### 5.5 Editing Records

1. Navigate to **Emission Records**
2. Find the record in the table
3. Click "Edit"
4. Modify the fields
5. Click "Save"

### 5.6 Deleting Records

1. Navigate to **Emission Records**
2. Find the record
3. Click "Delete"
4. Confirm deletion

**Note:** Only users with delete permissions can delete records.

### 5.7 Viewing Records

1. Navigate to **Emission Records**
2. Use filters to find specific records:
   - Date range
   - Scope
   - Facility
   - Emission source
3. Click "View" to see full details
4. Export data using the "Export" button

### 5.8 Data Validation

The system validates:
- Required fields are filled
- Dates are not in the future
- Numeric values are positive
- Sites belong to the current company
- Suppliers belong to the current company
- File uploads are within size limits

---

## 6. Scope 3 Management

### 6.1 Overview

Scope 3 emissions are indirect emissions from your value chain. The system provides comprehensive tools for managing these emissions.

### 6.2 Scope 3 Categories

The system supports all 15 GHG Protocol Scope 3 categories:

**Upstream:**
1. Purchased goods and services
2. Capital goods
3. Fuel and energy-related activities
4. Upstream transportation and distribution
5. Waste generated in operations
6. Business travel
7. Employee commuting
8. Upstream leased assets

**Downstream:**
9. Downstream transportation and distribution
10. Processing of sold products
11. Use of sold products
12. End-of-life treatment of sold products
13. Downstream leased assets
14. Franchises
15. Investments

### 6.3 Supplier Management

**Adding a Supplier:**

1. Navigate to **Scope 3** → **Suppliers** → **Add Supplier**
2. Fill in supplier information:
   - Name (required)
   - Email
   - Contact Person
   - Phone
   - Address
   - Industry
   - Data Quality
   - Status
3. Click "Save"

**Managing Suppliers:**

1. Navigate to **Scope 3** → **Suppliers**
2. View supplier list with:
   - Total emissions
   - Data quality score
   - Last data submission
   - Status
3. Click on a supplier to view details
4. Edit or delete as needed

### 6.4 Supplier Surveys

**Creating a Survey:**

1. Navigate to **Scope 3** → **Supplier Surveys** → **Create Survey**
2. Select suppliers to survey
3. Configure survey:
   - Title
   - Description
   - Questions (emission categories)
   - Due date
4. Click "Send Survey"

**Supplier Portal:**

Suppliers receive a unique link to complete the survey:
1. Supplier clicks the survey link
2. Fills in emission data by category
3. Uploads supporting documents
4. Submits the survey

**Managing Surveys:**

1. Navigate to **Scope 3** → **Supplier Surveys**
2. View survey status:
   - Pending
   - In Progress
   - Completed
   - Overdue
3. Send reminders to suppliers
4. Review and approve submitted data

### 6.5 Scope 3 Summary

**Viewing Scope 3 Summary:**

1. Navigate to **Scope 3** → **Summary**
2. View:
   - Total Scope 3 emissions
   - Emissions by category
   - Supplier contributions
   - Data quality breakdown
   - Trends over time

### 6.6 EIO Factors (Spend-Based Calculations)

**Using EIO Factors:**

1. Navigate to **Scope 3** → **EIO Factors**
2. View available factors by:
   - Sector code
   - Country
   - Year
3. When entering spend-based emissions:
   - Select sector code
   - Enter spend amount
   - System calculates emissions automatically

**Calculating from Spend:**

1. In emission entry form, select Scope 3
2. Select "Spend-based" calculation method
3. Enter spend amount and currency
4. Select sector code
5. System auto-calculates CO₂e value

---

## 7. Reports and Analytics

### 7.1 Report Types

The system supports various report types:

- **GHG Protocol Report**: Standard GHG Protocol format
- **Custom Reports**: User-defined reports
- **Scope Breakdown**: Emissions by scope
- **Facility Reports**: Facility-level analysis
- **Department Reports**: Department-level analysis
- **Supplier Reports**: Supplier-level analysis
- **Trend Reports**: Time-series analysis

### 7.2 Generating a Report

**GHG Protocol Report:**

1. Navigate to **Reports** → **GHG Protocol Report**
2. Select:
   - Reporting period (start and end date)
   - Company/Scope
   - Facilities (optional)
3. Click "Generate Report"
4. Review the report
5. Export as PDF or Excel

**Custom Report:**

1. Navigate to **Reports** → **Create Report**
2. Select report template or create new
3. Configure:
   - Report name
   - Date range
   - Filters (scope, facility, source, etc.)
   - Charts and visualizations
   - Columns to include
4. Preview report
5. Save template (optional)
6. Generate and export

### 7.3 Report Templates

**Creating a Template:**

1. Navigate to **Reports** → **Templates**
2. Click "Create Template"
3. Design the template:
   - Add sections
   - Configure filters
   - Add charts
   - Set formatting
4. Save template
5. Use template for future reports

**Using Templates:**

1. Navigate to **Reports** → **Create Report**
2. Select a template
3. Adjust parameters if needed
4. Generate report

### 7.4 Scheduled Reports

**Setting Up Scheduled Reports:**

1. Navigate to **Reports** → **Scheduled Reports**
2. Click "Create Scheduled Report"
3. Configure:
   - Report template
   - Schedule (daily, weekly, monthly, quarterly, yearly)
   - Recipients
   - Format (PDF, Excel)
4. Save schedule
5. Reports will be automatically generated and emailed

### 7.5 Statistics Dashboard

**Viewing Statistics:**

1. Navigate to **Reports** → **Statistics**
2. View:
   - Total emissions by scope
   - Emissions over time
   - Top emission sources
   - Facility comparison
   - Data quality metrics
   - Target progress

### 7.6 Exporting Data

**Export Options:**

1. From any data table, click "Export"
2. Select format:
   - Excel (.xlsx)
   - CSV
   - PDF
3. Select columns to include
4. Apply filters
5. Click "Export"

---

## 8. Targets and Goals

### 8.1 Setting Targets

**Creating a Target:**

1. Navigate to **Targets** → **Create Target**
2. Fill in target details:
   - **Target Name**: Descriptive name
   - **Scope**: 1, 2, 3, or All
   - **Baseline Year**: Reference year
   - **Baseline Value**: Starting emission value
   - **Target Year**: Year to achieve target
   - **Target Value**: Goal emission value
   - **Reduction Percentage**: Auto-calculated
   - **Facility**: Specific facility (optional)
   - **Status**: Active, Pending, Achieved, Missed
3. Click "Save"

### 8.2 Target Types

**Absolute Targets:**
- Specific emission value (e.g., 1000 tonnes CO₂e)

**Relative Targets:**
- Percentage reduction (e.g., 30% reduction)

**Intensity Targets:**
- Emissions per unit (e.g., per revenue, per product)

### 8.3 Tracking Progress

**Viewing Target Progress:**

1. Navigate to **Targets**
2. View target list with:
   - Current progress
   - Percentage complete
   - Status indicator
   - Time remaining
3. Click on a target to see:
   - Progress chart
   - Monthly breakdown
   - Forecast
   - Gap analysis

### 8.4 Target Alerts

The system can send alerts when:
- Targets are at risk
- Targets are achieved
- Targets are missed
- Progress milestones are reached

**Note:** Alert functionality is being enhanced.

---

## 9. Data Import and Export

### 9.1 Importing Data

**Excel Import:**

1. Navigate to **Emission Records** → **Import**
2. Download the sample template
3. Fill in the template with your data:
   - Entry Date
   - Facility
   - Scope
   - Emission Source
   - Activity Data
   - Emission Factor
   - CO₂e Value
   - Additional fields
4. Click "Choose File" and select your file
5. Click "Import"
6. Review import preview
7. Confirm import
8. Check import history for status

**Import Template Columns:**

Required columns:
- Entry Date
- Facility
- Scope
- Emission Source
- Activity Data
- Emission Factor
- CO₂e Value
- Confidence Level
- Data Source

Optional columns:
- Site
- Department
- Scope 3 Category
- Supplier
- Calculation Method
- Data Quality
- Spend Amount
- Spend Currency
- Notes

### 9.2 Import History

**Viewing Import History:**

1. Navigate to **Import History**
2. View all imports with:
   - Import date
   - File name
   - Status (Success, Failed, Partial)
   - Records imported
   - Errors
3. Click on an import to see:
   - Detailed logs
   - Error messages
   - Imported records
   - Failed rows

### 9.3 Exporting Data

**Exporting Emission Records:**

1. Navigate to **Emission Records**
2. Apply filters if needed
3. Click "Export"
4. Select format (Excel, CSV, PDF)
5. Select columns
6. Click "Export"

**Exporting Reports:**

1. Generate a report
2. Click "Export" in the report view
3. Select format
4. Download file

### 9.4 Data Validation on Import

The system validates:
- Required fields
- Data types
- Date formats
- Numeric values
- Foreign key relationships
- Duplicate records

---

## 10. Settings and Configuration

### 10.1 User Profile

**Updating Profile:**

1. Click on your name in the top navigation
2. Select "Profile"
3. Update:
   - Name
   - Email
   - Password
   - Timezone
   - Language preferences
4. Click "Save"

### 10.2 Company Settings

**Accessing Company Settings:**

1. Navigate to **Companies** → Select Company → **Settings**
2. Configure:
   - Default emission factors
   - Data quality thresholds
   - Notification preferences
   - Report defaults
   - Custom fields

### 10.3 Emission Sources

**Managing Emission Sources:**

1. Navigate to **Settings** → **Emission Sources**
2. View list of sources
3. Click "Add" to create new source:
   - Name
   - Scope (1, 2, 3, or All)
   - Description
   - Default unit
4. Edit or delete existing sources

### 10.4 Emission Factors

**Managing Emission Factors:**

1. Navigate to **Settings** → **Emission Factors**
2. View factors by source
3. Click "Add" to create new factor:
   - Emission Source
   - Factor Value
   - Unit
   - Region/Country
   - Year
   - Source (e.g., IPCC, EPA)
   - Notes
4. Edit or delete existing factors

### 10.5 Facilities and Sites

**Managing Facilities:**

1. Navigate to **Settings** → **Facilities**
2. Click "Add Facility"
3. Enter:
   - Facility Name
   - Location
   - Description
4. Save

**Managing Sites:**

1. Navigate to **Settings** → **Sites**
2. Click "Add Site"
3. Enter:
   - Site Name
   - Location
   - Latitude/Longitude (optional)
   - Description
4. Save

**Managing Departments:**

1. Navigate to **Settings** → **Departments**
2. Click "Add Department"
3. Enter:
   - Department Name
   - Facility (optional)
   - Description
4. Save

---

## 11. Troubleshooting

### 11.1 Common Issues

**Cannot Login:**
- Verify email and password
- Check if account is active
- Contact administrator if locked out

**Data Not Saving:**
- Check required fields are filled
- Verify numeric values are valid
- Check file upload size limits
- Review validation errors

**Reports Not Generating:**
- Verify date range is valid
- Check if data exists for selected period
- Ensure proper permissions
- Try refreshing the page

**Import Failing:**
- Verify file format matches template
- Check all required columns are present
- Review error messages in import history
- Ensure data types are correct

**Slow Performance:**
- Clear browser cache
- Reduce date range in filters
- Contact administrator if persistent

### 11.2 Error Messages

**"Site does not belong to your company"**
- Verify you're in the correct company
- Check site assignment

**"Supplier does not belong to your company"**
- Verify supplier is assigned to your company
- Check company selection

**"Emission factor not found"**
- Verify emission source is selected
- Check if factor exists for the source
- Add factor if missing

**"File size exceeds limit"**
- Maximum file size is 10MB
- Compress files or split into multiple uploads

### 11.3 Getting Help

**Support Channels:**
- Check this user manual
- Review FAQ section (if available)
- Contact system administrator
- Submit support ticket (if available)

**Reporting Issues:**
When reporting issues, include:
- Steps to reproduce
- Error messages
- Screenshots
- Browser and version
- Date and time of issue

---

## 12. Appendix

### 12.1 Glossary

**Activity Data**: The quantity of an activity that results in GHG emissions (e.g., kWh of electricity consumed).

**CO₂e (Carbon Dioxide Equivalent)**: A metric used to compare emissions of different GHGs based on their global warming potential.

**Emission Factor**: A factor that converts activity data into GHG emissions (e.g., kg CO₂e per kWh).

**Emission Source**: The origin of GHG emissions (e.g., electricity consumption, vehicle fuel).

**Scope 1**: Direct emissions from owned or controlled sources.

**Scope 2**: Indirect emissions from purchased energy.

**Scope 3**: All other indirect emissions in the value chain.

**Data Quality**: Assessment of the reliability of emission data (Primary, Secondary, Estimated).

### 12.2 Keyboard Shortcuts

- `Ctrl + S`: Save current form
- `Ctrl + Enter`: Submit form
- `Esc`: Cancel/Close dialog
- `Ctrl + F`: Search in current page

### 12.3 File Format Specifications

**Supported Import Formats:**
- Excel (.xlsx, .xls)
- CSV (.csv)

**Supported Document Formats:**
- PDF (.pdf)
- Images (.jpg, .jpeg, .png, .webp)
- Excel (.xlsx, .xls)
- CSV (.csv)

**Maximum File Sizes:**
- Document uploads: 10MB per file
- Import files: 50MB

### 12.4 Data Retention

- Emission records: Retained indefinitely
- Import history: Retained for 2 years
- Reports: Retained based on company policy
- User activity logs: Retained for 1 year

### 12.5 Best Practices

**Data Entry:**
- Enter data regularly (daily/weekly)
- Use consistent facility and source names
- Include supporting documents
- Set appropriate confidence levels
- Review data before submission

**Data Quality:**
- Use primary data when available
- Document data sources
- Regular data quality reviews
- Validate calculations
- Keep supporting documents

**Reporting:**
- Generate reports regularly
- Review for accuracy
- Save report templates
- Schedule automated reports
- Archive important reports

**Target Management:**
- Set realistic targets
- Monitor progress regularly
- Adjust targets if needed
- Document target rationale
- Celebrate achievements

### 12.6 Contact Information

For technical support or questions:
- Email: [Your Support Email]
- Phone: [Your Support Phone]
- Hours: [Support Hours]

---

## Document Control

**Version History:**
- v1.0 (January 2026): Initial release

**Next Review Date:** July 2026

**Document Owner:** System Administrator

---

*This user manual is a living document and will be updated as new features are added to the system.*
