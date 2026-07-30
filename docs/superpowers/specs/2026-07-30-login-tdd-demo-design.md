# Login TDD Demo Design

**Date:** 2026-07-30
**Status:** Approved for implementation planning
**Application:** CakePHP 5.4 / MariaDB local demo

## Context

The application is an otherwise unmodified CakePHP 5.4 skeleton. It has no authentication feature or authentication plugin. The existing PHPUnit baseline is 9 tests and 23 assertions, all passing. The `my_app` MariaDB database already contains empty `articles` and `categories` tables; this feature must preserve them.

The demo implements the classroom user story:

> As the registered user John, I want to log in with my email and password so that I can access my account securely.

## Goals

- Provide a working browser login flow backed by the local database.
- Accept John's valid email and password and establish a session.
- Return the same generic error for an unknown email and an incorrect password.
- Lock John's account for five minutes after three consecutive failed attempts.
- Reject correct credentials while the account is locked.
- Allow login again after the lock expires.
- Reset failed-attempt state after successful authentication.
- Demonstrate the work through explicit RED, GREEN, and REFACTOR test cycles.

## Non-goals

- Registration, password reset, email verification, roles, and administration.
- Distributed or IP-based rate limiting.
- Production deployment, TLS termination, or a shared cache.
- Changes to the existing `articles` and `categories` tables.
- A frontend framework or elaborate visual design.

## Architecture

The feature uses CakePHP MVC and the official CakePHP Authentication plugin.

### Components

- `Application` configures Authentication middleware with session and form authenticators.
- `AppController` loads the Authentication component and applies the default protected-page policy.
- `UsersController` exposes `login` and `logout` actions.
- `DashboardController` exposes the authenticated landing page.
- `UsersTable` owns persistence, validation, normalized unique email lookup, and the authentication finder.
- `User` hashes assigned plaintext passwords before persistence.
- `LoginThrottleService` owns failed-attempt transitions and clock-dependent lock decisions. Authentication details remain outside this service.
- Login and dashboard templates provide the minimal browser interface.

`LoginThrottleService` is separated from controllers so its state transitions can be tested without HTTP or Authentication middleware.

## Data Model

A new `users` table is created through a portable CakePHP migration:

| Column | Type | Rules |
| --- | --- | --- |
| `id` | integer | primary key, auto increment |
| `name` | string(100) | required |
| `email` | string(255) | required, normalized lowercase, unique index |
| `password` | string(255) | required, password hash only |
| `failed_login_attempts` | integer | required, default `0` |
| `locked_until` | datetime | nullable |
| `created` | datetime | required |
| `modified` | datetime | required |

The migration does not alter or delete existing tables.

A local demo seed creates:

- Name: `John`
- Email: `john@example.com`
- Demo password: `DemoPass123!`

Only the password hash is stored in the database.

## Routes and UI

- `GET /login` renders the public login form.
- `POST /login` processes credentials with CSRF protection. Success redirects with HTTP 302; ordinary invalid credentials re-render the form with HTTP 401; an active lock re-renders it with HTTP 429.
- `GET /dashboard` renders `Welcome, John` for an authenticated user and otherwise redirects to `/login` with HTTP 302.
- `POST /logout` clears the authenticated session and redirects to `/login` with HTTP 302.

The login page contains email and password fields, a submit button, and a flash-message area. The dashboard contains the welcome message and a CSRF-protected logout form. Existing CakePHP styles are reused with only small page-specific additions.

## Authentication and Lockout Flow

1. Normalize the submitted email by trimming it and converting it to lowercase.
2. Determine whether the matching user is currently locked.
3. A locked user is not eligible for Authentication identifier resolution, even when the supplied password is correct.
4. An unlocked user is verified by the Authentication plugin against the stored password hash.
5. On success, persist the identity in the session, clear `failed_login_attempts` and `locked_until`, and redirect to `/dashboard`.
6. On failure, display `Invalid email or password.` for both unknown emails and incorrect passwords.
7. For an existing unlocked user, increment `failed_login_attempts`. On the third consecutive failure, set `locked_until` to five minutes after the current time.
8. Unknown-email attempts are not persisted because this intentionally small demo stores throttle state on registered user rows; their visible response remains identical to a wrong password.
9. During the lock period, display `Too many failed attempts. Please try again later.` and do not extend the lock on every request.
10. Once the lock has expired, the next attempt starts from an unlocked state. A successful attempt clears all failure state; a failed attempt becomes the first failure of a new sequence.

The demo deliberately keeps lockout state on the user row. This satisfies the registered-user acceptance criteria but is not a substitute for distributed IP/email throttling in a production system.

## Error Handling and Security

- Passwords are never logged, rendered, or stored as plaintext.
- Ordinary authentication failures use one generic message to avoid distinguishing an unknown email from a wrong password.
- Authentication and lockout decisions are server-side.
- CSRF protection remains enabled for login and logout POST requests.
- The dashboard does not render without an authenticated identity.
- Database exceptions use CakePHP's existing error handling and do not expose credentials.
- Session cookies use CakePHP's configured HTTP-only behavior.

## TDD Strategy

Implementation follows one behavior at a time. Production code is written only after the corresponding test has failed for the expected missing-feature reason.

### Planned RED-GREEN-REFACTOR Cycles

1. **Password hashing**
   - RED: a newly assigned plaintext password remains unchanged or cannot be verified as a hash.
   - GREEN: add the minimal entity password setter.
2. **Failure counting**
   - RED: the first two failures do not persist the expected count.
   - GREEN: implement minimal failure incrementing.
3. **Third-failure lock**
   - RED: the third consecutive failure does not set a five-minute lock.
   - GREEN: add the lock transition.
4. **Lock expiry**
   - RED: an expired lock remains active or preserves the old failure sequence.
   - GREEN: reset expired lock state before the next attempt.
5. **Successful browser login**
   - RED: valid credentials do not establish an identity or redirect to the dashboard.
   - GREEN: wire Authentication middleware, identifiers, controller, and routes.
6. **Invalid credentials**
   - RED: invalid credentials do not return the generic message or update a known user's counter.
   - GREEN: connect controller failure handling to `LoginThrottleService`.
7. **Locked account**
   - RED: correct credentials can authenticate during the five-minute lock.
   - GREEN: exclude locked identities and return the rate-limit response.
8. **Protected dashboard and logout**
   - RED: unauthenticated dashboard access succeeds or logout retains the identity.
   - GREEN: enforce the protected route and clear the session on logout.

Tests use real application code and the existing CakePHP test database/migration setup. Time-dependent tests use a controllable clock so they do not sleep or depend on wall-clock timing. Mocks are avoided unless a framework boundary cannot be exercised directly.

The implementation records each focused RED command and expected failure, followed by the corresponding GREEN command and passing result, in `docs/superpowers/tdd-evidence.md`.

## Verification

Fresh final verification must include:

```bash
composer test
composer cs-check
composer audit
```

Browser verification covers:

- Valid John credentials redirect to the dashboard.
- A wrong password shows the generic error.
- Three consecutive failures lock the account.
- Correct credentials remain rejected during the lock.
- Logout returns to the login page and the dashboard becomes inaccessible.

## Acceptance Mapping

| Acceptance criterion | Implementation evidence |
| --- | --- |
| Valid email and correct password logs the user in | Controller integration test plus browser dashboard verification |
| Valid email and wrong password denies access with a generic error | Controller integration test asserting the response and absent identity |
| Repeated failed attempts temporarily rate-limit the account | Service tests for the third-failure transition and expiry, controller test for locked credentials |

## Version Control

The directory initially had no Git repository. The approved workflow initializes a local repository so the design and implementation can be committed in reviewable steps. No remote is configured and nothing is pushed.
