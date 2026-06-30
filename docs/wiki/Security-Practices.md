# Security Practices

Validate authorization in routes/controllers, scope tenant queries, verify webhooks, make financial processing idempotent, sanitize uploads and URLs, and keep provider calls server-side. Do not serialize secrets into views or public configuration endpoints.

CI scans tracked application/frontend source for common provider credential patterns. A passing scan does not replace provider-side rotation after exposure.
