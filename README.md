# Eco Glow Lighting

A CakePHP 5 web application for **Eco Glow Lighting** — a public marketing site with
a contact form, plus a password-protected admin area for reviewing the messages that
customers submit.

- **Public**: landing page (`/`), contact form (`/contact`) protected by reCAPTCHA v2.
- **Admin** (login required): list, read and delete contact messages under
  `/admin/contact-messages`.

## Requirements

- PHP >= 8.2 (developed on 8.5)
- MySQL / MariaDB
- [Composer](https://getcomposer.org/)

## Setup

1. Install dependencies:

   ```bash
   composer install
   ```

2. Create the application database and a database user, then copy the local config
   template and fill in your credentials and a random security salt:

   ```bash
   cp config/app_local.example.php config/app_local.php
   ```

   Edit `config/app_local.php`:
   - `Security.salt` — a long random string.
   - `Datasources.default` — your database `host`, `username`, `password`, `database`.
   - `Recaptcha` — see [reCAPTCHA](#recaptcha) below.

3. Build the schema and seed the default admin account:

   ```bash
   bin/cake migrations migrate
   bin/cake seeds run --seed UsersSeed
   ```

4. Start the development server:

   ```bash
   bin/cake server -p 8765
   ```

   Then visit <http://localhost:8765/>.

## Default admin account

`UsersSeed` creates a single administrator:

| Field    | Value                    |
| -------- | ------------------------ |
| Email    | `admin@ecoglow.local`    |
| Password | `admin123` (default)     |

Override the password by exporting `ADMIN_SEED_PASSWORD` before running the seeder.
The seeder is idempotent — running it again will not create a duplicate account.
**Change the default password before deploying anywhere shared.**

The admin login is throttled: after 5 failed attempts from the same IP the form is
locked for 15 minutes (configurable via the `login_throttle` cache in `config/app.php`).

## reCAPTCHA

The public contact form uses Google reCAPTCHA v2. Configure it via environment
variables (or directly in `config/app_local.php`):

| Variable            | Purpose                                             |
| ------------------- | --------------------------------------------------- |
| `RECAPTCHA_ENABLED` | `false` to skip verification (handy for local dev). |
| `RECAPTCHA_SITEKEY` | Public site key.                                    |
| `RECAPTCHA_SECRET`  | Server-side secret.                                 |

The keys default to **empty**, which fails closed (every submission is rejected) so a
misconfigured deployment never silently disables the CAPTCHA. Google's universal test
keys are accepted only while `debug` is on; they are refused in production.

## Production notes

- Set `APP_FULL_BASE_URL` to your domain — the app refuses to serve without it in
  production (Host Header Injection protection).
- Serve over HTTPS: the CSRF cookie is marked `Secure` whenever `debug` is off.
- Security headers (`X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`)
  are sent on every response.

## Tests

```bash
composer test        # PHPUnit
composer cs-check    # Coding standard (phpcs)
```

The test suite uses the `test` datasource from `config/app_local.php` (SQLite by
default). Note that SQLite does not enforce column lengths, so validation rules — not
the database — are the source of truth for input limits.
