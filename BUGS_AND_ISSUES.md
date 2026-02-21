# Bugs and Issues by Module

This document lists known bugs, security concerns, and code-quality issues across all modules of the GHG Emission application. Use it for triage and remediation.

---

## 1. Home / Dashboard

| # | Severity | Location | Issue |
|---|----------|----------|--------|
| 1.1 | Medium | `App\Http\Controllers\HomeController.php` (line 21) | **Unused parameter:** Method signature is `index(Request $request, $companyId = 1)`. The route `GET /home` does not pass `companyId`, so the parameter is always `1` and never used. Either remove it or implement company-scoped dashboard filtering. |

---

## 2. Utility Bills / Upload Bills

| # | Severity | Location | Issue |
|---|----------|----------|--------|
| 2.1 | **Critical** | `App\Http\Controllers\UtilityBillController.php` (lines 239–253) | **Missing `company_id` on EmissionRecord:** `EmissionRecord::create([...])` does not set `company_id`. The model uses `HasCompanyScope`; records created here will have `company_id = null` and may be excluded by the global scope or visible to the wrong company. **Fix:** Add `'company_id' => Auth::user()->company_id ?? $bill->company_id` to the create array. |
| 2.2 | Low | `UtilityBillController.php` (lines 72, 104, 137) | **`env()` in controller:** Uses `env('OCR_SPACE_API_KEY')` and `env('TESSERACT_PATH', 'tesseract')`. In production, `env()` may be empty when config is cached. Prefer `config('services.ocr_space.key')` etc. and define in `config/services.php`. |

---

## 3. Scope Finder (Scope Classifier)

| # | Severity | Location | Issue |
|---|----------|----------|--------|
| 3.1 | **Critical** | `resources/views/scope_classifier/index.blade.php` (lines 406–412) | **Possible TypeError:** In `applyFilters()`, each tile is looked up by `data-id` in the `cats` array. If a tile has an invalid or missing `data-id`, `cat` is `null` and `cat.name`, `cat.hint`, `cat.sources` throw. **Fix:** After resolving `cat`, add `if (!cat) { t.classList.add("hidden-tile"); continue; }`. |
| 3.2 | Medium | `scope_classifier/index.blade.php` (lines 432–433) | **Modal focus:** In `closeResult()`, `selectedTile.focus({ preventScroll: true })` is called. Tiles are `<div>` elements and may not be focusable; focus restoration can be unreliable. Prefer storing the previously focused element when opening the modal and restoring it on close, or focus a known focusable element. |
| 3.3 | Medium | `scope_classifier/index.blade.php` (lines 262–274) | **Keyboard accessibility:** Scope tabs are `<div class="scope-tab">` with only click handlers. They are not focusable or activatable via keyboard. **Fix:** Use `<button type="button">` or add `tabindex="0"` and handle `keydown` for Enter/Space. |
| 3.4 | Low | `scope_classifier/index.blade.php` (lines 499, 526) | **XSS risk if data becomes dynamic:** `tile.innerHTML` and `rSources.innerHTML` use `c.name`, `c.hint`, and `cat.sources[j]` without escaping. Data is currently hardcoded, so risk is low; if this data ever comes from the DB or user input, escape before inserting or use `textContent`. |
| 3.5 | Low | `scope_classifier/index.blade.php` (line 303) | **SVG in HTML:** Uses `stroke-width="2"` in inline SVG. Valid in HTML5; ensure consistency if other SVG attributes are added. |

---

## 4. Emission Records / Manual Entry

| # | Severity | Location | Issue |
|---|----------|----------|--------|
| 4.1 | — | `App\Http\Controllers\EmissionRecordController.php` | Emission record create/update paths correctly set `company_id` and validate site/supplier against current company. No critical bugs identified in this module. |

---

## 5. Import Data / Emissions Import

| # | Severity | Location | Issue |
|---|----------|----------|--------|
| 5.1 | — | `App\Http\Controllers\EmissionImportController.php` | Uses `current_company_id()` and passes company context; `App\Imports\EmissionsImport` sets `company_id` on created records. No critical bugs in import logic. |

---

## 6. Import History

| # | Severity | Location | Issue |
|---|----------|----------|--------|
| 6.1 | **High** | `App\Models\ImportHistory.php` | **No `company_id`:** The `ImportHistory` model has no `company_id` column. The Import History list shows all imports from all users/companies. In a multi-tenant setup, one company can see another’s import history. **Fix:** Add `company_id` to the model and migration, set it when creating import history, and scope all queries by current company. |
| 6.2 | Medium | `App\Models\ImportHistory.php` (lines 71–76) | **Race condition in `generateImportId()`:** Uses `self::latest('id')->first()` then increments the number. Under concurrent requests, two imports can get the same `import_id`. **Fix:** Use a unique identifier (e.g. UUID) or a database sequence/atomic increment scoped per company. |
| 6.3 | Low | `App\Http\Controllers\ImportHistoryController.php` (DataTables) | **XSS in raw HTML:** DataTables columns build HTML from `$row->file_name`, `$row->import_id`, etc. If any of these ever come from user input, escape output. Currently file names are from uploads; consider escaping for safety. |

---

## 7. Review Data

| # | Severity | Location | Issue |
|---|----------|----------|--------|
| 7.1 | — | `App\Http\Controllers\ReviewDataController.php` | Uses `EmissionRecord` which has `HasCompanyScope`; queries are automatically scoped to the current company. No additional bugs identified. |

---

## 8. Reports

| # | Severity | Location | Issue |
|---|----------|----------|--------|
| 8.1 | **High** | `App\Models\Report.php` | **No `company_id`:** The `Report` model has no `company_id`. `ReportController` uses `Report::count()`, `Report::where(...)` etc. without company filtering. In multi-tenant, reports from all companies are mixed. **Fix:** Add `company_id` to Report, set it on create, and scope all report queries by current company. |
| 8.2 | Medium | `App\Http\Controllers\ReportController.php` (lines 25–26) | **Facilities/Departments not company-scoped in view:** `Facilities::all()` and `Department::all()` are passed to the view. If these models use company scoping via middleware/global scope, ensure the current company is set before this runs; otherwise the lists may include other companies’ data. |

---

## 9. Data Quality

| # | Severity | Location | Issue |
|---|----------|----------|--------|
| 9.1 | — | `App\Http\Controllers\DataQualityController.php` | Uses `CompanyHelper::currentCompanyId()` and explicitly scopes `EmissionRecord` and `Supplier` by `company_id`. No bugs identified. |

---

## 10. Targets & Goals

| # | Severity | Location | Issue |
|---|----------|----------|--------|
| 10.1 | — | `App\Http\Controllers\TargetController.php` | Uses `company_id` in create/update and checks target ownership. No critical bugs identified. |

---

## 11. Suppliers & Supplier Surveys

| # | Severity | Location | Issue |
|---|----------|----------|--------|
| 11.1 | — | `App\Http\Controllers\SupplierController.php`, `SupplierSurveyController.php` | Company checks present on create/update/show/destroy. No critical bugs identified. |

---

## 12. Scope 1 / 2 / 3 Entry (Entry Pages)

| # | Severity | Location | Issue |
|---|----------|----------|--------|
| 12.1 | Low | `resources/views/scope1_entry/script.blade.php`, `scope2_entry/script.blade.php`, `scope3_entry/script.blade.php` | **XSS if server data is untrusted:** Various `innerHTML` / `.html()` usages with source names, facility names, descriptions (e.g. `selSrc.name`, `f.name`, `c.emission_source_name`). If these values come from the database or user input, they should be escaped before insertion. |
| 12.2 | — | `App\Http\Controllers\Scope1EntryController.php`, etc. | Queries use `EmissionRecord` which has global company scope. No controller-level bugs identified. |

---

## 13. Scope 3 Calculator

| # | Severity | Location | Issue |
|---|----------|----------|--------|
| 13.1 | Low | `resources/views/scope3/calculator-js.blade.php` | **innerHTML with category data:** Template literals and `innerHTML` use category names and IDs. Data is from a client-side constant; if any of it is later loaded from the server, escape or use safe DOM APIs. |
| 13.2 | Low | Same file | **Save/status messages:** Some `statusEl.innerHTML = '<span>...' + (data.message || ...) + '</span>'` — if `data.message` comes from the server, escape to prevent XSS. |

---

## 14. Facilities / Departments / Sites

| # | Severity | Location | Issue |
|---|----------|----------|--------|
| 14.1 | — | `App\Http\Controllers\FacilitiesController.php`, `DepartmentController.php`, `SiteController.php` | Company checks are performed on update/destroy and when resolving facilities/departments/sites. No critical bugs identified. |

---

## 15. Emission Sources / Emission Factors / Countries

| # | Severity | Location | Issue |
|---|----------|----------|--------|
| 15.1 | — | Controllers for emission sources, factors, and countries | No critical bugs identified in reviewed code. Ensure company or global scoping is consistent with product requirements (e.g. shared vs per-company factors). |

---

## 16. Companies

| # | Severity | Location | Issue |
|---|----------|----------|--------|
| 16.1 | Low | `resources/views/companies/index.blade.php` | **Error display:** `$('#formErrors').html(errorHtml)` — ensure `errorHtml` is built from escaped strings if it includes user or server-provided content. |

---

## 17. Users & Roles

| # | Severity | Location | Issue |
|---|----------|----------|--------|
| 17.1 | Medium | `App\Http\Controllers\UserController.php` (`getData`) | **User list not company-scoped:** `User::query()` loads all users. If the app is multi-tenant and users should see only users of their company, add a filter (e.g. `where('company_id', current_company_id())` or via relationship). If super-admin is meant to see all users, consider a role-based exception. |
| 17.2 | Low | `UserController.php` (DataTables) | **XSS in name/role badges:** `$user->name` and role names are concatenated into HTML. Escape if names/roles can contain HTML or be user-editable. |

---

## 18. Bill OCR (if used)

| # | Severity | Location | Issue |
|---|----------|----------|--------|
| 18.1 | Low | `App\Http\Controllers\BillOCRController.php` | Same as Utility Bills: uses `env('OCR_SPACE_API_KEY')` and `env('TESSERACT_PATH')`. Prefer config. |
| 18.2 | — | Same controller | Emission record creation includes `company_id`; no duplicate of UtilityBillController bug. |

---

## 19. Scope 3 Controller (Statistics / Data)

| # | Severity | Location | Issue |
|---|----------|----------|--------|
| 19.1 | — | `App\Http\Controllers\Scope3Controller.php` | All emission queries use `EmissionRecord::where('company_id', $companyId)`. No bugs identified. |

---

## 20. General / Cross-Cutting

| # | Severity | Location | Issue |
|---|----------|----------|--------|
| 20.1 | Low | Multiple Blade/JS views | **Use of `innerHTML` / `.html()`:** Many views inject dynamic content (messages, names, numbers) into the DOM. Where content comes from the server or user input, use a small `escapeHtml()` (or similar) and apply it consistently to avoid XSS. |
| 20.2 | Low | Controllers using `env()` | **Config cache:** Controllers that call `env()` directly (e.g. OCR, Tesseract) should use `config()` and centralize values in config files so production config caching works. |
| 20.3 | Low | DataTables with raw columns | **Escaping:** Any DataTables column that returns raw HTML built from row data should escape that data unless it is intentionally safe (e.g. server-rendered Blade). |

---

## Summary by Severity

| Severity | Count | Modules |
|----------|-------|--------|
| Critical | 2 | Utility Bills, Scope Finder |
| High | 2 | Import History, Reports |
| Medium | 5 | Home, Scope Finder (2), Import History, Users |
| Low | 10+ | Scope Finder, Import History, Reports, Scope entry views, Scope 3 calculator, Companies, Users, Bill OCR, General |

---

## Recommended Fix Order

1. **UtilityBillController:** Add `company_id` to `EmissionRecord::create()` (critical for data integrity).
2. **Scope classifier:** Add null check for `cat` in `applyFilters()` to prevent TypeError.
3. **ImportHistory:** Add `company_id` and scope all listing/detail actions by company; fix `generateImportId()` race condition.
4. **Report:** Add `company_id` and scope report listing and actions by company.
5. **HomeController:** Remove or use `$companyId` parameter.
6. **Scope Finder:** Improve modal focus and keyboard accessibility for tabs.
7. **UserController:** Decide and implement company (or role-based) scoping for user list.
8. **General:** Replace `env()` with config in controllers; add escaping for dynamic content in DataTables and JS `innerHTML` where data is untrusted.

---

*Document generated from codebase review. Re-validate after changes.*
