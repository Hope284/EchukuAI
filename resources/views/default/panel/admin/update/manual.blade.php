@extends('panel.layout.app')

@section('title', __('DZEVA Update Status'))

@section('content')
    <div class="py-10">
        <div class="container max-w-3xl">
            <x-card size="lg">
                <div class="flex flex-col gap-4">
                    <div>
                        <h1 class="mb-1 text-2xl font-semibold">{{ __('DZEVA Update Status') }}</h1>
                        <p class="mb-0 text-foreground/70">
                            {{ __('Application updates are installed through the protected production deployment workflow.') }}
                        </p>
                    </div>

                    <x-alert type="success">
                        {{ __('DZEVA is installed and the update route is healthy.') }}
                    </x-alert>

                    <dl class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-medium uppercase text-foreground/50">{{ __('Installed version') }}</dt>
                            <dd class="font-semibold">{{ dzeva_version_label() }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase text-foreground/50">{{ __('Deployment method') }}</dt>
                            <dd class="font-semibold">{{ __('GitHub production workflow') }}</dd>
                        </div>
                    </dl>
                </div>
            </x-card>
        </div>
    </div>
@endsection
