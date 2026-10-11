# Contributing

Thanks for considering contributing to **filament-booking-engine**!

## Running the test suite

```bash
composer install
vendor/bin/pest
```

## Code style

We use [Laravel Pint](https://laravel.com/docs/pint):

```bash
vendor/bin/pint
```

## Pull requests

- Add tests for any behaviour change (the engine is correctness-critical).
- Keep the domain (`src/Engine`, `src/Data`) framework-free — persistence belongs in
  `src/Schedule` and `src/Models`.
- Run `vendor/bin/pest` and `vendor/bin/pint` before opening a PR.

## Reporting bugs

Open an issue with: what you did, what you expected, what happened, and your
PHP / Laravel / Filament versions.
