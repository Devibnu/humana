<!-- Short, targeted instructions for AI coding agents. Keep this file minimal and link-first. -->
# AGENTS.md

Purpose
-------
Provide minimal, actionable guidance for AI coding agents working in this repository. Link to existing docs rather than duplicating them.

Quick facts
-----------
- Project type: Laravel PHP application (see [composer.json](composer.json) and `artisan`).
- Test runner: PHPUnit / Laravel test runner (see `phpunit.xml`). Tests live in `tests/`.
- Frontend build: `webpack.mix.js` (npm / Laravel Mix).

Useful commands
---------------
Run these when asked to bootstrap or run the repo locally:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed    # requires DB configured
php artisan serve
npm install
npm run dev
```

Run tests
---------

```bash
./vendor/bin/phpunit
# or
php artisan test
```

Where to look
-------------
- Application code: `app/`
- Routes: `routes/` (see [routes/web.php](routes/web.php) and [routes/api.php](routes/api.php))
- Tests: `tests/`
- Database migrations: `database/migrations/`
- Frontend sources: `resources/js/`, `resources/views/`
- Project README and setup notes: [README.md](README.md)

Conventions & notes for agents
-----------------------------
- Prefer linking to the canonical documentation in the repo (see "Link, don't embed").
- When asked to modify code, prefer minimal, focused changes and include tests when feasible.
- Use `php artisan` for framework-level tasks and migrations; use Composer for PHP dependency management and npm for JS tooling.
- This repo uses Excel exports (see `app/Exports/`) — large data exports may rely on `maatwebsite/excel`.

Proposed next agent customizations
---------------------------------
- Create a small skill to run tests and return summarized failures.
- Add a prompt to produce a high-level architecture summary by scanning `app/`, `routes/`, and `config/`.

Links
-----
- Main README: [README.md](README.md)
- Composer manifest: [composer.json](composer.json)
- Frontend config: [webpack.mix.js](webpack.mix.js)
