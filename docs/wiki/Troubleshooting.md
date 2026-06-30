# Troubleshooting

For HTTP 500 errors, capture the exact Laravel/PHP/web-server exception before patching. For missing routes, clear route cache and compare route names. For uploads, check validation, target path, ownership, ACLs, and stored relative paths. For payments, inspect order/reference verification without logging secrets. For social publishing, inspect cron, schedule, workers, tokens, provider scopes, and persisted failure logs.

Do not hide root causes with empty catches.
