# Emission Records Views – Missing Things & Issues

**Scope:** `resources/views/emission_records/` (mainly `index.blade.php`)  
**Date:** January 2026  
**Status:** Items 1–7, 9, 10, 11 **fixed** (Jan 2026). Item 8 (unit conversion) remains open.

---

## Bugs (should fix)

### 1. Single entry: facility stored as ID instead of name — ✅ FIXED

- **Where:** Single entry form – Facility dropdown.
- **Fix applied:** Use facility **name** as value: `value="{{ e($facility->name) }}"`. Backend and dashboard now match.

### 2. Single entry: department stored as ID instead of name — ✅ FIXED

- **Where:** Department dropdown.
- **Fix applied:** Use department **name** as value: `value="{{ e($department->name) }}"`. Department select is optional; label set to “Select department (optional)...”.

### 3. Template mode: missing `#templateEntryForm` → JS error — ✅ FIXED

- **Fix applied:** Added `<div id="templateEntryForm">` with template cards (Electricity, Fleet, Travel, Waste) that call `useTemplate(...)`. In `setEntryMode()`, added guard: `const templateForm = document.getElementById('templateEntryForm'); if (templateForm) templateForm.style.display = ...`.

### 4. Confidence level: “Estimated” fails validation — ✅ FIXED

- **Fix applied:** Controller validation updated to `'confidenceLevel' => 'required|in:low,medium,high,estimated'` in all four validation blocks (store bulk, store single, storeOrUpdate, update).

---

## Missing / incomplete (feature gaps)

### 5. Activity unit not submitted — ✅ FIXED

- **Fix applied:** Added `name="activity_unit"` to the activity unit `<select>`. Value is now submitted with the form. (Model/DB does not yet store `activity_unit`; add column and fillable if you want to persist it.)

### 6. Template form UI not implemented — ✅ FIXED

- **Fix applied:** Template form block added inside `#templateEntryForm` with four template cards (Electricity, Fleet, Travel, Waste) that switch to single entry and pre-fill scope/source via `useTemplate()`.

### 7. Supporting documents: no preview or delete in UI — ✅ FIXED

- **Fix applied:** Added `#supportingDocumentsPreview` div. JS: `window.selectedSupportingDocuments` array; on file input change, files are pushed to the array and preview is rendered with a “×” remove button per file. On submit, FormData is built from this array. On form reset/clear, array and preview are cleared.

### 8. Unit conversion — ❌ NOT IMPLEMENTED

- **Where:** Activity data + unit; emission factor is per unit.
- **Issue:** No conversion between units (e.g. MWh → kWh, gallons → liters). Documented in `ALL_MISSING_FEATURES.md`.
- **Fix:** Add unit conversion helper/UI (calculator or auto-convert when unit differs from factor unit).

### 9. Quick entry: no Site, Department — ✅ FIXED

- **Fix applied:** Quick entry table now has **Site** and **Dept** columns. `sitesData` and `departmentsData` passed from PHP (`@json(sites())`, `@json(departments())`). Each row has optional Site dropdown (value = site id) and Department dropdown (value = department name). Payload includes `siteSelect` and `departmentSelect` per row.

### 10. Scope options show HTML in option text — ✅ FIXED

- **Fix applied:** Scope select options use plain text only: `Scope 1 - Direct Emissions`, `Scope 2 - Indirect Emissions (Purchased Energy)`, `Scope 3 - Other Indirect Emissions`.

---

## Consistency / UX

### 11. Department: required in view vs nullable in controller — ✅ FIXED

- **Fix applied:** Removed `required` from the department select and set label to “Select department (optional)...”. Aligns with controller `'departmentSelect' => 'nullable'`.

### 12. Direct entry: CO₂e input

- No change; behavior is correct (hidden input used in activity mode, visible in direct mode).

---

## Summary

| # | Type   | Item                                      | Status   |
|---|--------|-------------------------------------------|----------|
| 1 | Bug    | Single entry facility = ID (should be name) | ✅ Fixed |
| 2 | Bug    | Single entry department = ID (should be name) | ✅ Fixed |
| 3 | Bug    | Missing `#templateEntryForm` → JS error   | ✅ Fixed |
| 4 | Bug    | Confidence “Estimated” fails validation   | ✅ Fixed |
| 5 | Missing| Activity unit not submitted               | ✅ Fixed |
| 6 | Missing| Template form UI not implemented         | ✅ Fixed |
| 7 | Missing| Document preview/delete in UI             | ✅ Fixed |
| 8 | Missing| Unit conversion                           | ❌ Open  |
| 9 | Missing| Quick entry: Site, Dept                   | ✅ Fixed |
| 10| UX     | Scope option HTML in option text          | ✅ Fixed |
| 11| Consistency | Department required vs nullable      | ✅ Fixed |

**Remaining:** Unit conversion (item 8) – see `ALL_MISSING_FEATURES.md` for design.
