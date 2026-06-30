# Architecture

DZEVA is a Laravel application with Blade/JavaScript interfaces, extension service providers, queues, scheduled commands, and gateway/provider adapters. Existing plans, subscriptions, orders, entity credits, and affiliate records remain the system of record.

Browser requests enter Laravel routes and policies. Long-running work uses queues. Scheduled publishing is discovered by Laravel's scheduler. Provider secrets remain server-side and are read from environment-backed configuration.
