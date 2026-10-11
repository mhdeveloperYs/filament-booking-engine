# Build vs Wrap — recommendation

## Recommendation: **BUILD the core engine** (confirmed)

Re-assessed after the technical review (`docs/01-competitor-review.md`). The conclusion is
unchanged from the original decision, but the *reason* is now evidence-based rather than assumed.

### Why not wrap `artisanpack-ui/bookings`
1. **It is a competitor, not a component.** It is a complete booking product (16 tables, REST API, frontends). Wrapping it means reselling someone else's engine — you cannot meaningfully differentiate.
2. **It is not Filament-native.** It ships its own admin routes + DaisyUI + Livewire components, and only looks like Filament if you also install `artisanpack-ui/cms-framework`. You would still build the entire Filament UI.
3. **Heavy proprietary ecosystem.** Hard deps on `artisanpack-ui/core`, `artisanpack-ui/hooks`, `artisanpack-ui/security`. Committing to all three for a booking feature is poor coupling and a licensing/roadmap risk.
4. **Unproven adoption.** 0 GitHub stars despite a large scope — no evidence the architecture has survived real-world use.
5. **Commercial control.** MIT lets you use it, but you lose roadmap control, licensing flexibility and the ability to differentiate.

### Why BUILD
- Full control of architecture, roadmap and commercial licensing (Free MIT core → Pro).
- Differentiation is **correctness** (timezones/DST, concurrency, buffers) + **Filament-native UX**, which cannot be bought by wrapping another engine.
- Independently maintainable; Pro features (payments, reminders, recurring, multi-tenant) can be layered without rewriting the core.

### Reuse (do not reinvent infrastructure without competitive advantage)
| Capability | Reuse |
|---|---|
| Timezones / DST arithmetic | `nesbot/carbon` |
| Recurring rules (RRULE) — Pro | `simshaun/recurr` or `rlanvin/php-rrule` |
| iCal export — Pro | `sabre/vobject` |
| Email / reminders — Pro | Laravel Notifications + scheduler |
| Payments — Pro | `laravel/cashier` behind a `PaymentGateway` interface |
| Google / Outlook sync — Pro | official Google / Microsoft SDKs |
| Concurrency primitives | native Laravel cache locks + DB unique index (see `zaber-dev/laravel-reservation` for the *concept* of stateful holds) |

### Residual risk (record)
- `xavicabot/filament-appointments` is a **free, Filament-native** competitor on v4/v5 that already does the happy path + Google sync. Mitigation: win on correctness (per-provider timezone, buffers, DST), UX, and the Pro tier (payments, reminders, recurring, multi-tenant, analytics) it does not have.
- Filament team shipping official booking features. Mitigation: stay vertical/deep, ship fast.
