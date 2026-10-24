# Fest Event Organizer

Project 19 of the final-year PHP project suite. College-fest management platform: a Fest
Committee runs one or more fests, Clubs/Organizers propose and run Events inside them, and
Participants browse and register (solo or as a team), get an e-pass, and later see results and a
certificate.

## 1. Overview & objective

A campus fest usually has three concerns that fight each other in a spreadsheet: which events are
actually approved and schedulable, whether a student is double-booking themselves into overlapping
events, and whether an event's last few seats get oversold when many students register at once.
This project makes all three server-enforced instead of trusted to the UI:

- **Approval workflow**: only a Committee-approved event in a published fest is bookable.
- **Schedule-conflict rejection**: a participant can't register for an event that time-overlaps
  another event they're already registered for in the same fest.
- **Race-safe capacity enforcement**: two near-simultaneous team registrations can't both claim
  more seats than an event actually has left, verified under genuine concurrency (see §9).

## 2. Tech stack

- **Frontend**: Tailwind CSS (via CDN + inline config, no build step), vanilla JS for small
  progressive enhancements (add/remove teammate rows, flash auto-dismiss, confirm dialogs).
  Visual theme — "Deep Teal & Charcoal", a professional/corporate identity: teal `#0F4C4C` and
  cyan `#0E7490` over a barely-tinted canvas, with marquee amber `#F59E0B` reserved for
  prizes/certificates and mint/rose carrying approval state, so colour always means something
  rather than decorating. (Originally built in a festive magenta/violet identity per the
  suite-wide theme assignment; swapped to this palette at the user's request, since the fest
  organizer's own back-office tooling reads more credibly in a professional register than a
  festive one.) Type is a three-role system — Space Grotesk (display), Inter (body), JetBrains
  Mono (e-pass codes and numerics). Every surface is a flat solid fill, no gradients anywhere,
  including the nav band and the e-pass ticket. The signature element is the **e-pass drawn as a
  real perforated ticket** (punched side notches, dashed tear line, mono code) — see
  `public/assets/css/style.css`, which documents the palette and the restraint rules at the top.
- **Backend**: Plain PHP 8, custom MVC (no framework) — `core/` has `Database` (PDO singleton),
  `Auth`, `Csrf`, `Router`, `Validator`, `helpers.php`.
- **Database**: MariaDB (InnoDB, foreign keys enforced), PDO with prepared statements throughout.
- **Auth**: native PHP sessions, `password_hash()`/`password_verify()`, `session_regenerate_id(true)`
  on login, CSRF token per session verified on every state-changing POST.

## 3. Data model / table list

| Table | Purpose |
|---|---|
| `users` | Super Admin (Fest Committee) / Organizer / Participant, `status` active/inactive |
| `clubs` | One club per Organizer account (`organizer_id` unique FK) |
| `fests` | draft → published → completed lifecycle |
| `events` | Belongs to a fest + club; schedule (`start_time`/`end_time`), `venue`, `capacity`, `team_size_min/max`, `entry_fee`, approval `status` (pending/approved/rejected) + `rejection_reason` |
| `registrations` | One row per team/solo entry; `leader_id` (the logged-in Participant), `team_size`, unique `epass_code`, unique `(leader_id, event_id)`, `status` confirmed/cancelled |
| `registration_members` | Named teammates (name + optional email) — not user accounts, see §10 |
| `results` | Per-event, per-registration `position` (winner/runner_up/second_runner_up/participant) + remarks, unique `(event_id, registration_id)` |

ER shape: `fests 1—N events N—1 clubs`; `events 1—N registrations 1—N registration_members`;
`events 1—N results N—1 registrations`; `clubs 1—1 users` (organizer_id), `registrations N—1 users`
(leader_id).

## 4. Feature list

See `FEATURES.md` for the full spec (modules, persona feature table, screen list, design notes,
out-of-scope list). Summary:

- Auth: single login, 3 roles, participant self-registration, server-side role guards everywhere.
- Committee: create/publish/complete fests; create clubs and assign one Organizer account each;
  approve/reject proposed events with a reason; venue/time clash report across approved events;
  suite-wide results oversight.
- Organizer: propose/edit events (editing a previously approved/rejected event resets it to
  pending for re-review); event roster; record results; club profile; read-only fest browse.
- Organizer: verify a participant's e-pass by typing its code into the roster for that event;
  cancelled, unknown, or cross-event codes are rejected.
- Participant: browse published fests/approved events by category; register solo or as a team
  (schedule-conflict + capacity checked server-side, see §9); cancel a registration; printable
  e-pass; view/print a certificate once a result is recorded for them.

## 5. Default login credentials

All demo accounts use the same password: **`Password123!`**

| Persona | Email | Notes |
|---|---|---|
| Super Admin (Fest Committee) | `committee@fest.test` | |
| Organizer — ByteForge Coding Club | `organizer.coding@fest.test` | Owns most of the demo events |
| Organizer — Euphony Music Club | `organizer.music@fest.test` | |
| Organizer — Rhythm Dance Crew | `organizer.dance@fest.test` | |
| Organizer — Vertex Robotics Club | `organizer.robotics@fest.test` | |
| Organizer — unassigned | `organizer.unassigned@fest.test` | No club yet — used to demo Admin → Create Club |
| Participant | `aarav@student.test` | Has existing registrations (CodeStorm team lead, CP Sprint solo) |
| Participant | `diya@student.test` / `reyansh@student.test` / `ananya@student.test` / `vivaan@student.test` / `myra@student.test` / `arnav@student.test` / `ira@student.test` / `sai@student.test` / `kiara@student.test` | Various seeded registrations across events |
| Participant (deactivated) | `dev@student.test` | Login correctly rejected — for demoing inactive-account handling |

## 6. Setup instructions

1. Start Apache and MySQL from the XAMPP Control Panel (XAMPP's MySQL listens on the default port
   `3306`).
2. Import the schema (drops and recreates the `fest_event_organizer` database, includes seed data),
   either via phpMyAdmin's Import tab or:
   ```
   mysql -u root < database/schema.sql
   ```
3. `config/database.php` is already set for `127.0.0.1:3306`, user `root`, empty password — XAMPP's
   defaults. Adjust only if your XAMPP MySQL has a non-default port/credentials.
4. Serve via the PHP built-in server (note `-t public` — without it static assets 404 silently):
   ```
   php -S localhost:8018 -t public public/index.php
   ```
5. Visit `http://localhost:8018/` — redirects to login.

No Composer dependencies — this project doesn't need PHPMailer/DomPDF/PhpSpreadsheet, so there's
no `composer.json` (see §10 for why certificates/e-passes are plain HTML, not PDF/QR).

## 7. Folder structure

Follows the suite-standard layout (`GROUNDRULES.md` §1):

```
fest-event-organizer/
  public/               web root — index.php front controller, assets/{css,js}
  app/
    controllers/        AuthController, AdminController, OrganizerController, ParticipantController
    models/              User, Club, Fest, Event, Registration, Result
    views/                auth/, admin/, organizer/, participant/, partials/, errors/
  core/                  Database, Auth, Csrf, Router, Validator, helpers.php
  config/database.php
  database/schema.sql
  FEATURES.md            pre-build feature spec
  README.md              this file
```

## 8. Concurrency/integrity mechanic — how it works

`Registration::register()` in `app/models/Registration.php` is the technical centerpiece. It
implements **two independent server-side integrity checks inside one transaction**:

1. `SELECT * FROM events WHERE id = ? FOR UPDATE` locks the target event row first. Every
   concurrent registration attempt against the *same event* must wait for that lock, which
   serializes them — the same "row lock as mutex" pattern this suite already uses for slot booking
   (project 3) and seat holds (project 16), applied here to a variable-size team claim instead of a
   single seat.
2. While holding that lock, it sums `team_size` across `confirmed` registrations for the event and
   rejects (`ERR_FULL`) if adding this team would push the total over `capacity` — so two
   simultaneous team registrations that both observe "2 seats left" can't both succeed if only one
   of them actually fits.
3. Independently, it checks every other `confirmed` registration the same leader holds *in the same
   fest* for a start/end time overlap with this event, and rejects (`ERR_CONFLICT`) on any overlap —
   a participant can never end up double-booked into two events happening at once, even across
   different clubs.
4. The unique `(leader_id, event_id)` key on `registrations` is a second line of defense against a
   double-submit registering the same leader into the same event twice (caught as a duplicate-key
   exception and mapped back to `ERR_ALREADY_REGISTERED`).

All checks plus the insert commit or roll back together as one transaction.

## 9. Testing performed

Done via a curl-driven walkthrough against a real MariaDB on port 3306 with `php -S localhost:8018
-t public public/index.php` (`-t public` confirmed necessary — static assets served correctly with
it). `php -l` passed on every PHP file with zero syntax errors.

- **Auth**: login for all 3 personas succeeded (302 → correct role dashboard); inactive account
  (`dev@student.test`) correctly rejected with "Invalid email/password, or the account is
  inactive." and no session created.
- **Role enforcement, both directions**: Participant → `admin/dashboard` = 403. Organizer →
  `admin/fests` = 403. Committee → a participant-only route = 403.
- **CSRF**: a POST to `admin/fests/store` with no `csrf_token` field = 419.
- **CRUD**: Committee created a club (assigned to a previously-unassigned Organizer account) and a
  new fest, published it; Organizer proposed an event in it; Committee approved it — each step
  verified against the live database, not just the HTTP status.
- **Ownership-scoped 404s**: a Participant fetching another participant's e-pass URL by id = 404;
  fetching another participant's certificate by id = 404; an Organizer fetching another club's
  event roster = 404.
- **Schedule-conflict rejection**: Aarav (already registered for CodeStorm Hackathon, which runs
  2026-09-18 20:00 → 2026-09-19 08:00 in fest 1) was rejected attempting to register for Battle of
  Bands (18:00 → 21:00 the same fest, overlapping window) with the exact message "Schedule
  conflict: you are already registered for 'CodeStorm Hackathon' which overlaps this time slot." —
  confirmed **zero** rows written to `registrations` for the rejected attempt.
- **Team-size-range rejection**: a solo (1-person) registration attempt against an event requiring
  4–10 members was rejected server-side with "...does not fit this event's allowed range
  (4-10)." and zero rows written — this can't be bypassed by a forged POST since the server
  recomputes team size from however many named-member rows survive, it never trusts a client-sent
  team size.
- **Race-safe capacity — genuine concurrency test**: Since `PHP_CLI_SERVER_WORKERS` is a no-op on
  Windows and a single `php -S` instance serializes requests, this suite's established workaround
  (used previously in project 16) was repeated: **5 separate `php -S` processes** were started on
  ports 8018/8118/8119/8120/8121, all pointing at the same codebase and the same MariaDB database.
  A capacity-3, solo-entry event already had 1 confirmed registration (2 seats left). **8 different
  participant accounts, logged in and pre-authenticated with valid CSRF tokens, fired their
  registration POSTs simultaneously** across the 5 server processes (backgrounded curl + `wait` in
  bash, no artificial delay between them). Result: **exactly 2 of the 8 succeeded** (the two seats
  that existed), the other 6 were rejected with "Sorry, only 0 seat(s) remain and your team does
  not fit." — confirmed against the database (`SUM(team_size)` for the event = exactly 3, matching
  `capacity`, and exactly 3 confirmed registration rows total). No overselling occurred under real
  concurrent writes.
- **Venue/time clash report**: verified empty on the clean seed data (no clashes among approved
  events), then a deliberate clashing event (same venue, overlapping time, same fest) was proposed
  and approved, and confirmed it appeared correctly in `admin/clashes` — then the database was
  re-imported from `schema.sql` to discard that test data and return to the pristine seed state.
- **Results & certificates**: Organizer recorded a "winner" result against a registration;
  confirmed it appears on that participant's Certificates page and renders correctly, and that a
  different participant gets a 404 fetching it by id.
- **404 handling**: an unregistered route returns 404 via the custom error page, not a stack trace.
- Database re-imported from `database/schema.sql` after testing to leave a clean seeded state
  (verified row counts match the seed file: 17 users, 4 clubs, 3 fests, 14 events, 10
  registrations, 1 result).

**Browser visual check**: completed in a follow-up verification pass (the build session's
`claude-in-chrome` tooling couldn't reach `localhost:8018` from its sandbox; a later session's
browser could reach it once the dev server was bound to `127.0.0.1:8018` explicitly rather than
`localhost:8018` — an IPv6-vs-IPv4 resolution quirk on this machine, not an application bug).
Confirmed live: the Tailwind magenta/violet gradient theme renders correctly on login and the
Committee dashboard, and the Venue & Time Clash Report page.

**Bug found during that follow-up pass, fixed**: this app (like every other project in the suite)
called plain `session_start()` with no `session_name()`. PHP's default `PHPSESSID` cookie isn't
port-scoped on `127.0.0.1`/`localhost`, so with two suite projects' dev servers running at once in
the same browser, logging into one and then visiting the other showed the *other* project's session
data (its user name/role) — not just a stale cookie, but genuinely shared session data, since the
default file-based session handler keys purely by session ID in a shared OS temp directory with no
per-app namespace. Fixed by calling `session_name('fest_event_organizer_sess')` before
`session_start()` in `public/index.php`, forcing a distinct cookie name and therefore a fresh
session ID per app. Re-verified: visiting this app after being logged into a sibling project now
correctly shows this app's own login page. This is a latent gap across the whole suite (every
project shares the same default session cookie name) that only surfaces when two projects' dev
servers are exercised in one browser at once — worth a suite-wide pass at some point, not just here.

### Second review pass — lifecycle locks added

A later audit pass found that several state changes could strand data that already existed
underneath them. The rule applied throughout: **a parent can't change state in a way that
invalidates children that already exist** — each refusal names the dependent count rather than
failing silently, and the corresponding UI control is replaced by an explanatory badge rather
than left clickable.

| Action | Now refused when | Where |
|---|---|---|
| Organizer edits an event | the event has confirmed registrations (any edit resets it to `pending`, which would pull it off the published schedule while e-passes are live) | `OrganizerController::eventUpdate()` |
| Committee rejects an event | the event has confirmed registrations | `AdminController::eventReject()` |
| Committee reverts a fest to draft | any of its events has a confirmed registration (use *Mark Completed* instead) | `AdminController::festStatus()` |
| Participant cancels a registration | the event has already started, or a result has been recorded | `Registration::cancel()` |

Two further gaps closed in the same pass:

- **Club deactivation was a no-op.** `clubs.status` was toggleable from the Committee's Clubs
  screen but checked nowhere, so a deactivated club's events stayed browsable *and bookable* and
  its organizer kept full write access. Now enforced on the browse listing
  (`Event::forFestApproved()`), the event page, the registration write path
  (`Registration::register()`, inside the same locked transaction as the capacity check), and
  every organizer write action.
- **Account status was only checked at login.** `Auth::requireLogin()` now re-reads
  `users.status` on every request and force-logs-out a deactivated account with an explanatory
  message, instead of letting an existing session run until the user happens to log out.

Also fixed: `Event::update()` threw `SQLSTATE[HY093] Invalid parameter number` on every
otherwise-valid edit (the data array carried a `fest_id` key with no matching placeholder, fatal
because `Database::connect()` disables emulated prepares) — so the Edit Event feature had never
actually worked. The rewritten method also performs its capacity re-check inside a transaction
holding the same `SELECT ... FOR UPDATE` event-row lock the registration mutex uses, so an
organizer lowering capacity can no longer interleave with a registration and oversell the event.

## 10. Anticipated viva questions

**Q: Why does capacity enforcement need a database transaction instead of just checking in PHP
before inserting?**
A read-then-write check ("if seats_taken < capacity, insert") has a race window: two requests can
both read "2 seats left" before either writes, and both insert, overselling the event. The fix is
to make the whole check-and-insert one atomic unit. `SELECT ... FOR UPDATE` on the event row first
means the second concurrent transaction blocks until the first one commits or rolls back, so by the
time it re-reads the seat count, it sees the first transaction's write and can correctly reject.
This was verified under real concurrency (§9), not just reasoned about.

**Q: Why lock the event row specifically, rather than just relying on a unique constraint?**
A unique constraint (like `(leader_id, event_id)`) only prevents the same person registering twice
— it can't stop the *sum* of several different people's team sizes from exceeding capacity. The
event-row lock is what serializes the "read current sum, check against capacity" step across
different registrants, which no unique key alone can do since each registration row is otherwise
independent.

**Q: Why aren't teammates full user accounts?**
Real fest teams routinely include people who never touch the portal (other colleges, other
branches, or just don't want an account for a one-time competition). Only the team leader who
actually does the registering needs to be a Participant account; the rest are captured as
name/email rows on the registration. This keeps the data model proportionate to what the flow
actually needs — see FEATURES.md §3 and §7 for the tradeoff and what a v2 teammate-self-service
model would add.

**Q: How is CSRF protection implemented, and what happens on a forged request?**
A random token is generated once per session (`Csrf::token()`, `bin2hex(random_bytes(32))`) and
embedded as a hidden field in every form. Every state-changing POST calls
`Csrf::requireValid()`, which does a `hash_equals()` comparison against the session's token and
aborts with HTTP 419 if it's missing or wrong — verified live by POSTing to `admin/fests/store`
with no token.

**Q: How are roles enforced, and can a normal user reach an admin URL by typing it directly?**
No. `Auth::requireRole(...roles)` is called at the top of every controller action (not just hidden
in the nav UI), checks the session-stored role against the allowed list, and returns 403 if it
doesn't match. This was verified live in both directions — Participant/Organizer hitting Committee
routes, and Committee hitting a Participant-only route, all correctly return 403.

**Q: Why is the event-approval workflow separate from event creation?**
An Organizer represents one club and shouldn't be able to unilaterally put an event on a published
fest's schedule — venues, timing, and quality need a single point of oversight (the Committee) to
avoid double-booked venues and scheduling chaos across clubs. Editing a previously-approved event
resets its status back to `pending`, so a club can't slip a change past the Committee after the
fact either.

### Third review pass — UI polish finished, four more real bugs found

The flat magenta/violet redesign (no gradients — the nav band, e-pass, and everything else are
solid fills; colour is reserved for state, e.g. gold = prize, mint = approved, rose = rejected) was
already largely built going into this pass; two leftover `bg-fest-gradient` references (a utility
class removed from the Tailwind config when gradients were eliminated, so it silently rendered with
*no* background at all) were still driving the active-tab pill on the participant fest page and the
Committee's event-approval filter — swapped for `bg-violet-dark` (see palette swap below),
consistent with the nav band. Re-audited the whole `app/views` tree afterward: zero off-palette
Tailwind color utilities anywhere, confirming the design system is applied consistently rather than
partially.

**Palette swap to "Deep Teal & Charcoal"**: at the user's request, immediately after this pass, the
magenta/violet identity above was replaced with a professional teal/cyan/charcoal one (§2 has the
current hexes) — same structure and discipline (flat fills, amber/mint/rose unchanged for
prize/approved/rejected), only the brand hues changed. The Tailwind config keys and every
`badge-`/utility class referencing them were renamed to match (`magenta`→`cyan`,
`violet`→`teal`, so `bg-violet-dark` above is now `bg-teal-dark` in the actual code) rather than
just re-pointing the old names at new hex values, so the source doesn't lie about what colour
things render. Re-verified: all 14 routes across all 3 personas still render clean, and the CSS
correctly serves the new hex values.

Four further real bugs found and fixed, each verified live against MariaDB, not just read in code:

- **Re-registering after cancelling was silently blocked.** `registrations` has a hard
  `UNIQUE(leader_id, event_id)`. The "already registered?" check correctly only matches `confirmed`
  rows, so a participant who cancelled could pass that check — but the plain `INSERT` that followed
  then collided with their own leftover `cancelled` row on that same unique key, was caught by the
  generic duplicate-key handler, and came back as "You are already registered for this event" —
  permanently locking them out of an event they were, in fact, not registered for. Reproduced live
  (register → cancel → re-register → wrongly rejected), then fixed by turning the insert into
  `INSERT ... ON DUPLICATE KEY UPDATE` that revives the same row as a fresh confirmed registration
  (and clears its old team-member rows first) — re-verified the identical steps now succeed and
  issue a new e-pass. `app/models/Registration.php`.
- **Results could be recorded for an event that hadn't happened yet** — `resultStore()` had no
  check that the event had ended. Fixed with a `NOW() >= end_time` guard using the DB's own clock,
  the same pattern `Registration::register()` already used. `app/controllers/OrganizerController.php`.
- **Two different teams could both be recorded as "winner" (or runner-up) of the same event** —
  `Result::record()`'s `ON DUPLICATE KEY` is keyed on `(event_id, registration_id)`, which does
  nothing to stop a second, different registration being awarded the same rank. Added
  `Result::holderOfPosition()` and a check in `resultStore()` that rejects awarding a rank already
  held by someone else (correcting your own recorded result is still allowed).
- **An event could be scheduled outside its own fest's published date range** — `eventStore`/
  `eventUpdate` never checked `start_time`/`end_time` against the parent fest's `start_date`/
  `end_date`. Fixed with a bounds check against the fest actually being edited into.
- **Seed data had quietly gone viva-blocking.** Every event in `Zenith 2026` (the flagship demo
  fest) has a literal, now-past `start_time`, so the live register → e-pass → cancel flow —
  arguably the centerpiece flow of this app — could no longer be demoed against the seed data as
  originally written, only the "declare results for a finished event" side of it. Rather than bump
  the literal dates (which would just go stale again on the next viva), added a fourth fest,
  **Winter Carnival 2026**, dated with `DATE_ADD(CURDATE(), INTERVAL … DAY)` in `schema.sql` instead
  of a fixed calendar date, with three approved events and one pending one — so it's always
  upcoming and always open for a live registration demo, however long from now `schema.sql` is
  re-imported, without touching the historical Zenith 2026 / 2025-archive data the results and
  certificate demos depend on. Verified end-to-end after import: participant registered for
  *Rapid Code Duel*, got an e-pass, cancelled it, re-registered (exercising the fix above), Committee
  approved the pending *Dance Fusion Workshop*, and an Organizer recorded (and was correctly blocked
  from double-recording) a winner for an already-finished Zenith 2026 event — all against the real
  database, then reset via a clean re-import before handing back.
