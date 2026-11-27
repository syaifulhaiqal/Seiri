# Seiri

*Seiri* (整理) is Japanese for "sort, put in order": the first of the 5S workplace principles.

An office back-office system covering **HR**, **payroll**, **accounting / finance** and an **audit trail**. Built on the LAB stack conventions (**L**aravel + **A**lpine.js + Bootstrap 5), starting from the LAB auth starter: two roles (**admin** and **user**), email-activated accounts, a dashboard, a profile page and user management.

## Modules

| Module | Status |
|--------|--------|
| Accounts & users | Done |
| HR: employees, departments, leave, attendance | Done |
| Public holidays, QR / remote clock-in, approval chains | Done |
| Overtime, movements, training records | Done |
| Payroll: salaries, overtime, EPF / SOCSO / EIS / PCB, payslips, EA forms | Done |
| Finance: double-entry ledger, invoices, bills, expense claims, reports | Done |
| Purchasing & stock: purchase requests, purchase orders, products, stock | Done |
| Sales & CRM: leads, activities, quotations | Done |
| Audit trail | Done |
| Roles & permissions: super admin assigns admin roles per module | Done |
| Notifications: bell menu and email | Done |
| Bahasa Melayu / English switch and dark mode | Done |

## HR features

**Admins** (`/admin`)
- **Employees**: search by name, email, number or job title; filter by department and status. Add, edit and delete. Each employee gets the next number (`EMP-0001`, …) unless you type one, and can be linked to a user login.
- **Employee profile**: details, this year's leave balance, recent leave and recent attendance.
- **Departments**: name, short code and description. Deleting one keeps its employees, without a department.
- **Leave types**: days per calendar year (0 = no limit) and paid / unpaid. A type with requests cannot be deleted.
- **Leave requests**: pending first. The review page shows the employee's balance and who else in the department is away on those days. Approve, or reject with a reason.
- **Attendance**: any day's present / late / on leave / holiday / absent list and counts, filterable by department and by how people clocked in (office QR, clock-in link or remote). The *How & where* column shows the method, work location, IP address and a Google Maps link for remote clock-ins. Today's board refreshes every minute, so HR can keep it open. Late means clocking in after `HR_WORK_START` (default `09:00`, see `config/hr.php`).
- **Office QR screen** (Attendance → *Office QR screen*): show it full screen on a tablet or monitor at the entrance. Staff scan it with their phone camera to clock in or out. The code changes every `HR_QR_REFRESH_SECONDS` (30) seconds and each code stops working after `HR_QR_VALID_MINUTES` (5), so a photo of it is useless at home. Set `HR_OFFICE_IPS` (comma-separated, e.g. your office's public IP) to only accept scans from the office network.
- **Clock-in links** (Attendance → *Clock-in links*): one link for all staff, for work from home or work outside the office. Send it in your staff group chat (or print its QR code); staff open it, sign in and clock in or out. It records their location (if the browser allows it, or always if you tick *Require location*), IP address and where they are working from. Give a link a last day for a WFH period, *Get a new link* if it reached the wrong people (the old one stops at once), or turn it off.
- **Remote clock-in per employee**: tick *Can clock in remotely* on an employee who always works remotely. They get *Clock in remotely* on My attendance without needing a link.
- **Public holidays**: a calendar per year. Holidays are not working days: leave does not deduct them, payroll does not count them and nobody is marked absent. The seeder adds the fixed-date Malaysian holidays; add Hari Raya, Chinese New Year, Deepavali and state holidays from the gazette each year.
- **Approval chains**: on an employee, choose up to three approvers for leave, claims and overtime (staff with a login, such as their team lead and manager). Requests then go to level 1, then 2, then 3, and HR or finance only hears about it once the chain approves. Admins with the review permission can still decide directly, which closes the chain. Without a chain, requests go straight to HR or finance as before.
- **Overtime**: review claims with the employee's attendance that day. Approved overtime is paid in the next payroll run at 1.5× (working day), 2× (rest day) or 3× (public holiday) the hourly rate, which is basic ÷ `HR_OVERTIME_DAYS_PER_MONTH` (26) ÷ `HR_OVERTIME_HOURS_PER_DAY` (8).
- **Movements**: who is out of the office on a day, where and why, and who is still out.
- **Training**: courses per employee with dates, hours, cost and status; totals per year.
- The dashboard shows active employees, who has clocked in, who is on leave today and leave waiting for review.

**Employees** (users linked to an employee record)
- **My leave**: balance per leave type, apply for leave, cancel a pending request, see the reviewer's note and who it is waiting for.
  Only working days count (weekends and public holidays are skipped). Requests that overlap other leave, go over the balance or cross the new year are refused.
- **My attendance**: clock in and out once a day by scanning the office QR code, with a clock-in link from HR, or remotely if allowed; the last 30 days.
- **My movements**: log going out during work hours and tap *Back* on return.
- **My overtime**: claim overtime worked in the last 30 days (up to 12 hours per claim, one per day) and follow the decision.
- **My training**: courses HR has recorded.
- **Approvals** (team leads and managers in someone's approval chain): approve or reject what is waiting for them.
- Users without an employee record see a notice asking them to contact HR.

## Payroll features

**Admins**
- **Pay settings per employee**: basic salary, monthly PCB (from the LHDN schedule), bank details, and recurring allowances or deductions.
- **Payroll runs**: pick a month and a draft payslip is made for everyone paid that month. Pay is pro-rated for people who join or leave mid-month, approved **unpaid leave** is deducted and approved **overtime** not yet paid is added. Overtime counts for SOCSO and EIS but not EPF.
- Statutory contributions from `config/payroll.php`: EPF 11% employee and 13% / 12% employer (rounded up to the ringgit), SOCSO and EIS with a RM6,000 wage ceiling. **These are simplified percentage rates.** The official KWSP / PERKESO tables use wage bands, so check the figures before paying real salaries.
- **Recalculate** a draft after changing pay settings, then **approve** it. Approving locks the payslips and posts the salary cost and payables to the ledger. **Mark paid** records the bank transfer.
- Printable payslips, showing earnings, employee deductions, net pay and the employer's EPF / SOCSO / EIS contributions. An employee with payslips or expense claims cannot be deleted; set them to Resigned instead.

- **EA forms**: every employee's yearly statement (salary, overtime, allowances, gross, PCB, EPF, SOCSO and EIS, plus a month-by-month table) from approved payroll, printable. Set `PAYROLL_EMPLOYER_NAME`, `PAYROLL_EMPLOYER_TAX_NO` (the E number) and `PAYROLL_EMPLOYER_ADDRESS`, and fill in each employee's IC and income tax numbers. It is a summary in the EA layout: copy the figures into the official LHDN form.

**Employees**
- **My payslips**: every approved month, printable, and **My EA forms** for each year.
- **My expense claims**: claim with a category, amount and receipt (JPG / PNG / PDF, up to 5 MB, within 90 days), and cancel while it is pending.

## Finance features

All amounts post to a **double-entry ledger**. Every journal entry must balance, entries are never edited, and mistakes are fixed with a reversing entry.

- **Chart of accounts**: assets, liabilities, equity, income and expenses, each with its balance. The accounts the app posts to (bank, receivables, payables, salaries, EPF and so on) are marked *System*; they cannot be deleted or change type.
- **Invoices** to customers and **bills** from suppliers: drafts with line items and sales tax, then issue (posts to the ledger), record full or part payments, or void an unpaid one (reverses the posting). Overdue invoices are flagged. Invoices print cleanly.
- **Customers & suppliers** with what each one still owes.
- **Expense claims**: approve (posts the expense and owes it to the employee), reject with a note, then pay back from a bank account. Receipts are stored privately and only the claimant and admins can open them.
- **Journal**: manual entries with a live debit/credit balance check, and one-click reversal.
- **Reports**: profit & loss, balance sheet, trial balance and a general ledger per account with a running balance. All of them are printable and filter by date.
- The admin dashboard shows cash and bank, what customers owe, what you owe suppliers, this month's profit, and claims or payroll waiting for you.

## Purchasing & stock

- **Purchase requests** (`PR-0001`): what is needed, why and by when, with estimated lines. Approvers (*Approve purchase requests*) are told and approve or reject with a note; raising a request does not let you approve it.
- **Purchase orders** (`PO-0001`): raise one from an approved request (its lines are copied) or from scratch, to a supplier. Print it, *Mark as sent*, then *Mark as received* when the goods arrive. Cancelling an order frees its request to be ordered again.
- **Create bill** turns a sent or received order into a draft supplier bill (booked to the first expense account; change the accounts before issuing).
- **Products & stock**: SKU, unit, cost and sale price, and a reorder level. Receiving an order adds its product lines to stock; *Adjust stock* takes stock out (used, sold, damaged) or corrects a count, and never below zero. Each product shows its stock history, and the list shows stock value at cost and what is low.
- Stock tracks **quantities and value only**: it is not posted to the ledger. The cost reaches the books through the supplier bill.

## Sales & CRM

- **Leads**: the opportunity, contact person and company, estimated value, owner, source and status (new → contacted → qualified → won or lost), with a pipeline count and value per status.
- Log **calls, meetings, emails and notes** on a lead to keep its history.
- **Make customer** creates the customer record (or reuses the one with the same email), so the lead can be quoted.
- **Quotations** (`QT-0001`): lines from your products' sale prices or free text, with sales tax. Print it, mark it sent, then accepted or declined. Expired quotations are flagged.
- **Create invoice** turns a quotation into a draft sales invoice (booked to Sales revenue) and marks its lead as won.

**Not included**: point of sale (POS) and vouchers.

## Audit trail

- Every create, update and delete on employees, leave, attendance, overtime, movements, training, holidays, clock-in links, approvers, users, accounts, invoices, payments, claims, pay items, payroll runs, products, stock movements, purchase requests and orders, leads and quotations is recorded with the user, time, IP address and the **old and new value of each changed field**. Passwords and other hidden fields show only that they changed.
- Approvals, rejections, ledger postings, payments, voids, sign-ins, sign-outs and **failed sign-ins** (email only) are recorded too.
- Filter by text, event, record type, user and date. Open an entry to see the before/after table and the record's full history, or **export CSV**.
- Entries cannot be edited or deleted, not even from code: the model refuses.

## Roles & permissions

- **Super admin**: the owner (e.g. the CEO). Can use every module, and is the only one who manages roles and other admins. The account from `ADMIN_EMAIL` is the super admin.
- **Admins** sign in at `/admin/login` and each has one **admin role**. The role decides which modules and actions they see. The menu, dashboard and buttons hide everything else, and the server refuses it too.
- **Roles & permissions** (super admin only): create roles such as *HR manager*, *Payroll officer*, *Finance manager*, *Purchasing officer*, *Sales executive* and *Auditor*, and tick permissions per module. Examples are *view employees*, *prepare payroll*, *approve payroll*, *pay back claims* and *view the audit trail*. Manage permissions include view; approving payroll is separate from preparing it, approving claims is separate from paying them, and approving purchase requests is separate from raising them.
- The seeder only creates roles that do not exist yet. On a database seeded before these modules, tick the new permissions (holidays, overtime, training, purchasing, stock, CRM) on the existing roles yourself.
- Assign a role on **Users → Edit**. An admin without a role can sign in but sees no modules.
- Admins with the *Manage user accounts* permission edit ordinary users only. They cannot edit admins or change anyone's role.
- Salary and bank details are shown and saved only for admins who can prepare payroll.
- Local demo admins (password `password123`): `hr@`, `payroll@`, `finance@` and `auditor@example.com`.

## Notifications

- The **bell** in the top bar shows the number of unread notifications and the latest five. Clicking one marks it read and opens what it is about. **See all notifications** lists the rest.
- Who is told what:

  | When | Who is told |
  |------|-------------|
  | An employee applies for leave, a claim or overtime and has an approval chain | The next approver in the chain; HR or finance once everyone approves |
  | An employee applies for leave | Admins who can approve leave |
  | Leave is approved or rejected | The employee (with the reason, if rejected) |
  | An employee sends an expense claim | Admins who can review claims |
  | A claim is approved, rejected or paid back | The employee |
  | An employee claims overtime | Admins who can approve overtime |
  | Overtime is approved or rejected | The employee |
  | A purchase request is raised | Admins who can approve purchase requests |
  | A purchase request is approved or rejected | Whoever raised it |
  | Payroll for a month is prepared | Admins who can approve payroll |
  | Payroll is approved | Every employee with a login: their payslip is ready |
  | The super admin changes an admin's role | That admin |

  Whoever caused it is not told about their own action.
- Each notification is also **emailed**, unless the user turns off *Email me my notifications* on their Profile page. Emails go out in the user's chosen language.
- Emails wait in the queue, so a **queue worker** must run: `php artisan queue:work` (or `composer run dev`, which starts one). The in-app copy shows straight away, without the worker.

## Logo

- The logo is the name in a diamond under a fading beam. In the app it is drawn in the text colour (`resources/views/components/logo.blade.php`), so it is black in light mode and white in dark mode.
- Brand sheets for documents and print: `public/images/seiri-logo-light.svg` and `public/images/seiri-logo-dark.svg`.
- Tab and home-screen icons: `public/favicon.svg`, `public/favicon.ico` and `public/apple-touch-icon.png`.
- The name everywhere (titles, emails, footer, wordmark) comes from `APP_NAME` in `.env`.

## Language and dark mode

- The **EN / BM** switch in the top bar (and on the sign-in pages) changes the whole app between English and Bahasa Melayu: pages, messages, validation errors, dates and emails. The choice is saved on the account, so the next sign-in and emails use it too.
- Translations are in `lang/ms.json` (app text) and `lang/ms/*.php` (Laravel's validation, auth and pagination messages). New text written with `__()` needs a line in `lang/ms.json`.
- The **moon / sun** button switches dark mode. It is remembered in this browser and starts from the device setting. Payslips, invoices and reports always print on white.

## Account features

**Every user**
- Register with name, email, phone number (optional) and password.
- After registering, an **activation email** is sent and the user is sent back to the login page. They are **not** logged in.
- Until the email link is clicked, the account is **inactive** and login is refused. The login page offers a "Resend link" form.
- After login, the user lands on the **Dashboard**.
- **Profile** page: update name, email and phone number, turn notification emails on or off, and change password (current password required).
  Changing the email logs the user out until they confirm the new address.

**Admins** (everything above, plus)
- Dashboard shows user stats (total / active / pending / admins) and the newest users.
- **Users** page: search by name, email or phone; filter by role and status; paginated.
- **Edit** a user: name, email, phone, role, active status, and an optional password reset.
- **Delete** a user, with a confirmation dialog.
- Safety rails: an admin cannot delete, demote or deactivate their own account.
- If an admin deactivates a signed-in user, that user is signed out on their next request.

## Tech stack

| Layer    | Choice |
|----------|--------|
| Backend  | Laravel 13, PHP 8.3+ |
| Frontend | Blade, Alpine.js 3, Bootstrap 5.3 (SCSS), Bootstrap Icons, Vite |
| Database | MySQL 8 (or SQLite) |
| Tests    | PHPUnit feature tests |

## Requirements

- **PHP 8.3+** with `pdo_mysql`, `mbstring`, `openssl`, `fileinfo` (Laragon and Herd include these)
- **Composer 2**
- **Node.js 20+** and npm
- **MySQL 8** (Laragon / HeidiSQL), or SQLite
- A mail catcher for local email, e.g. **[Mailpit](https://mailpit.axllent.org/)** (optional, see step 5)

---

## Setup, step by step

The commands below are for **Windows PowerShell**. On macOS or Linux, use `cp` instead of `Copy-Item`.

### 1. Clone the repository

```powershell
git clone https://github.com/<your-username>/<repo-name>.git
cd <repo-name>
```

If you use **Laragon**, clone into `C:\laragon\www`. If you use **Herd**, clone into your Herd sites folder (e.g. `C:\Users\<you>\Herd`).

### 2. Install PHP and JavaScript dependencies

```powershell
composer install
npm install
```

### 3. Create your `.env` and app key

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

### 4. Create the database

The default `.env` uses MySQL with a database called `nexoffice` and user `root` with no password (the Laragon default).

- **Laragon / HeidiSQL:** open HeidiSQL, right-click your connection → *Create new* → *Database* → name it `nexoffice` (collation `utf8mb4_unicode_ci`).
- **Command line:** `mysql -u root -e "CREATE DATABASE nexoffice CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"`

If your MySQL user or password is different, edit `DB_USERNAME` and `DB_PASSWORD` in `.env`.

<details>
<summary>Prefer SQLite? (no MySQL needed)</summary>

In `.env`, set `DB_CONNECTION=sqlite` and comment out the other `DB_` lines, then create the file:

```powershell
New-Item database/database.sqlite -ItemType File
```
</details>

### 5. Set up email (so you can receive the activation link)

Pick **one** option:

**Option A: Mailpit (recommended).** It catches every email locally and shows it in a web inbox.
1. Download `mailpit-windows-amd64.zip` from <https://github.com/axllent/mailpit/releases>, unzip, and run `mailpit.exe`.
2. Open the inbox at <http://localhost:8025>.
3. The `.env` is already set for it: `MAIL_MAILER=smtp`, `MAIL_HOST=127.0.0.1`, `MAIL_PORT=1025`.

**Option B: Herd Pro mail.** Turn on the Mail service in Herd and set `MAIL_PORT=2525` in `.env`.

**Option C: No mail tool.** Set `MAIL_MAILER=log` in `.env`. Emails are written to `storage/logs/laravel.log`; copy the activation link from there.

**Option D: Real email (e.g. Gmail).** Use an [App Password](https://support.google.com/accounts/answer/185833):

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_SCHEME=null
MAIL_USERNAME=you@gmail.com
MAIL_PASSWORD="your-16-char-app-password"
MAIL_FROM_ADDRESS=you@gmail.com
```

### 6. Set your admin account (optional)

The first admin is created from these `.env` values. Change them before seeding if you like:

```dotenv
ADMIN_NAME="Administrator"
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=password123
```

### 7. Run the migrations and seed the database

```powershell
php artisan migrate --seed
```

This creates the tables, the admin account, the default leave types (annual, sick, emergency, unpaid) and the chart of accounts. When `APP_ENV=local` it also adds demo data: `user@example.com` / `password123` and 19 sample users, plus 5 departments, about 25 employees, two weeks of attendance, leave requests, customers, suppliers, invoices, bills, expense claims and two months of paid payroll (with this month left as a draft). The demo user is linked to an employee, so **My leave**, **My attendance**, **My payslips** and **My expense claims** have data. Seeding is not written to the audit trail.

### 8. Build the frontend

For development, with hot reload (leave it running):

```powershell
npm run dev
```

Or a one-off production build:

```powershell
npm run build
```

### 9. Start the app

Pick one:

- **`php artisan serve`** → open <http://localhost:8000> (run it in a second terminal next to `npm run dev`).
- **Laragon:** start all services, then open `http://<folder-name>.test`.
- **Herd:** the folder is served automatically at `http://<folder-name>.test`.

Set `APP_URL` in `.env` to the URL you actually use.

Notification emails are queued. Run a worker next to the app so they are sent:

```powershell
php artisan queue:work
```

### 10. Log in

| Role  | Login page     | Email               | Password      |
|-------|----------------|---------------------|---------------|
| Admin | `/admin/login` | `admin@example.com` | `password123` |
| User  | `/login`       | `user@example.com`  | `password123` (local demo data only) |

Each role can only sign in on its own page. Using the wrong page gives the same error as a wrong password.
The two areas use separate guards (`web` and `admin`), so you can be signed in as a user and as an admin in the same browser at once. The admin area has its own dashboard, profile and logout under `/admin`.

**Change the admin password** from the Profile page after your first login.

---

## Try the full flow

1. Open **/register** and create an account. You're sent to the login page with a "check your email" message.
2. Try to log in right away. It's refused because the account isn't active yet, and a **Resend link** form appears.
3. Open Mailpit (<http://localhost:8025>) and click **Activate Account** in the email.
4. Log in. You land on the **Dashboard**. Open **Profile** to edit your details or password.
5. Log in as the admin at **/admin/login** and open **Users** to search, edit, deactivate or delete accounts.
6. Open **Payroll runs**, open this month's draft, then **Approve** and **Mark paid**. Then check **Reports → Balance sheet**.
7. Create an invoice, issue it, record a payment, and watch each step appear in the **Journal** and the **Audit trail**.

## Running the tests

```powershell
php artisan test
```

The tests use an in-memory SQLite database and a fake mailer, so they never touch your real data or send email.

## Project structure

```
app/
├── Enums/                                  UserRole, EmployeeStatus, InvoiceStatus, AuditEvent, … with label() and context()
├── Http/
│   ├── Controllers/
│   │   ├── Auth/LoginController.php        login form, login, logout
│   │   ├── Auth/RegisterController.php     register + send activation email
│   │   ├── Auth/EmailVerificationController.php  activation link + resend
│   │   ├── DashboardController.php
│   │   ├── ProfileController.php           profile info + password
│   │   ├── PayslipController.php, ClaimController.php   employee self-service
│   │   └── Admin/                          users, HR, payroll, finance, reports, audit
│   ├── Middleware/
│   │   ├── EnsureAccountIsActive.php       'active' – signs out unverified users
│   │   └── EnsureUserIsAdmin.php           'admin' – 403 for non-admins
│   └── Requests/                           one FormRequest per form
├── Listeners/RecordAuthenticationEvents.php  sign-ins, sign-outs and failed sign-ins → audit trail
├── Models/                                 Concerns/Auditable.php logs every change
├── Services/
│   ├── Ledger.php                          posts and reverses balanced journal entries
│   ├── FinancialReports.php                trial balance, P&L, balance sheet, account ledger
│   ├── InvoicePosting.php                  drafts, issue, payments, void
│   ├── ExpenseClaimPosting.php             approve, reject, reimburse
│   ├── PayrollCalculator.php               one employee's payslip for a month
│   └── PayrollService.php                  prepare, approve and pay a payroll run
├── Support/Money.php                       amounts are worked out in sen (integers)
└── Providers/AppServiceProvider.php        morph map, @money directive, password rules
config/                                     admin.php, hr.php, finance.php, payroll.php
database/seeders/                           admin, leave types, chart of accounts, demo data
resources/
├── js/app.js + js/components/              Alpine.data() components
├── scss/                                   Bootstrap theme + app styles
└── views/
    ├── components/                         layouts, x-form.input, badges, buttons
    ├── auth/                               login, register
    ├── dashboard.blade.php
    ├── profile/edit.blade.php
    └── admin/users/                        index, edit, delete modal
routes/web.php
tests/Feature/                              auth, profile, admin, Hr/, Payroll/, Finance/, Audit/, page smoke test
```

## Conventions (LAB style)

- **PHP:** typed parameters and returns, `match` over `switch`, arrow functions, `type|null` over `?type`, single quotes, spaces around `.`, sorted imports. Run `./vendor/bin/pint` before committing; `pint.json` enforces these rules.
- **Validation:** one `FormRequest` per form, rules written as arrays (not `'a|b'` strings).
- **Routes:** `kebab-case` paths, `snake_case` route names (e.g. `profile.update_password`), controller callable syntax, route model binding.
- **Alpine:** components registered with `Alpine.data()` in `resources/js/components`, and full `x-on:` / `x-bind:` syntax (no `@click` / `:attr` shorthand).
- **Roles:** `role` is not mass-assignable, so a form can never make someone an admin. Admin changes go through `Admin\UserController` only.

## Troubleshooting

| Problem | Fix |
|---------|-----|
| Error on register: *Connection could not be established with host 127.0.0.1:1025* | Mailpit isn't running. Start it, or set `MAIL_MAILER=log`. |
| *Vite manifest not found* | Run `npm run build`, or keep `npm run dev` running. |
| Activation link shows **403 Invalid signature** | The link expired (60 minutes) or was altered. Use **Resend link** on the login page. |
| *Access denied for user 'root'* | Fix `DB_USERNAME` / `DB_PASSWORD` in `.env`. |
| Changed `.env` but nothing happens | Run `php artisan config:clear`. |
| Forgot the admin password | Run `php artisan tinker`, then `App\Models\User::where('email', 'admin@example.com')->first()->update(['password' => 'newpass123']);` |
