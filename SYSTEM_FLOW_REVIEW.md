# System Flow Review – GHG Emissions Management System

**Date:** January 2026  
**Scope:** End-to-end flow for auth, company context, emission entry, dashboard, reports, and import.  
**Updates:** Auth applied to data routes; import sets `company_id` (see §2).

---

## 1. What’s Working Correctly

### 1.1 Company context

- **SetCompanyConnection** (web middleware) runs on every request (except login/register/password).
- It sets `current_company_id` from: session → user’s `company_id` → or from `?company_id=` if user can access it.
- `app()->instance('current_company_id', $companyId)` and session are updated so helpers and controllers see the same company.

### 1.2 Emission record creation (single & bulk)

- **store()** uses `getCurrentCompanyId()` and sets `company_id` on every created record.
- **Site** and **supplier** are checked to belong to the current company before save.
- **Facility** and **department** are stored as **names** (after your recent fixes); dashboard filters by name, so behaviour is consistent.

### 1.3 Data display (when logged in + company set)

- **EmissionRecord** uses **HasCompanyScope**: all queries get `where('company_id', $companyId)` when `current_company_id` is set.
- **HomeController**, **GHGReportController**, **Scope3Controller**, **DataQualityController** use `EmissionRecord::...`; they automatically see only the current company’s data.
- **Helpers** `facilities()`, `sites()`, `departments()`, `suppliers()` use `current_company_id()` and scope their queries by `company_id`, so dropdowns and filters are company-safe.

### 1.4 Facility / department / site consistency

- **DB:** `emission_records.facility` and `emission_records.department` store **names**; `emission_records.site_id` stores **ID**.
- **Single entry:** Sends facility name, department name, site ID → matches DB and dashboard.
- **Quick entry:** Sends facility name, department name, site ID → same.
- **Dashboard:** Filter uses facility/department **ID** from dropdown, then resolves to **name** and filters by name → correct.

### 1.5 Scope 3 and spend-based

- Scope 3 fields (category, supplier, calculation method, data quality, spend, currency) are validated and saved when scope = 3.
- EIO spend-based calculation is used when method is spend-based and sector/country/currency are present; result is converted kg → tonnes and stored.

---

## 2. Issues That Affect Flow / Security

### 2.1 Routes without auth (high)

Many important routes have **no** `auth` middleware, so unauthenticated users can hit them:

- `/home`
- `/emission-records/*` (index, scope-entry, data, store, storeOrUpdate, update, show, destroy)
- `/emissions/import`, `/emissions/import` (POST), `/emissions/sample`
- `/review-data/*`
- `/reports/*`
- `/companies/*`, `/facilities/*`, `/sites/*`, `/departments/*`
- `/emission-sources/*`, `/emission-factors/*`

**Impact:**  
Anyone can open these URLs. If they hit `/emission-records/data` or reports, **HasCompanyScope** will not add a company filter when there is no logged-in user (see 2.2), so they could see **all** emission records.

**Recommendation:**  
Wrap all data and admin routes in `auth` middleware (e.g. group under `Route::middleware(['auth'])->group(...)`), except truly public routes (e.g. supplier portal survey by token).

---

### 2.2 HasCompanyScope when not logged in (high)

In **HasCompanyScope**, the global scope only adds `where('company_id', $companyId)` when `app()->bound('current_company_id')` and `$companyId` is truthy.

- If the user is **not** logged in, `SetCompanyConnection` does not set `current_company_id`, so `$companyId` is null.
- Then **no** company filter is applied: `EmissionRecord::` (and any other model using the trait) can return **all** companies’ data.

**Impact:**  
Combined with unprotected routes, this can expose cross-company data.

**Recommendation:**  
1. Put all sensitive routes behind `auth` (so unauthenticated users never reach these queries).  
2. Optionally, in **HasCompanyScope**, when no `current_company_id` is set but the model has `company_id`, either:  
   - do not apply the scope and rely on auth + explicit company checks, or  
   - apply a “no company” filter (e.g. `whereRaw('1=0')`) so that missing context never returns all companies. Prefer (1) and keep scope behaviour for “logged in + company set” only.

---

### 2.3 Import does not set company_id (high) — ✅ FIXED

**Previously:** In **EmissionsImport**, `$data` did **not** include `company_id`.  
**Fix applied:** `company_id` is set from `current_company_id()` (or `auth()->user()->company_id`) and added to `$data`. On overwrite, `updateOrCreate` match array includes `company_id` so updates stay within the current company.

**Impact:**  
Imported records are created with `company_id = null` (or default). They will not be scoped to the current company and will not show in dashboard/reports when filtering by company.

**Recommendation:**  
In **EmissionsImport**, resolve current company (e.g. `current_company_id()` from **CompanyHelper** or same logic as controller) and add:

```php
'company_id' => current_company_id(),
```

to `$data` before creating/updating the record. Ensure the import route is only callable by authenticated users (see 2.1).

---

### 2.4 Company switcher and multi-company access

- **CompanySwitcherController** checks `canAccessCompany($requestedCompanyId)` and only then sets session and app instance.  
- This is consistent with “one current company per request” and correct for flow.

No change needed for flow; only ensure switcher routes stay under `auth` (they already are in your `web.php` snippet).

---

## 3. Flow Summary (Current vs Recommended)

### 3.1 Current flow (simplified)

1. User opens app → redirected to login (only from `/`).
2. After login, user can open `/home`, `/emission-records`, etc. **without** auth being enforced on those routes.
3. **SetCompanyConnection** sets `current_company_id` from session or user’s company.
4. Emission entry (single/quick/template) → **store()** sets `company_id` on each record; facility/department as names, site as ID.
5. Dashboard/reports use **EmissionRecord** with **HasCompanyScope** → only current company when `current_company_id` is set.
6. Import creates records **without** `company_id` → those records are not tied to any company.

### 3.2 Recommended flow

1. **All** data and admin routes (emission records, home, reports, companies, facilities, sites, departments, emission sources/factors, import, review-data, targets, scope3, suppliers, etc.) should be inside an `auth` middleware group (except public pages like supplier portal survey).
2. **Import:** Set `company_id` in **EmissionsImport** from `current_company_id()` (or equivalent) so every imported row belongs to the current company.
3. Optionally harden **HasCompanyScope** so that when there is no current company, it does not fall back to “no filter” (e.g. require company for EmissionRecord when the app is used in multi-tenant mode).

---

## 4. Quick Checklist

| Area                     | Status   | Action |
|--------------------------|----------|--------|
| Company context set      | OK       | None   |
| Emission store sets company_id | OK | None   |
| Facility/department as names | OK | None   |
| HasCompanyScope on read  | OK when logged in + company set | Add auth so “no company” case is never hit for data routes |
| Routes without auth      | ✅ Fixed | `auth` middleware applied to home, roles, users, companies, departments, facilities, sites, emission-sources, emission-factors, emission-records, emissions/import, review-data, reports. |
| Import company_id       | ✅ Fixed | `company_id` set in EmissionsImport from `current_company_id()` / user's company. |
| Site/supplier validation | OK       | None   |
| Scope 3 / spend-based    | OK       | None   |

---

## 5. Conclusion

- **Data flow for emission entry and reporting is correct** when the user is logged in and a company is set: facility/department names and site IDs are consistent, and company scoping works.
- **Two main fixes have been applied:**
  1. **Protect routes:** All data and admin routes are now behind `auth` middleware (home, roles, users, companies, departments, facilities, sites, emission-sources, emission-factors, emission-records, emissions/import, review-data, reports). Supplier portal (survey by token) remains public.
  2. **Import:** `company_id` is set in **EmissionsImport** from the current company so imported records belong to the active company and appear correctly in dashboard and reports.

The system flow is in good shape for normal use: login → choose company → enter/view emissions → reports.
