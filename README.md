# GHG Emissions Management System

A comprehensive Laravel 11 application for tracking, managing, and reporting greenhouse gas (GHG) emissions. The system supports multi-company architecture, scope 1/2/3 emissions tracking, supplier management, compliance reporting, and data quality management.

## Features

- ✅ Multi-company architecture with company switching
- ✅ User roles and permissions (Spatie Laravel Permission)
- ✅ Emission record management (Scope 1, 2, 3)
- ✅ Supplier management and surveys
- ✅ Scope 3 category tracking (15 GHG Protocol categories)
- ✅ Data import/export (Excel)
- ✅ Utility bill OCR processing
- ✅ Target and goal management
- ✅ Custom reports and templates
- ✅ Data quality tracking
- ✅ Site, facility, and department management

## Documentation

- 📖 [User Manual](USER_MANUAL.md) - Complete user documentation and guide
- 🗺️ [Implementation Roadmap](IMPLEMENTATION_ROADMAP.md) - Development plan and missing features
- 📋 [Quick Reference Guide](QUICK_REFERENCE_GUIDE.md) - Quick task reference
- ❌ [Missing Features](MISSING_FEATURES.md) - Complete feature gap analysis
- 📝 [Data Entry Features](MISSING_DATA_ENTRY_FEATURES.md) - Data entry specific requirements

## Technology Stack

- **Backend:** Laravel 11, PHP 8.2+
- **Frontend:** Bootstrap 5, JavaScript, Vite
- **Database:** MySQL/MariaDB
- **Key Packages:**
  - Spatie Laravel Permission (roles/permissions)
  - DataTables (data tables)
  - Maatwebsite Excel (import/export)
  - DomPDF (PDF generation)
  - Tesseract OCR (bill processing)
  - OpenAI PHP Client

## Installation

Make sure that you have setup the environment properly. You will need minimum PHP 8.2, MySQL/MariaDB, composer and Node.js.

1. Download the project (or clone using GIT)
2. Copy `.env.example` into `.env` and configure your database credentials
3. Go to the project's root directory using terminal window/command prompt
4. Run `composer install`
5. Set the application key by running `php artisan key:generate --ansi`
6. Run migrations `php artisan migrate:fresh --seed`
7. Run `npm install`
8. Run `npm run build` to build assets
9. Start local server by executing `php artisan serve`
10. Visit here http://127.0.0.1:8000/login to test the application

## Default Login Credentials

After running migrations with seed, check the seeder files for default user credentials.

## Project Status

**Current Version:** 1.0  
**Status:** Development/Enhancement Phase

### Completed Features ✅
- Core emission tracking
- Multi-company support
- Basic reporting
- Data import/export
- Supplier management

### In Progress ⚠️
- Data entry form enhancements
- API development
- Notification system
- Advanced reporting

### Planned ❌
See [Implementation Roadmap](IMPLEMENTATION_ROADMAP.md) for detailed development plan.

## Contributing

This is a private project. For contributions or questions, contact the project administrator.

## License

Proprietary - All rights reserved

## Support

For technical support or questions:
- Review the [User Manual](USER_MANUAL.md)
- Check the [Quick Reference Guide](QUICK_REFERENCE_GUIDE.md)
- Contact your system administrator