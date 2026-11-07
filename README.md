# DZEVA

DZEVA is Africa's indigenous all-in-one AI platform built with and for African context, empowering businesses, institutions, educators, creators and communities through practical AI solutions.

## Production

- Domain: https://dzeva.com
- Laravel root: `/var/www/dzeva`
- Public root: `/var/www/dzeva/public`
- Runtime: Ubuntu 24.04, Nginx, PHP 8.3 FPM, MySQL, Supervisor

## Deployment

Production deploys use `.github/workflows/deploy.yml`.

- Pull requests to `main` run PHP validation, frontend asset build, secret-pattern checks, and Laravel tests without deploying.
- Pushes to `main` continue to deploy automatically to the existing AWS host using `EC2_HOST`, `EC2_USER`, and `EC2_SSH_KEY`. This keeps AWS available as the rollback deployment.
- Oracle deployments are manual. Run the workflow from `main`, select `oracle`, and select `DEPLOY` in the confirmation field. Configure an `oracle-production` GitHub environment, restricted to `main`, with `ORACLE_HOST`, `ORACLE_USER`, `ORACLE_SSH_KEY`, `ORACLE_SSH_FINGERPRINT`, and `ORACLE_PUBLIC_IP`. The workflow pins the SSH host fingerprint, deploys only the validated `main` commit, and directs route checks to the configured Oracle IP while preserving the `dzeva.com` hostname for TLS.
- The Oracle server must be provisioned and configured before dispatch. The workflow expects Ubuntu with the current DZEVA runtime, `/var/www/dzeva`, a production `.env`, MySQL client access to the private database, Composer, Git, Nginx, PHP 8.3 FPM, Supervisor, and the existing `ubuntu` administrative account. It runs the repository's established backup, migration, cache, worker, scheduler, and smoke-check steps; it does not provision Oracle resources or change DNS.
- Set `ORACLE_PUBLIC_IP` to the reserved IP actually attached to the server (preferably `92.4.142.24` when confirmed available). The SSH host may use the same IP.

The environment example uses the DZEVA domain, a dedicated database account, and database-backed queues. Supply real SMTP and database credentials in the server's untracked `.env` before deployment.

Never commit `.env`, private keys, database dumps, backups, logs, user uploads, or cache files.
