# Login TDD Demo Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a database-backed CakePHP login demo for John with generic invalid-credential handling, a three-failure/five-minute account lock, session-protected dashboard, logout, and recorded RED-GREEN-REFACTOR evidence.

**Architecture:** CakePHP Authentication 4.2 provides form and session authentication. A `Users` model owns normalized identities and hashed passwords, while a focused `LoginThrottleService` owns persisted failure/lock transitions. Controllers coordinate those components, and integration tests exercise the real middleware, database, session, CSRF, and HTTP responses.

**Tech Stack:** PHP 8.5, CakePHP 5.4, CakePHP Authentication 4.2, CakePHP Migrations 5, MariaDB 12.2 for the local app, SQLite for automated tests, PHPUnit 13.

## Global Constraints

- Use `cakephp/authentication:^4.2`; Composer currently resolves Authentication 4.2.1 and may patch CakePHP from 5.4.0 to 5.4.1.
- Demo identity is `John`, `john@example.com`, password `DemoPass123!`; store only its password hash.
- The third consecutive failed password attempt locks the account for exactly five minutes.
- Ordinary invalid credentials return HTTP 401 and `Invalid email or password.`
- An active lock returns HTTP 429 and `Too many failed attempts. Please try again later.`
- Successful login, unauthenticated dashboard redirect, and logout use HTTP 302.
- Preserve the existing `articles` and `categories` tables and all local database credentials.
- Keep CSRF enabled. Login accepts GET/POST; logout accepts POST only.
- Do not add registration, reset-password, roles, remember-me cookies, IP throttling, or a frontend framework.
- No production behavior is added before a focused test has been observed failing for the intended reason.
- Record each observed RED and GREEN command, exit status, and decisive output in `docs/superpowers/tdd-evidence.md`.
- Run focused tests during each cycle, then `composer test`, `composer cs-check`, and `composer audit` before completion.
- Do not configure a remote or push.

## File Structure

### Create

- `config/Migrations/20260730000000_CreateUsers.php` — portable `users` schema only.
- `config/Seeds/DemoUserSeed.php` — hashes and inserts the local John demo account.
- `src/Model/Entity/User.php` — email normalization, password hashing, accessible/hidden fields.
- `src/Model/Table/UsersTable.php` — validation, unique email rule, timestamp behavior, lock-aware authentication finder.
- `src/Service/LoginThrottleService.php` — failed-attempt and lock state transitions.
- `src/Middleware/NormalizeLoginEmailMiddleware.php` — normalizes the login POST email before Authentication middleware runs.
- `src/Controller/UsersController.php` — login/logout orchestration.
- `src/Controller/DashboardController.php` — authenticated dashboard action.
- `templates/Users/login.php` — login form and CSS inclusion.
- `templates/Dashboard/index.php` — welcome message and POST logout form.
- `webroot/css/login.css` — small page-specific layout.
- `tests/Fixture/UsersFixture.php` — real hashed John record for database and HTTP tests.
- `tests/TestCase/Model/Entity/UserTest.php` — password-hash and email-normalization behavior.
- `tests/TestCase/Model/Table/UsersTableTest.php` — persistence, uniqueness, and lock-aware finder behavior.
- `tests/TestCase/Service/LoginThrottleServiceTest.php` — deterministic lock state-machine tests.
- `tests/TestCase/Controller/UsersControllerTest.php` — login, invalid credentials, lockout, normalization, logout.
- `tests/TestCase/Controller/DashboardControllerTest.php` — unauthenticated redirect and authenticated page behavior.
- `docs/superpowers/tdd-evidence.md` — actual observed RED/GREEN evidence, never predicted output.

### Modify

- `.gitignore` — ignore `.playwright-mcp/` artifacts before the baseline commit.
- `composer.json`, `composer.lock` — add Authentication 4.2.
- `src/Application.php:19-100` — provider interface, service configuration, and Authentication middleware.
- `src/Controller/AppController.php:40-51` — load Flash and Authentication components.
- `src/Controller/PagesController.php:17-52` — explicitly preserve the skeleton's public static pages after global authentication is enabled.
- `config/routes.php:52-79` — explicit login, dashboard, and logout routes.

---

### Task 0: Capture the Existing CakePHP Baseline

**Files:**
- Modify: `.gitignore`
- Commit: all existing CakePHP skeleton files not already ignored

**Interfaces:**
- Consumes: the locally installed CakePHP application and ignored `config/app_local.php`.
- Produces: a reviewable baseline commit; later commits contain only feature deltas.

- [ ] **Step 1: Ignore browser automation artifacts**

Add this entry under tool-specific files in `.gitignore`:

```gitignore
/.playwright-mcp/
```

- [ ] **Step 2: Verify secrets and generated directories remain ignored**

Run:

```bash
git check-ignore -v config/app_local.php vendor/autoload.php tmp/cache logs/error.log .playwright-mcp/page.yml
```

Expected: each path is matched by `.gitignore`; `config/app_local.php` is not staged.

- [ ] **Step 3: Re-run the pre-feature baseline**

Run:

```bash
composer test
composer cs-check
composer audit
```

Expected: 9 tests and 23 assertions pass, CodeSniffer exits 0, and Composer reports no security advisories.

- [ ] **Step 4: Commit the baseline**

```bash
git add .
git status --short
git commit -m "chore: capture CakePHP application baseline"
```

Expected: CakePHP skeleton files are committed; ignored local credentials, dependencies, caches, logs, and browser artifacts are absent.

---

### Task 1: Authentication Dependency and User Entity

**Files:**
- Modify: `composer.json`
- Modify: `composer.lock`
- Create: `src/Model/Entity/User.php`
- Create: `tests/TestCase/Model/Entity/UserTest.php`
- Create: `docs/superpowers/tdd-evidence.md`

**Interfaces:**
- Consumes: `Authentication\PasswordHasher\DefaultPasswordHasher` from Authentication 4.2.
- Produces: `App\Model\Entity\User`, whose `email` is normalized and whose assigned `password` is hashed.

- [ ] **Step 1: Install the supported Authentication dependency**

Run:

```bash
composer require 'cakephp/authentication:^4.2' -W
composer show cakephp/authentication cakephp/cakephp
```

Expected: Authentication 4.2.x is installed and CakePHP remains 5.4.x.

- [ ] **Step 2: Write the first failing password-hashing test**

Create `tests/TestCase/Model/Entity/UserTest.php`:

```php
<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\User;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\TestSuite\TestCase;

class UserTest extends TestCase
{
    public function testAssignedPasswordIsHashed(): void
    {
        if (!class_exists(User::class)) {
            $this->fail('User entity is not implemented');
        }

        $user = new User(['password' => 'DemoPass123!']);

        $this->assertNotSame('DemoPass123!', $user->password);
        $this->assertTrue(
            (new DefaultPasswordHasher())->check('DemoPass123!', $user->password),
        );
    }
}
```

- [ ] **Step 3: Run the focused test and verify RED**

Run:

```bash
vendor/bin/phpunit tests/TestCase/Model/Entity/UserTest.php --filter testAssignedPasswordIsHashed
```

Expected: FAIL with `User entity is not implemented`; it must not pass or fail because of a syntax/bootstrap error.

- [ ] **Step 4: Implement only password hashing**

Create `src/Model/Entity/User.php`:

```php
<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\ORM\Entity;

class User extends Entity
{
    protected array $_accessible = [
        'name' => true,
        'email' => true,
        'password' => true,
        'failed_login_attempts' => true,
        'locked_until' => true,
        'created' => true,
        'modified' => true,
    ];

    protected array $_hidden = ['password'];

    protected function _setPassword(string $password): ?string
    {
        if ($password === '') {
            return null;
        }

        return (new DefaultPasswordHasher())->hash($password);
    }
}
```

- [ ] **Step 5: Run the focused test and verify GREEN**

Run the same PHPUnit command. Expected: one test passes with no warnings.

- [ ] **Step 6: Add a failing email-normalization test**

Add to `UserTest`:

```php
public function testAssignedEmailIsTrimmedAndLowercased(): void
{
    $user = new User(['email' => ' JOHN@EXAMPLE.COM ']);

    $this->assertSame('john@example.com', $user->email);
}
```

- [ ] **Step 7: Verify normalization RED**

Run:

```bash
vendor/bin/phpunit tests/TestCase/Model/Entity/UserTest.php --filter testAssignedEmailIsTrimmedAndLowercased
```

Expected: FAIL because the value is still ` JOHN@EXAMPLE.COM `.

- [ ] **Step 8: Add the minimal email setter**

Add to `User`:

```php
protected function _setEmail(string $email): string
{
    return mb_strtolower(trim($email));
}
```

- [ ] **Step 9: Verify both entity behaviors are GREEN**

Run:

```bash
vendor/bin/phpunit tests/TestCase/Model/Entity/UserTest.php
```

Expected: two tests pass.

- [ ] **Step 10: Start the evidence record with observed output only**

Create `docs/superpowers/tdd-evidence.md` with a title, environment versions, and the actual Step 3/5/7/9 commands, exit statuses, and decisive PHPUnit lines. Do not copy the expected text from this plan when it differs from observed output.

- [ ] **Step 11: Commit the entity slice**

```bash
git add composer.json composer.lock src/Model/Entity/User.php tests/TestCase/Model/Entity/UserTest.php docs/superpowers/tdd-evidence.md
git commit -m "feat: add normalized hashed user identity"
```

---

### Task 2: Users Schema, Table, Fixture, and Demo Seed

**Files:**
- Create: `config/Migrations/20260730000000_CreateUsers.php`
- Create: `config/Seeds/DemoUserSeed.php`
- Create: `src/Model/Table/UsersTable.php`
- Create: `tests/Fixture/UsersFixture.php`
- Create: `tests/TestCase/Model/Table/UsersTableTest.php`
- Modify: `docs/superpowers/tdd-evidence.md`

**Interfaces:**
- Consumes: `User` from Task 1.
- Produces: `UsersTable::findAuth(SelectQuery $query, array $options): SelectQuery` and a migrated `users` table with unique normalized email and lock fields.

- [ ] **Step 1: Write failing real-persistence tests**

Create `tests/TestCase/Model/Table/UsersTableTest.php`:

```php
<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\TestSuite\TestCase;
use Throwable;

class UsersTableTest extends TestCase
{
    public function testPersistsNormalizedUserWithHashedPassword(): void
    {
        try {
            $users = $this->getTableLocator()->get('Users');
            $users->deleteAll([]);
            $user = $users->newEntity([
                'name' => 'John',
                'email' => ' JOHN@EXAMPLE.COM ',
                'password' => 'DemoPass123!',
            ]);
            $saved = $users->saveOrFail($user);
        } catch (Throwable $exception) {
            $this->fail('Users persistence is not implemented: ' . $exception->getMessage());
        }

        $this->assertSame('john@example.com', $saved->email);
        $this->assertSame(0, $saved->failed_login_attempts);
        $this->assertTrue(
            (new DefaultPasswordHasher())->check('DemoPass123!', $saved->password),
        );
    }

    public function testRejectsDuplicateNormalizedEmail(): void
    {
        try {
            $users = $this->getTableLocator()->get('Users');
            $users->deleteAll([]);
            $users->saveOrFail($users->newEntity([
                'name' => 'John',
                'email' => 'john@example.com',
                'password' => 'DemoPass123!',
            ]));
            $duplicate = $users->newEntity([
                'name' => 'Other John',
                'email' => 'JOHN@EXAMPLE.COM',
                'password' => 'OtherPass123!',
            ]);
        } catch (Throwable $exception) {
            $this->fail('Users persistence is not implemented: ' . $exception->getMessage());
        }

        $this->assertFalse($users->save($duplicate));
        $this->assertNotEmpty($duplicate->getError('email'));
    }
}
```

- [ ] **Step 2: Verify persistence RED**

Run:

```bash
vendor/bin/phpunit tests/TestCase/Model/Table/UsersTableTest.php
```

Expected: assertion failures explaining that Users persistence is not implemented, caused by the missing table/model rather than malformed test code.

- [ ] **Step 3: Add the portable users migration**

Create `config/Migrations/20260730000000_CreateUsers.php`:

```php
<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateUsers extends BaseMigration
{
    public function change(): void
    {
        $this->table('users')
            ->addColumn('name', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('email', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('password', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('failed_login_attempts', 'integer', [
                'default' => 0,
                'null' => false,
            ])
            ->addColumn('locked_until', 'datetime', ['default' => null, 'null' => true])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addIndex(['email'], ['name' => 'UNIQUE_USERS_EMAIL', 'unique' => true])
            ->create();
    }
}
```

- [ ] **Step 4: Add the minimal UsersTable**

Create `src/Model/Table/UsersTable.php`:

```php
<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class UsersTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('users');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('name')
            ->maxLength('name', 100)
            ->requirePresence('name', 'create')
            ->notEmptyString('name')
            ->email('email')
            ->requirePresence('email', 'create')
            ->notEmptyString('email')
            ->scalar('password')
            ->requirePresence('password', 'create')
            ->notEmptyString('password');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['email']), [
            'errorField' => 'email',
            'message' => 'This email is already registered.',
        ]);

        return $rules;
    }
}
```

- [ ] **Step 5: Verify persistence GREEN**

Run the focused table test again. Expected: both tests pass after the test migrator creates `users`.

- [ ] **Step 6: Write a failing lock-aware authentication finder test**

Add to `UsersTableTest`:

```php
public function testAuthFinderExcludesActiveLocksAndIncludesExpiredLocks(): void
{
    $users = $this->getTableLocator()->get('Users');
    $users->deleteAll([]);
    $user = $users->saveOrFail($users->newEntity([
        'name' => 'John',
        'email' => 'john@example.com',
        'password' => 'DemoPass123!',
        'locked_until' => '2026-07-30 12:05:00',
    ]));

    \Cake\I18n\DateTime::setTestNow('2026-07-30 12:00:00');
    try {
        $activeCount = $users->find('auth')->where(['Users.id' => $user->id])->count();
    } catch (Throwable $exception) {
        $this->fail('Auth finder is not implemented: ' . $exception->getMessage());
    } finally {
        \Cake\I18n\DateTime::setTestNow();
    }

    $this->assertSame(0, $activeCount);

    $user->locked_until = new \Cake\I18n\DateTime('2026-07-30 11:59:59');
    $users->saveOrFail($user);
    \Cake\I18n\DateTime::setTestNow('2026-07-30 12:00:00');
    try {
        $expiredCount = $users->find('auth')->where(['Users.id' => $user->id])->count();
    } finally {
        \Cake\I18n\DateTime::setTestNow();
    }

    $this->assertSame(1, $expiredCount);
}
```

- [ ] **Step 7: Verify finder RED**

Run only `testAuthFinderExcludesActiveLocksAndIncludesExpiredLocks`. Expected: FAIL with `Auth finder is not implemented`.

- [ ] **Step 8: Implement the finder**

Add imports and method to `UsersTable`:

```php
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;

public function findAuth(SelectQuery $query, array $options): SelectQuery
{
    return $query->where([
        'OR' => [
            $this->aliasField('locked_until') . ' IS' => null,
            $this->aliasField('locked_until') . ' <=' => DateTime::now(),
        ],
    ]);
}
```

- [ ] **Step 9: Verify all table tests are GREEN**

Run the whole `UsersTableTest` file and record the RED/GREEN evidence.

- [ ] **Step 10: Add the reusable fixture**

Create `tests/Fixture/UsersFixture.php`:

```php
<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\TestSuite\Fixture\TestFixture;

class UsersFixture extends TestFixture
{
    public string $table = 'users';

    public array $records = [];

    public function init(): void
    {
        $this->records = [[
            'id' => 1,
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => (new DefaultPasswordHasher())->hash('DemoPass123!'),
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'created' => '2026-07-30 12:00:00',
            'modified' => '2026-07-30 12:00:00',
        ]];
        parent::init();
    }
}
```

- [ ] **Step 11: Add the real-database demo seed**

Create `config/Seeds/DemoUserSeed.php`:

```php
<?php
declare(strict_types=1);

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Migrations\BaseSeed;

class DemoUserSeed extends BaseSeed
{
    public function run(): void
    {
        $this->table('users')->insert([
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => (new DefaultPasswordHasher())->hash('DemoPass123!'),
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'created' => date('Y-m-d H:i:s'),
            'modified' => date('Y-m-d H:i:s'),
        ])->saveData();
    }
}
```

- [ ] **Step 12: Commit the persistence slice**

```bash
git add config/Migrations/20260730000000_CreateUsers.php config/Seeds/DemoUserSeed.php src/Model/Table/UsersTable.php tests/Fixture/UsersFixture.php tests/TestCase/Model/Table/UsersTableTest.php docs/superpowers/tdd-evidence.md
git commit -m "feat: add users persistence model"
```

---

### Task 3: Login Throttle State Machine

**Files:**
- Create: `src/Service/LoginThrottleService.php`
- Create: `tests/TestCase/Service/LoginThrottleServiceTest.php`
- Modify: `docs/superpowers/tdd-evidence.md`

**Interfaces:**
- Consumes: `UsersTable`, `User`, and controllable `Cake\I18n\DateTime::now()`.
- Produces: `isLocked(User): bool`, `recordFailure(User): bool`, and `recordSuccess(User): void`.

- [ ] **Step 1: Create the service test with the first failing behavior**

Create `tests/TestCase/Service/LoginThrottleServiceTest.php`:

```php
<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Entity\User;
use App\Model\Table\UsersTable;
use App\Service\LoginThrottleService;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

class LoginThrottleServiceTest extends TestCase
{
    protected array $fixtures = ['app.Users'];

    private UsersTable $users;

    protected function setUp(): void
    {
        parent::setUp();
        DateTime::setTestNow('2026-07-30 12:00:00');
        $this->users = $this->getTableLocator()->get('Users');
    }

    protected function tearDown(): void
    {
        DateTime::setTestNow();
        parent::tearDown();
    }

    public function testFirstTwoFailuresIncrementWithoutLocking(): void
    {
        if (!class_exists(LoginThrottleService::class)) {
            $this->fail('LoginThrottleService is not implemented');
        }

        $service = new LoginThrottleService($this->users);
        $user = $this->john();

        $this->assertFalse($service->recordFailure($user));
        $this->assertFalse($service->recordFailure($user));

        $saved = $this->users->get(1);
        $this->assertSame(2, $saved->failed_login_attempts);
        $this->assertNull($saved->locked_until);
    }

    private function john(): User
    {
        return $this->users->get(1);
    }
}
```

- [ ] **Step 2: Verify the first throttle RED**

Run the first test. Expected: FAIL with `LoginThrottleService is not implemented`.

- [ ] **Step 3: Implement incrementing and lock inspection only**

Create `src/Service/LoginThrottleService.php`:

```php
<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\User;
use App\Model\Table\UsersTable;
use Cake\I18n\DateTime;

final class LoginThrottleService
{
    public const MAX_FAILURES = 3;
    public const LOCK_MINUTES = 5;

    public function __construct(private readonly UsersTable $users)
    {
    }

    public function isLocked(User $user): bool
    {
        return $user->locked_until !== null && $user->locked_until > DateTime::now();
    }

    public function recordFailure(User $user): bool
    {
        $user->failed_login_attempts = (int)$user->failed_login_attempts + 1;
        $this->users->saveOrFail($user);

        return false;
    }
}
```

- [ ] **Step 4: Verify first behavior GREEN**

Run the focused first test and record the evidence.

- [ ] **Step 5: Add the active-lock no-extension RED test**

```php
public function testActiveLockIsReturnedWithoutExtendingIt(): void
{
    $user = $this->john();
    $user->failed_login_attempts = 3;
    $user->locked_until = new DateTime('2026-07-30 12:05:00');
    $this->users->saveOrFail($user);

    $service = new LoginThrottleService($this->users);
    $this->assertTrue($service->recordFailure($user));

    $saved = $this->users->get(1);
    $this->assertSame(3, $saved->failed_login_attempts);
    $this->assertEquals(new DateTime('2026-07-30 12:05:00'), $saved->locked_until);
}
```

Run only this test. Expected: FAIL because the count becomes four and `recordFailure()` returns false.

- [ ] **Step 6: Add the minimal active-lock guard**

Add at the start of `recordFailure()`:

```php
if ($this->isLocked($user)) {
    return true;
}
```

Run the active-lock test again. Expected: PASS without changing `locked_until`.

- [ ] **Step 7: Add the third-failure RED test**

```php
public function testThirdFailureCreatesFiveMinuteLock(): void
{
    $service = new LoginThrottleService($this->users);
    $user = $this->john();

    $service->recordFailure($user);
    $service->recordFailure($user);
    $this->assertTrue($service->recordFailure($user));

    $saved = $this->users->get(1);
    $this->assertSame(3, $saved->failed_login_attempts);
    $this->assertEquals(new DateTime('2026-07-30 12:05:00'), $saved->locked_until);
}
```

Run only this test. Expected: FAIL because `recordFailure()` still returns false and `locked_until` remains null.

- [ ] **Step 8: Add the minimal third-failure transition**

Insert before saving in `recordFailure()`:

```php
if ($user->failed_login_attempts >= self::MAX_FAILURES) {
    $user->locked_until = DateTime::now()->addMinutes(self::LOCK_MINUTES);
}
```

Change the final return to:

```php
return $this->isLocked($user);
```

Run the third-failure test again. Expected: PASS.

- [ ] **Step 9: Add the expired-lock RED test**

```php
public function testExpiredLockStartsANewFailureSequence(): void
{
    $user = $this->john();
    $user->failed_login_attempts = 3;
    $user->locked_until = new DateTime('2026-07-30 11:59:59');
    $this->users->saveOrFail($user);

    $service = new LoginThrottleService($this->users);
    $this->assertFalse($service->recordFailure($user));

    $saved = $this->users->get(1);
    $this->assertSame(1, $saved->failed_login_attempts);
    $this->assertNull($saved->locked_until);
}
```

Run only this test. Expected: FAIL because the old count increments to four and creates a new lock.

- [ ] **Step 10: Reset expired state before incrementing**

Add at the start of `recordFailure()`, after the active-lock guard:

```php
if ($user->locked_until !== null && $user->locked_until <= DateTime::now()) {
    $user->failed_login_attempts = 0;
    $user->locked_until = null;
}
```

Run the expired-lock test again. Expected: PASS.

- [ ] **Step 11: Add the successful-login reset RED test**

```php
public function testSuccessClearsFailureAndLockState(): void
{
    $user = $this->john();
    $user->failed_login_attempts = 3;
    $user->locked_until = new DateTime('2026-07-30 11:59:59');
    $this->users->saveOrFail($user);

    $service = new LoginThrottleService($this->users);
    if (!method_exists($service, 'recordSuccess')) {
        $this->fail('recordSuccess is not implemented');
    }
    $service->recordSuccess($user);

    $saved = $this->users->get(1);
    $this->assertSame(0, $saved->failed_login_attempts);
    $this->assertNull($saved->locked_until);
}
```

Run only this test. Expected: FAIL with `recordSuccess is not implemented`.

- [ ] **Step 12: Implement success reset**

Add to the service:

```php
public function recordSuccess(User $user): void
{
    $user->failed_login_attempts = 0;
    $user->locked_until = null;
    $this->users->saveOrFail($user);
}
```

- [ ] **Step 13: Verify the complete service GREEN and refactor check**

Run:

```bash
vendor/bin/phpunit tests/TestCase/Service/LoginThrottleServiceTest.php
```

Expected: all five tests pass with no warnings. Record all observed cycles.

- [ ] **Step 14: Commit the throttle slice**

```bash
git add src/Service/LoginThrottleService.php tests/TestCase/Service/LoginThrottleServiceTest.php docs/superpowers/tdd-evidence.md
git commit -m "feat: add login throttle state machine"
```

---

### Task 4: Public Login Page

**Files:**
- Create: `src/Controller/UsersController.php`
- Create: `templates/Users/login.php`
- Create: `webroot/css/login.css`
- Create: `tests/TestCase/Controller/UsersControllerTest.php`
- Modify: `config/routes.php:52-79`
- Modify: `docs/superpowers/tdd-evidence.md`

**Interfaces:**
- Consumes: CakePHP Form/Flash helpers.
- Produces: named `/login` route and a GET-rendered login form; POST behavior remains intentionally absent until Task 5.

- [ ] **Step 1: Write the failing page test**

Create `tests/TestCase/Controller/UsersControllerTest.php`:

```php
<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class UsersControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Users'];

    public function testLoginPageRendersForm(): void
    {
        $this->get('/login');

        $this->assertResponseOk();
        $this->assertResponseContains('Sign in');
        $this->assertResponseContains('name="email"');
        $this->assertResponseContains('name="password"');
    }
}
```

- [ ] **Step 2: Verify page RED**

Run the focused test. Expected: FAIL because `/login` is not routed/rendered.

- [ ] **Step 3: Add the route and minimal controller**

Inside the existing root scope in `config/routes.php`, before fallbacks:

```php
$builder->connect('/login', ['controller' => 'Users', 'action' => 'login'])
    ->setMethods(['GET', 'POST']);
```

Create `src/Controller/UsersController.php`:

```php
<?php
declare(strict_types=1);

namespace App\Controller;

class UsersController extends AppController
{
    public function login(): void
    {
        $this->request->allowMethod(['get', 'post']);
    }
}
```

- [ ] **Step 4: Add the minimal login template**

Create `templates/Users/login.php`:

```php
<?php
/** @var \App\View\AppView $this */
$this->assign('title', 'Sign in');
$this->Html->css('login', ['block' => true]);
?>
<main class="login-shell">
    <section class="login-card" aria-labelledby="login-title">
        <h1 id="login-title">Sign in</h1>
        <p>Use the registered John demo account.</p>
        <?= $this->Form->create(null, ['url' => '/login']) ?>
        <?= $this->Form->control('email', ['type' => 'email', 'required' => true]) ?>
        <?= $this->Form->control('password', ['type' => 'password', 'required' => true]) ?>
        <?= $this->Form->button('Sign in', ['class' => 'button']) ?>
        <?= $this->Form->end() ?>
    </section>
</main>
```

Create `webroot/css/login.css`:

```css
.login-shell {
    display: grid;
    min-height: 70vh;
    place-items: center;
}

.login-card {
    background: #fff;
    border: 1px solid #dfe3e8;
    border-radius: 12px;
    box-shadow: 0 12px 32px rgb(31 41 55 / 10%);
    max-width: 440px;
    padding: 2.5rem;
    width: 100%;
}
```

- [ ] **Step 5: Verify page GREEN and preserve the full baseline**

Run the focused controller test, then `composer test`. Record the RED/GREEN output.

- [ ] **Step 6: Commit the page slice**

```bash
git add config/routes.php src/Controller/UsersController.php templates/Users/login.php webroot/css/login.css tests/TestCase/Controller/UsersControllerTest.php docs/superpowers/tdd-evidence.md
git commit -m "feat: add public login page"
```

---

### Task 5: Authentication Middleware, Successful Login, and Dashboard

**Files:**
- Modify: `src/Application.php:19-100`
- Modify: `src/Controller/AppController.php:40-51`
- Modify: `src/Controller/PagesController.php:17-52`
- Modify: `src/Controller/UsersController.php`
- Modify: `config/routes.php:52-79`
- Create: `src/Controller/DashboardController.php`
- Create: `templates/Dashboard/index.php`
- Create: `tests/TestCase/Controller/DashboardControllerTest.php`
- Modify: `tests/TestCase/Controller/UsersControllerTest.php`
- Modify: `docs/superpowers/tdd-evidence.md`

**Interfaces:**
- Consumes: `UsersTable::findAuth()`, `LoginThrottleService::recordSuccess()`, and Authentication Form/Session authenticators.
- Produces: a persisted identity, `/dashboard`, and unauthenticated redirect to `/login`.

- [ ] **Step 1: Add failing valid-login and dashboard-protection tests**

Add to `UsersControllerTest`:

```php
public function testValidCredentialsRedirectToDashboardAndPersistIdentity(): void
{
    $this->enableCsrfToken();
    $this->post('/login', [
        'email' => 'john@example.com',
        'password' => 'DemoPass123!',
    ]);

    $this->assertRedirect('/dashboard');

    $this->get('/dashboard');
    $this->assertResponseOk();
    $this->assertResponseContains('Welcome, John');
}
```

Create `tests/TestCase/Controller/DashboardControllerTest.php`:

```php
<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class DashboardControllerTest extends TestCase
{
    use IntegrationTestTrait;

    public function testUnauthenticatedRequestRedirectsToLogin(): void
    {
        $this->get('/dashboard');

        $this->assertRedirectContains('/login');
    }
}
```

- [ ] **Step 2: Verify both integration tests RED**

Run the two focused files. Expected: valid login does not redirect/persist, and dashboard does not perform the required login redirect.

- [ ] **Step 3: Configure Authentication service and middleware**

In `src/Application.php`, add imports:

```php
use Authentication\AuthenticationService;
use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Authentication\Middleware\AuthenticationMiddleware;
use Cake\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;
```

Change the class declaration:

```php
class Application extends BaseApplication implements AuthenticationServiceProviderInterface
```

Place Authentication middleware after CSRF so an invalid CSRF request cannot produce a persisted login identity:

```php
->add(new BodyParserMiddleware())
->add(new CsrfProtectionMiddleware([
    'httponly' => true,
]))
->add(new AuthenticationMiddleware($this));
```

Add to `Application`:

```php
public function getAuthenticationService(
    ServerRequestInterface $request,
): AuthenticationServiceInterface {
    $service = new AuthenticationService([
        'unauthenticatedRedirect' => Router::url('/login'),
        'queryParam' => 'redirect',
    ]);
    $fields = ['username' => 'email', 'password' => 'password'];

    $service->loadIdentifier('Authentication.Password', [
        'fields' => $fields,
        'resolver' => [
            'className' => 'Authentication.Orm',
            'userModel' => 'Users',
            'finder' => 'auth',
        ],
    ]);
    $service->loadAuthenticator('Authentication.Session');
    $service->loadAuthenticator('Authentication.Form', [
        'fields' => $fields,
        'loginUrl' => Router::url('/login'),
    ]);

    return $service;
}
```

- [ ] **Step 4: Load the component globally and allow login publicly**

Add in `AppController::initialize()` after Flash:

```php
$this->loadComponent('Authentication.Authentication', [
    'logoutRedirect' => '/login',
]);
```

Add imports and `beforeFilter()` to `UsersController`:

```php
use Cake\Event\EventInterface;

public function beforeFilter(EventInterface $event): void
{
    parent::beforeFilter($event);
    $this->Authentication->allowUnauthenticated(['login']);
}
```

- [ ] **Step 5: Implement the success path only**

Replace `UsersController::login()` with:

```php
public function login(): ?\Cake\Http\Response
{
    $this->request->allowMethod(['get', 'post']);
    $result = $this->Authentication->getResult();

    if ($result->isValid()) {
        $user = $result->getData();
        if ($user instanceof \App\Model\Entity\User) {
            $users = $this->fetchTable('Users');
            (new \App\Service\LoginThrottleService($users))->recordSuccess($user);
        }

        return $this->redirect('/dashboard');
    }

    return null;
}
```

- [ ] **Step 6: Add dashboard route, controller, and template**

Add route:

```php
$builder->connect('/dashboard', ['controller' => 'Dashboard', 'action' => 'index'])
    ->setMethods(['GET']);
```

Create `src/Controller/DashboardController.php`:

```php
<?php
declare(strict_types=1);

namespace App\Controller;

class DashboardController extends AppController
{
    public function index(): void
    {
        $identity = $this->request->getAttribute('identity');
        $this->set('name', (string)$identity->get('name'));
    }
}
```

Create `templates/Dashboard/index.php`:

```php
<?php
/** @var \App\View\AppView $this */
/** @var string $name */
$this->assign('title', 'Dashboard');
?>
<main class="container">
    <h1>Welcome, <?= h($name) ?></h1>
    <p>Your account is authenticated.</p>
</main>
```

- [ ] **Step 7: Verify the new authentication behavior GREEN, then expose the existing-page regression RED**

Run the two focused controller test files. Expected: valid login redirects and remains authenticated on the next request; anonymous dashboard access redirects to login.

Then run:

```bash
vendor/bin/phpunit tests/TestCase/Controller/PagesControllerTest.php
```

Expected: existing public page tests FAIL by redirecting to `/login`, proving the global component changed an unrelated baseline behavior.

- [ ] **Step 8: Preserve the skeleton's public Pages behavior**

Add the import and callback to `PagesController`:

```php
use Cake\Event\EventInterface;

public function beforeFilter(EventInterface $event): void
{
    parent::beforeFilter($event);
    $this->Authentication->allowUnauthenticated(['display']);
}
```

- [ ] **Step 9: Verify the complete controller and baseline GREEN**

Run the Users, Dashboard, and Pages controller test files, then `composer test`. Expected: all tests pass. Record the authentication GREEN, Pages regression RED, and restored GREEN evidence.

- [ ] **Step 10: Commit the authentication slice**

```bash
git add src/Application.php src/Controller/AppController.php src/Controller/PagesController.php src/Controller/UsersController.php src/Controller/DashboardController.php config/routes.php templates/Dashboard/index.php tests/TestCase/Controller/UsersControllerTest.php tests/TestCase/Controller/DashboardControllerTest.php docs/superpowers/tdd-evidence.md
git commit -m "feat: authenticate users into dashboard"
```

---

### Task 6: Generic Failure and Temporary Lock Responses

**Files:**
- Modify: `src/Controller/UsersController.php`
- Modify: `tests/TestCase/Controller/UsersControllerTest.php`
- Modify: `docs/superpowers/tdd-evidence.md`

**Interfaces:**
- Consumes: Authentication result plus all `LoginThrottleService` methods.
- Produces: HTTP 401 generic failure and HTTP 429 active/new lock behavior without lock extension.

- [ ] **Step 1: Add failing ordinary-invalid tests**

Add to `UsersControllerTest`:

```php
public function testWrongPasswordReturnsGeneric401AndRecordsFailure(): void
{
    $this->enableCsrfToken();
    $this->post('/login', [
        'email' => 'john@example.com',
        'password' => 'wrong-password',
    ]);

    $this->assertResponseCode(401);
    $this->assertResponseContains('Invalid email or password.');
    $user = $this->getTableLocator()->get('Users')->get(1);
    $this->assertSame(1, $user->failed_login_attempts);
}

public function testUnknownEmailUsesTheSameGeneric401(): void
{
    $this->enableCsrfToken();
    $this->post('/login', [
        'email' => 'nobody@example.com',
        'password' => 'wrong-password',
    ]);

    $this->assertResponseCode(401);
    $this->assertResponseContains('Invalid email or password.');
    $this->assertResponseNotContains('not registered');
}
```

- [ ] **Step 2: Verify ordinary-invalid RED**

Run those two tests. Expected: FAIL because the controller currently renders a normal 200 response without the generic message or persisted failure.

- [ ] **Step 3: Implement only generic 401 and one-failure persistence**

Add to `UsersController`:

```php
private function renderLoginError(string $message, int $status): void
{
    $this->Flash->error($message);
    $this->response = $this->response->withStatus($status);
}
```

Replace `login()` with this intermediate implementation:

```php
public function login(): ?\Cake\Http\Response
{
    $this->request->allowMethod(['get', 'post']);
    $result = $this->Authentication->getResult();

    if ($result->isValid()) {
        $user = $result->getData();
        if ($user instanceof \App\Model\Entity\User) {
            $users = $this->fetchTable('Users');
            (new \App\Service\LoginThrottleService($users))->recordSuccess($user);
        }

        return $this->redirect('/dashboard');
    }

    if ($this->request->is('post')) {
        $users = $this->fetchTable('Users');
        $email = (string)$this->request->getData('email');
        $user = $users->find()->where(['email' => $email])->first();
        if ($user instanceof \App\Model\Entity\User) {
            (new \App\Service\LoginThrottleService($users))->recordFailure($user);
        }
        $this->renderLoginError('Invalid email or password.', 401);
    }

    return null;
}
```

Run the two ordinary-invalid tests. Expected: PASS.

- [ ] **Step 4: Add failing lock response tests**

```php
public function testThirdFailureReturns429AndLocksAccount(): void
{
    for ($attempt = 1; $attempt <= 3; $attempt++) {
        $this->enableCsrfToken();
        $this->post('/login', [
            'email' => 'john@example.com',
            'password' => 'wrong-password',
        ]);
    }

    $this->assertResponseCode(429);
    $this->assertResponseContains('Too many failed attempts. Please try again later.');
    $user = $this->getTableLocator()->get('Users')->get(1);
    $this->assertSame(3, $user->failed_login_attempts);
    $this->assertNotNull($user->locked_until);
}

public function testCorrectPasswordIsRejectedDuringLockWithoutExtendingIt(): void
{
    $users = $this->getTableLocator()->get('Users');
    $user = $users->get(1);
    $user->failed_login_attempts = 3;
    $user->locked_until = new \Cake\I18n\DateTime('2026-07-30 12:05:00');
    $users->saveOrFail($user);
    \Cake\I18n\DateTime::setTestNow('2026-07-30 12:00:00');

    try {
        $this->enableCsrfToken();
        $this->post('/login', [
            'email' => 'john@example.com',
            'password' => 'DemoPass123!',
        ]);
    } finally {
        \Cake\I18n\DateTime::setTestNow();
    }

    $this->assertResponseCode(429);
    $this->assertResponseContains('Too many failed attempts. Please try again later.');
    $this->assertEquals(
        new \Cake\I18n\DateTime('2026-07-30 12:05:00'),
        $users->get(1)->locked_until,
    );
}
```

- [ ] **Step 5: Verify lock response RED**

Run the two lock tests. Expected: FAIL because the intermediate controller always returns the ordinary 401 message/status.

- [ ] **Step 6: Branch on active/new lock without extending it**

Within the POST block, create the service and replace the failure handling with:

```php
$throttle = new \App\Service\LoginThrottleService($users);
if ($user instanceof \App\Model\Entity\User && $throttle->isLocked($user)) {
    $this->renderLoginError(
        'Too many failed attempts. Please try again later.',
        429,
    );

    return null;
}

$becameLocked = $user instanceof \App\Model\Entity\User
    ? $throttle->recordFailure($user)
    : false;

if ($becameLocked) {
    $this->renderLoginError(
        'Too many failed attempts. Please try again later.',
        429,
    );
} else {
    $this->renderLoginError('Invalid email or password.', 401);
}
```

- [ ] **Step 7: Verify all failure tests GREEN**

Run the entire `UsersControllerTest` and `LoginThrottleServiceTest`; then run `composer test`. Record observed evidence.

- [ ] **Step 8: Commit the failure/lock slice**

```bash
git add src/Controller/UsersController.php tests/TestCase/Controller/UsersControllerTest.php docs/superpowers/tdd-evidence.md
git commit -m "feat: enforce login failure lockout"
```

---

### Task 7: Pre-Authentication Email Normalization

**Files:**
- Create: `src/Middleware/NormalizeLoginEmailMiddleware.php`
- Modify: `src/Application.php:89-98`
- Modify: `tests/TestCase/Controller/UsersControllerTest.php`
- Modify: `docs/superpowers/tdd-evidence.md`

**Interfaces:**
- Consumes: PSR-15 request/handler interfaces and parsed login form body.
- Produces: a login request whose `email` is trimmed/lowercased before Authentication identifies it.

- [ ] **Step 1: Write the failing normalized-login test**

```php
public function testLoginNormalizesEmailBeforeAuthentication(): void
{
    $this->enableCsrfToken();
    $this->post('/login', [
        'email' => ' JOHN@EXAMPLE.COM ',
        'password' => 'DemoPass123!',
    ]);

    $this->assertRedirect('/dashboard');
}
```

- [ ] **Step 2: Verify normalization RED**

Run only this test. Expected: FAIL with an invalid-credential response because Form authentication receives the unnormalized email.

- [ ] **Step 3: Implement the focused normalization middleware**

Create `src/Middleware/NormalizeLoginEmailMiddleware.php`:

```php
<?php
declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class NormalizeLoginEmailMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        if ($request->getMethod() === 'POST' && $request->getUri()->getPath() === '/login') {
            $data = $request->getParsedBody();
            if (is_array($data) && array_key_exists('email', $data)) {
                $data['email'] = mb_strtolower(trim((string)$data['email']));
                $request = $request->withParsedBody($data);
            }
        }

        return $handler->handle($request);
    }
}
```

- [ ] **Step 4: Register it in the only valid position**

Import it in `Application` and place it after `BodyParserMiddleware`, before both CSRF and Authentication. The final security order is parse body → normalize email → validate CSRF → authenticate:

```php
use App\Middleware\NormalizeLoginEmailMiddleware;

->add(new BodyParserMiddleware())
->add(new NormalizeLoginEmailMiddleware())
->add(new CsrfProtectionMiddleware([
    'httponly' => true,
]))
->add(new AuthenticationMiddleware($this))
```

- [ ] **Step 5: Verify normalization GREEN**

Run the focused test and the entire controller suite. Record the evidence.

- [ ] **Step 6: Commit the normalization slice**

```bash
git add src/Middleware/NormalizeLoginEmailMiddleware.php src/Application.php tests/TestCase/Controller/UsersControllerTest.php docs/superpowers/tdd-evidence.md
git commit -m "feat: normalize login emails before authentication"
```

---

### Task 8: POST Logout and Session Removal

**Files:**
- Modify: `src/Controller/UsersController.php`
- Modify: `templates/Dashboard/index.php`
- Modify: `config/routes.php:52-79`
- Modify: `tests/TestCase/Controller/UsersControllerTest.php`
- Modify: `docs/superpowers/tdd-evidence.md`

**Interfaces:**
- Consumes: Authentication component identity/session state.
- Produces: `POST /logout`, cleared identity, redirect to `/login`, and dashboard protection after logout.

- [ ] **Step 1: Write the failing logout integration test**

```php
public function testLogoutClearsIdentityAndProtectsDashboardAgain(): void
{
    $this->enableCsrfToken();
    $this->post('/login', [
        'email' => 'john@example.com',
        'password' => 'DemoPass123!',
    ]);
    $this->assertRedirect('/dashboard');

    $this->enableCsrfToken();
    $this->post('/logout');
    $this->assertRedirect('/login');

    $this->get('/dashboard');
    $this->assertRedirectContains('/login');
}
```

- [ ] **Step 2: Verify logout RED**

Run only the logout test. Expected: FAIL because the POST route/action does not exist and the session remains authenticated.

- [ ] **Step 3: Add logout route and action**

Add route:

```php
$builder->connect('/logout', ['controller' => 'Users', 'action' => 'logout'])
    ->setMethods(['POST']);
```

Add to `UsersController`:

```php
public function logout(): \Cake\Http\Response
{
    $this->request->allowMethod(['post']);
    $this->Authentication->logout();

    return $this->redirect('/login');
}
```

- [ ] **Step 4: Add the CSRF-protected dashboard logout form**

Append inside the dashboard `<main>`:

```php
<?= $this->Form->create(null, ['url' => '/logout']) ?>
<?= $this->Form->button('Log out', ['class' => 'button button-outline']) ?>
<?= $this->Form->end() ?>
```

- [ ] **Step 5: Verify logout GREEN**

Run the logout test, both controller test files, and `composer test`. Record observed results.

- [ ] **Step 6: Commit the logout slice**

```bash
git add src/Controller/UsersController.php templates/Dashboard/index.php config/routes.php tests/TestCase/Controller/UsersControllerTest.php docs/superpowers/tdd-evidence.md
git commit -m "feat: add secure logout flow"
```

---

### Task 9: Real Database, Browser Acceptance, and Final Verification

**Files:**
- Modify: `docs/superpowers/tdd-evidence.md`
- Verify: all files listed in this plan

**Interfaces:**
- Consumes: completed feature, local `my_app` MariaDB connection, CakePHP development server, and Playwright browser control.
- Produces: migrated/seeded local demo, browser acceptance evidence, and fresh quality/security evidence.

- [ ] **Step 1: Apply only pending migrations and seed John once**

Run:

```bash
bin/cake migrations status
bin/cake migrations migrate
bin/cake seeds run DemoUser
```

Expected: the users migration completes, the John row is inserted, and existing tables remain.

- [ ] **Step 2: Verify real database state without exposing password hashes**

Run a CakePHP `ConnectionManager` query or MariaDB query that reports only:

- `articles`, `categories`, and `users` still exist;
- exactly one `john@example.com` row exists;
- its `password` differs from `DemoPass123!`;
- `failed_login_attempts = 0` and `locked_until IS NULL`.

- [ ] **Step 3: Start or reuse the development server**

Run:

```bash
bin/cake server -H localhost -p 8765
```

If port 8765 is already owned by this application, reuse it instead of starting a duplicate.

- [ ] **Step 4: Browser-verify ordinary failure and lockout**

Using Playwright with a fresh snapshot before each interaction:

1. Open `http://localhost:8765/login`.
2. Submit `john@example.com` with a wrong password once; verify HTTP/UI generic invalid message.
3. Submit the wrong password twice more; verify the third response shows the rate-limit message.
4. Submit the correct demo password while locked; verify it remains rejected.

- [ ] **Step 5: Reset only the demo lock and verify success/logout**

Reset John's `failed_login_attempts` to `0` and `locked_until` to `NULL` through a scoped local SQL update. Then:

1. Log in with `john@example.com` / `DemoPass123!`.
2. Verify `/dashboard` shows `Welcome, John`.
3. Use the dashboard POST logout button.
4. Verify `/login` is shown and direct `/dashboard` access redirects back to login.

- [ ] **Step 6: Run the complete fresh verification gate**

Run:

```bash
composer test
composer cs-check
composer audit
composer validate --strict
```

Expected: all tests and assertions pass with zero failures/warnings, CodeSniffer exits 0, Composer finds no advisories, and the manifest is valid.

- [ ] **Step 7: Check acceptance coverage and working tree**

Review the approved design's three acceptance rows against actual tests and browser evidence. Run:

```bash
git diff --check
git status --short
git log --oneline --decorate -12
```

Expected: no whitespace errors; only intentional evidence/plan tracking changes remain.

- [ ] **Step 8: Finalize and commit observed evidence**

Update `docs/superpowers/tdd-evidence.md` with actual final command output summaries, test/assertion counts, HTTP outcomes, and browser routes. Do not include the database password or password hash.

```bash
git add docs/superpowers/tdd-evidence.md
git commit -m "docs: record login TDD verification"
```
