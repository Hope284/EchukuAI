<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Roles;
use App\Models\ParentAffiliate;
use App\Models\User;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Throwable;

class DzevaAuthenticatedSmokeCommand extends Command
{
    protected $signature = 'dzeva:auth-smoke {--skip-runtime : Skip upload and realtime relay runtime checks}';

    protected $description = 'Render critical DZEVA dashboard routes as authenticated users without exposing credentials.';

    /** @var array<int, string> */
    private array $activeSubscriptionStatuses = [
        'active',
        'trialing',
        'bank_approved',
        'banktransfer_approved',
        'bank_renewed',
        'free_approved',
        'stripe_approved',
        'paypal_approved',
        'iyzico_approved',
        'paystack_approved',
    ];

    /** @var array<int, string> */
    private array $phoneCallEligiblePlans = [
        'Scale',
        'Enterprise',
        'Scale Yearly',
        'Enterprise Yearly',
        'Lifetime Access',
    ];

    public function handle(HttpKernel $kernel): int
    {
        $failed = false;

        $user = $this->normalUser();
        $admin = User::query()->where('type', Roles::SUPER_ADMIN->value)->orderBy('id')->first();

        if (! $user) {
            $this->error('auth-smoke normal user: missing');
            $failed = true;
        }

        if (! $admin) {
            $this->error('auth-smoke super-admin user: missing');
            $failed = true;
        }

        if ($user) {
            foreach ($this->normalRoutes() as $label => [$route, $allowedStatuses]) {
                $failed = ! $this->assertAuthenticatedRoute($kernel, $user, $label, $route, $allowedStatuses) || $failed;
            }

            $partnerUser = $this->strategicPartnerUser();
            if ($partnerUser) {
                $failed = ! $this->assertAuthenticatedRoute(
                    $kernel,
                    $partnerUser,
                    'normal strategic partner dashboard',
                    'dashboard.user.strategic-partner.index',
                ) || $failed;
            } else {
                $this->warn('auth-smoke normal strategic partner dashboard: skipped; no approved partner user found');
            }
        }

        if ($admin) {
            foreach ($this->adminRoutes() as $label => [$route, $allowedStatuses]) {
                $failed = ! $this->assertAuthenticatedRoute($kernel, $admin, $label, $route, $allowedStatuses) || $failed;
            }
        }

        if (! $this->option('skip-runtime')) {
            $failed = ! $this->assertUploadRuntime() || $failed;
            $failed = ! $this->assertPhoneCallRelay() || $failed;
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array<string, array{0: string, 1: array<int, int>}>
     */
    private function normalRoutes(): array
    {
        return [
            'normal dashboard redirect'         => ['dashboard.index', [200, 302]],
            'normal user dashboard'            => ['dashboard.user.index', [200]],
            'normal user settings'             => ['dashboard.user.settings.index', [200]],
            'normal AI chat list'              => ['dashboard.user.openai.chat.list', [200]],
            'normal AI chat conversation'      => ['dashboard.user.openai.chat.chat', [200]],
            'normal AI Chat Pro'               => ['dashboard.user.openai.chat.pro.index', [200]],
            'normal AI Chat Pro connectors'    => ['dashboard.user.ai-chat-pro.connectors.index', [200]],
            'normal Phone Call Agent dashboard' => ['dashboard.phone-call-agent.index', [200]],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: array<int, int>}>
     */
    private function adminRoutes(): array
    {
        return [
            'super-admin dashboard'              => ['dashboard.admin.index', [200]],
            'super-admin general settings'       => ['dashboard.admin.settings.general', [200]],
            'super-admin extensions marketplace' => ['dashboard.admin.marketplace.index', [200]],
            'super-admin AI Chat Pro settings'   => ['dashboard.admin.openai.chat.pro.settings', [200]],
            'super-admin AI chat settings'       => ['dashboard.admin.openai.chat.list', [200]],
            'super-admin plans'                  => ['dashboard.admin.finance.plans.index', [200]],
            'super-admin users'                  => ['dashboard.admin.users.index', [200]],
            'super-admin strategic partners'     => ['dashboard.admin.strategic-partners.index', [200]],
            'super-admin update page'            => ['dashboard.admin.update.index', [200]],
            'super-admin Phone Call Agent settings' => ['dashboard.phone-call-agent.admin.settings', [200]],
        ];
    }

    /**
     * @param array<int, int> $allowedStatuses
     */
    private function assertAuthenticatedRoute(
        HttpKernel $kernel,
        User $user,
        string $label,
        string $routeName,
        array $allowedStatuses = [200],
    ): bool {
        if (! Route::has($routeName)) {
            $this->error("auth-smoke {$label}: route {$routeName} missing");

            return false;
        }

        $uri = route($routeName, [], false);
        $status = null;
        $response = null;

        DB::beginTransaction();

        try {
            $session = app('session')->driver();
            $session->start();
            $session->put('save_login_2fa', true);

            $request = Request::create($uri, 'GET', server: [
                'HTTP_HOST'   => parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'dzeva.com',
                'HTTPS'       => 'on',
                'SERVER_PORT' => 443,
            ]);
            $request->setLaravelSession($session);
            $request->headers->set('Accept', 'text/html,application/xhtml+xml');
            $request->setUserResolver(static fn (): User => $user);

            Auth::guard('web')->setUser($user);

            $response = $kernel->handle($request);
            $status = $response->getStatusCode();

            $kernel->terminate($request, $response);
        } catch (Throwable $e) {
            $this->error(sprintf(
                'auth-smoke %s: %s: %s at %s:%s',
                $label,
                $e::class,
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
            ));

            return $this->rollbackSmokeTransaction(false);
        } finally {
            Auth::guard('web')->forgetUser();
        }

        $ok = in_array($status, $allowedStatuses, true);
        $method = $ok ? 'info' : 'error';
        $this->{$method}(sprintf(
            'auth-smoke %s: %s (%s)',
            $label,
            $ok ? 'ready' : 'failed',
            $status ?? 'no response',
        ));

        unset($response);

        return $this->rollbackSmokeTransaction($ok);
    }

    private function rollbackSmokeTransaction(bool $result): bool
    {
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        return $result;
    }

    private function normalUser(): ?User
    {
        $eligibleUserId = DB::table('subscriptions')
            ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->join('users', 'users.id', '=', 'subscriptions.user_id')
            ->where('users.type', Roles::USER->value)
            ->whereIn('subscriptions.stripe_status', $this->activeSubscriptionStatuses)
            ->where(static function ($query): void {
                $query->whereNull('subscriptions.ends_at')
                    ->orWhere('subscriptions.ends_at', '>', now());
            })
            ->whereIn('plans.name', $this->phoneCallEligiblePlans)
            ->orderByRaw(
                "CASE plans.name WHEN 'Lifetime Access' THEN 0 WHEN 'Enterprise Yearly' THEN 1 WHEN 'Enterprise' THEN 2 WHEN 'Scale Yearly' THEN 3 WHEN 'Scale' THEN 4 ELSE 5 END"
            )
            ->orderByDesc('subscriptions.updated_at')
            ->value('users.id');

        if ($eligibleUserId) {
            return User::query()->find($eligibleUserId);
        }

        return User::query()->where('type', Roles::USER->value)->orderBy('id')->first();
    }

    private function strategicPartnerUser(): ?User
    {
        $partnerUserId = ParentAffiliate::query()
            ->where('status', ParentAffiliate::STATUS_APPROVED)
            ->orderBy('id')
            ->value('user_id');

        return $partnerUserId ? User::query()->find($partnerUserId) : null;
    }

    private function assertUploadRuntime(): bool
    {
        $failed = false;

        foreach ([
            'upload_max_filesize' => '100M',
            'post_max_size'       => '100M',
        ] as $key => $expected) {
            $actual = (string) ini_get($key);
            $ok = strcasecmp($actual, $expected) === 0;
            $this->{$ok ? 'info' : 'error'}("auth-smoke upload {$key}: " . ($ok ? 'ready' : "expected {$expected}, got {$actual}"));
            $failed = $failed || ! $ok;
        }

        foreach ([
            public_path('uploads'),
            public_path('uploads/media/voices'),
            public_path('upload/images/avatar'),
            public_path('upload/images/blog'),
        ] as $path) {
            $ok = is_dir($path) && is_writable($path);
            $this->{$ok ? 'info' : 'error'}('auth-smoke upload path ' . str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path) . ': ' . ($ok ? 'writable' : 'not writable'));
            $failed = $failed || ! $ok;
        }

        return ! $failed;
    }

    private function assertPhoneCallRelay(): bool
    {
        $asset = public_path('vendor/phone-call-agent/images/robot.png');
        $assetOk = is_file($asset);
        $this->{$assetOk ? 'info' : 'error'}('auth-smoke phone-call assets: ' . ($assetOk ? 'ready' : 'missing'));

        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client('tcp://127.0.0.1:8090', $errno, $errstr, 2);
        $relayOk = is_resource($socket);

        if ($relayOk) {
            fclose($socket);
        }

        $this->{$relayOk ? 'info' : 'error'}('auth-smoke phone-call realtime relay: ' . ($relayOk ? 'ready' : "unreachable ({$errno})"));

        return $assetOk && $relayOk;
    }
}
