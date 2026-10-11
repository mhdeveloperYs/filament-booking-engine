# Filament Bookings — Product Spec

> Working name: **Filament Bookings** (name candidates: `Scheduly`, `Chrono`, `Bookflow`, `Zeus Bookings` is taken by Lara Zeus)
> One-liner: **The self-hosted appointment & booking engine for Filament — services, staff, availability, payments, reminders and 2-way calendar sync.**

---

## 1. Why this exists (opportunity)

Verified against the Filament marketplace + Packagist ecosystem:

- **No dominant free Filament booking engine.** Free options are only *building blocks*:
  - `wooserv/filament-date-time-slots` — a field/picker (1 322 downloads)
  - `Guava Calendar` — a calendar component (220★)
  - `Fila Calendar`, `Guava`, `Cine Reserve` — fields / niche
- **Paid incumbent is thin.** `Athena` (Lara Zeus) costs **$69**, supports v5 — but its features are: *services, available times per day, timeslot duration, list/calendar of requests, frontend scaffolding*. It has **no payments, no reminders, no recurring, no calendar sync, no resources/analytics**.
- **Demand is proven** (downloads on adjacent packages): `artisanpack-ui/bookings` 2 465, `wooserv/filament-date-time-slots` 1 322, `andydefer/laravel-chronos` 856.
- **Recurring, vertical market**: salons, clinics, consultants, agencies, rentals, coaching, workshops.

**Wedge = depth.** Win with the "serious ops" booking flow: money (payments), time (reminders + 2-way sync), and complexity (recurring, resources, timezones) — the parts Athena and the field-level plugins do not cover.

---

## 2. Target users (ICP)

1. **Agencies / freelancers** building a booking product for a client (salon, clinic, trainer).
2. **SaaS builders** who need bookings as one module of a bigger Filament app.
3. **Self-hosters** who want a Calendly/Cal.com alternative they fully own.

Non-target: enterprise scheduling (Acuity/Calendly scale), multi-calendar routing at enterprise level.

---

## 3. Competitive landscape & positioning

| Product | Type | Version | Price | Gap we exploit |
|---|---|---|---|---|
| Athena (Lara Zeus) | Booking | v5 ✅ | $69 | No payments/reminders/recurring/sync |
| Guava Calendar | Calendar component | v5 | Free | Not a booking system |
| wooserv date-time-slots | Field | v5 | Free | Field only |
| artisanpack-ui/bookings | Laravel engine | — | Free | Not Filament-native (manual wiring) |
| Calendly / Cal.com | SaaS | — | $$$ | Not self-hosted, not in your panel |

**Positioning statement:** *"The email-and-payments booking engine that lives inside your Filament panel — free core, Pro for money, reminders and sync."*

---

## 4. Feature matrix

### Free core (distribution — drives GitHub stars + installs)
- Services (name, duration, price, color, active)
- Providers/staff (configurable model) + per-weekday availability rules
- Date exceptions / time-off / closures
- Booking calendar in the panel (day / week / month)
- Appointments resource (list, filter, search, statuses, notes)
- Customizable appointment statuses
- Public booking page (Blade + Livewire) with slot picker
- Confirmation email (Laravel notifications)
- Timezone-correct slot generation (store UTC)
- Dark mode, i18n, Filament v4 **and** v5, Laravel 12/13, PHP 8.3/8.4

### Pro (paid — $59)
- **Payments**: deposit or full, Stripe (+ PayPal/Paddle drivers), payment status on appointments
- **Reminders**: email + SMS/WhatsApp, configurable offsets, editable templates
- **2-way calendar sync**: Google Calendar & Microsoft Outlook, incremental + webhook
- **Recurring appointments** (RRULE)
- **Resources / capacity / group bookings**
- Buffer before/after, lead time, min/max notice, cutoff
- **Analytics**: utilization, revenue, no-show rate, busiest slots
- **AI**: smart slot suggestions, no-show risk, auto-reply to notes
- **Multi-tenant** support
- **Webhooks / events API** (Zapier/n8n ready)
- Priority support + updates for 1 year

---

## 5. Pricing & licensing

- **Pro — Single project**: **$59** (1 domain, dev+staging+prod), 1 year updates
- **Pro — Unlimited / Lifetime**: **$149–199** (SaaS + unlimited projects)
- License via **LemonSqueezy / Polar / Anystack** (private Composer repo + license key + fingerprint, same model as Athena/Pennant)
- Free core on **Packagist + GitHub (MIT)**; marketplace listing on filamentphp.com links to checkout

---

## 6. Technical architecture

### Stack
- `spatie/laravel-package-tools` + Filament Plugin Skeleton
- Filament **v4 + v5** (dual support), Livewire 3
- RRULE: `rlanvin/php-rrule` (recurrence)
- Timezones: Carbon (native, UTC storage)
- Payments: `laravel/cashier` (Stripe) behind a small `PaymentGateway` driver interface
- Calendar sync: `google/apiclient`, `microsoft/microsoft-graph`
- Queue for reminders + sync jobs
- Tests: **Pest** (availability engine must be fully unit-tested)

### Package structure
```
filament-bookings/
├── src/
│   ├── BookingsPlugin.php              # Filament\Contracts\Plugin
│   ├── BookingsServiceProvider.php
│   ├── Engine/
│   │   ├── AvailabilityEngine.php      # rules - exceptions - appointments - buffers
│   │   ├── SlotGenerator.php           # timezone-correct slots
│   │   ├── BookingManager.php          # transactional create/reschedule/cancel
│   │   ├── RecurrenceExpander.php      # RRULE -> appointments
│   │   └── ConflictGuard.php           # lock + unique index (no double-booking)
│   ├── Payments/
│   │   ├── PaymentGateway.php          # interface
│   │   └── StripeGateway.php
│   ├── Reminders/
│   │   ├── ReminderScheduler.php
│   │   └── Channels/{Mail,Sms,Whatsapp}.php
│   ├── Calendar/
│   │   ├── GoogleCalendarSyncer.php
│   │   └── OutlookCalendarSyncer.php
│   ├── Models/                          # Service, Provider, AvailabilityRule,
│   │                                    # AvailabilityException, Appointment,
│   │                                    # AppointmentStatus, Payment, Reminder,
│   │                                    # CalendarConnection
│   ├── Filament/
│   │   ├── Resources/{ServiceResource,ProviderResource,AppointmentResource}
│   │   ├── Pages/{ManageAvailability,BookingCalendar}
│   │   ├── Widgets/{CalendarWidget,UpcomingWidget,StatsWidget}
│   │   └── Components/PublicBookingPage.php
│   ├── Http/Controllers/PublicBookingController.php
│   └── Support/Config.php
├── database/migrations/
├── resources/{lang,views}               # 50+ locales UI + public views
├── config/filament-bookings.php
├── tests/                               # Pest
└── README.md
```

### Core data model
- `fb_services` — name, duration, buffer_before/after, price, currency, capacity, color, active
- `fb_providers` — polymorphic or configurable (User/Staff), timezone, active
- `fb_availability_rules` — provider_id, weekday, start, end
- `fb_availability_exceptions` — provider_id, date/datetime range, type (closed/custom)
- `fb_appointments` — service_id, provider_id, client_ref + name/email/phone, start_at(UTC), end_at, timezone, status, price, payment_status, source
- `fb_appointment_statuses` — label, color, is_default/closing
- `fb_payments` — appointment_id, gateway, amount, currency, txn, status
- `fb_reminders` — appointment_id, channel, offset, send_at, status
- `fb_calendar_connections` — provider_id, provider(google/outlook), tokens, sync_token, calendar_id

### Critical correctness concerns (this is where amateurs fail)
1. **Timezone + DST** — store UTC, generate slots in the provider's/client's tz, test DST edges.
2. **Concurrency / double-booking** — wrap create in a DB transaction with a lock + a unique index guard; `ConflictGuard`.
3. **Recurrence edge cases** — exceptions to a series, single-instance edits/cancellations.
4. **Sync conflict resolution** — last-write-wins policy + idempotent upserts + sync tokens.
5. **Reminder delivery** — idempotency (never double-send), retries, per-channel status.

---

## 7. Milestones (≈11 weeks, solo)

| Week | Deliverable |
|---|---|
| 0 | Plugin skeleton, CI, Pest setup, name/brand, decision: build engine (recommended) vs wrap `artisanpack-ui/bookings` |
| 1–2 | Data model + **AvailabilityEngine + SlotGenerator + ConflictGuard** (fully tested) |
| 3–4 | Filament resources: Services, Providers, Appointments + Calendar widget |
| 5 | Public booking page (Livewire) + confirmation email |
| 6 | i18n, dark mode, v4+v5 pass, docs, README → **🎉 Free core v0.1 release** |
| 7–8 | Pro: Payments (Stripe) + Reminders (email/SMS/WhatsApp) |
| 9 | Pro: 2-way Google/Outlook sync |
| 10 | Pro: Recurring + Resources + Analytics |
| 11 | Pro: AI + multi-tenant + webhooks → **🚀 Pro launch ($59)** |

---

## 8. Go-to-market

1. **Free core first** — GitHub repo (MIT) + Packagist + Filament marketplace listing. Goal: 300+ stars before Pro.
2. **Content** — build-in-public on X/Twitter, YouTube (English **and** Darija), 1 video "build a booking system with Filament".
3. **Distribution** — Laravel News submission, r/laravel, Laravel Discord, Filament Discord, Product Hunt.
4. **SEO / comparison pages** — "self-hosted Calendly alternative", "Athena alternative", "Filament booking plugin".
5. **Pro launch** — email the free-core list + GitHub stargazers; launch discount 20%.

---

## 9. Risks & mitigations

| Risk | Mitigation |
|---|---|
| Filament team ships official bookings | Keep it vertical/deep; move fast on payments + sync |
| Athena adds payments/reminders | Compete on v5-native + public page + AI + self-hosted pricing |
| Scope too big → never ships | Ship free core in week 6; Pro features incremental |
| Double-booking bugs destroy trust | Transaction + lock + unique index + heavy tests |
| Effort vs "passive income" | Treat as side business; budget ~10–20% ongoing maintenance |

---

## 10. Success metrics

- Free core: GitHub stars, Packagist installs, marketplace clicks
- Conversion: free → Pro (%) and revenue per month
- Quality: zero double-booking incidents, sync reliability, support response time
- Realistic target Y1: 30–150 Pro sales (~$1.8k–$8.8k); stretch 300+ (~$18k+)

---

## 11. Immediate next steps

1. Confirm name + positioning.
2. Scaffold the package (Filament plugin skeleton) in this repo.
3. Implement the **AvailabilityEngine** with Pest tests first (the moat is correctness).
4. Build the free core to a shippable v0.1, then launch.
