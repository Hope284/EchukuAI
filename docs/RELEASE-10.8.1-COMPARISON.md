# DZEVA 10.8.1 Comparison Notes

The update archives were extracted to a temporary staging directory and compared before integration. No archive was overlaid onto production.

## Files used for regression comparison

- `app/Http/Controllers/BlogController.php`
- `app/Http/Controllers/Dashboard/UserController.php`
- `app/Services/PaymentGateways/PaystackService.php`
- `public/themes/default/assets/js/panel/blog.js`

## Applied upstream-compatible behavior

- Restored the expected profile/blog form-to-controller upload flow while retaining absolute public paths, validation, SVG sanitization, ownership checks, and deployment permissions.
- Retained the existing plan/order/credit architecture and hardened Paystack's callback verification instead of adding a second payment processor.
- Added the missing 10.8.1 entity driver/enum registration required by the shipped model catalog.
- Published extension assets and retained their runtime service-provider conventions.

## DZEVA behavior intentionally retained

- DZEVA/ECHUKU public branding and DZEVA-native model labels.
- Strategic Partner routes and authorization.
- Public version `DZEVA Version 1.2` with technical release visibility restricted to super administrators.
- Existing plans, subscriptions, orders, credits, affiliates, uploads, and production data.
- Server-side provider identifiers and extension/package namespaces required for compatibility.

The archives also contained environment material, license-bypass material, and a destructive activity migration. Those items were not applied.
