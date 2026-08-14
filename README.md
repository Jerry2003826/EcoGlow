# Eco Glow Lighting

CakePHP 5 storefront and staff console for **Eco Glow Lighting**.

- **Public:** home, shop, product, cart, contact, customer register / login
- **Customer account:** `/account` — profile, addresses, orders, bookings
- **Staff console:** `/login` then `/admin` — orders, inventory, customers, messages, RBAC

Work lives on the **`jiarui`** branch. Clone that branch, not `main`.

## One-click start (macOS and Windows)

This is the path for a teammate who just cloned the repo. The first run installs
missing tools when it can, creates a demo MySQL database, imports seed data,
opens the browser, and starts the server.

### 1. Clone `jiarui`

GitLab (Monash):

```bash
git clone -b jiarui https://git.infotech.monash.edu/UGIE/ugie-2026/team236/team236-app_fit3047.git
cd team236-app_fit3047
```

GitHub:

```bash
git clone -b jiarui https://github.com/Jerry2003826/EcoGlow.git
cd EcoGlow
```

### 2. Run the launcher

**macOS** — double-click `start.command`, or in Terminal:

```bash
chmod +x start.command start.sh
./start.sh
```

If macOS says the file is from an unidentified developer: right-click
`start.command` → Open.

**Windows** — double-click `start.bat`, or in PowerShell:

```powershell
.\start.ps1
```

If PowerShell blocks scripts:

```powershell
Set-ExecutionPolicy -Scope CurrentUser RemoteSigned
```

The launcher will:

1. Find PHP 8.2+ (Homebrew, MAMP, XAMPP, Laragon, WAMP, or PATH)
2. Install PHP / Composer / MySQL when missing (Homebrew on Mac, winget on Windows)
3. Start MySQL or MariaDB
4. Create database `ecoglow` and user `ecoglow` / `ecoglow`
5. Write a local-only `config/app_local.php` (never committed)
6. Run `composer install`, migrations, and all seeds
7. Open http://127.0.0.1:8765 and keep the server running

Stop the server with `Ctrl+C`.

### Demo accounts

The launcher prints a **one-time** staff and customer password to the terminal.
There is no default password in this repository. Set `ADMIN_SEED_PASSWORD` and
`CUSTOMER_SEED_PASSWORD` (at least 20 characters, not a placeholder such as
`admin123`) before running seeders yourself.

reCAPTCHA is **off** in this local demo. Stripe checkout still needs test keys in
`config/app_local.php` if you want to complete a payment.

### 中文：给组员的一键启动

1. 一定要克隆 **`jiarui` 分支**，不要用默认的 `main`。
2. Mac 双击 `start.command`；Windows 双击 `start.bat`。
3. 第一次可能要装 Homebrew / PHP / MySQL，Mac 可能会要电脑密码。
4. 浏览器打开后，用终端里打印的一次性密码登录：
   - 员工后台：`admin@ecoglow.local`
   - 客户账号：`customer@ecoglow.local`
5. 本机配置写在 `config/app_local.php`，不会上传到 git。
6. 结账要自己填 Stripe 测试密钥；不填也能逛店和看后台。

## Requirements

- PHP >= 8.2 (developed on 8.5) with the PDO MySQL driver
- MySQL / MariaDB
- [Composer](https://getcomposer.org/) — the one-click scripts can download this

## Manual setup

Use this only if you do not want the launcher.

```bash
composer install
cp config/app_local.example.php config/app_local.php
```

Edit `config/app_local.php`:

- `Security.salt` — a long random string
- `Datasources.default` — host, username, password, database
- `Recaptcha` — see [reCAPTCHA](#recaptcha) below

Create the MySQL database, then:

```bash
bin/cake migrations migrate
bin/cake seeds run --seed UsersSeed
MASTER_USER_EMAIL=admin@ecoglow.local bin/cake seeds run --seed EcoGlowAuthorizationSeed
bin/cake seeds run --seed FrontendCatalogSeed
bin/cake seeds run --seed FrontendInventorySeed
bin/cake seeds run --seed DemoCustomerSeed
bin/cake server -p 8765
```

Open http://127.0.0.1:8765/

`UsersSeed` and `DemoCustomerSeed` are idempotent. They refuse to run unless
`ADMIN_SEED_PASSWORD` / `CUSTOMER_SEED_PASSWORD` are strong (20+ characters,
not a public placeholder).

The staff login is throttled: after 5 failed attempts from the same IP the form
is locked for 15 minutes (`login_throttle` cache in `config/app.php`).

## reCAPTCHA

The public contact form uses Google reCAPTCHA v2. The one-click launcher turns
it off locally. For a manual setup, configure:

| Variable            | Purpose                                             |
| ------------------- | --------------------------------------------------- |
| `RECAPTCHA_ENABLED` | `false` to skip verification (handy for local dev). |
| `RECAPTCHA_SITEKEY` | Public site key.                                    |
| `RECAPTCHA_SECRET`  | Server-side secret.                                 |

Empty keys fail closed (every submission is rejected). Google's universal test
keys are accepted only while `debug` is on; they are refused in production.

## Production notes

- Set `APP_FULL_BASE_URL` to your domain — the app refuses to serve without it in
  production (Host Header Injection protection).
- Serve over HTTPS: the CSRF and session cookies are marked `Secure` whenever
  `debug` is off. Set `SESSION_COOKIE_SECURE=1` if TLS terminates at a proxy.
- List trusted reverse-proxy IPs in `TRUSTED_PROXIES`. The origin must not be
  reachable from the public internet except through those proxies, and the proxy
  must overwrite `X-Forwarded-*` rather than append client-supplied values.
- Document root must be `webroot/`. Keep `config/`, `logs/`, `tmp/` and `.git/`
  off the public web server.
- Enable GitHub branch protection on `main` and `jiarui`, require reviews and
  CI, and turn on Secret Scanning / Push Protection.
- If `admin@ecoglow.local` exists in a shared database, rotate its password and
  review roles, last login, and audit rows.
- Release unpaid checkout holds with `bin/cake orders.release_expired_holds`.
- Security headers (`X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`,
  CSP Report-Only) are sent on every response. Set `CSP_ENFORCE=true` after
  reviewing reports.

## Tests

```bash
composer test        # PHPUnit
composer cs-check    # Coding standard (phpcs)
```

The test suite uses the `test` datasource from `config/app_local.php` (SQLite by
default). SQLite does not enforce column lengths, so validation rules — not the
database — are the source of truth for input limits.
