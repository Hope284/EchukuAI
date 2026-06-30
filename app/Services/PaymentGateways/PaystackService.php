<?php

namespace App\Services\PaymentGateways;

use App\Actions\CreateActivity;
use App\Actions\EmailPaymentConfirmation;
use App\Enums\Plan\FrequencyEnum;
use App\Enums\Plan\TypeEnum;
use App\Events\PaystackWebhookEvent;
use App\Extensions\Affilate\System\Events\AffiliateEvent;
use App\Models\Coupon;
use App\Models\Currency;
use App\Models\CustomBilingPlans;
use App\Models\GatewayProducts;
use App\Models\Gateways;
use App\Models\OldGatewayProducts;
use App\Models\PaystackPaymentInfo;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Usage;
use App\Models\UserOrder;
use App\Services\PaymentGateways\Contracts\CreditUpdater;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Cashier\Subscription as Subscriptions;
use RuntimeException;
use Throwable;

/**
 * Base functions foreach payment gateway
 *
 * @param saveAllProducts                                       || Used to generate new product id and price id of all saved membership plans in paypal gateway
 * @param saveProduct ($plan)                                   || Saves Membership plan product in the gateway
 * @param subscribe ($plan)                                     || Displays Payment Page of the gateway
 * @param subscribeCheckout (Request $request, $referral= null) || -
 * @param prepaid ($plan)                                       || Displays Payment Page of the gateway for prepaid plans
 * @param prepaidCheckout (Request $request, $referral= null)   || -
 * @param getSubscriptionStatus ($incomingUserId = null)        ||
 * @param getSubscriptionDaysLeft                               ||
 * @param subscribeCancel                                       ||
 * @param checkIfTrial                                          ||
 * @param getSubscriptionRenewDate                              ||
 * @param cancelSubscribedPlan ($subscription)                  ||
 */
class PaystackService
{
    use CreditUpdater;

    protected static $GATEWAY_CODE = 'paystack';

    protected static $GATEWAY_NAME = 'PayStack';

    protected static $client = 'https://api.paystack.co/';

    protected static $product_endpoint = 'product';

    protected static $plan_endpoint = 'plan';

    protected static $subscription_endpoint = 'subscription';

    protected static $transaction_verify_endpoint = 'transaction/verify/';

    private static function isLifetimeSubscription(Plan $plan): bool
    {
        return $plan->type === TypeEnum::SUBSCRIPTION->value
            && in_array($plan->frequency, FrequencyEnum::lifetimeValues(), true);
    }

    private static function lifetimeEndsAt(Plan $plan): ?Carbon
    {
        return match ($plan->frequency) {
            FrequencyEnum::LIFETIME_MONTHLY->value => Carbon::now()->addMonths(1),
            FrequencyEnum::LIFETIME_YEARLY->value  => Carbon::now()->addYears(1),
            FrequencyEnum::LIFETIME->value         => null,
            default                                => Carbon::now()->addYears(1),
        };
    }

    private static function recurringEndsAt(Plan $plan): Carbon
    {
        return match ($plan->frequency) {
            FrequencyEnum::YEARLY->value => Carbon::now()->addYear(),
            default                      => Carbon::now()->addMonth(),
        };
    }

    private static function callbackPayload(Request $request): array
    {
        $response = $request->input('response');
        $payload = is_array($response) ? $response : json_decode((string) $response, true);
        $payload = is_array($payload) ? $payload : [];

        $reference = data_get($payload, 'reference')
            ?: data_get($payload, 'trxref')
            ?: $request->input('reference')
            ?: $request->input('trxref');

        if (! is_string($reference) || ! preg_match('/^[A-Za-z0-9.=_-]{4,200}$/', $reference)) {
            throw new RuntimeException('The payment reference is missing or invalid.');
        }

        $payload['reference'] = $reference;

        return $payload;
    }

    private static function verifiedTransaction(string $reference, string $key): array
    {
        $response = self::curl_req_get(self::$transaction_verify_endpoint . rawurlencode($reference), $key);
        $data = data_get($response, 'data');

        if (data_get($response, 'status') !== true || ! is_array($data)) {
            throw new RuntimeException('The payment could not be verified.');
        }

        if (data_get($data, 'status') !== 'success') {
            throw new RuntimeException('The payment was not completed successfully.');
        }

        $verifiedReference = data_get($data, 'reference');
        if (! is_string($verifiedReference) || ! hash_equals($reference, $verifiedReference)) {
            throw new RuntimeException('The verified payment reference does not match the callback.');
        }

        return $data;
    }

    private static function assertVerifiedPayment(array $transaction, UserOrder|float|int $expected, string $currency, string $email): void
    {
        $expectedAmount = $expected instanceof UserOrder ? (float) $expected->price : (float) $expected;
        $expectedMinor = (int) round($expectedAmount * 100);
        $actualMinor = (int) data_get($transaction, 'amount', -1);

        if ($actualMinor !== $expectedMinor) {
            throw new RuntimeException('The verified payment amount does not match the order.');
        }

        $actualCurrency = strtoupper((string) data_get($transaction, 'currency'));
        if ($actualCurrency === '' || $actualCurrency !== strtoupper($currency)) {
            throw new RuntimeException('The verified payment currency does not match the order.');
        }

        $customerEmail = strtolower(trim((string) data_get($transaction, 'customer.email')));
        if ($customerEmail === '' || ! hash_equals(strtolower($email), $customerEmail)) {
            throw new RuntimeException('The verified payment customer does not match the signed-in user.');
        }
    }

    private static function verifiedBillingPlanId(
        array $transaction,
        GatewayProducts $product,
        Plan $plan,
        ?string $submittedPlanId
    ): string {
        if ($product->price_id === 'Not Needed') {
            return 'Not Needed';
        }

        $transactionPlan = data_get($transaction, 'plan');
        $providerPlanId = is_string($transactionPlan) && $transactionPlan !== ''
            ? $transactionPlan
            : data_get($transaction, 'plan_object.plan_code');
        $billingPlanId = trim((string) ($providerPlanId ?: $submittedPlanId));

        if ($billingPlanId === '') {
            throw new RuntimeException('The verified subscription plan is missing.');
        }

        $isMainPlan = is_string($product->price_id) && hash_equals($product->price_id, $billingPlanId);
        $isKnownCustomPlan = CustomBilingPlans::query()
            ->where('gateway', self::$GATEWAY_CODE)
            ->where('plan_id', $plan->id)
            ->where('main_plan_price_id', $product->price_id)
            ->where('custom_plan_price_id', $billingPlanId)
            ->exists();

        if (! $isMainPlan && ! $isKnownCustomPlan) {
            throw new RuntimeException('The verified subscription plan does not match the selected plan.');
        }

        return $billingPlanId;
    }

    private static function subscriptionCode(array $transaction, string $billingPlanId, string $key): string
    {
        $existingCode = data_get($transaction, 'subscription.subscription_code')
            ?: data_get($transaction, 'subscription_code');
        if (is_string($existingCode) && $existingCode !== '') {
            return $existingCode;
        }

        $customer = data_get($transaction, 'customer.customer_code') ?: data_get($transaction, 'customer.id');
        $transactionPlan = data_get($transaction, 'plan');
        $plan = is_string($transactionPlan) && $transactionPlan !== ''
            ? $transactionPlan
            : (data_get($transaction, 'plan_object.plan_code') ?: $billingPlanId);

        if (blank($customer) || blank($plan)) {
            throw new RuntimeException('The verified subscription details are incomplete.');
        }

        $response = self::curl_req_get(
            self::$subscription_endpoint . '?customer=' . rawurlencode((string) $customer) . '&plan=' . rawurlencode((string) $plan),
            $key
        );
        $subscriptions = data_get($response, 'data', []);
        $subscriptions = is_array($subscriptions) ? $subscriptions : [];
        $subscription = collect($subscriptions)->first(static fn ($item) => data_get($item, 'status') === 'active')
            ?? collect($subscriptions)->first();
        $code = data_get($subscription, 'subscription_code');

        if (! is_string($code) || $code === '') {
            throw new RuntimeException('The payment was verified, but the recurring subscription is not ready yet.');
        }

        return $code;
    }

    private static function storePaymentInfo(array $payload, array $transaction, int $userId, string $email, int $planId): void
    {
        $reference = (string) $payload['reference'];
        $plan = data_get($transaction, 'plan');
        $planCode = is_string($plan) ? $plan : (string) data_get($transaction, 'plan_object.plan_code', '');

        PaystackPaymentInfo::query()->create([
            'user_id'       => $userId,
            'email'         => $email,
            'reference'     => $reference,
            'reference_hash' => hash('sha256', $reference),
            'trans'         => (string) data_get($payload, 'trans', ''),
            'status'        => (string) data_get($transaction, 'status', ''),
            'message'       => (string) data_get($payload, 'message', ''),
            'transaction'   => (string) data_get($payload, 'transaction', data_get($transaction, 'id', '')),
            'trxref'        => (string) data_get($payload, 'trxref', ''),
            'amount'        => (string) ((int) data_get($transaction, 'amount', 0) / 100),
            'customer_code' => (string) data_get($transaction, 'customer.customer_code', ''),
            'plan_code'     => $planCode . ' / ' . $planId,
            'currency'      => (string) data_get($transaction, 'currency', ''),
            'other'         => (string) (data_get($transaction, 'paid_at') ?: data_get($transaction, 'paidAt', '')),
        ]);
    }

    private static function gatewayCurrencyCode(Gateways $gateway): string
    {
        return Currency::query()->where('id', $gateway->currency)->value('code') ?: 'NGN';
    }

    // payment functions
    // tested
    public static function saveAllProducts()
    {
        try {
            // Get all membership plans
            $plans = Plan::where('active', 1)->get();
            foreach ($plans as $plan) {
                self::saveProduct($plan);
            }
        } catch (Exception $ex) {
            Log::error("paystack::saveAllProducts()\n" . $ex->getMessage());

            return back()->with(['message' => $ex->getMessage(), 'type' => 'error']);
        }

    }

    public static function getPlansPriceIdsForMigration(): void
    {
        try {
            $gateway = Gateways::where('code', self::$GATEWAY_CODE)->where('is_active', 1)->first();
            if (! $gateway) {
                throw new Exception('Active Paystack gateway not found.');
            }

            $key = self::getKey($gateway);
            $paystackPlansResponse = self::curl_req_get(self::$plan_endpoint . '?perPage=100', $key);
            if (! isset($paystackPlansResponse['data'])) {
                throw new RuntimeException('Could not fetch plans from Paystack.');
            }
            $paystackPlans = collect($paystackPlansResponse['data']);
            DB::beginTransaction();
            $plans = Plan::query()->where('active', 1)->get();
            foreach ($plans as $plan) {
                $product = GatewayProducts::where([
                    'plan_id'      => $plan->id,
                    'gateway_code' => self::$GATEWAY_CODE,
                ])->first();
                if (! $product) {
                    continue;
                }
                $productId = $product->getAttribute('product_id');
                $matchedPlan = $paystackPlans->firstWhere('description', $productId);
                if ($matchedPlan) {
                    $priceId = $matchedPlan['plan_code'];
                    if ($product->price_id !== $priceId) {
                        $product->price_id = $priceId;
                        $product->save();
                    }
                }
            }

            DB::commit();
        } catch (Exception|Throwable $ex) {
            Log::error(self::$GATEWAY_CODE . '-> getPlansPriceIdsForMigration(): ' . $ex->getMessage());
            DB::rollBack();
        }
    }

    public static function getUsersCustomerIdsForMigration(Subscriptions $subscription): null
    {
        return null;
    }

    public static function saveProduct($plan)
    {
        $gateway = Gateways::where('code', self::$GATEWAY_CODE)->where('is_active', 1)->first() ?? abort(404);

        try {
            // 1 begain db transaction
            DB::beginTransaction();
            $currency = self::gatewayCurrencyCode($gateway);
            $taxValue = taxToVal($plan->price, $gateway->tax);
            $total = $plan->price + $taxValue;
            $price = (int) (((float) $total) * 100); // Must be in cents level for paystack
            $key = self::getKey($gateway);
            // Reuse an existing gateway product. Product IDs are stable; recurring
            // plan codes may still be replaced when plan pricing changes.
            $product = GatewayProducts::where(['plan_id' => $plan->id, 'gateway_code' => self::$GATEWAY_CODE])->first();

            $data = [
                'name'        => $plan->name,
                'description' => $plan->name,
                'price'       => $price == 0 ? 1000 : $price,
                'currency'    => $currency,
            ];
            if ($product?->product_id) {
                $productCode = $product->product_id;
            } else {
                $newProduct = self::curl_req(self::$product_endpoint, $key, $data);
                $productCode = data_get($newProduct, 'data.product_code');

                if (! $productCode) {
                    throw new Exception('Paystack did not return a product code.');
                }
            }

            if (! $product) {
                $product = new GatewayProducts;
                $product->plan_id = $plan->id;
                $product->gateway_code = self::$GATEWAY_CODE;
                $product->gateway_title = self::$GATEWAY_NAME;
            }
            $product->product_id = $productCode;
            $product->plan_name = $plan->name;
            $product->save();
            // if not lifetime or free or onetime then create priceID
            if ($plan->price != 0 && $plan->type == TypeEnum::SUBSCRIPTION->value && ! self::isLifetimeSubscription($plan)) {
                $interval = $plan->frequency == FrequencyEnum::MONTHLY->value ? FrequencyEnum::MONTHLY->value : 'annually';

                if (
                    filled($product->price_id)
                    && $product->price_id !== 'Not Needed'
                    && self::paystackPlanMatches($product->price_id, $key, $price, $currency, $interval)
                ) {
                    $product->payload = self::mappingPayload($price, $currency, $plan, $interval);
                    $product->save();
                    DB::commit();

                    return;
                }

                $billingPlan = self::curl_req(self::$plan_endpoint, $key, [
                    'name'        => $plan->name,
                    'interval'    => $interval,
                    'amount'      => $price,
                    'description' => $product->product_id,
                    'currency'    => $currency,
                ]);
                $newPlanCode = data_get($billingPlan, 'data.plan_code');
                if (! is_string($newPlanCode) || $newPlanCode === '') {
                    throw new RuntimeException('Paystack did not return a recurring plan code.');
                }
                if ($product->price_id != null) {
                    $history = new OldGatewayProducts;
                    $history->plan_id = $plan->id;
                    $history->plan_name = $plan->name;
                    $history->gateway_code = self::$GATEWAY_CODE;
                    $history->product_id = $product->product_id;
                    $history->old_product_id = $product->product_id;
                    $history->old_price_id = $product->price_id;
                    $history->new_price_id = $newPlanCode;
                    $history->status = 'check';
                    $history->save();
                    self::updateUserData();
                }
                $product->price_id = $newPlanCode;
                $product->payload = self::mappingPayload($price, $currency, $plan, $interval);
            } else {
                $product->price_id = 'Not Needed';
                $product->payload = self::mappingPayload($price, $currency, $plan);
            }
            $product->save();
            DB::commit();
        } catch (Throwable $ex) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::error(self::$GATEWAY_CODE . "-> saveProduct():\n" . $ex->getMessage());

            throw new RuntimeException('Paystack could not synchronize the plan product mapping.', 0, $ex);
        }
    }

    private static function paystackPlanMatches(string $planCode, string $key, int $amount, string $currency, string $interval): bool
    {
        try {
            $response = self::curl_req_get(self::$plan_endpoint . '/' . rawurlencode($planCode), $key);
            $remote = data_get($response, 'data', []);

            return (int) data_get($remote, 'amount') === $amount
                && strtolower((string) data_get($remote, 'interval')) === strtolower($interval)
                && strtoupper((string) data_get($remote, 'currency')) === strtoupper($currency);
        } catch (Throwable $exception) {
            Log::warning(self::$GATEWAY_CODE . '-> paystackPlanMatches(): ' . $exception->getMessage());

            return false;
        }
    }

    private static function mappingPayload(int $amount, string $currency, Plan $plan, ?string $interval = null): array
    {
        return [
            'amount_minor' => $amount,
            'currency' => strtoupper($currency),
            'frequency' => $plan->frequency,
            'interval' => $interval,
            'plan_type' => $plan->type,
        ];
    }

    // tested
    public static function subscribe($plan)
    {
        $gateway = Gateways::where('code', self::$GATEWAY_CODE)->where('is_active', 1)->first() ?? abort(404);

        try {
            DB::beginTransaction();
            $planId = $plan->id;
            $settings = Setting::getCache();
            $exception = null;
            $orderId = Str::random(12);
            $currency = self::gatewayCurrencyCode($gateway);
            $key = self::getKey($gateway);
            $user = auth()->user();
            $taxRate = $gateway->tax;
            $taxValue = taxToVal($plan->price, $taxRate);
            $coupon = checkCouponInRequest(); // if there a coupon in request it will return the coupin instanse

            $productId = self::getPaystackProductId($plan->id);
            $billingPlanId = self::getPaystackPriceId($plan->id);
            $mainBillingPlanId = $billingPlanId;
            if ($productId == null) {
                $exception = __('Product ID is not set! Please save Membership Plan again.');

                return back()->with(['message' => $exception, 'type' => 'error']);
            }
            if ($billingPlanId == null) {
                $exception = __('Product Price ID is not set! Please save Membership Plan again.');

                return back()->with(['message' => $exception, 'type' => 'error']);
            }
            $newDiscountedPrice = $plan->price + $taxValue; // total with tax
            if ($coupon && $plan->price != 0) {
                $newDiscountedPrice -= ($plan->price * ($coupon->discount / 100));
                if ($newDiscountedPrice != floor($newDiscountedPrice)) {
                    $newDiscountedPrice = round((float) $newDiscountedPrice, 2);
                }
                if ($plan->price != 0 && $plan->type == TypeEnum::SUBSCRIPTION->value && ! self::isLifetimeSubscription($plan)) {
                    $interval = $plan->frequency == FrequencyEnum::MONTHLY->value ? FrequencyEnum::MONTHLY->value : 'annually';
                    $billingPlan = self::curl_req(self::$plan_endpoint, $key, [
                        'name'        => 'discount_item_' . time(),
                        'interval'    => $interval,
                        'amount'      => (int) (((float) $newDiscountedPrice) * 100),
                        'description' => 'coupon_' . $coupon->code . '_user_' . $user->id . '_plan_' . $plan->id,
                        'currency'    => $currency,
                    ]);
                    $billingPlanId = data_get($billingPlan, 'data.plan_code');
                    if (! is_string($billingPlanId) || $billingPlanId === '') {
                        throw new RuntimeException('Paystack did not return a recurring plan code.');
                    }

                    CustomBilingPlans::query()->firstOrCreate([
                        'gateway'              => self::$GATEWAY_CODE,
                        'plan_id'              => $plan->id,
                        'main_plan_price_id'   => $mainBillingPlanId,
                        'custom_plan_price_id' => $billingPlanId,
                    ]);
                }
            }
            $payment = new UserOrder;
            $payment->order_id = $orderId;
            $payment->plan_id = $plan->id;
            $payment->user_id = $user->id;
            $payment->payment_type = self::$GATEWAY_CODE;
            $payment->price = $newDiscountedPrice;
            $payment->affiliate_earnings = ($newDiscountedPrice * $settings->affiliate_commission_percentage) / 100;
            $payment->status = 'Waiting';
            $payment->country = $user->country ?? 'Unknown';
            $payment->save();
            DB::commit();
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error(self::$GATEWAY_CODE . '-> subscribe(): ' . $ex->getMessage());

            return back()->with(['message' => Str::before($ex->getMessage(), ':'), 'type' => 'error']);
        }

        return view('panel.user.finance.subscription.' . self::$GATEWAY_CODE, compact('plan', 'taxRate', 'taxValue', 'newDiscountedPrice', 'billingPlanId', 'exception', 'orderId', 'productId', 'gateway', 'planId'));
    }

    // tested
    public static function subscribeCheckout(Request $request, $referral = null)
    {
        $gateway = Gateways::where('code', self::$GATEWAY_CODE)->where('is_active', 1)->first() ?? abort(404);
        $user = $request->user();
        $dashboardRoute = 'dashboard.' . $user->type->value . '.index';
        $reference = null;

        try {
            $payload = self::callbackPayload($request);
            $reference = $payload['reference'];
            $planId = (int) $request->input('planID');
            $orderId = (string) $request->input('orderID');
            $couponCode = $request->string('couponID')->trim()->toString();

            $plan = Plan::query()->findOrFail($planId);
            $payment = UserOrder::query()
                ->where('order_id', $orderId)
                ->where('plan_id', $planId)
                ->where('user_id', $user->id)
                ->firstOrFail();
            $product = GatewayProducts::query()
                ->where('plan_id', $planId)
                ->where('gateway_code', self::$GATEWAY_CODE)
                ->firstOrFail();
            $key = self::getKey($gateway);
            $transaction = self::verifiedTransaction($payload['reference'], $key);

            self::assertVerifiedPayment($transaction, $payment, self::gatewayCurrencyCode($gateway), $user->email);
            $billingPlanId = self::verifiedBillingPlanId(
                $transaction,
                $product,
                $plan,
                $request->input('billingPlanId')
            );

            $subscriptionCode = $billingPlanId === 'Not Needed'
                ? 'PSLS-' . strtoupper(substr(hash('sha256', $payload['reference']), 0, 24))
                : self::subscriptionCode($transaction, $billingPlanId, $key);

            $processed = DB::transaction(function () use (
                $billingPlanId,
                $couponCode,
                $gateway,
                $orderId,
                $payload,
                $plan,
                $planId,
                $product,
                $subscriptionCode,
                $transaction,
                $user
            ): bool {
                $payment = UserOrder::query()
                    ->where('order_id', $orderId)
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($payment->status === 'Success'
                    || PaystackPaymentInfo::query()->where('reference', $payload['reference'])->exists()) {
                    return false;
                }

                if ($product->price_id !== $billingPlanId) {
                    CustomBilingPlans::query()->firstOrCreate([
                        'gateway'             => self::$GATEWAY_CODE,
                        'plan_id'             => $planId,
                        'main_plan_price_id'  => $product->price_id,
                        'custom_plan_price_id' => $billingPlanId,
                    ]);
                }

                $coupon = $couponCode !== '' ? Coupon::query()->where('code', $couponCode)->first() : null;
                $total = (float) $payment->price;
                $taxValue = taxToVal($plan->price, $gateway->tax);

                $subscription = Subscriptions::query()->firstOrNew([
                    'stripe_id' => $subscriptionCode,
                ]);
                $subscription->stripe_price = $billingPlanId === 'Not Needed' ? $product->price_id : $billingPlanId;
                $subscription->stripe_status = $billingPlanId === 'Not Needed' ? 'paystack_approved' : 'active';
                $subscription->ends_at = $billingPlanId === 'Not Needed'
                    ? self::lifetimeEndsAt($plan)
                    : ($plan->trial_days ? Carbon::now()->addDays($plan->trial_days) : self::recurringEndsAt($plan));
                $subscription->auto_renewal = $billingPlanId === 'Not Needed' && $plan->frequency === FrequencyEnum::LIFETIME->value ? 0 : 1;
                $subscription->user_id = $user->id;
                $subscription->name = (string) $planId;
                $subscription->quantity = 1;
                $subscription->plan_id = $planId;
                $subscription->paid_with = self::$GATEWAY_CODE;
                $subscription->tax_rate = $gateway->tax;
                $subscription->tax_value = $taxValue;
                $subscription->coupon = $coupon?->discount;
                $subscription->total_amount = $total;
                $subscription->save();

                if ($coupon) {
                    $coupon->usersUsed()->syncWithoutDetaching([$user->id]);
                }

                $payment->status = 'Success';
                $payment->save();
                self::storePaymentInfo($payload, $transaction, $user->id, $user->email, $planId);
                self::creditIncreaseSubscribePlan($user, $plan);

                return true;
            }, 3);

            if ($processed) {
                CreateActivity::for($user, __('Subscribed'), $plan->name . ' ' . __('Plan'));
                EmailPaymentConfirmation::create($user, $plan)->send();
                Usage::getSingle()->updateSalesCount((float) $payment->price);

                if (class_exists('App\Extensions\Affilate\System\Events\AffiliateEvent')) {
                    event(new AffiliateEvent((float) $payment->price, $gateway->currency));
                }
            }

            return redirect()->route($dashboardRoute)->with([
                'message' => __('Payment verified successfully. Your plan is active.'),
                'type'    => 'success',
            ]);
        } catch (Throwable $throwable) {
            if ($reference && PaystackPaymentInfo::query()->where('reference', $reference)->exists()) {
                return redirect()->route($dashboardRoute)->with([
                    'message' => __('This payment was already processed successfully.'),
                    'type'    => 'success',
                ]);
            }

            Log::error('Paystack subscription callback failed.', [
                'user_id'  => $user->id,
                'order_id' => $request->input('orderID'),
                'error'    => $throwable->getMessage(),
            ]);

            return redirect()->route($dashboardRoute)->with([
                'message' => __('We could not confirm this payment. No access was granted. Please contact support if you were charged.'),
                'type'    => 'error',
            ]);
        }
    }

    // tested
    public static function prepaid($plan)
    {
        $gateway = Gateways::where('code', self::$GATEWAY_CODE)->where('is_active', 1)->first() ?? abort(404);

        try {
            $taxRate = $gateway->tax;
            $taxValue = taxToVal($plan->price, $taxRate);

            $newDiscountedPrice = $plan->price;
            $coupone = checkCouponInRequest();
            if ($coupone) {
                $newDiscountedPrice = $plan->price - ($plan->price * ($coupone->discount / 100));
                if ($newDiscountedPrice != floor($newDiscountedPrice)) {
                    $newDiscountedPrice = round((float) $newDiscountedPrice, 2);
                }
            }
            $currency = self::gatewayCurrencyCode($gateway);
            $orderId = null;
            $exception = null;
            if (self::getPaystackProductId($plan->id) == null) {
                $exception = 'Product ID is not set! Please save Membership Plan again.';
            }
        } catch (Exception $th) {
            $exception = Str::before($th->getMessage(), ':');
        }

        return view('panel.user.finance.prepaid.' . self::$GATEWAY_CODE, compact('plan', 'newDiscountedPrice', 'taxValue', 'taxRate', 'orderId', 'gateway', 'exception', 'currency'));
    }

    // tested
    public static function prepaidCheckout(Request $request)
    {
        $gateway = Gateways::where('code', self::$GATEWAY_CODE)->where('is_active', 1)->first() ?? abort(404);
        $user = $request->user();
        $dashboardRoute = 'dashboard.' . $user->type->value . '.index';
        $reference = null;

        try {
            $payload = self::callbackPayload($request);
            $reference = $payload['reference'];
            $plan = Plan::query()->findOrFail((int) $request->input('planID'));
            $couponCode = $request->string('couponID')->trim()->toString();
            $coupon = $couponCode !== '' ? Coupon::query()->where('code', $couponCode)->first() : null;
            $price = (float) $plan->price;
            if ($coupon) {
                $price -= $price * ((float) $coupon->discount / 100);
            }

            $transaction = self::verifiedTransaction($payload['reference'], self::getKey($gateway));
            self::assertVerifiedPayment($transaction, $price, self::gatewayCurrencyCode($gateway), $user->email);

            $processed = DB::transaction(function () use ($coupon, $gateway, $payload, $plan, $price, $transaction, $user): bool {
                if (PaystackPaymentInfo::query()->where('reference', $payload['reference'])->lockForUpdate()->exists()) {
                    return false;
                }

                $settings = Setting::getCache();
                $payment = new UserOrder;
                $payment->order_id = 'PS-' . strtoupper(substr(hash('sha256', $payload['reference']), 0, 20));
                $payment->plan_id = $plan->id;
                $payment->type = 'prepaid';
                $payment->user_id = $user->id;
                $payment->payment_type = self::$GATEWAY_CODE;
                $payment->price = $price;
                $payment->affiliate_earnings = ($price * $settings->affiliate_commission_percentage) / 100;
                $payment->status = 'Success';
                $payment->country = $user->country ?? 'Unknown';
                $payment->save();

                if ($coupon) {
                    $coupon->usersUsed()->syncWithoutDetaching([$user->id]);
                }

                self::storePaymentInfo($payload, $transaction, $user->id, $user->email, $plan->id);
                self::creditIncreaseSubscribePlan($user, $plan);

                return true;
            }, 3);

            if ($processed) {
                CreateActivity::for($user, __('Purchased'), $plan->name . ' ' . __('Token Pack'));
                EmailPaymentConfirmation::create($user, $plan)->send();
                Usage::getSingle()->updateSalesCount($price);

                if (class_exists('App\Extensions\Affilate\System\Events\AffiliateEvent')) {
                    event(new AffiliateEvent($price, $gateway->currency));
                }
            }

            return redirect()->route($dashboardRoute)->with([
                'message' => __('Payment verified successfully. Your tokens are ready.'),
                'type'    => 'success',
            ]);
        } catch (Throwable $throwable) {
            if ($reference && PaystackPaymentInfo::query()->where('reference', $reference)->exists()) {
                return redirect()->route($dashboardRoute)->with([
                    'message' => __('This payment was already processed successfully.'),
                    'type'    => 'success',
                ]);
            }

            Log::error('Paystack prepaid callback failed.', [
                'user_id' => $user->id,
                'plan_id' => $request->input('planID'),
                'error'   => $throwable->getMessage(),
            ]);

            return redirect()->route($dashboardRoute)->with([
                'message' => __('We could not confirm this payment. No tokens were added. Please contact support if you were charged.'),
                'type'    => 'error',
            ]);
        }

    }

    // tested
    public static function getSubscriptionDaysLeft()
    {
        $gateway = Gateways::where('code', 'paystack')->first();
        if ($gateway == null) {
            return null;
        }
        if ($gateway->mode == 'sandbox') {
            $key = $gateway->sandbox_client_secret;
        } else {
            $key = $gateway->live_client_secret;
        }
        $user = auth()->user();
        // Get current active subscription
        $activeSub = getCurrentActiveSubscription($user->id);
        if ($activeSub != null) {
            if ($activeSub->stripe_price != 'Not Needed') {
                $reqs = self::curl_req_get(self::$subscription_endpoint . '/' . $activeSub->stripe_id, $key);
                if ($reqs['status'] == false) { // if something went wrong with the request
                    Log::error("PaystackController::getSubscriptionRenewDate() :\n" . json_encode($reqs));

                    return back()->with(['message' => 'Paystack Gateway : ' . json_encode($reqs), 'type' => 'error']);
                }
                if (isset($reqs['data']['next_payment_date'])) {
                    // return \Carbon\Carbon::parse($reqs['data']['next_payment_date'])->format('F jS, Y');
                    return Carbon::now()->diffInDays($reqs['data']['next_payment_date']);
                }
            } else {
                return Carbon::now()->diffInDays(Carbon::parse($activeSub->ends_at));
            }
        }

        return null;
    }

    // tested
    public static function getSubscriptionRenewDate()
    {
        $gateway = Gateways::where('code', 'paystack')->first();
        if ($gateway == null) {
            return null;
        }
        if ($gateway->mode == 'sandbox') {
            $key = $gateway->sandbox_client_secret;
        } else {
            $key = $gateway->live_client_secret;
        }
        $user = auth()->user();
        // Get current active subscription
        $activeSub = getCurrentActiveSubscription($user->id);
        if ($activeSub != null) {
            if ($activeSub->stripe_price != 'Not Needed') {
                $reqs = self::curl_req_get(self::$subscription_endpoint . '/' . $activeSub->stripe_id, $key);
                if ($reqs['status'] == false) { // if something went wrong with the request
                    Log::error("PaystackController::getSubscriptionRenewDate() :\n" . json_encode($reqs));

                    return back()->with(['message' => 'Paystack Gateway : ' . json_encode($reqs), 'type' => 'error']);
                }
                if (isset($reqs['data']['next_payment_date'])) {
                    return Carbon::parse($reqs['data']['next_payment_date'])->format('F jS, Y');
                }

                $activeSub->stripe_status = 'cancelled';
                $activeSub->ends_at = Carbon::now();
                $activeSub->save();

                return Carbon::now()->format('F jS, Y');
            }

            return Carbon::createFromTimeStamp($activeSub->ends_at)->format('F jS, Y');
        }

        return null;
    }

    // tested
    public static function getSubscriptionStatus()
    {
        $gateway = Gateways::where('code', 'paystack')->first();
        if ($gateway == null) {
            return null;
        }
        if ($gateway->mode == 'sandbox') {
            $key = $gateway->sandbox_client_secret;
        } else {
            $key = $gateway->live_client_secret;
        }
        $user = auth()->user();
        $activeSub = getCurrentActiveSubscription($user->id);
        if ($activeSub != null) {
            if ($activeSub->stripe_price != 'Not Needed') {
                $reqs = self::curl_req_get(self::$subscription_endpoint . '/' . $activeSub->stripe_id, $key);
                if ($reqs['status'] == false) { // if something went wrong with the request
                    Log::error("PaystackController::getSubscriptionStatus() :\n" . json_encode($reqs));

                    return back()->with(['message' => 'Paystack Gateway : ' . json_encode($reqs), 'type' => 'error']);
                }
                if ($reqs['data']['status'] == 'active') {
                    return true;
                }

                $activeSub->stripe_status = 'cancelled';
                $activeSub->ends_at = Carbon::now();
                $activeSub->save();

                return false;
            }

            return true;
        }

        return null;
    }

    // tested
    public static function cancelSubscribedPlan($planId, $subsId)
    {
        $currentSubscription = Subscriptions::where('id', $subsId)->first();
        if ($currentSubscription != null) {
            $plan = Plan::where('id', $planId)->first();
            $gateway = Gateways::where('code', 'paystack')->first();
            if ($gateway == null) {
                return null;
            }

            if ($gateway->mode == 'sandbox') {
                $key = $gateway->sandbox_client_secret;
            } else {
                $key = $gateway->live_client_secret;
            }

            if ($currentSubscription->stripe_price != 'Not Needed') {
                $get_subscribe_info = self::curl_req_get(self::$cancelSubscribedPlan . '/' . $currentSubscription->stripe_id, $key);
                if ($get_subscribe_info['status'] == false) { // if something went wrong with the request
                    Log::error("PaystackController::cancelSubscribedPlan() :\n" . json_encode($get_subscribe_info));

                    return back()->with(['message' => 'Paystack Gateway : ' . json_encode($get_subscribe_info), 'type' => 'error']);
                }

                $request = self::curl_req(self::$subscription_endpoint . '/disable', $key, [
                    'code'  => $currentSubscription->stripe_id,
                    'token' => $get_subscribe_info['data']['email_token'],
                ]);

                if ($request['status'] == true && $request['message'] == 'Subscription disabled successfully') {
                    $currentSubscription->stripe_status = 'cancelled';
                    $currentSubscription->ends_at = Carbon::now();
                    $currentSubscription->save();

                    return true;
                }
            } else {
                $currentSubscription->stripe_status = 'cancelled';
                $currentSubscription->ends_at = Carbon::now();
                $currentSubscription->save();

                return true;
            }
        }

        return false;
    }

    // tested
    public static function subscribeCancel()
    {
        $user = auth()->user();
        // Get current active subscription
        $activeSub = getCurrentActiveSubscription($user->id);
        if ($activeSub != null) {
            $plan = Plan::where('id', $activeSub->plan_id)->first();
            $gateway = Gateways::where('code', 'paystack')->first();
            if ($gateway == null) {
                abort(404);
            }
            if ($gateway->mode == 'sandbox') {
                $key = $gateway->sandbox_client_secret;
            } else {
                $key = $gateway->live_client_secret;
            }
            if ($activeSub->stripe_price != 'Not Needed') {
                $reqs = self::curl_req_get(self::$subscription_endpoint . '/' . $activeSub->stripe_id, $key);
                if ($reqs['status'] == false) { // if something went wrong with the request
                    abort(404, $reqs['message']);
                }
                $mailToken = $reqs['data']['email_token'];
                $request = self::curl_req(self::$subscription_endpoint . '/disable', $key, [
                    'code'  => $activeSub->stripe_id,
                    'token' => $mailToken,
                ]);
                if ($request['status'] == true && $request['message'] == 'Subscription disabled successfully') {
                    $activeSub->stripe_status = 'cancelled';
                    $activeSub->ends_at = Carbon::now();
                    $activeSub->save();

                    self::creditDecreaseCancelPlan($user, $plan);

                    CreateActivity::for($user, 'Cancelled', 'Subscription plan');

                    return back()->with(['message' => __('Your subscription is cancelled succesfully.'), 'type' => 'success']);
                }

                Log::error('PaystackController::disableOldSubscriptionAndReturnNew(): ' . $request['message']);

                return back()->with(['message' => __('Your subscription could not cancelled.'), 'type' => 'error']);
            }

            $activeSub->stripe_status = 'cancelled';
            $activeSub->ends_at = Carbon::now();
            $activeSub->save();

            self::creditDecreaseCancelPlan($user, $plan);

            CreateActivity::for($user, 'Cancelled', 'Subscription plan');

            return back()->with(['message' => __('Your subscription is cancelled succesfully.'), 'type' => 'success']);
        }

        return back()->with(['message' => __('Could not find active subscription. Nothing changed!'), 'type' => 'error']);
    }

    // tested
    private static function isValidWebhookSignature(string $input, string $secret, ?string $signature): bool
    {
        if (blank($signature)) {
            return false;
        }

        $expectedSignature = hash_hmac('sha512', $input, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    // tested
    public static function handleWebhook(Request $request)
    {
        $input = $request->getContent();
        $secret = self::getKey();

        if (! self::isValidWebhookSignature($input, $secret, $request->header('x-paystack-signature'))) {
            return response()->json(['status' => 'error'], 401);
        }

        $payload = json_decode($input, true);
        if (! is_array($payload) || blank(data_get($payload, 'event')) || ! is_array(data_get($payload, 'data'))) {
            return response()->json(['status' => 'error'], 422);
        }

        event(new PaystackWebhookEvent($payload));

        return response()->json(['status' => 'ok']);
    }

    // tested
    /**
     * curl post request template
     */
    public static function curl_req($second_url, $key, $data = [])
    {
        $fields_string = http_build_query($data);
        // open connection
        $ch = curl_init();
        // set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, self::$client . $second_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $key,
            'Cache-Control: no-cache',
        ]);
        // So that curl_exec returns the contents of the cURL; rather than echoing it
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // execute post
        $request = curl_exec($ch);
        curl_close($ch);
        if ($request) {
            $result = json_decode($request, true);
            if (isset($result['status']) && $result['status'] !== true) {
                abort(400, 'Paystack: ' . $result['message']);
            }

            return $result;
        }

        abort(400);
    }

    // tested
    /**
     * curl get request template
     */
    public static function curl_req_get($param, $key)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => self::$client . $param,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $key,
                'Cache-Control: no-cache',
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            abort(400, 'Paystack: ' . $err);
        } else {
            $result = json_decode($response, true);
            if (isset($result['status']) && $result['status'] !== true) {
                abort(400, 'Paystack: ' . $result['message']);
            }

            return $result;
        }
    }

    // tested
    /**
     * Reads GatewayProducts table and returns price id of the given plan
     */
    public static function getPaystackPriceId($planId)
    {

        // check if plan exists
        $plan = Plan::where('id', $planId)->first();
        if ($plan != null) {
            $product = GatewayProducts::where(['plan_id' => $planId, 'gateway_code' => 'paystack'])->first();
            if ($product != null) {
                return $product->price_id;
            }

            return null;
        }

        return null;
    }

    // tested
    /**
     * Reads GatewayProducts table and returns price id of the given plan
     */
    public static function getPaystackProductId($planId)
    {

        // check if plan exists
        $plan = Plan::where('id', $planId)->first();
        if ($plan != null) {
            $product = GatewayProducts::where(['plan_id' => $planId, 'gateway_code' => 'paystack'])->first();
            if ($product != null) {
                return $product->product_id;
            }

            return null;
        }

        return null;
    }

    // tested
    /**
     * get key if sadbox or live
     */
    private static function getKey($gateway = null)
    {
        $theGateway = $gateway ?? Gateways::where('code', self::$GATEWAY_CODE)->where('is_active', 1)->first() ?? abort(404);
        if ($theGateway->mode == 'sandbox') {
            $key = $theGateway->sandbox_client_secret;
        } else {
            $key = $theGateway->live_client_secret;
        }

        return $key;
    }

    // tested
    /**
     * Since price id (billing plan) is changed, we must update user data, i.e cancel current subscriptions.
     */
    public static function updateUserData()
    {
        $key = self::getKey();

        try {
            $history = OldGatewayProducts::where(['gateway_code' => self::$GATEWAY_CODE, 'status' => 'check'])->get();
            if ($history != null) {
                foreach ($history as $record) {
                    // check record current status from gateway
                    $lookingFor = $record->old_price_id; // billingPlan id in paystack
                    // get also subscription id and customer id and mail token
                    // search subscriptions for record
                    $subs = Subscriptions::where('paid_with', self::$GATEWAY_CODE)
                        ->where('stripe_status', 'active')
                        ->where('stripe_price', $lookingFor)
                        ->get();
                    foreach ($subs ?? [] as $sub) {
                        $subscriptionId = $sub->stripe_id;
                        $reqs = self::curl_req_get(self::$subscription_endpoint . '/' . $subscriptionId, $key);
                        if ($reqs['status'] == false) { // if something went wrong with the request
                            abort(404);
                        }
                        $mailToken = $reqs['data']['email_token'];
                        $customerId = $reqs['data']['customer']['customer_code'];
                        $planId = $reqs['data']['plan']['plan_code'];
                        // cancel old subscription from gateway
                        $new_subscription_code = self::disableOldSubscriptionAndReturnNew($subscriptionId, $mailToken, $customerId, $planId);
                        if ($new_subscription_code == false) {
                            Log::error('PaystackService::updateUserData(): Could not create new subscription for user: ' . $sub->user_id);

                            continue;
                        }
                        $sub->stripe_id = $new_subscription_code;
                        $sub->save();
                    }
                    $record->status = 'checked';
                    $record->save();
                }
            }
        } catch (Exception $ex) {
            Log::error(self::$GATEWAY_CODE . "-> updateUserData():\n" . $ex->getMessage());

            return ['result' => Str::before($ex->getMessage(), ':')];
        }

    }

    // tested
    public static function disableOldSubscriptionAndReturnNew($subscriptionId, $mail_token, $customerID, $planID)
    {
        $key = self::getKey();
        $request = self::curl_req(self::$subscription_endpoint . '/disable', $key, [
            'code'  => $subscriptionId,
            'token' => $mail_token,
        ]);
        if ($request['status'] == true && $request['message'] == 'Subscription disabled successfully') {
            // create new subscription insted of old one
            $req = self::curl_req(self::$subscription_endpoint, $key, [
                'customer' => $customerID,
                'plan'     => $planID,
            ]);
            if ($req['status'] == false) {
                Log::error('PaystackService::disableOldSubscriptionAndReturnNew(): ' . $req['message']);

                return false;
            }

            return $req['data']['subscription_code'];
        }

        Log::error('PaystackService::disableOldSubscriptionAndReturnNew(): ' . $request['message']);

        return false;
    }

    // tested
    public static function checkIfTrial()
    {
        return false;
    }

    public static function gatewayDefinitionArray(): array
    {
        return [
            'code'                  => 'paystack',
            'title'                 => 'Paystack',
            'link'                  => 'https://paystack.com/',
            'active'                => 0,
            'available'             => 1,
            'img'                   => '/assets/img/payments/paystack-2.svg',
            'whiteLogo'             => 0,
            'mode'                  => 1,
            'sandbox_client_id'     => 1,
            'sandbox_client_secret' => 1,
            'sandbox_app_id'        => 0,
            'live_client_id'        => 1,
            'live_client_secret'    => 1,
            'live_app_id'           => 0,
            'currency'              => 1,
            'currency_locale'       => 0,
            'notify_url'            => 0,
            'base_url'              => 0,
            'sandbox_url'           => 0,
            'locale'                => 0,
            'validate_ssl'          => 0,
            'webhook_secret'        => 0,
            'logger'                => 0,
            'tax'                   => 1,              // Option in settings
            'bank_account_details'  => 0,
            'bank_account_other'    => 0,
        ];
    }
}
