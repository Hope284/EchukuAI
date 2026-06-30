# Production Deployment

Production is deployed only through the validated GitHub Actions workflow. The workflow checks syntax, extension manifests, committed secret patterns, and Laravel tests before opening an SSH deployment session.

Deployment creates restricted application, database, environment, storage, upload, and extension-asset backups; pulls with fast-forward only; installs optimized dependencies; runs safe migrations; publishes assets; repairs permissions; rebuilds caches; restarts workers; and verifies public routes.

Never use `migrate:fresh`, `db:wipe`, or an unreviewed database seeder in production.
