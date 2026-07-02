# DZEVA 10.9 Comparison Notes

## Package sources

- Core incremental update: `new-version10.90.zip` from the supplied 10.9 core archive.
- New extensions: Phone Call Agent 1.0 and the Gmail, Google Calendar, Google Drive, Notion, and Outlook AI Chat Pro connectors 1.0.
- Updated extensions: AIChatPro 3.7 and AI Agent 1.1.

The full nulled server archive, licence-bypass SQL, extension recovery SQL, uploaded avatars, environment files, and vendor directory were not imported.

## DZEVA integration

- All 125 non-vendor files in the official incremental core update are present; DZEVA-customized files were merged rather than overwritten.
- Existing payment, upload, blog, social publishing, Strategic Partner, Scrolling Buttons, update access, session, and model-runtime fixes remain in place.
- OAuth state is single-use and user-bound, connector tokens are encrypted, connector routes enforce plan access, and client secrets remain server-side.
- Phone-agent routes enforce plan access and ownership, webhooks reject missing signatures, remote training URLs reject private networks, and stored credentials are hidden/encrypted.
- Lifetime Access retains zero included numeric credits while receiving all eligible connector and phone-agent entitlements.

## Version contract

- Internal compatibility version: `10.9.0` (`version.txt` value `10.90`).
- Public version for normal users: `DZEVA Version 1.2`.

## External configuration

Connector OAuth credentials and the phone-agent webhook secret are configured only through production environment variables documented in `.env.example`. External account approval and credentials are required before live OAuth or telephony calls can complete.
