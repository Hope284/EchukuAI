# Environment Variables

Environment variables hold database, cache, mail, queue, payment, OAuth, and AI provider configuration. Relevant protected names include `PAYSTACK_SECRET_KEY`, provider API keys, OAuth client secrets, `GOOGLE_FONTS_API_KEY`, and `AI_REALTIME_IMAGE_API_KEY` where configured.

Document names and purpose only. Store production values in the production environment or an approved secret manager. After changing values, rebuild Laravel configuration cache and restart affected workers.
