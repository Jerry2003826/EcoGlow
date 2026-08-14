# Test Cases — Eco Glow Lighting

Client-supplied acceptance test cases TC1–TC9 for the Contact Us feature, each mapped to
the automated test that actually covers it in this repository.

**Team:** FIT3047 team236 · **Last executed:** 10 August 2026

Every test case below has a stable anchor, so a Trello acceptance-checklist item can link
straight to the case it verifies — for example `docs/test-cases.md#tc4-verify-contact-form-valid-input`.

Where a case cannot be fully automated, it says so and gives the manual steps instead. A
test case marked *Manual* is not a gap in the product; it is a limit of what a server-side
test suite can observe. Section 6 lists the real gaps.

---

## 1. Test environment

| Item | Value |
| --- | --- |
| Framework | CakePHP 5.4.0 |
| Runtime | PHP 8.5.2 |
| Test runner | PHPUnit 13.2.5 |
| Application database | MySQL, schema `my_app` |
| Test database | MySQL, schema `test_myapp` (wiped and re-seeded from fixtures on every run) |
| Dev server | `bin/cake server -p 8765` → <http://localhost:8765> |
| Browser used for manual checks | Chrome 151 (headless, via DevTools Protocol) |
| Machine | Apple M4, macOS 26.5.2 |

The automated suite never touches the application database and never calls Google. It
runs against `test_myapp`, and reCAPTCHA verification is switched off or stubbed in every
test, so the suite is safe to run repeatedly and works offline.

---

## 2. Running the automated tests

From the repository root:

```bash
# The whole suite
composer test

# The same thing with a readable per-test list
vendor/bin/phpunit --testdox

# One test class
vendor/bin/phpunit tests/TestCase/Controller/ContactControllerTest.php

# One test method
vendor/bin/phpunit --filter testIndexPostCaptchaFailure

# Coding standard (not part of these test cases, but expected to pass before merging)
composer cs-check
```

To follow the manual steps you also need the site running:

```bash
bin/cake server -p 8765
```

At the last execution the suite was **50 tests / 190 assertions, all passing**.

One point worth recording for the mentor, because it shows the suite doing its job:
enabling HTTPS enforcement in production broke `PagesControllerTest::testMissingTemplate`.
That test turns debug off to check the production 404 page, and turning debug off is also
what mounts `HttpsEnforcerMiddleware` — so the request was answered with a 301 redirect to
HTTPS before routing ever ran. The fix was to mark the simulated request as already being
over HTTPS, which is how it arrives in production anyway.

---

## 3. Status legend

| Status | Meaning |
| --- | --- |
| **Automated** | A committed test in `tests/` fully covers the expected result. Re-run it with `composer test`. |
| **Partly automated** | A committed test covers the core behaviour; one part of the case still needs a human. |
| **Manual** | No committed test can cover this. The manual steps are written out in full. |
| **Pass / Fail** | Result of the most recent execution, dated above. |

---

## 4. Test cases

### TC1: Verify Page Load

**Objective** — Ensure the Contact Us page loads correctly.

**Preconditions** — Application deployed and reachable; database migrated.

**Steps**

1. Navigate to the Contact Us page.
2. Observe the load time and the page elements.

**Expected result** — The page loads within 3 seconds and all elements display correctly.

**Actual result — Pass.** All three public pages return HTTP 200 with their expected
content, and every measured load finished inside the 3-second budget. Median browser
`load` event: **0.44 s** for `/`, **0.88 s** for `/contact`, **0.44 s** for `/login`.
The slowest run in the reported batch was 1.05 s, and the slowest measurement recorded
at any point during testing — a one-off outlier that did not recur — was 1.88 s. Full
method, raw numbers and caveats are in
[Appendix A](#appendix-a-tc1-load-time-measurement).

**Automated coverage** — the *loads correctly* half of this case is automated; the
*within 3 seconds* half is measured manually (see Appendix A), because response time
depends on the server it runs on and is not a meaningful assertion inside a unit test.

| Page | Test |
| --- | --- |
| `/` (landing) | `PagesControllerTest::testDisplay` — `tests/TestCase/Controller/PagesControllerTest.php` |
| `/contact` | `ContactControllerTest::testIndexGet` — `tests/TestCase/Controller/ContactControllerTest.php` |
| `/login` | `UsersControllerTest::testLoginGet` — `tests/TestCase/Controller/UsersControllerTest.php` |

**Notes** — The `/contact` page is roughly twice as slow as the other two because it
loads Google's reCAPTCHA script from `https://www.google.com/recaptcha/api.js`. That
cost is unavoidable while reCAPTCHA v2 is a requirement, and it is still well inside
budget.

---

### TC2: Verify Responsiveness

**Objective** — Ensure the contact form is responsive across devices.

**Preconditions** — Site running; a browser with device emulation, or real devices.

**Steps**

1. Open the site on desktop, tablet and mobile.
2. Click through to Contact us.

**Expected result** — The page is responsive and fully functional on all devices.

**Actual result — Pass.** Checked at three viewports on all three pages. No horizontal
overflow anywhere (a page that overflows forces sideways scrolling on a phone, which is
the usual way a responsive layout fails):

| Viewport | `/` | `/contact` | `/login` |
| --- | --- | --- | --- |
| Mobile 390 × 844 | 0 px overflow | 0 px overflow | 0 px overflow |
| Tablet 768 × 1024 | 0 px overflow | 0 px overflow | 0 px overflow |
| Desktop 1440 × 900 | 0 px overflow | 0 px overflow | 0 px overflow |

**Automated coverage** — **Manual.** No committed test. Layout is a visual property of a
rendered page; PHPUnit only sees the HTML string, not how a browser paints it, so a
passing unit test here would prove nothing about responsiveness.

**Manual steps to reproduce**

1. Open <http://localhost:8765/contact> in Chrome.
2. Open DevTools (`F12`) → toggle the device toolbar (`Cmd/Ctrl + Shift + M`).
3. Select iPhone 12 (390 px), then iPad (768 px), then switch back to Responsive at
   1440 px.
4. At each width confirm: no horizontal scrollbar; the navigation bar collapses to a
   usable menu; form fields stack to full width instead of being cut off; the submit
   and Clear buttons are both reachable without zooming.

**Notes** — At ≥ 992 px (`col-lg-*`) the contact panel and the form sit side by side;
below that they stack. The measurements above were taken with Chrome device emulation,
which reproduces layout faithfully but not device-specific browser quirks — one pass on
a real iOS and a real Android handset is still worth doing before hand-over.

---

### TC3: Verify Contact Form Fields

**Objective** — Ensure all form fields are present and usable.

**Preconditions** — Contact page loaded.

**Steps**

1. Check that the Name, Email, Subject and Message fields exist.
2. Cross-check the fields against the wireframe.

**Expected result** — All fields are present and accept input correctly.

**Actual result — Pass (with one step outstanding).** All required fields render, plus an
optional Phone field. Each carries a maximum length matching its database column, and the
four mandatory fields carry the HTML `required` attribute:

| Field | Present | Required | Max length |
| --- | --- | --- | --- |
| Name | yes | yes | 128 |
| Email | yes | yes | 255 |
| Phone | yes | no (optional) | 32 |
| Subject | yes | yes | 255 |
| Message (textarea) | yes | yes | 65535 |

Step 2, the wireframe cross-check, **has not been done** — the wireframe is a BA
deliverable that does not exist yet. See section 6.

**Automated coverage — Partly automated.** Step 1 is automated; step 2, the wireframe
cross-check, is the part still waiting on a human, and on a wireframe existing.

| Test | What it proves |
| --- | --- |
| `ContactControllerTest::testIndexGet` | The page renders, and each of Name, Email, Subject and Message is present as a real control with a `<label for>` bound to it. |
| `ContactControllerTest::testIndexPostSuccess` | All five fields accept input and are stored — it submits every field and asserts the row is saved. |

**Notes** — `testIndexGet` was verified by mutation: deleting the Subject control from
`templates/Contact/index.php` turns it red with *"The contact form is missing its Subject
control"*, and the control was restored afterwards. Before that assertion existed the same
deletion left the whole suite green.

---

### TC4: Verify Contact Form valid input

**Objective** — Ensure the contact form works.

**Preconditions** — Contact page loaded; reCAPTCHA solvable (or disabled for testing).

**Steps**

1. Fill in valid information.
2. Submit the form.
3. Confirm input validation runs.
4. Verify the submission confirmation.

**Expected result** — Submission succeeds, a confirmation message is shown, and the data
is stored in the database.

**Actual result — Pass.** Verified end-to-end against a local instance started with
`RECAPTCHA_ENABLED=false`, so the submission could complete without a human solving the
challenge. Submitting valid data returned `302 Found` with `Location: /contact`; the
follow-up request rendered the confirmation *"Thank you! Your message has been sent. We
will get back to you soon."*; and the row was written to `contact_messages` with
`is_read = 0`, so it shows as unread in the admin list. The test row was deleted
afterwards.

**The confirmation now looks like a confirmation.** Until now it did not, and this
document was quietly overstating the case: the manual step below has always told the
tester to look for a *green* confirmation, but nothing on the page was green.
`templates/element/flash/success.php` emits `<div class="message success">`, and
`.message` is styled only in `webroot/css/cake.css`, which this site's layout never
loads. Every flash therefore rendered as plain white body text pinned to the top-left
corner of the viewport, outside the page's content column — visually indistinguishable
from a stray paragraph. `webroot/css/site.css` now styles the banners itself, and the
layout renders them inside a `.container` so they line up with the content. As with the
field errors in TC5, the state does not depend on colour alone (WCAG 1.4.1):

| Cue | Treatment |
| --- | --- |
| Colour | Success in `--eg-success` `#5ce0a0` — **12.24:1** against the page background `rgb(5, 5, 8)` and **10.82:1** against the banner's own tinted fill `rgb(13, 25, 21)`, both measured from the rendered pixels. Error reuses `--eg-error` `#ff8a8a` at **8.97:1** and **8.13:1**. WCAG AA needs 4.5:1. |
| Glyph | ✔ success, ✖ error, ⚠ warning, ℹ info. This is the cue that does the real work: success and error sit only **1.36:1** apart in luminance, so with the page desaturated the two banners are near-identical greys and the glyph carries the entire distinction. Verified by screenshotting both under `grayscale(1)`. |
| Shape | A tinted, rounded panel with a 3 px left rule in the state colour, matching the glass cards used elsewhere, so a flash reads as a banner rather than as body copy. |
| Screen readers | `role="alert"` on error and warning, `role="status"` on success and info, so the message is announced rather than left to be discovered. |

Green rather than amber, for the same reason TC5's field errors are red rather than a
dimmed amber: amber is this site's brand colour and is already used for every link,
button and badge, so an amber success banner would read as ordinary page furniture
instead of as something that just happened.

**Automated coverage — Automated**

| Test | What it proves |
| --- | --- |
| `ContactControllerTest::testIndexPostSuccess` | A valid submission returns a redirect, shows the confirmation wording, stores exactly one row, and flags it unread. |
| `ContactControllerTest::testIndexPostIgnoresForgedAdminFields` | A submitter cannot forge `is_read` or `created` to hide a message from the admin's unread list. |
| `Admin\ContactMessagesControllerTest::testIndex` | The stored message is visible to a logged-in admin. |
| `Admin\ContactMessagesControllerTest::testViewMarksMessageAsRead` | Opening the message marks it read. |

All in `tests/TestCase/Controller/ContactControllerTest.php` and
`tests/TestCase/Controller/Admin/ContactMessagesControllerTest.php`.

**Notes** — `testIndexPostSuccess` asserts the confirmation wording itself, not just the
redirect, so the message TC4 asks for cannot quietly disappear or change meaning.

**Manual end-to-end check** — On a normal deployment reCAPTCHA is enabled, so submitting
the form by hand also requires solving the challenge; that combined path is TC7. To
exercise TC4 on its own, start the server with verification switched off:

```bash
RECAPTCHA_ENABLED=false bin/cake server -p 8766
```

Then submit the form at <http://localhost:8766/contact> and confirm the green
confirmation message appears. Delete the resulting row from `/admin/contact-messages`
afterwards so it does not mix in with real enquiries.

---

### TC5: Verify Contact Form invalid input

**Objective** — Ensure the contact form works.

**Steps**

1. Fill in invalid information.
2. Try each field in turn.
3. Leave one or more required fields empty.
4. Submit the form.

**Expected result** — Every field in error shows an error message with guidance on how to
resolve it, and the message stays visible until the user takes action.

**Actual result — Pass.** Submitting an empty form with a malformed email returns the page
with a per-field message directly beneath each offending input, and nothing is written to
the database:

| Field | Message shown |
| --- | --- |
| Name | This field cannot be left empty |
| Email | The provided value must be an e-mail address |
| Subject | This field cannot be left empty |
| Message | This field cannot be left empty |

Each message is rendered server-side into the returned HTML, so it persists until the
user edits and resubmits — it is not a transient popup. Each input is also correctly
wired for screen readers with `aria-invalid="true"` and `aria-describedby` pointing at
its message.

Over-length input is rejected as a friendly form error rather than a database crash:
without the length rules, a 256-character email or a 65 536-character message would
overflow its column and raise an HTTP 500.

**Errors also look like errors.** Until now they did not: the message text inherited the
ordinary body colour `rgb(240, 240, 248)` at 16 px — pixel-identical to normal paragraph
text — because `.error-message` is styled only in `webroot/css/cake.css`, which this
site's layout never loads. `webroot/css/site.css` now carries its own rules, and a failed
field is marked three separate ways so the state does not depend on colour alone
(WCAG 1.4.1):

| Cue | Treatment |
| --- | --- |
| Colour | Message text in `--eg-error` `#ff8a8a`, measured **8.55:1** against the `rgb(13, 13, 16)` form card it sits on — WCAG AA needs 4.5:1. |
| Weight and size | 600 weight at 14.4 px, against 400 weight at 16 px for body copy. |
| Glyph | A ⚠ leading the message, so the line reads as a problem in greyscale too. |
| The field itself | Border recoloured to `--eg-error` plus a 1 px inset ring — a 2 px outline without the field reflowing — over a faint `rgba(255, 138, 138, 0.09)` fill. |

Against body copy the error colour is only 2:1, which is exactly why the other three cues
are there. Focus is unaffected: a focused invalid field still draws its outer ring, in the
error colour rather than amber.

The page-level *"Your message could not be sent"* banner that accompanies these field
messages was unstyled for exactly the same reason, and has since been fixed the same way —
see TC4.

**Automated coverage — Automated**

| Test | What it proves |
| --- | --- |
| `ContactControllerTest::testIndexPostValidationFailure` | Empty and malformed input is rejected, the page-level error is shown, and no row is saved. |
| `ContactControllerTest::testIndexPostShowsPerFieldValidationErrors` | Each offending field carries its own message, wired to that field by `aria-describedby`; the optional Phone field is not flagged. |
| `ContactControllerTest::testIndexPostRejectsOverlongFields` | Over-length email and message are caught by validation instead of hitting the database. |

**Notes** — The per-field test is what makes this case real rather than nominal: it fails
if a field loses its message, if a message loses its `id`, or if the message stops being
associated with its input. Verified by mutation alongside TC3 — deleting the Subject
control turns it red with *"The subject field has no error message of its own"*.

---

### TC6: Verify CAPTCHA Presence

**Objective** — Ensure a CAPTCHA is present on the form.

**Steps**

1. Scroll to the bottom of the form.
2. Confirm the CAPTCHA is present.

**Expected result** — The CAPTCHA is visible and interactive.

**Actual result — Pass.** Confirmed in a real browser, not just in the HTML source. The
page ships the widget container `<span class="g-recaptcha d-block" data-sitekey="…">` and
loads `https://www.google.com/recaptcha/api.js`; Google's script then replaces the
container with the live checkbox:

| Check | Result |
| --- | --- |
| Widget container in the markup | present |
| reCAPTCHA iframe actually rendered | yes |
| Rendered size | 304 × 78 px (visible, non-zero) |
| Frame type | `api2/anchor` — the "I'm not a robot" checkbox |
| Position | between the Message field and the submit row |
| `grecaptcha` API object available | yes |

If the site key is missing, the page shows an explicit warning instead of silently
rendering no CAPTCHA — a misconfigured deployment is visible rather than quietly
unprotected.

**Automated coverage — Partly automated.** *Present* is automated; *interactive* is not,
because that half is Google's script painting an iframe and only a real browser can see it.

| Test | What it proves |
| --- | --- |
| `ContactControllerTest::testIndexGetRendersCaptchaWhenEnabled` | With reCAPTCHA on and a site key configured, the page ships the `g-recaptcha` container carrying that key and loads Google's `api.js` — and does not fall through to the missing-key notice. |

The rest of the suite sets `Recaptcha.enabled = false` so it never calls Google, which is
why the widget is invisible to every other test. This one case turns it back on with a
stand-in site key; rendering the container is pure markup, so it still makes no network
call. `setUp()` snapshots the whole `Recaptcha` config and `tearDown()` puts it back, so
the override cannot leak into another test.

**Manual steps to reproduce** (still worth running — this is the *interactive* half)

1. Ensure `Recaptcha.enabled` is true and `Recaptcha.sitekey` is set in
   `config/app_local.php` (or via the `RECAPTCHA_*` environment variables).
2. Open <http://localhost:8765/contact>.
3. Scroll to just above the *Light up my inbox* button.
4. Confirm the "I'm not a robot" checkbox is rendered and responds to a click.

---

### TC7: Verify CAPTCHA Functionality

**Objective** — Ensure the CAPTCHA works correctly.

**Steps**

1. Complete the CAPTCHA challenge.
2. Submit the form.

**Expected result** — With the CAPTCHA correctly completed, the form submits successfully
and a confirmation message is displayed.

**Actual result — Not yet executed.** This is the one case in the set that has not been
run end-to-end, because it needs a human to tick a real "I'm not a robot" checkbox; that
cannot be scripted, which is the entire point of a CAPTCHA. **A team member should run the
manual steps below and record the result before hand-over.**

Every component of the path has been verified individually, so the case is expected to
pass — but "expected to pass" is not the same as executed, and it is recorded here as
outstanding rather than quietly marked green:

| Part of the path | How it was verified |
| --- | --- |
| The checkbox renders and is interactive | TC6 — confirmed in a real browser |
| A success reply from Google is accepted | `RecaptchaVerifierTest::testSuccessfulVerification` (Google's API stubbed) |
| A verified submission saves and confirms | TC4 — run end-to-end with verification disabled |

What is untested is only the join between them: a genuine token, issued by Google to a
real user, being accepted by the live application.

**Automated coverage — Manual, with the underlying logic automated.** This case cannot be
fully automated by design: reCAPTCHA exists precisely to be unsolvable by a script, so
any test that "passes the CAPTCHA" would either need Google's universal test key (refused
in production by this application) or would be stubbing out the very thing under test.

What *is* automated is the verification logic the challenge feeds into:

| Test | What it proves |
| --- | --- |
| `RecaptchaVerifierTest::testSuccessfulVerification` | A success response from Google's `siteverify` API is accepted. The API call is stubbed, so no network is needed. |
| `RecaptchaVerifierTest::testDisabledAlwaysPasses` | With verification disabled, tokens pass without a network call. |
| `ContactControllerTest::testIndexPostSuccess` | The controller's *CAPTCHA passed* branch saves the message. |

`tests/TestCase/Service/RecaptchaVerifierTest.php`.

**Manual steps to execute** (still outstanding — record the result here once run)

1. Open <http://localhost:8765/contact>.
2. Fill in valid Name, Email, Subject and Message.
3. Tick the "I'm not a robot" checkbox and complete any image challenge.
4. Click *Light up my inbox*.
5. Confirm the green confirmation message appears and the form is cleared.
6. Log in at `/login` and confirm the message is listed at `/admin/contact-messages`.
7. **Clean up:** delete the test message from the admin list so it does not pollute real
   enquiries.

---

### TC8: Verify Form Submission with Invalid CAPTCHA

**Objective** — Ensure the form is not submitted when the CAPTCHA is wrong.

**Steps**

1. Fill in valid data.
2. Enter an incorrect CAPTCHA.
3. Submit the form.

**Expected result** — The form is not submitted and a CAPTCHA error message is displayed.

**Actual result — Pass.** A submission carrying a missing or rejected CAPTCHA token is
refused with *"Please complete the CAPTCHA to prove you are human."* and nothing is
written to the database. The values the user typed are preserved in the form so they do
not have to retype them.

**Automated coverage — Automated.** This is the best-covered case in the set, because it
is the security-relevant half of the CAPTCHA pair and every failure mode can be
reproduced offline.

| Test | What it proves |
| --- | --- |
| `ContactControllerTest::testIndexPostCaptchaFailure` | A failed CAPTCHA shows the error and saves nothing. |
| `RecaptchaVerifierTest::testGoogleRejectionReturnsFalse` | An explicit rejection from Google is treated as failure. |
| `RecaptchaVerifierTest::testEmptyTokenFailsClosed` | A submission with no token at all is rejected — i.e. a bot posting straight to the endpoint and skipping the widget. |
| `RecaptchaVerifierTest::testEmptySecretFailsClosed` | A deployment with no secret configured rejects everything rather than silently accepting everything. |
| `RecaptchaVerifierTest::testMalformedResponseFailsClosed` | A non-JSON or broken reply from Google is treated as failure, not success. |
| `RecaptchaVerifierTest::testTestSecretRefusedInProduction` | Google's universal test key — which accepts any token — is refused once debug mode is off, so test keys cannot be shipped to production by accident. |

**Notes for the Cyber reviewers** — the last four all verify *fail-closed* behaviour: every
error path denies the submission. The common CAPTCHA bypass is a misconfiguration that
turns verification into a no-op, and those tests are what stop it.

---

### TC9: Verify Form Reset

**Objective** — Ensure the reset button clears all fields.

**Steps**

1. Fill in data.
2. Click the reset button.

**Expected result** — All fields are cleared.

**Actual result — Pass.** The button is labelled **Clear** and sits next to the submit
button. With all five fields populated, one click empties every one of them:

| Field | Before | After clicking Clear |
| --- | --- | --- |
| Name | `Test Visitor` | *(empty)* |
| Email | `test@example.com` | *(empty)* |
| Phone | `0400000000` | *(empty)* |
| Subject | `Reset check` | *(empty)* |
| Message | `This text should disappear after reset.` | *(empty)* |

**Automated coverage — Manual.** No committed test. Reset is native browser behaviour
from `<button type="reset">`; it never reaches the server, so a server-side test cannot
observe it. Verifying it would need a browser automation tool (Cypress, Playwright or
Selenium), which this project does not currently use. Logged in section 6.

**Manual steps to reproduce**

1. Open <http://localhost:8765/contact>.
2. Type something into every field, including Message.
3. Click **Clear**.
4. Confirm all five fields are empty. Nothing is submitted and no message is saved.

---

## 5. Coverage summary

| # | Test case | Status | Result | Automated tests |
| --- | --- | --- | --- | --- |
| TC1 | Verify Page Load | Partly automated | Pass | `PagesControllerTest::testDisplay`, `ContactControllerTest::testIndexGet`, `UsersControllerTest::testLoginGet` |
| TC2 | Verify Responsiveness | Manual | Pass | — |
| TC3 | Verify Contact Form Fields | Partly automated | Pass | `ContactControllerTest::testIndexGet`, `ContactControllerTest::testIndexPostSuccess` |
| TC4 | Verify Contact Form valid input | Automated | Pass | `ContactControllerTest::testIndexPostSuccess`, `ContactControllerTest::testIndexPostIgnoresForgedAdminFields`, `Admin\ContactMessagesControllerTest::testIndex`, `Admin\ContactMessagesControllerTest::testViewMarksMessageAsRead` |
| TC5 | Verify Contact Form invalid input | Automated | Pass | `ContactControllerTest::testIndexPostValidationFailure`, `ContactControllerTest::testIndexPostShowsPerFieldValidationErrors`, `ContactControllerTest::testIndexPostRejectsOverlongFields` |
| TC6 | Verify CAPTCHA Presence | Partly automated | Pass | `ContactControllerTest::testIndexGetRendersCaptchaWhenEnabled` |
| TC7 | Verify CAPTCHA Functionality | Manual | **Not yet executed** | `RecaptchaVerifierTest::testSuccessfulVerification`, `RecaptchaVerifierTest::testDisabledAlwaysPasses` (supporting logic only) |
| TC8 | Verify Form Submission with Invalid CAPTCHA | Automated | Pass | `ContactControllerTest::testIndexPostCaptchaFailure` plus five `RecaptchaVerifierTest` cases |
| TC9 | Verify Form Reset | Manual | Pass | — |

**Totals** — 8 of 9 executed, all 8 passing. **TC7 is outstanding** and needs a team member
to run it by hand before hand-over. By coverage: 3 fully automated, 3 partly automated,
3 manual.

The three remaining manual cases are manual for structural reasons rather than neglect:
TC2 and TC9 are layout and native browser behaviour that a server-side test cannot observe,
and TC7 needs a human to solve a challenge designed to exclude machines. The parts of TC1,
TC3 and TC6 that are still hand-checked are load timing, a wireframe that does not exist
yet, and Google's script painting its iframe. Adding Playwright or Cypress would close
TC2, TC9 and the interactive half of TC6 — see section 6.

---

## 6. Known gaps and follow-ups

Found while cross-checking each case against the actual suite. None of these change a
Pass to a Fail; they are places where a future regression could slip through.

### Closed since the previous execution

Six gaps have been closed. The first five were recorded in this section earlier; the sixth
was not on anyone's list — it surfaced while re-checking TC4 in a browser, because the
document was asking testers to confirm something that could not actually happen. They are
listed rather than deleted so the mentor can see what moved, and each one is described in
the test case it belongs to:

| Was | Now |
| --- | --- |
| No test asserted the form's fields exist (TC3) | `testIndexGet` asserts each control and its label, and was mutation-checked by deleting the Subject field. |
| No test asserted the CAPTCHA widget renders (TC6) | `testIndexGetRendersCaptchaWhenEnabled` enables reCAPTCHA with a stand-in key and asserts the container and script, then restores the config. |
| No test asserted the per-field validation messages (TC5) | `testIndexPostShowsPerFieldValidationErrors` asserts each message and its `aria-describedby` link, and that optional Phone is not flagged. |
| No test asserted the confirmation wording (TC4) | `testIndexPostSuccess` now asserts the flash text. |
| Validation errors were not visually distinguished (TC5) | `webroot/css/site.css` styles `.error-message` and `.form-error` — colour at 8.55:1, heavier weight, a ⚠ glyph and a thickened field outline. |
| Flash banners had no styling at all, so TC4's "green confirmation message" was neither green nor a banner | `webroot/css/site.css` styles `.message` and its `success` / `error` / `warning` variants — state colour at 8.13:1 or better, a per-state glyph that survives greyscale, and a glass panel the layout now renders inside the page's `.container`. |

### Still open

**Validation messages are framework defaults.** *"This field cannot be left empty"* states
the problem; TC5 asks for guidance on resolving it. They are adequate, but field-specific
wording (for example *"Enter an email address such as name@example.com"*) would meet the
requirement more convincingly.

**No browser-automation layer.** TC2 and TC9, and the *interactive* half of TC6, are
hand-checked because nothing here drives a real browser. Adding Playwright or Cypress would
let all three run in CI, would let TC7 be covered end-to-end using Google's test keys in a
non-production environment, and would let the field-error and flash styling described in
TC4 and TC5 be asserted rather than eyeballed. Both styling gaps were invisible to the
suite precisely because PHPUnit only ever sees the HTML string: every assertion about the
confirmation message passed happily while the message was rendering as unstyled text in
the corner of the page.

**Wireframe cross-check outstanding (TC3 step 2).** The wireframe is a BA deliverable and
does not exist yet, so field-by-field comparison against it has not been performed.

**TC7 not yet executed.** Needs a team member to solve a live CAPTCHA and submit the form
once, then record the result in this document. It is the only test case here without an
execution result.

---

## Appendix A: TC1 load-time measurement

### What "load time" means here

TC1 asks for a page that "loads within 3 seconds". That is an end-to-end, user-perceived
number, so it was measured two different ways. Reading only the first would badly
understate what a user experiences.

**Method 1 — `curl`, server response time.** How long the server takes to produce the
HTML. It excludes everything the browser does afterwards: parsing, stylesheets, fonts,
JavaScript, and third-party scripts such as reCAPTCHA. **These numbers are not the load
time a user perceives** — they are roughly 3–50× smaller. They are useful as a floor and
for spotting a slow server, nothing more.

**Method 2 — headless Chrome via the DevTools Protocol.** Real browser timings from the
Navigation Timing API, including every resource the page pulls in. `load` is the closest
available proxy for "the page has finished loading" and is the number TC1 should be judged
against.

Both were run against the local development server with the browser cache cleared before
each run and one warm-up discarded.

### Method 1 — `curl` server response time

10 requests per page after two warm-up requests:

```bash
for url in / /contact /login; do
  for i in $(seq 1 10); do
    curl -s -o /dev/null -w "%{time_total}\n" "http://localhost:8765$url"
  done
done
```

| Page | Min | **Median** | Max | Page size |
| --- | --- | --- | --- | --- |
| `/` | 14.2 ms | **14.9 ms** | 45.3 ms | 15.1 KB |
| `/contact` | 15.9 ms | **16.7 ms** | 28.3 ms | 9.8 KB |
| `/login` | 15.1 ms | **16.6 ms** | 42.3 ms | 5.7 KB |

### Method 2 — headless Chrome, browser timings

12 runs per page, cache cleared before each:

| Page | Metric | Min | **Median** | Max |
| --- | --- | --- | --- | --- |
| `/` | Time to first byte | 15.0 ms | **17.2 ms** | 22.3 ms |
| | DOMContentLoaded | 212.9 ms | **214.4 ms** | 219.9 ms |
| | **Load event** | 434.9 ms | **437.9 ms** | 453.5 ms |
| `/contact` | Time to first byte | 17.2 ms | **18.6 ms** | 27.8 ms |
| | DOMContentLoaded | 214.2 ms | **216.3 ms** | 231.6 ms |
| | **Load event** | 859.8 ms | **875.4 ms** | 1051.1 ms |
| `/login` | Time to first byte | 17.1 ms | **18.5 ms** | 40.7 ms |
| | DOMContentLoaded | 212.5 ms | **214.6 ms** | 238.5 ms |
| | **Load event** | 434.0 ms | **437.1 ms** | 460.9 ms |

Resources requested per page: 9 for `/`, 12 for `/contact`, 8 for `/login`. The three
extra requests on `/contact` are Google's reCAPTCHA script and its dependencies, which
account for essentially all of the ~440 ms gap between `/contact` and the other two pages.

**Verdict against TC1:** the worst single measurement of any page was 1.05 s, roughly a
third of the 3-second budget. TC1 passes with substantial headroom.

**One observed outlier.** An earlier 7-run batch recorded a single `/login` load of
1.88 s while the machine was still busy; it did not recur in the 12-run batch reported
above, where the slowest `/login` was 460.9 ms. It is noted for honesty: even that
outlier was inside budget, but it shows single measurements are noisy, which is why
medians over repeated runs are quoted throughout.

### Caveats — read before quoting these numbers

- **Local machine, not the production host.** Measured on an Apple M4 with the server,
  the database and the browser all on the same machine, so network latency is
  effectively zero. The real cPanel host is shared and will be slower. Re-run after
  deployment before claiming TC1 passes in production.
- **Debug mode was on, with DebugKit active.** DebugKit adds per-request profiling
  overhead that will not exist in production, so if anything these numbers *overstate*
  server time on identical hardware.
- **A real user adds latency this cannot see.** DNS, TLS handshake, distance to the
  server and mobile network conditions all sit on top. The headroom is large enough to
  absorb a lot of that, but the local figure is a floor, not a promise.
- **`curl` numbers are not user-perceived load time.** Repeating the point because it is
  the easiest mistake to make with this table: 16.7 ms is how fast the server produced
  the HTML for `/contact`; 875 ms is how long the browser took to actually finish
  loading it.

### Reproducing these measurements

The `curl` command above works as-is. The browser measurements were taken with a
throwaway Node script driving Chrome over the DevTools Protocol; it was deliberately not
committed, since the project has no browser-automation tooling and a single loose script
would be dead weight. To re-measure by hand:

1. Open Chrome DevTools → Network, tick **Disable cache**.
2. Load the page and read the `Load` figure in the status bar at the bottom.
3. Repeat 5–10 times and take the median — single measurements vary considerably.
