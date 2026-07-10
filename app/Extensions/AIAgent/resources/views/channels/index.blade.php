@extends('panel.layout.app', ['disable_tblr' => true])

@section('title', $title ?? __('Connected Channels'))
@section('titlebar_subtitle', __('Manage the external channels connected to your DZEVA AI agents.'))

@section('titlebar_actions')
    <x-button
        href="{{ route('dashboard.user.ai-agent.channels.create') }}"
        variant="primary"
    >
        <x-tabler-plus class="size-4" />
        @lang('Connect Channel')
    </x-button>
@endsection

@section('content')
    <div class="py-10">
        <div class="container">
            @if ($channels->isEmpty())
                <x-empty-state
                    class="py-10"
                    icon="tabler-plug-x"
                    title="{{ __('No channels connected yet') }}"
                    description="{{ __('Connect Telegram, WhatsApp, or Slack so your agents can receive and respond to messages.') }}"
                >
                    <x-button
                        href="{{ route('dashboard.user.ai-agent.channels.create') }}"
                        variant="primary"
                    >
                        @lang('Connect your first channel')
                    </x-button>
                </x-empty-state>
            @else
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($channels as $channel)
                        <article class="rounded-2xl border bg-background p-5 shadow-sm">
                            <div class="mb-5 flex items-start justify-between gap-4">
                                <div class="flex min-w-0 items-center gap-3">
                                    <img
                                        class="size-9 object-contain"
                                        src="{{ custom_theme_url('/vendor/ai-agent/images/platforms/' . $channel->type->value . '.png') }}"
                                        alt="{{ $channel->type->label() }}"
                                        width="36"
                                        height="36"
                                    >
                                    <div class="min-w-0">
                                        <h3 class="mb-1 truncate text-base font-semibold">
                                            {{ $channel->name }}
                                        </h3>
                                        <p class="mb-0 text-xs text-foreground/60">
                                            {{ $channel->type->label() }}
                                        </p>
                                    </div>
                                </div>

                                <span @class([
                                    'rounded-full px-2.5 py-1 text-2xs font-medium',
                                    'bg-green-500/10 text-green-600' => $channel->is_active,
                                    'bg-foreground/10 text-foreground/60' => ! $channel->is_active,
                                ])>
                                    {{ $channel->is_active ? __('Active') : __('Inactive') }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between gap-3 text-xs text-foreground/60">
                                <span>
                                    {{ __('Last message') }}:
                                    {{ $channel->last_message_at ? $channel->last_message_at->diffForHumans() : __('Never') }}
                                </span>

                                <form
                                    method="POST"
                                    action="{{ route('dashboard.user.ai-agent.channels.destroy', $channel) }}"
                                    onsubmit="return confirm('{{ __('Remove this channel?') }}')"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <x-button
                                        size="sm"
                                        variant="ghost"
                                        type="submit"
                                    >
                                        @lang('Remove')
                                    </x-button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
