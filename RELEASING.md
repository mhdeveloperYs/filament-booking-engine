# Releasing

The repository is already prepared: `LICENSE`, `CHANGELOG.md`, CI workflows, `.gitattributes`,
a valid `composer.json` and a `v0.1.0` tag. The remaining steps need accounts/credentials.

## 1. Push to GitHub

Create a **public** repo named `filament-booking-engine` under your account/org, then:

```bash
git remote add origin git@github.com:mhstudio/filament-booking-engine.git
git push -u origin main
git push origin --tags
```

> If your GitHub org/user is not `mhstudio`, update `homepage` and `support` in `composer.json`
> and the badge URLs in `README.md` first.

GitHub Actions will run `run-tests` (PHP 8.3/8.4) and `fix-php-code-style-issues` (Pint).

## 2. Publish on Packagist

1. Sign in at https://packagist.org with GitHub.
2. **Submit** → paste `https://github.com/mhstudio/filament-booking-engine`.
3. Enable the **GitHub webhook** (Packagist → package → Settings) so new tags auto-update.

`composer require mhstudio/filament-booking-engine` now works.

## 3. List on the Filament marketplace

1. Go to https://filamentphp.com/author and sign in.
2. Submit the plugin with the Packagist/repo URL, a description, tags (`Panels`, `Forms`,
   `Scheduling`), supported versions (**5.x**), and price **Free**.
3. Add screenshots (panel resources + the public wizard). A short demo GIF converts best.

## 4. Marketing (the part that actually drives installs)

- Build-in-public posts on X/Twitter.
- A YouTube video (English + Darija): "Build a booking system with Filament".
- Submit to Laravel News; post on r/laravel and the Filament Discord.
- A comparison page vs Athena / self-hosted Calendly alternatives (SEO).

## Versioning

- Tag releases: `git tag v0.1.1 && git push origin v0.1.1`.
- Keep `CHANGELOG.md` up to date (Keep a Changelog).

## Before the Pro tier

Ship the free core, gather stars/installs, then gate Pro features (payments, reminders,
recurring, calendar sync, multi-tenant, analytics, AI) behind a license.
