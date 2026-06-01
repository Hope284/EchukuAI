# DZEVA

DZEVA is Africa's indigenous all-in-one AI platform built with and for African context, empowering businesses, institutions, educators, creators and communities through practical AI solutions.

## Production

- Domain: https://dzeva.com
- Laravel root: `/var/www/dzeva`
- Public root: `/var/www/dzeva/public`
- Runtime: Ubuntu 24.04, Nginx, PHP 8.3 FPM, MySQL, Supervisor

## Deployment

Production deploys from the `main` branch using `.github/workflows/deploy.yml`.

The workflow connects to the EC2 host over SSH, pulls the latest commit, installs Composer dependencies, runs migrations, refreshes Laravel caches, refreshes theme cache, fixes writable permissions, restarts the queue worker, reloads Nginx, and restarts PHP-FPM.

Required GitHub secrets:

- `EC2_HOST`
- `EC2_USER`
- `EC2_SSH_KEY`

Never commit `.env`, private keys, database dumps, backups, logs, user uploads, or cache files.
