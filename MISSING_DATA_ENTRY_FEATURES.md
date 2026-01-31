# Missing Features in Data Entry - GHG Emissions Management System

This document lists all missing features and improvements needed in the data entry functionality.

---

## 🔴 Critical Missing Features

### 1. Site Selection Field
**Status:** ❌ Missing  
**Impact:** High  
**Description:** The database model has `site_id` field, but the entry forms don't have a site selection dropdown.

**Missing in:**
- `resources/views/scope_entry/index.blade.php`
- `resources/views/emission_records/index.blade.php`

**Required Implementation:**
- Add site dropdown after facility selection
- Load sites based on selected facility (if applicable)
- Store `site_id` in emission records
- Display site information in records list

**Code Location:**
- Model: `app/Models/EmissionRecord.php` (has `site_id` field)
- Controller: `app/Http/Controllers/EmissionRecordController.php` (needs to handle site_id)

---

### 2. Scope 3 Specific Fields (Partially Missing)
**Status:** ⚠️ Partially Implemented  
**Impact:** High  
**Description:** Scope 3 entry forms are missing critical fields that exist in the database and controller logic.

**Missing Fields in Forms:**
- `scope3_category_id` - Scope 3 category selection (1-15 GHG Protocol categories)
- `supplier_id` - Supplier selection for Scope 3 emissions
- `calculation_method` - Activity-based vs Spend-based vs Hybrid
- `data_quality` - Primary, Secondary, or Estimated
- `spend_amount` - For spend-based calculations
- `spend_currency` - Currency for spend amount

**Current Status:**
- ✅ Database has these fields (migration exists)
- ✅ Controller handles these fields in `storeOrUpdate()` method
- ❌ Forms don't have input fields for these
- ❌ No conditional display based on scope selection

**Required Implementation:**
- Show Scope 3 specific fields only when Scope 3 is selected
- Add Scope 3 category dropdown (load from `scope3_categories` table)
- Add supplier dropdown (load from `suppliers` table)
- Add calculation method selector (activity-based/spend-based/hybrid)
- Add spend amount and currency fields (show only for spend-based)
- Add data quality selector
- Auto-calculate emissions for spend-based using EIO factors

**Code Location:**
- Migration: `database/migrations/2026_01_17_100002_add_scope3_fields_to_emission_records_table.php`
- Controller: `app/Http/Controllers/EmissionRecordController.php` (lines 249-273)
- Forms: Missing in both entry forms

---

### 3. Supporting Documents Upload
**Status:** ❌ Missing  
**Impact:** High  
**Description:** The database has `supporting_documents` field (JSON), but there's no file upload functionality in entry forms.

**Missing Features:**
- File upload input field
- Multiple file upload support
- File type validation (PDF, images, Excel, etc.)
- File size limits
- Preview uploaded documents
- Delete uploaded documents
- Document storage in `storage/app/public/documents/`
- Store file paths in `supporting_documents` JSON field

**Required Implementation:**
- Add file upload section in entry forms
- Implement file upload handling in controller
- Add file validation (type, size)
- Store files and save paths to database
- Display uploaded documents in record view/edit

**Code Location:**
- Model: `app/Models/EmissionRecord.php` (has `supporting_documents` cast as array)
- Controller: Needs file upload handling
- Views: Missing upload UI

---

## 🟡 Important Missing Features

### 4. Dynamic Emission Factor Selection
**Status:** ⚠️ Partially Implemented  
**Impact:** Medium  
**Description:** Emission factors are partially hardcoded in forms instead of dynamically loaded from database.

**Current Issues:**
- Some emission sources have hardcoded factors
- No region-specific factor selection
- No factor version/date tracking
- Limited factor options in dropdowns

**Required Implementation:**
- Load all emission factors from `emission_factors` table
- Allow selection of emission factor by region/country
- Show factor source and date
- Allow custom factor entry with justification
- Factor validation and suggestions

**Code Location:**
- Model: `app/Models/EmissionFactor.php` (exists)
- Controller: `app/Http/Controllers/EmissionRecordController.php` (partially loads factors)
- Views: Need dynamic factor loading

---

### 5. Unit Conversion Tools
**Status:** ❌ Missing  
**Impact:** Medium  
**Description:** No unit conversion functionality for activity data entry.

**Missing Features:**
- Unit conversion calculator
- Common unit conversions (kWh to MWh, liters to gallons, etc.)
- Automatic unit conversion based on selected unit
- Unit validation (ensure compatible units with emission factors)

**Required Implementation:**
- Add unit conversion helper/calculator
- Show conversion options when unit is selected
- Auto-convert activity data if needed
- Display original and converted values

---

### 6. Template-Based Entry (Incomplete)
**Status:** ⚠️ Partially Implemented  
**Impact:** Medium  
**Description:** Template entry mode exists in UI but functionality is incomplete.

**Current Status:**
- ✅ UI has template entry card
- ❌ No template selection interface
- ❌ No saved templates
- ❌ No template application logic

**Required Implementation:**
- Create template management system
- Allow saving entry forms as templates
- Load and apply templates
- Pre-fill forms from templates
- Template categories (by scope, by source type, etc.)

**Code Location:**
- View: `resources/views/emission_records/index.blade.php` (has UI but no functionality)
- Need: Template model, controller, and views

---

### 7. Bulk Entry Validation & Error Handling
**Status:** ⚠️ Needs Improvement  
**Impact:** Medium  
**Description:** Quick entry mode exists but validation and error handling could be better.

**Current Issues:**
- Limited row-level validation feedback
- No duplicate detection
- No bulk edit capability
- Limited error messages

**Required Implementation:**
- Real-time validation for each row
- Highlight invalid rows
- Show specific error messages per field
- Duplicate record detection
- Bulk edit selected rows
- Import validation before save

**Code Location:**
- View: `resources/views/emission_records/index.blade.php` (quick entry section)
- JavaScript: Needs enhanced validation

---

### 8. Date Range Entry
**Status:** ❌ Missing  
**Impact:** Low-Medium  
**Description:** Currently only single date entry is supported, but some emissions occur over date ranges.

**Missing Features:**
- Start date and end date fields
- Automatic calculation of period duration
- Monthly/quarterly/yearly period selection
- Period-based emission allocation

**Use Cases:**
- Monthly utility bills
- Quarterly fuel consumption
- Annual facility emissions

---

### 9. Emission Source Search & Filter
**Status:** ⚠️ Needs Improvement  
**Impact:** Low-Medium  
**Description:** Emission source dropdowns are long and hard to navigate.

**Missing Features:**
- Searchable emission source dropdown (Select2 with search)
- Filter sources by scope
- Recent/frequently used sources
- Favorite sources
- Source suggestions based on facility/scope

**Current Status:**
- Basic dropdown exists
- Select2 is partially implemented
- No search/filter functionality

---

### 10. Auto-Calculation Enhancements
**Status:** ⚠️ Partially Implemented  
**Impact:** Medium  
**Description:** Auto-calculation exists but needs improvements.

**Missing Features:**
- Auto-calculate from spend amount (Scope 3 spend-based)
- Auto-select emission factor based on source and region
- Unit conversion in calculation
- Calculation history/audit trail
- Manual override with justification

**Current Status:**
- ✅ Basic activity × factor calculation works
- ❌ Spend-based calculation not in forms
- ❌ No factor auto-selection

---

## 🟢 Nice-to-Have Features

### 11. Entry Form Validation Improvements
**Status:** ⚠️ Needs Enhancement  
**Impact:** Low-Medium

**Missing Validations:**
- Date cannot be in future
- Activity data reasonable range checks
- Emission factor validation against standards
- Duplicate entry detection (same date, facility, source)
- Outlier detection (unusually high/low values)
- Required field dependencies (e.g., supplier required for Scope 3)

---

### 12. Entry Form Help & Guidance
**Status:** ❌ Missing  
**Impact:** Low

**Missing Features:**
- Contextual help tooltips
- Field-level guidance
- Example values
- Calculation formula explanations
- Scope definitions and examples
- Data quality guidance

---

### 13. Draft Entry Management
**Status:** ⚠️ Partially Implemented  
**Impact:** Low-Medium

**Current Status:**
- ✅ Save as draft button exists
- ❌ No draft entries list/view
- ❌ No draft completion workflow
- ❌ No draft expiration/cleanup

**Required Implementation:**
- Draft entries dashboard
- Resume draft entry
- Delete draft entries
- Convert draft to active
- Draft expiration (auto-delete after X days)

---

### 14. Entry History & Versioning
**Status:** ❌ Missing  
**Impact:** Low

**Missing Features:**
- View entry history (who created, when modified)
- Entry versioning
- Compare entry versions
- Revert to previous version
- Entry change log

---

### 15. Quick Entry Templates
**Status:** ❌ Missing  
**Impact:** Low

**Missing Features:**
- Pre-defined quick entry templates
- Common emission scenarios
- One-click template application
- Template customization

---

### 16. Entry Form Accessibility
**Status:** ⚠️ Needs Improvement  
**Impact:** Low

**Missing Features:**
- Keyboard navigation support
- Screen reader compatibility
- High contrast mode
- Field focus indicators
- Error announcements for screen readers

---

### 17. Mobile-Optimized Entry
**Status:** ⚠️ Needs Improvement  
**Impact:** Low-Medium

**Current Issues:**
- Forms work on mobile but not optimized
- File upload difficult on mobile
- Date picker could be better
- Long dropdowns are hard to use

**Required Improvements:**
- Mobile-friendly date pickers
- Touch-optimized inputs
- Simplified mobile entry form
- Camera integration for document upload
- Offline entry capability

---

### 18. Entry Form Analytics
**Status:** ❌ Missing  
**Impact:** Low

**Missing Features:**
- Entry completion rate tracking
- Time to complete entry
- Most common sources
- Entry error patterns
- User entry statistics

---

## 📋 Implementation Priority

### Priority 1 (Critical - Implement First)
1. ✅ Site Selection Field
2. ✅ Scope 3 Specific Fields (Complete Implementation)
3. ✅ Supporting Documents Upload

### Priority 2 (Important - Implement Next)
4. ✅ Dynamic Emission Factor Selection
5. ✅ Unit Conversion Tools
6. ✅ Template-Based Entry (Complete)
7. ✅ Bulk Entry Validation Improvements

### Priority 3 (Enhancement - Implement Later)
8. Date Range Entry
9. Emission Source Search & Filter
10. Auto-Calculation Enhancements
11. Entry Form Validation Improvements
12. Draft Entry Management

### Priority 4 (Nice-to-Have)
13. Entry Form Help & Guidance
14. Entry History & Versioning
15. Quick Entry Templates
16. Entry Form Accessibility
17. Mobile-Optimized Entry
18. Entry Form Analytics

---

## 🔧 Technical Implementation Notes

### Database Fields Available but Not Used in Forms:
- `site_id` - Site selection
- `scope3_category_id` - Scope 3 category
- `supplier_id` - Supplier selection
- `calculation_method` - Calculation method
- `data_quality` - Data quality rating
- `spend_amount` - Spend amount for Scope 3
- `spend_currency` - Currency code
- `supporting_documents` - JSON array of document paths

### Controller Methods That Handle Missing Fields:
- `storeOrUpdate()` - Lines 249-273 handle Scope 3 fields
- But forms don't send these fields

### Models Available:
- `Site` - For site selection
- `Scope3Category` - For Scope 3 categories
- `Supplier` - For supplier selection
- `EmissionFactor` - For emission factors
- `EmissionSource` - For emission sources

---

## 📝 Form Comparison

### Current Form Fields:
✅ Date
✅ Facility
✅ Scope
✅ Emission Source
✅ Activity Data
✅ Emission Factor
✅ CO₂e Value
✅ Department
✅ Confidence Level
✅ Data Source
✅ Notes
✅ Status (Draft/Active)

### Missing Form Fields:
❌ Site
❌ Scope 3 Category (for Scope 3)
❌ Supplier (for Scope 3)
❌ Calculation Method (for Scope 3)
❌ Data Quality (for Scope 3)
❌ Spend Amount (for Scope 3 spend-based)
❌ Spend Currency (for Scope 3 spend-based)
❌ Supporting Documents Upload

---

## 🎯 Quick Wins (Easy to Implement)

1. **Add Site Dropdown** - Simple addition to forms
2. **Add Scope 3 Category Dropdown** - Load from existing table
3. **Add Supplier Dropdown** - Load from existing table
4. **Add Calculation Method Selector** - Simple radio/select
5. **Add Data Quality Selector** - Simple dropdown
6. **Add Spend Amount Fields** - Simple number inputs

---

## 📚 Related Files to Modify

### Views:
- `resources/views/scope_entry/index.blade.php`
- `resources/views/emission_records/index.blade.php`

### Controllers:
- `app/Http/Controllers/EmissionRecordController.php`

### Models:
- `app/Models/EmissionRecord.php` (already has fields)
- `app/Models/Site.php`
- `app/Models/Scope3Category.php`
- `app/Models/Supplier.php`
- `app/Models/EmissionFactor.php`

### Migrations:
- Already exist for all fields

---

**Last Updated:** 2026-01-XX  
**Status:** Comprehensive analysis of missing data entry features

---

*This document should be updated as features are implemented.*
