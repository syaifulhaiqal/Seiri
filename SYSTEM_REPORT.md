# Seiri: system report

*Seiri* (整理) is Japanese for "sort, put in order", the first of the 5S workplace principles. Seiri is an office back-office system for Malaysian companies: HR, attendance, payroll, finance, purchasing, sales and an audit trail in one app.

Report date: 27 November 2026. Covers the code in this repository as it stands, uncommitted changes included.

---

## 1. At a glance

| | |
|---|---|
| Stack | Laravel 13.44, PHP 8.4, MySQL (SQLite for tests), Blade, Alpine.js 3, Bootstrap 5.3 (SCSS), Vite |
| Languages | English and Bahasa Melayu (1,390 translated strings), light and dark mode |
| Size | 221 routes, 50 controllers, 37 form requests, 36 models, 12 services, 26 enums, 13 notifications, 18 migrations, 123 Blade views; about 13,600 lines of PHP and 8,700 of Blade |
| Tests | 148 feature tests, 923 assertions, all passing (about 5 seconds) |
| Dependencies added in this project | `bacon/bacon-qr-code` (QR codes), with approval |

---

## 2. What the system does

### For staff (self-service, `/login`)
- **Attendance**: clock in and out by scanning the office QR code, with a shared clock-in link from HR (work from home), or remotely if HR allows it.
- **Leave**: balances per leave type, apply and cancel. Weekends and public holidays are not counted.
- **Overtime and movements**: claim overtime (paid with salary) and log time out of the office.
- **Expense claims** with receipt upload.
- **Payslips and EA forms**, printable.
- **Training** records.
- **Approvals**: team leads and managers approve their colleagues' requests.
- **Tutorial**: a how-to guide for the pages they can use.

### For admins (`/admin/login`, one role each)
- **HR**: employees, departments, leave types, public holidays, approval chains (up to three levels), leave and overtime review, training.
- **Attendance**: daily board (present, late, on leave, holiday, absent, and how and where people clocked in). Also the office QR kiosk screen, shared clock-in links and movements.
- **Payroll**: monthly runs with EPF, SOCSO, EIS and PCB, unpaid leave, overtime and allowances, in three steps: prepare, then approve, then pay. EA forms.
- **Finance**: chart of accounts, journal, sales invoices, supplier bills, payments, customers and suppliers, reports, and expense claims (review, then reimburse).
- **Purchasing and stock**: purchase requests, then approval, then purchase orders, receiving into stock, and supplier bills; products, stock adjustments and stock value.
- **Sales and CRM**: leads with an activity log, conversion to customers, quotations, then invoices.
- **Users and roles**: the super admin defines roles from 30+ permissions. Preparing and approving are separate permissions.
- **Audit trail**: every change, sign-in and failed sign-in, with old and new values. Can be exported to CSV.
- **Notifications** in the app and by email (queued).

Not included: point of sale and vouchers.

---

## 3. How it is built

- **Two sign-in areas.** The `web` guard serves staff and the `admin` guard serves admins. Each has its own session key, so a person can be signed in to both at once. An admin account cannot sign in at the staff page, and the reverse, and both get the same generic error.
- **Permissions.** Each `App\Enums\Permission` is a gate (`can:payroll.approve`). Routes are grouped by permission, menus and buttons hide what a role cannot use, and the server refuses it too. The super admin passes every gate.
- **Thin controllers, services for workflows.** Examples: `PayrollService`, `PayrollCalculator`, `ApprovalFlow`, `Procurement`, `Sales`, `InvoicePosting`, `Ledger`, `Clock`.
- **One form request per form.** Validation, authorisation and data shaping live there.
- **Money** is stored as decimals and calculated in cents (`App\Support\Money`). Document numbers (`INV-0001`, `PO-0001` and so on) are issued under a row lock.
- **Audit** is added to a model with the `Auditable` trait and stored under short type names (morph map).
- **Front end:** Blade components, small Alpine components (one per file in `resources/js/components`), and Bootstrap 5 SCSS with dark mode.

---

## 4. Security review

### 4.1 What was checked

| Area | How | Result |
|---|---|---|
| SQL injection | Every raw query (`selectRaw`, `orderByRaw`, `whereRaw`, `DB::`) and every sort, filter and search input | **Safe.** All values are bound parameters. The one built string (`DocumentNumber`) uses a column name from code, never from input. Searches use bound `LIKE` values. |
| Cross-site scripting (XSS) | Every unescaped `{!! !!}`, `x-html` and `innerHTML` | **Safe.** Everything else is escaped with `{{ }}` or `@js()`. The only raw output is QR-code SVG generated on the server from the app's own URLs. |
| Cross-site request forgery (CSRF) | Forms and routes | **Safe.** Every state-changing route is POST, PUT or DELETE behind Laravel's CSRF check. |
| Access to other people's records (IDOR) | Every staff route with an ID: leave, overtime, movements, claims, receipts, payslips, approvals, notifications | **Safe.** Each checks that the record belongs to the signed-in person. Draft payslips are hidden, and approvers can only open receipts waiting for them. |
| Privilege escalation | Users, roles, admin sign-in | **Safe.** Only the super admin assigns roles. Admins with *Manage users* cannot edit other admins or change roles, and nobody can change their own role. |
| Mass assignment | Models and controllers | **Safe.** Fillable lists are explicit, `user_id` on employees is set on purpose, and no `$request->all()` reaches a model. |
| File uploads | Claim receipts | **Safe.** Only jpg, png and pdf up to 5 MB, checked by content. Stored on the private disk under random names and served only through permission-checked routes. |
| Brute force | Sign-in, activation email, sign-up | Sign-in is limited to 5 tries per email and IP, and activation emails to 6 a minute. **Sign-up had no limit: fixed.** |
| Secrets | `.env`, seeders | `.env` is ignored by git. Demo accounts with `password123` are only seeded when `APP_ENV=local`. |
| Signed and secret links | Office QR, clock-in links, activation | QR links are signed and expire after 5 minutes. Clock-in links use 40-character random tokens, can be renewed or turned off, and still need a sign-in. Activation links are signed. |
| Dependencies | `composer audit`, `npm audit` | npm: none. Composer: advisories in Laravel and Symfony. **Fixed** by updating to patch releases (see 4.2, #6). |

### 4.2 Problems found and fixed

| # | Problem | Risk | Fix | Test |
|---|---|---|---|---|
| 1 | **Pay and identity data leaked through the audit trail.** An admin who can read the audit trail but not payroll (e.g. an Auditor) could see salary, PCB, bank account, IC and income tax number changes. | Data leak (personal data under PDPA) | `AuditLog::changes()` hides those fields unless the viewer can see payroll. This applies to the page and the CSV export. | `SecurityTest::test_the_audit_trail_hides_pay_and_bank_details…` |
| 2 | **CSV formula injection.** A name such as `=HYPERLINK(...)` would run as a formula when the audit export is opened in Excel. | Code or link execution on an admin's computer | Cells starting with `=`, `+`, `-` or `@` get a leading apostrophe. | `SecurityTest::test_exported_cells_cannot_run_as_spreadsheet_formulas` |
| 3 | **No browser protection headers.** Pages could be framed by other sites (clickjacking), and receipts could be MIME-sniffed. | Clickjacking on admin actions | `SetSecurityHeaders` middleware adds `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy` and `Permissions-Policy` (location only), plus HSTS over HTTPS. | `SecurityTest::test_pages_send_browser_protection_headers` |
| 4 | **Forged Host header.** Email links are built from the request's host, so a forged host could point activation links to another site. | Phishing, account takeover | `trustHosts()`: outside local and tests, only `APP_URL`'s host is accepted. | Framework behaviour; off in tests by design |
| 5 | **Unlimited sign-ups.** | Spam accounts, email flooding | `throttle:6,1` on sign-up. | `SecurityTest::test_sign_up_is_rate_limited` |
| 6 | **Known flaws in Laravel and Symfony** (host-header URL generation, trusted proxies, SMTP STARTTLS, email header injection, routing) | Varies; mostly spoofed links and email tampering | Updated `laravel/framework` 13.37.0 → 13.44.1 and the Symfony packages to 8.1.12 (patch releases, same version limits). `composer audit` now reports none. | Full suite: 148 pass after the update |

### 4.3 Still open

1. **Content Security Policy.** Not added. Alpine.js's standard build needs `unsafe-eval`, and a strict policy needs Alpine's CSP build plus nonces. This is a hardening step, not a known hole.
2. **Two-factor sign-in for admins.** Not built. Recommended before production, because admins can see salaries and bank details.

### 4.4 Before going live (production checklist)

- [ ] `APP_ENV=production`, `APP_DEBUG=false` (currently `local` / `true`, which is fine for development), `LOG_LEVEL=warning`.
- [ ] HTTPS, with `SESSION_SECURE_COOKIE=true` and `SESSION_ENCRYPT=true`.
- [ ] A strong `ADMIN_PASSWORD` before the first seed, then change it after first sign-in.
- [ ] A queue worker kept running (Supervisor, or Laravel Cloud's worker) for email notifications.
- [ ] Daily database backups and a backup of `storage/app/private` (receipts).
- [ ] If behind a load balancer or Cloudflare, list it in `trustProxies()` so IP addresses in the audit trail and the office-network QR check are right.
- [ ] Set `HR_OFFICE_IPS` to the office's public IP so the office QR only works on office Wi-Fi.
- [ ] Run `composer audit` before each release and apply patch updates.

---

## 5. Code review and optimisation

| Change | Why |
|---|---|
| **N+1 query guard.** `Model::preventLazyLoading()` is on during tests. | Any relation loaded inside a loop now fails the test suite, so slow pages are caught before release. All 148 tests pass with it on, so no N+1 queries were found on the tested pages. |
| **Receipt download in one place:** `ExpenseClaim::receiptResponse()` | The same file-serving code was copied in three controllers. |
| **QR rendering in one place:** `App\Support\QrCode::svg()` | It was duplicated in the kiosk and clock-in link controllers. |
| **One nav-link partial** for all sidebar links | It replaces three copies of the same markup. |
| **App name in one setting** (`APP_NAME`) | Notification subjects and buttons no longer hard-code the product name. |
| **Indexes reviewed** | Status, date, employee and date pairs, document numbers and audit lookups are all indexed. Nothing was missing. |
| **Dead code scan** | No unused views, Alpine components or classes were found. |
| **Pint** | The code style is clean. |

Bugs found and fixed earlier in this work:
- **Payroll month overflow.** Preparing February's payroll on 31 March produced March. It is fixed with `createFromFormat('!Y-m')` and has a regression test.
- **Sidebar.** It could not scroll, and its sections now fold away.

---

## 6. Testing

- 148 feature tests run on in-memory SQLite. They cover:
  - sign-in for both areas
  - permissions per role
  - payroll maths (EPF, SOCSO, EIS, PCB and overtime)
  - EA forms, leave rules, approval chains, QR and link clock-in
  - claims posting, invoices and the ledger
  - procurement and stock, the CRM flow
  - the audit trail, security headers and masking, and the tutorial
- A page smoke test opens every main admin and staff page with demo data.
- Run them with:

  ```bash
  php artisan test --compact
  ```

---

## 7. Known limits

- Stock tracks quantities and value but does not post to the ledger. The cost reaches the books through the supplier bill.
- EA forms are a summary in the EA layout. Copy the figures into the official LHDN form or e-Filing.
- Moving public holidays (Hari Raya, Chinese New Year, Deepavali, state holidays) must be added each year.
- Malaysian statutory rates live in `config/payroll.php` and should be checked when KWSP, PERKESO or LHDN change them.
- No point of sale, vouchers or two-factor sign-in.

---

## 8. Recommended next steps

1. Add two-factor sign-in for admin accounts.
2. Work through the production checklist (4.4) and deploy over HTTPS with a queue worker and backups.
3. Optional: post stock movements to the ledger (inventory accounting), and add a strict Content Security Policy using Alpine's CSP build.
