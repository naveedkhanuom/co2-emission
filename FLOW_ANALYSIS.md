# GHG Emissions Management System - Flow Analysis

## ❌ CRITICAL ISSUES FOUND

### 1. Missing Controller Method
**Issue:** Route defines `storeOrUpdate` but method doesn't exist in `EmissionRecordController`
- **Location:** `routes/web.php:90`
- **Route:** `POST /emission-records/store-or-update`
- **Controller:** `EmissionRecordController::storeOrUpdate()` - **DOES NOT EXIST**
- **Impact:** Route will fail with 404/method not found error

---

### 2. Database Schema vs Model Relationships Mismatch

#### 2.1 Company & Site Relationships
**Issue:** Model defines relationships but database columns don't exist
- **Model:** `EmissionRecord::company()` and `EmissionRecord::site()` relationships defined
- **Database:** `emission_records` table has NO `company_id` or `site_id` columns
- **Impact:** 
  - `EmissionRecord::with(['company', 'site'])` will return NULL
  - `$emissionRecord->company` will always be NULL
  - `$emissionRecord->site` will always be NULL
  - DataTables and views expecting company/site data will fail

**Code Locations:**
- `app/Models/EmissionRecord.php:30-31` (relationships defined)
- `app/Http/Controllers/EmissionRecordController.php:25` (eager loading)
- `app/Http/Controllers/EmissionRecordController.php:145` (eager loading)

#### 2.2 EmissionSource & EmissionFactor Relationships
**Issue:** Model defines relationships but stores as strings
- **Model:** `EmissionRecord::emissionSource()` and `EmissionRecord::emissionFactor()` relationships
- **Database:** Stores `emission_source` as STRING (VARCHAR 100), not ID
- **Database:** Stores `emission_factor` as DECIMAL, not ID
- **Impact:** Relationships will not work correctly

---

### 3. Data Type Inconsistency in Import Flow

**Issue:** Import stores IDs but schema expects strings
- **Location:** `app/Imports/EmissionsImport.php:125-126`
- **Problem:** 
  ```php
  'facility' => $facility->id,      // Stores INTEGER ID
  'department' => $department->id,   // Stores INTEGER ID
  ```
- **Schema Expects:**
  ```php
  $table->string('facility', 50);      // Expects STRING
  $table->string('department', 100);   // Expects STRING
  ```
- **Impact:** Database error or data corruption when importing

**Comparison:**
- ✅ **Manual Entry:** Stores facility/department as STRINGS (correct)
- ✅ **Utility Bill:** Stores facility as NAME string (correct)
- ❌ **Import:** Stores facility/department as IDs (WRONG)

---

### 4. User Model Missing company_id

**Issue:** Code tries to access `Auth::user()->company_id` but User model doesn't have this field
- **Location:** `app/Http/Controllers/UtilityBillController.php:177`
- **Code:** `'company_id' => Auth::user()->company_id ?? null,`
- **Impact:** Will always be NULL, no company linkage for utility bills

---

### 5. Inconsistent Data Storage Patterns

**Facility Storage:**
- Manual Entry: String (facility name)
- Import: Integer ID (WRONG)
- Utility Bill: String (facility name)
- Database Schema: String (VARCHAR 50)

**Department Storage:**
- Manual Entry: String (department name)
- Import: Integer ID (WRONG)
- Utility Bill: String (department name)
- Database Schema: String (VARCHAR 100)

**Emission Source Storage:**
- All flows: String (name)
- Database Schema: String (VARCHAR 100)
- But Model has relationship expecting ID

---

## ⚠️ MEDIUM PRIORITY ISSUES

### 6. Missing Foreign Keys
- `emission_records.company_id` - doesn't exist but model expects it
- `emission_records.site_id` - doesn't exist but model expects it
- `emission_records.emission_source_id` - doesn't exist but model expects it
- `emission_records.emission_factor_id` - doesn't exist but model expects it

### 7. UpdateOrCreate Logic Issue in Import
- Import uses `updateOrCreate` with facility/department as IDs
- But schema stores as strings, so matching logic is broken

---

## ✅ WHAT'S WORKING CORRECTLY

1. Manual entry flow stores data as strings (matches schema)
2. Utility bill OCR extraction and processing
3. Review data workflow with status management
4. DataTables integration for listing
5. Basic CRUD operations for other entities

---

## 🔧 RECOMMENDED FIXES

### Fix 1: Add Missing storeOrUpdate Method
Add to `EmissionRecordController`:
```php
public function storeOrUpdate(Request $request) {
    // Implementation needed
}
```

### Fix 2: Decide on Data Model Strategy

**Option A: Use IDs (Recommended for data integrity)**
- Add `company_id`, `site_id`, `facility_id`, `department_id`, `emission_source_id` columns
- Update all controllers to use IDs
- Add foreign key constraints

**Option B: Use Strings (Current schema)**
- Remove relationship definitions from model (or make them work with strings)
- Fix import to store names instead of IDs
- Remove eager loading of non-existent relationships

### Fix 3: Fix Import Data Types
If keeping string schema:
```php
'facility' => $facility->name,      // Not $facility->id
'department' => $department->name,  // Not $department->id
```

### Fix 4: Add company_id to Users Table
- Migration to add `company_id` to users table
- Or remove company_id logic from UtilityBillController

---

## 📊 FLOW DIAGRAM ISSUES

```
Manual Entry → Stores Strings ✅
Import → Stores IDs ❌ (Schema expects strings)
Utility Bill → Stores Strings ✅
Database → Expects Strings ✅

Model Relationships → Expect IDs ❌ (Schema has strings)
```

**Conclusion:** There's a fundamental mismatch between:
- Database schema (strings)
- Import logic (IDs)
- Model relationships (expects IDs)

