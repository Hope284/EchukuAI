# GitHub Actions Workflow

The production workflow runs on `main` and serializes deployments. Validation must pass before deployment. The remote script stops when the production tree contains unexpected changes and preserves known generated assets separately.

Required repository secrets identify the SSH host, deploy user, and SSH key. Their values must never appear in issues, logs, documentation, or source files.
