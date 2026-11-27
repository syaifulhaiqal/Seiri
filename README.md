# LAB Starter

A Laravel starter with two roles (**admin** and **user**), email-activated accounts, a dashboard, a profile page, and an admin area for managing users. Built on the LAB stack conventions: **L**aravel + **A**lpine.js + Bootstrap 5.

## Features

**Every user**
- Register with name, email, phone number (optional) and password.
- After registering, an **activation email** is sent and the user is sent back to the login page. They are **not** logged in.
- Until the email link is clicked, the account is **inactive** and login is refused. The login page offers a "Resend link" form.
- After login, the user lands on the **Dashboard**.
- **Profile** page: update name, email and phone number, and change password (current password required).
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
| Tests    | PHPUnit feature tests (34 tests) |

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

The default `.env` uses MySQL with a database called `lab_auth_starter` and user `root` with no password (the Laragon default).

- **Laragon / HeidiSQL:** open HeidiSQL, right-click your connection → *Create new* → *Database* → name it `lab_auth_starter` (collation `utf8mb4_unicode_ci`).
- **Command line:** `mysql -u root -e "CREATE DATABASE lab_auth_starter CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"`

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

This creates the tables and the admin account. When `APP_ENV=local` it also adds demo data: `user@example.com` / `password123` and 19 sample users, so the admin pages have something to show.

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

## Running the tests

```powershell
php artisan test
```

The tests use an in-memory SQLite database and a fake mailer, so they never touch your real data or send email.

## Project structure

```
app/
├── Enums/UserRole.php                      admin | user, with label() and context()
├── Http/
│   ├── Controllers/
│   │   ├── Auth/LoginController.php        login form, login, logout
│   │   ├── Auth/RegisterController.php     register + send activation email
│   │   ├── Auth/EmailVerificationController.php  activation link + resend
│   │   ├── DashboardController.php
│   │   ├── ProfileController.php           profile info + password
│   │   └── Admin/UserController.php        list, edit, update, delete users
│   ├── Middleware/
│   │   ├── EnsureAccountIsActive.php       'active' – signs out unverified users
│   │   └── EnsureUserIsAdmin.php           'admin' – 403 for non-admins
│   └── Requests/                           one FormRequest per form
├── Models/User.php                         MustVerifyEmail, role cast, search scope
└── Providers/AppServiceProvider.php        activation email text, password rules
config/admin.php                            default admin account (from .env)
database/seeders/AdminUserSeeder.php
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
tests/Feature/                              registration, login, verification, profile, admin
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
