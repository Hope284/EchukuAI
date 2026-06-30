# Secret Rotation Procedure

1. Identify the provider, exposure location, and affected environments without copying the value into a ticket.
2. Revoke or rotate it in the authorized provider console.
3. update approved secret storage and production environment variables.
4. Clear configuration/frontend caches and restart workers.
5. verify provider functionality and scan current source, artifacts, and history.
6. Resolve the security alert only after the old credential is unusable.

History rewriting requires a coordinated maintenance window and collaborator notice.
