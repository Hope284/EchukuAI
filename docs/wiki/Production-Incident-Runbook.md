# Production Incident Runbook

1. Stop feature work and establish severity, start time, affected routes, and deployed commit.
2. Capture exact application/web-server/worker errors without exposing secrets or customer data.
3. Confirm backups and production working-tree safety.
4. Reproduce in the narrowest safe environment and patch the root cause.
5. Add a regression test, deploy through the validated workflow, and monitor rollback signals.
6. Verify the live user path, related queues/webhooks, and new log entries.
7. Record cause, impact, fix, evidence, and follow-up actions.
