# Social Publishing Scheduler and Queues

The server cron invokes `php artisan schedule:run` every minute. Laravel registers the social publish command and prevents overlapping scheduler executions. Due records are atomically claimed before a provider call; each provider result is stored as published or failed.

Operational checks include `php artisan schedule:list`, queue worker health, failed jobs, OAuth expiry, provider response logs, timezone conversion, and persisted post IDs.
