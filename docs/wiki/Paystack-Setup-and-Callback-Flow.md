# Paystack Setup and Callback Flow

1. Configure public and secret Paystack credentials in the gateway settings/environment.
2. Save or synchronize active subscription plans to create server-side product and plan mappings.
3. Initialize checkout with the authenticated customer's order.
4. On callback, verify the reference server-side and compare exact order facts.
5. Commit subscription or credit changes transactionally and record the reference once.
6. Verify webhook signatures before dispatching webhook events.

Do not trust callback metadata, hidden plan fields, or a frontend success message as proof of payment.
