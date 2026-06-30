# Local Development Setup

1. Install the PHP and Node versions required by the repository.
2. Run `composer install` and the configured frontend dependency install.
3. Create `.env` from `.env.example`; never commit it.
4. Configure a disposable local database and run `php artisan migrate`.
5. Run `php artisan test` before publishing changes.

Use sandbox provider credentials only. Local failures caused by missing extensions or services must be reported rather than bypassed.
