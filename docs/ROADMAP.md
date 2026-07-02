# DZEVA Roadmap

Production completion is measured by an automated test, a successful deployment workflow, and live verification. Owners are assigned by ECHUKU when work enters delivery.

| Item | Status | Priority | Milestone | Acceptance criteria | Dependencies |
| --- | --- | --- | --- | --- | --- |
| DZEVA 10.9 deployment | Complete | Critical | DZEVA 1.2 | Core, AIChatPro 3.7, AI Agent 1.1, connectors, phone agent, migrations, assets, branding, and routes are live | Production workflow |
| Strategic Partner routes | Complete | Critical | DZEVA 1.2 | Protected partner and admin routes load without server errors | Auth and partner profile |
| Paystack product mapping | Complete | Critical | DZEVA 1.2 | Every active subscription has a valid server-side mapping | Paystack credentials |
| Paystack callback hardening | In verification | Critical | Production recovery | Verification is exact, idempotent, and grants access once | Paystack API and orders |
| Scheduled social publishing | In verification | High | Production recovery | Due posts are claimed once and provider results are persisted | Cron, queue, OAuth scopes |
| Social platform discovery | In verification | High | Production recovery | Every connected supported account appears without cross-user leakage | Provider registry |
| Profile and blog uploads | In verification | High | Production recovery | Valid images persist; invalid files fail cleanly; ownership is enforced | Web-server permissions |
| Security remediation | In progress | Critical | Security hardening | No provider secret is served or committed; exposed credentials are rotated | Provider account access |
| Android production release | Planned | High | Mobile | Signed release passes store review and production API checks | Mobile signing and store accounts |
| D-App dashboard extension | Planned | Medium | Platform expansion | Extension installs, authorizes, and operates within tenant boundaries | Extension APIs |
| Provider expansion | Planned | Medium | Social expansion | Each provider has OAuth, capability checks, retry policy, and live verification | Provider approvals |
| Voice assistant | Planned | Medium | Agentic DZEVA | Consent-aware voice sessions work across supported devices | Audio provider and privacy review |
| Agentic automation | Planned | High | Agentic DZEVA | Auditable workflows execute with approval and rollback controls | Queue and policy engine |
| Mobile offline actions | Planned | Medium | Mobile | Supported actions queue offline and synchronize safely | Mobile release |
| Observability | Planned | High | Reliability | Alerts cover HTTP, queues, cron, payments, provider failures, and storage | Monitoring account |
| Test coverage | Ongoing | High | Reliability | Critical finance, auth, upload, affiliate, and provider paths have regression tests | CI capacity |
| African language support | Ongoing | Medium | Localization | Priority languages have reviewed UI copy and DZEVA model labels | Translation review |
| Enterprise controls | Planned | Medium | Enterprise | Workspace policies, audit exports, and delegated administration are available | Authorization review |
