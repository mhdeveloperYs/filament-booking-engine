# Competitor Review — Filament booking / appointments

Evidence collected from official repositories, Packagist metadata and vendor docs.
No proprietary code was read or copied; only public description, licensing, requirements and architecture were inspected.

## 1. `artisanpack-ui/bookings` (the "wrap candidate")

- **License:** MIT (from Packagist metadata, `v1.2.2`).
- **Status:** released v1.0 → v1.2.2, actively maintained (last publish 2026-08-31).
- **Adoption:** Packagist downloads 2 507 total / 1 480 monthly; **GitHub stars: 0**.
- **Scope (very large):** services, providers, availability, bookings, recurring (RFC 5545 via `simshaun/recurr`), two-way calendar sync (Google / Microsoft / Apple), notifications (mail, database, webhook, SMS), reminders, outbound webhooks (with SSRF guard), GDPR/erasure, retention & pruning, multi-site, rate limiting, REST API, iCal feeds, manage tokens, Livewire **and** React/Vue frontends, 16 tables.
- **Hard dependencies:** `artisanpack-ui/core`, `artisanpack-ui/hooks`, `artisanpack-ui/security` + `sabre/vobject` + `simshaun/recurr`.
- **Filament:** **NOT Filament-native.** It is a Laravel package with its own admin surface (`bookings-admin` routes, DaisyUI HTML). The Filament-looking screens only appear if you also install `artisanpack-ui/cms-framework`. It does not ship `Filament\Resources`.
- **Concurrency approach (good to learn from):** advisory lock on the provider's **local day** (Postgres/MySQL) or cache lock, plus a **partial unique index** on `bookings` as the last line of defence.

**Verdict:** high quality, but a *competitor in its own right*, not a component. Wrapping it means (a) reselling someone else's engine, (b) dragging a 3-package proprietary ecosystem, (c) still building the Filament UI, (d) tying your roadmap to a package with 0 stars.

## 2. `xavicabot/filament-appointments` (the real free Filament-native threat)

- **License:** MIT.
- **Versions:** v2.x → **Filament 4 or 5**, PHP 8.2+, Laravel 11.28+/12 (v1.x for Filament 3).
- **Adoption:** 75 Packagist downloads, 0 favers.
- **Features:** `AppointmentPicker` form field; weekly availability rules (per weekday, start/end/interval); manual blocks (full-day & partial); **double-booking prevention via DB-level lock**; email confirmation flow (signed URL, `require_confirmation`, expiry); **Google Calendar freeBusy sync** (marks busy slots) + event creation + Google Meet link.
- **Tables:** `fa_rules`, `fa_blocks`, `fa_bookings`, `fa_connections`, `fa_sources`, `fa_busy_cache`.
- **Model:** owner model configurable (`owner_model`, `owner_label`), timezone is a **single global config value** (`'timezone' => 'Europe/Madrid'`), slot value stored as a `"YYYY-MM-DD HH:MM"` string.
- **Language:** EN, ES, FR, DE, IT.

**Verdict:** the closest free Filament-native competitor. It already covers the *happy path* + Google sync. **Weaknesses:** single global timezone (not per-provider), string slot storage, no payments, no scheduled reminders, no recurring, no multi-tenant, no analytics, no resources/capacity, unproven adoption.

## 3. Athena (Lara Zeus) — the paid incumbent

- **Price:** $69 · **License:** proprietary (premium) · **Compat:** v3/v4/v5 · 28★.
- **Nature:** an extension for their **Bolt** form builder ("Build your own forms and use them as booking systems").
- **Features (complete list from vendor docs):** frontend scaffolding, services, available times per day, timeslot duration, view requests (list / calendar).
- **Absent:** payments, reminders, recurring, calendar sync, resources/capacity, analytics, per-provider timezones.

**Verdict:** v5-ready but **feature-thin**. Its public selling point is the Bolt front-end, not a scheduling engine.

## 4. Building blocks (not full systems)

| Package | Type | Notes |
|---|---|---|
| `guava/calendar` | Calendar component | 220★, free — display only |
| `wooserv/filament-date-time-slots` | Form field | 1 322 downloads — field only |
| `przwl/cine-reserve` | Seat booking | Niche (cinema seats) |
| `edissavov/filament-booking-calendar` | Widget | v3 only, 1 download — dead |

## 5. General Laravel (non-Filament) engines

| Package | License | Notes |
|---|---|---|
| `zaber-dev/laravel-reservation` | MIT | Stateful reservation lifecycle (held→confirmed/cancelled/expired) + atomic concurrency; reusable *concept* for holds |
| `andydefer/laravel-chronos` | MIT | Generic scheduling; 856 downloads |
| `binary-cats/coordinator` | MIT | Resource booking |
| `yanselmask/bookings` | MIT | rinvex-based generic resource booking; legacy (2024); no Filament |

## 6. What this means

1. There is **no free, Filament-native, correctness-focused booking engine with real adoption**. `xavicabot` is the closest but is unproven and structurally limited (global timezone).
2. The paid incumbent (Athena) is **thin** and tied to another product (Bolt).
3. The only comprehensive engine (`artisanpack-ui/bookings`) is **not Filament-native** and would be poor to wrap.
4. **Differentiation must be correctness + Filament-native UX + a Free→Pro path**, not "we also have a calendar".
