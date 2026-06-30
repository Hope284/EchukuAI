# Payment Architecture

Plans, subscriptions, user orders, gateway products, and existing credit allocation services are reused. Gateway callbacks never trust browser success alone: the server verifies the reference, status, amount, currency, customer identity, and plan mapping before granting access.

Idempotency is anchored to the gateway reference. Subscription activation, prepaid credit, sales reporting, email, and affiliate events must happen once.
