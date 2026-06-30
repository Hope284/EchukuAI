# Release Process

1. Reconcile the current deployed commit and working tree.
2. Compare upstream update packages in staging; never overlay them blindly.
3. Implement focused changes and regression tests.
4. Run syntax, manifest, secret, route, migration, frontend, and Laravel checks.
5. Review the final diff and commit intentionally.
6. Push `main`, monitor the production workflow, and fix any real failure.
7. Verify public and authorized flows, logs, queues, payments, and branding.
