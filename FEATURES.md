# FEATURES.md — Fest Event Organizer

Project 19 of the suite. Tailwind CSS, professional navy/teal theme (no decorative gradients).
Plain PHP custom MVC. Personas: Super Admin (Fest Committee), Club/Organizer, Participant.

## 1. One-line pitch

A college-fest management platform where the Fest Committee runs one or more fests, Clubs propose
and run Events inside them (competitions, workshops, shows) with a schedule slot/venue/capacity,
and Participants browse and register solo or as a team — with server-side schedule-conflict
rejection and race-safe capacity enforcement — then get an e-pass, and later see results and a
downloadable certificate.

## 2. Core modules

1. **Auth & roles** — single login, Super Admin (Fest Committee) / Organizer / Participant, server-side
   role guards, participant self-registration.
2. **Fest & club management** — Committee creates/publishes/completes fests; Committee creates clubs
   and assigns one Organizer account per club.
3. **Event proposal & approval workflow** — Organizer proposes events (category, venue, start/end
   time, capacity, team size range, entry fee) under a published-or-draft fest; Committee
   approves/rejects with a reason; only approved events in a published fest are bookable.
4. **Registration & e-pass** — Participant browses approved events, registers solo or as a team
   (leader + named teammates); registration is rejected server-side on a fest-scoped schedule
   overlap with the participant's own existing registrations, and is race-safe against event
   capacity via a row-locked transaction so two near-simultaneous team registrations can't both
  claim the last open seats. A successful registration issues a unique e-pass code, which the
  event Organizer can verify by typing it into that event's roster.
5. **Results & certificates** — Organizer records per-event results (winner / runner-up / 2nd
   runner-up / participant) against registrations; Participants see a certificate view generated
   from their own result rows (participation vs. winner wording), Committee sees results across
   every fest.
6. **Dashboards & reporting** — Committee: fest-wide stats + a venue/time clash report across
   approved events; Organizer: club/event roster + registration counts; Participant: "my schedule"
   across all registered events plus their e-passes/certificates in one place.

## 3. Design notes (why it's built this way)

- **Two independent server-side integrity mechanics, both inside one transaction.** `Registration::register()`
  (`app/models/Registration.php`) opens a transaction and takes `SELECT ... FOR UPDATE` on the
  target event row first — this serializes every concurrent registration attempt against that one
  event, the same "row-lock as mutex" pattern this suite already uses (project 3's slot booking,
  project 16's seat hold). While holding that lock it (a) sums `team_size` across existing
  `confirmed` registrations for the event and rejects if adding this team would exceed `capacity`,
  and (b) separately checks every other `confirmed` registration the same leader holds *in the same
  fest* for a time overlap with this event and rejects on any overlap. Both checks, plus the insert,
  commit or roll back together — a participant can never end up double-booked into two overlapping
  events, and an event can never accept more seats than its capacity even under a true race.
- **Team members aren't required to hold accounts.** Only the team leader (who is registering) is a
  logged-in Participant; teammates are captured as name (+ optional email) rows in
  `registration_members`. Real fest teams routinely include people from other colleges/branches who
  never touch the portal — modeling every teammate as a full user account would be unrealistic
  overhead for both the data model and the demo, and the leader is enough of an accountable owner
  for the registration.
- **One club per organizer account.** Mirrors the Coordinator/Club pattern already established in
  project 11 (CampusConnect) — an Organizer's events are implicitly scoped to their own club via a
  `clubs.organizer_id` foreign key, so `EventController` never needs a club-picker for that role and
  ownership checks reduce to one join.
- **Capacity counts individual seats, not teams.** `events.capacity` is a total participant-seat
  cap; a team registration consumes `team_size` seats at once. This is what makes the race
  condition interesting to test (a 2-seat-remaining event can admit one 2-person team but must
  reject a second simultaneous 2-person team, even though both requests see "2 remaining" if they
  read before either writes).

## 4. Feature breakdown by persona

| Feature | Super Admin (Committee) | Organizer | Participant |
|---|---|---|---|
| Manage fests (create/publish/complete) | Yes | Browse only | Browse published only |
| Manage clubs + assign organizer account | Yes | View own club | - |
| Propose event (venue/time/capacity/team size) | - | Yes (own club) | - |
| Approve / reject event with reason | Yes | - | - |
| Edit own pending/approved event | View all | Yes (own club, before fest completes) | - |
| Browse approved events, filter by fest/category | Yes | Yes | Yes |
| Register for an event (solo or team) | - | - | Yes |
| View own event's registration roster | View all | Yes (own club's events) | - |
| Cancel own registration | - | - | Yes |
| Record results (winner/runner-up/participant) | View all | Yes (own club's events) | - |
| View e-pass for own registration | - | - | Yes |
| Verify participant e-pass code for own event | - | Yes | - |
| View certificate for a completed event's result | - | - | Yes |
| Fest-wide venue/time clash report | Yes | - | - |
| Dashboard/stats scoped to own data | Yes (suite-wide) | Yes (own club) | Yes (own registrations) |

## 5. Screen list per persona

**Super Admin (Committee)** (~6): Dashboard (fest/club/event/registration stats), Fests
(list/create/edit/publish/complete), Clubs & Organizer accounts (list/create/assign), Event
approvals (pending/approved/rejected queue), Venue & time clash report, Results oversight (all
fests).

**Organizer** (~6): Dashboard (club's events + counts), My Events (list/propose/edit), Event
Roster (registrations + members for one event), Record Results (per event), Club Profile (edit
description), Fest/Event browse (read-only, to see the wider fest).

**Participant** (~6): Dashboard/My Schedule, Browse Fests, Browse Events (per fest, filter by
category), Event Detail + Register (solo/team form with live remaining-seats display), My
Registrations (list + cancel), E-Pass view (printable), My Certificates.

## 6. Data model summary

`users`, `clubs` (one organizer per club), `fests`, `events` (approval workflow, schedule +
capacity + team-size fields), `registrations` (leader + team_size + unique e-pass code, race-safe
capacity + schedule-conflict enforcement on insert), `registration_members` (named teammates, no
login), `results` (per-event, per-registration position).

## 7. Out of scope (considered and dropped)

- **Payment gateway integration for entry fees.** `events.entry_fee` is stored and shown, but
  no payment flow is wired up — registration is treated as free/offline-paid, matching this
  suite's local-only demo scope; a real system would need a payment gateway, which is out of
  scope for a viva project.
- **QR-code e-passes.** The e-pass uses a unique alphanumeric code on a printable page rather than
  an actual scannable QR image. Organizers verify the code by typing it into their event roster;
  QR rendering remains a cosmetic v2 enhancement.
- **PDF certificate download.** Certificates render as a styled HTML page (print-to-PDF works fine
  via the browser) rather than a server-generated PDF via DomPDF — kept for scope parity with
  other Tailwind-stack projects in this batch that also skip DomPDF; noted as a natural v2 addition.
- **Team-member accounts / teammate self-service.** Teammates are name+email rows, not users who can
  log in and see their own e-pass — see §3. A v2 could let a teammate claim their row by email.
  scoped tighter than that for now.
- **Waitlists when an event is full.** A full event simply rejects new registrations rather than
  queueing participants for a released seat (contrast with project 16's live queue, which already
  covers that mechanic elsewhere in the suite) — cancellations free seats but don't auto-promote
  anyone.
- **Multi-city / multi-campus fests, sponsor management, budget tracking.** Real fest software
  scope, deliberately dropped to keep this comparable in size to the rest of the suite.
