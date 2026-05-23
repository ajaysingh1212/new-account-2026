# BizAccount Pro

Industry-style Laravel accounting application with company-scoped masters, AdminLTE UI, DataTables, audit logging, and staged accounting modules.

## Completed Modules

- Authentication, admin dashboard, company management, roles, permissions, users, profile, and audit logs.
- Party master CRUD with customer, supplier, or both party types.
- Party profile fields: legal name, contacts, email, phone, WhatsApp, GSTIN, PAN, TAN, CIN, tax type, place of supply, billing/shipping address, credit limit, credit days, payment terms, bank details, UPI, status, and notes.
- Opening balance date and balance nature.
- Current balance convention: positive means payable to party, negative means receivable from party.
- Opening balance ledger entry created/updated automatically.
- Party statement page with ledger table.
- DataTable party index with payable/receivable summaries.
- Main cost center CRUD with manager, department, budget window, status, and description.
- Sub cost center CRUD linked to selected main cost center.
- Banking module:
  - Bank and cash account master with opening balance, current balance, main account flag, bill-print flag, IFSC/SWIFT/UPI/contact fields, status, and notes.
  - Bank-to-bank, bank-to-cash, cash-to-bank, and manual balance adjustment entries.
  - Double-entry style transfer rows with linked related account and running balance.
  - Bank transaction index and filtered bank statement report with party/related-account/reference details.
- Item, stock, purchase, CRM/production, and sales foundation:
  - Product type CRUD for finished goods, raw material, readymade product, and service categories.
  - Item master CRUD with product/service switch, auto item code/HSN suggestions, barcode/QR fields, sale and purchase GST inclusive/exclusive switches, low-stock warning, opening stock, and BOM/raw-material composition for finished goods.
  - Stock movement ledger with current stock, stock value, item history, party/reference tracking, and low-stock summary behavior.
  - Purchase bill posting that adds stock and increases party payable for credit purchases.
  - Sales invoice posting that validates stock, reduces stock, and increases party receivable for credit sales.
  - Production/CRM assembly that consumes BOM raw materials and adds finished goods stock with raw-material cost and cost-per-unit.
- Payment settlement cleanup:
  - Payment In and Payment Out screens with party, date, bank/cash account, reference, amount, discount, total, description, and attachment.
  - Payment posting updates party ledger and bank ledger together in one database transaction.
  - Sales and purchase forms now support attachments and improved invoice-style UI with live totals.
  - Special CRM menu added for finished goods assembly with a premium dark production screen.
- Stability and test readiness:
  - Added `/dashboard` compatibility route while keeping the admin dashboard at `/admin/dashboard`.
  - Fixed MySQL index-name length issue in bank transaction migration by using short explicit index names.
  - Test environment now bypasses CSRF safely for feature tests only, while production CSRF behavior remains unchanged.
  - Updated the default home-page test to match the accounting app flow: unauthenticated users are redirected to login.
  - Verified the current suite with `php artisan test`: 25 tests passing.
- Estimate, challan, print, and access-control phase:
  - Added Estimate/Quotation tables, models, controller, list, create, show, print, cancel, and convert-to-sale workflow.
  - Added Estimate edit, update, and delete flow for non-converted quotations.
  - Estimate conversion creates a posted sales invoice, validates stock, posts stock movement, and posts party receivable ledger.
  - Added Delivery Challan tables, models, controller, list, create, show, cancel, and print workflow with transport fields.
  - Added Delivery Challan edit, update, and delete flow.
  - Added shared print layout for Sales Invoice, Purchase Bill, Estimate/Quotation, and Delivery Challan.
  - Added reusable entry visibility service and form controls for sharing entries with all company users, selected roles, or selected users.
  - Applied permission middleware groups to parties, banking, inventory/stock, purchase, and sales document routes.
  - Expanded `PermissionSeeder` to cover Estimate, Delivery Challan, item/product type, production, cost center, party payments, and print/conversion permissions.
  - Rebuilt the sidebar as a Gate-aware menu so users only see modules their role can access.
  - Added role-aware dashboard designs for Super Admin, Company Admin, and normal role users with filtered stats, quick actions, animated wave trend, animated pie mix, recent activity, and company pulse.
  - Dashboard filters now support date range and Super Admin company selection.
  - Corrected role hierarchy rules: only Super Admin bypasses all permissions; company admins and role users are controlled by assigned role permissions.
  - Company admins can only assign roles/permissions that are within their own granted permission set.
  - User, role, and audit-log management routes now require exact permissions instead of only `company_admin`.
  - Fixed Super Admin role creation by requiring a company selection when creating company-scoped roles.
  - Company creation now automatically creates/assigns a default `Company Admin` role to the new admin user.
  - `PermissionSeeder` backfills default `Company Admin` roles and permissions for existing companies/admin users.
  - Company Admin keeps user/role management access, but cannot delegate user/role/audit/company/permission management permissions to child roles.
  - Entry visibility is now separated into view vs manage rules: shared entries are read-only for shared users, while creator/admin/super admin can manage.
  - Sales, purchase, estimate, and delivery challan indexes now show creator name and creator role names for admin/super-admin tracking.

## Reusable Access Pattern For Future Modules

When adding a new entry module, follow this pattern:

1. Add `company_id` and `created_by` columns to the table.
2. Add `created_by` to the model fillable list and define `creator()` relationship.
3. In index/report queries, call `EntryVisibilityService::scopeForUser($query, ModelClass::class)`.
4. In `show`/`print`, call `authorizeView($entry)`.
5. In `edit`/`update`/`delete`/`cancel`/`convert`, call `authorizeManage($entry)`.
6. Include `admin.partials.entry-visibility` in create/edit forms where admins should share their own entries.
7. Add module permissions in `PermissionSeeder` and protect routes with exact permissions such as `module.view`, `module.create`, `module.edit`, `module.delete`, and `module.print`.

## Current Visibility Coverage

- Party, bank accounts, bank transactions, bank reports, cost centers, sub cost centers, product types, items, stock reports, production batches, party payments, purchases, sales, estimates, delivery challans, dashboard stats, and users now use the same company/creator/shared-entry rule.
- Super Admin sees every company and every entry.
- Company Admin sees every entry in their company and gets creator name plus creator role on master/report indexes.
- Role users see only their own entries plus entries explicitly shared by Admin/Super Admin. Shared entries are view-only.

## Report Modules

- GST-1: sales GST report with month, party, without-GST toggle, invoice details, party summary, CSV export, and print/save-PDF view.
- GST-2: purchase GST report with the same filters and export flow.
- GST-3: output GST, input GST, and payable/credit summary.
- Party reports: party statement, party wise profit/loss, all parties, party by item, sale/purchase by party.
- Transaction reports: sale report, purchase report, day book/all transactions, profit/loss, bill wise profit, balance sheet.

## Next Planned Modules

- Advanced print branding with company logo, selected printable bank account, QR/UPI block, amount-in-words, and signature upload.
- Richer attachment preview, barcode/QR image rendering, serial number tracking, and deeper analytics charts.

## Accounting Notes

- Party ledgers use debit/credit rows so future purchase, sales, payment in, and payment out modules can update balances consistently.
- Party current balance is stored on the party for fast dashboard and list views, while detailed history remains in `party_ledgers`.

---

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
