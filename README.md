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

| Who | URL | Email | Password |
| --- | --- | --- | --- |
| Storefront | http://127.0.0.1:8765/ | — | — |
| Staff | http://127.0.0.1:8765/login | `admin@ecoglow.local` | `admin123` |
| Customer | http://127.0.0.1:8765/account/login | `customer@ecoglow.local` | `customer123` |

reCAPTCHA is **off** in this local demo. Stripe checkout still needs test keys in
`config/app_local.php` if you want to complete a payment.

Change these passwords before deploying anywhere shared.

### 中文：给组员的一键启动

1. 一定要克隆 **`jiarui` 分支**，不要用默认的 `main`。
2. Mac 双击 `start.command`；Windows 双击 `start.bat`。
3. 第一次可能要装 Homebrew / PHP / MySQL，Mac 可能会要电脑密码。
4. 浏览器打开后：
   - 员工后台：`admin@ecoglow.local` / `admin123`
   - 客户账号：`customer@ecoglow.local` / `customer123`
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

`UsersSeed` and `DemoCustomerSeed` are idempotent. Override passwords with
`ADMIN_SEED_PASSWORD` and `CUSTOMER_SEED_PASSWORD` if you need to.

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
- Serve over HTTPS: the CSRF cookie is marked `Secure` whenever `debug` is off.
- Security headers (`X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`)
  are sent on every response.
- Do not deploy the demo passwords.

## Tests

```bash
composer test        # PHPUnit
composer cs-check    # Coding standard (phpcs)
```

The test suite uses the `test` datasource from `config/app_local.php` (SQLite by
default). SQLite does not enforce column lengths, so validation rules — not the
database — are the source of truth for input limits.
