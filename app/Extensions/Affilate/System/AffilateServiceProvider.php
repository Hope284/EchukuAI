<?php

declare(strict_types=1);

namespace App\Extensions\Affilate\System;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class AffilateServiceProvider extends ServiceProvider
{
    // This should not be deleted, the extension tip is required
    protected $listen = [
        \App\Extensions\Affilate\System\Events\AffiliateEvent::class => [
            \App\Extensions\Affilate\System\Listeners\AffiliateListener::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();

        $this->registerViews()
            ->registerMigrations();
    }

    public function registerViews(): static
    {
        $this->loadViewsFrom([__DIR__ . '/../resources/views'], 'affilate');

        return $this;
    }

    public function registerMigrations(): static
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        return $this;
    }
}
