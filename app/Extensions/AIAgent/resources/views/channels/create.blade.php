@extends('panel.layout.app', ['disable_tblr' => true])

@section('title', $title ?? __('Connect a Channel'))
@section('titlebar_subtitle', __('Connect an external messaging channel to your DZEVA AI agents.'))

@section('titlebar_actions')
    <x-button
        href="{{ route('dashboard.user.ai-agent.channels.index') }}"
        variant="outline"
    >
        <x-tabler-arrow-left class="size-4" />
        @lang('Back to Channels')
    </x-button>
@endsection

@section('content')
    <div class="py-10">
        <div class="container max-w-3xl">
            <form
                class="rounded-2xl border bg-background p-6 shadow-sm"
                method="POST"
                action="{{ route('dashboard.user.ai-agent.channels.store') }}"
            >
                @csrf

                <div class="mb-5">
                    <x-forms.input
                        name="name"
                        type="text"
                        label="{{ __('Channel Name') }}"
                        value="{{ old('name') }}"
                        placeholder="{{ __('e.g. Support Telegram Bot') }}"
                        required
                    />
                    @error('name')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-5">
                    <label class="mb-2 block text-2xs font-medium text-label">
                        {{ __('Channel Type') }}
                    </label>
                    <select
                        class="form-select w-full rounded-input border-input-border bg-input-background text-input-foreground"
                        name="type"
                        required
                    >
                        @foreach ($channelTypes as $channelType)
                            @continue(! $channelType->isActive())
                            <option
                                value="{{ $channelType->value }}"
                                @selected(old('type') === $channelType->value)
                            >
                                {{ $channelType->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('type')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-forms.input
                        name="credentials[telegram_token]"
                        type="text"
                        label="{{ __('Telegram Bot Token') }}"
                        value="{{ old('credentials.telegram_token') }}"
                        placeholder="{{ __('Optional unless Telegram is selected') }}"
                    />
                    <x-forms.input
                        name="credentials[slack_bot_token]"
                        type="text"
                        label="{{ __('Slack Bot Token') }}"
                        value="{{ old('credentials.slack_bot_token') }}"
                        placeholder="xoxb-..."
                    />
                    <x-forms.input
                        name="credentials[slack_signing_secret]"
                        type="text"
                        label="{{ __('Slack Signing Secret') }}"
                        value="{{ old('credentials.slack_signing_secret') }}"
                    />
                    <x-forms.input
                        name="credentials[whatsapp_phone_number_id]"
                        type="text"
                        label="{{ __('WhatsApp Phone Number ID') }}"
                        value="{{ old('credentials.whatsapp_phone_number_id') }}"
                    />
                    <x-forms.input
                        name="credentials[whatsapp_access_token]"
                        type="text"
                        label="{{ __('WhatsApp Access Token') }}"
                        value="{{ old('credentials.whatsapp_access_token') }}"
                    />
                    <x-forms.input
                        name="credentials[whatsapp_verify_token]"
                        type="text"
                        label="{{ __('WhatsApp Verify Token') }}"
                        value="{{ old('credentials.whatsapp_verify_token') }}"
                    />
                </div>

                <div class="flex justify-end gap-3">
                    <x-button
                        href="{{ route('dashboard.user.ai-agent.channels.index') }}"
                        variant="ghost"
                    >
                        @lang('Cancel')
                    </x-button>
                    <x-button
                        type="submit"
                        variant="primary"
                    >
                        @lang('Connect Channel')
                    </x-button>
                </div>
            </form>
        </div>
    </div>
@endsection
