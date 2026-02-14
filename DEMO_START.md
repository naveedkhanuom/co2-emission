# 🎯 Client Demo – Start Here

**Use this file as your single starting point when giving a demo to clients.**

---

## ⚡ Quick Start (Before Demo)

```bash
# 1. Start the application
php artisan serve

# 2. Open in browser
# http://127.0.0.1:8000
```

---

## 🔐 Demo Login Credentials

| Role           | Email                    | Password   |
|----------------|--------------------------|------------|
| **Super Admin**| engr.naveedkhan3@gmail.com | secret@123 |
| **Admin**      | fawad@gmail.com          | secret@123 |
| **Product Manager** | zaheer@gmail.com   | secret@123 |
| **User**       | waqer@gmail.com          | secret@123 |

**Use Super Admin or Admin for full demo access.**

---

## 📋 Demo Flow (Suggested Order)

### 1. Login & Dashboard
- Go to http://127.0.0.1:8000/login
- Log in with Super Admin credentials
- **Show:** Dashboard, charts, overview

### 2. Manual Entry (Core Feature)
- **Sidebar:** Manual Entry
- **Show:** Single Entry, Quick Entry, Template Based modes
- Add a sample emission record (Scope 1, 2, or 3)
- **Talking point:** "Users can enter emissions manually with full calculation support."

### 3. Scope-Based Entry
- **Sidebar:** Scope-Based Entry
- **Show:** Scope 1, 2, 3 layout and quick entry

### 4. Import Data
- **Sidebar:** Import Data
- **Show:** 3-step wizard (Upload → Map Columns → Review)
- Download sample template, upload it, map columns, import
- **Talking point:** "Bulk import from Excel/CSV with column mapping."

### 5. Import History
- **Sidebar:** Import History
- **Show:** Statistics, charts, filters, recent imports
- View details, logs, download report
- **Talking point:** "Track and audit all import activities."

### 6. Reports
- **Sidebar:** Reports
- **Show:** Report list, create report
- **Sidebar:** GHG Protocol Report
- **Show:** GHG Protocol compliant reporting

### 7. Targets & Goals
- **Sidebar:** Targets & Goals
- **Show:** Set and track emission targets

### 8. Scope 3 & Suppliers
- **Sidebar:** Scope 3 Emissions – summary and categories
- **Sidebar:** Suppliers – supplier list and management
- **Sidebar:** Supplier Surveys – survey creation and tracking

### 9. Data Quality
- **Sidebar:** Data Quality
- **Show:** Data quality tracking and scoring

### 10. Settings (Optional)
- **Sidebar:** Facility/Location, Department, Emission Sources, Emission Factors
- **Show:** Master data configuration
- Companies, Users, Roles & Permissions (for admin features)

---

## 📝 Pre-Demo Checklist

- [ ] `php artisan serve` is running
- [ ] Database migrated: `php artisan migrate:fresh --seed`
- [ ] Assets built: `npm run build` (if changed)
- [ ] Browser opened to http://127.0.0.1:8000
- [ ] Logged in with Super Admin or Admin
- [ ] Company selected (if multi-company)
- [ ] Sample data present (emission records, facilities, etc.)

---

## 💡 Quick Tips

- **Demo Mode:** Some routes may be restricted for demo users. Use Super Admin for full access.
- **Company Switch:** Use the company switcher in the top bar to show multi-company.
- **Sample Data:** Run `php artisan migrate:fresh --seed` for a clean dataset.
- **Import Sample:** Use "Download Sample Template" on Import Data for a ready-to-use file.

---

## 📂 Key URLs

| Page          | URL                          |
|---------------|------------------------------|
| Login         | /login                       |
| Dashboard     | /home                        |
| Manual Entry  | /emission-records            |
| Import Data   | /emissions/import            |
| Import History| /import-history              |
| Reports       | /reports                     |
| GHG Report    | /reports/ghg-protocol        |

---

## 🆘 Troubleshooting

| Issue           | Fix                                        |
|-----------------|---------------------------------------------|
| Blank page      | Run `npm run build`, clear browser cache   |
| 500 error       | Check `.env`, run `php artisan config:clear`|
| Login fails     | Run `php artisan migrate:fresh --seed`     |
| No sidebar items| Check user role and permissions            |

---

**Good luck with your demo.**
