@php
    $example_prompts = collect(\App\Support\Dzeva\ScrollingButtonDefaults::items())
        ->map(fn($item) => (object) $item)
        ->toArray();
    $example_prompts_json = json_encode($example_prompts, JSON_THROW_ON_ERROR);
    $suggestions = setting('ai_chat_pro_suggestions', $example_prompts_json);
@endphp

@extends('panel.layout.settings')
@section('title', __('AI Chat Pro Settings'))
@section('titlebar_actions', '')

@section('additional_css')
@endsection

@section('settings')
    <form
        method="post"
        enctype="multipart/form-data"
        action=""
    >
        @csrf
        <x-form-step
            auto-increment
            label="{{ __('AI Chat Pro Models Features') }}"
        >
        </x-form-step>
        <x-card
            class="mb-2 max-md:text-center"
            szie="lg"
        >
            <x-form.group class="grid grid-cols-2 gap-1">
                @if (empty(setting('default_ai_engine')) || setting('default_ai_engine') === \App\Domains\Engine\Enums\EngineEnum::OPEN_AI->slug())
                    <!-- Image ability -->
                    <x-form.checkbox
                        class="border-input rounded-input border !px-2.5 !py-3"
                        name="ai_chat_pro_image_generation_feature"
                        label="{{ __('Image Generation') }}"
                        checked="{{ (bool) setting('ai_chat_pro_image_generation_feature', '0') }}"
                        tooltip="{{ __('AI Chat Pro OpenAI models can generate images.') }}"
                    />
                    <!-- Video ability -->
                    {{-- <x-form.checkbox --}}
                    {{-- 	class="border-input rounded-input border !px-2.5 !py-3" --}}
                    {{-- 	value="ai_chat_pro_video_generation_feature" --}}
                    {{-- 	label="{{ __('Video Generation') }}" --}}
                    {{-- 	tooltip="{{ __('AI Chat Pro OpenAI models can generate videos.') }}" --}}
                    {{-- /> --}}
                    <!-- Audio ability -->
                    {{-- <x-form.checkbox --}}
                    {{-- 	class="border-input rounded-input border !px-2.5 !py-3" --}}
                    {{-- 	value="ai_chat_pro_voice_generation_feature" --}}
                    {{-- 	label="{{ __('Audio Generation') }}" --}}
                    {{-- 	tooltip="{{ __('AI Chat Pro OpenAI models can generate audio.') }}" --}}
                    {{-- /> --}}
                @else
                    <x-alert
                        class="my-2"
                        type="warning"
                    >
                        @lang('AI Chat Pro features are only available for OpenAI models. Please select OpenAI as the default engine from the settings page to activate these features.')
                    </x-alert>
                @endif

                @includeIf('multi-model::settings')

                @includeIf('chat-pro-temp-chat::settings.allow-button')

                @includeIf('canvas::settings')

                @includeIf('ai-chat-pro-file-chat::settings.allow-button')

                @includeIf('ai-chat-pro-smart-image::settings')

                @includeIf('ai-chat-pro-entity-highlight::settings')

                @includeIf('ai-chat-pro-highlight-to-ask::settings')

                @includeIf('ai-chat-pro-skills::settings.allow-button')

                <x-form.checkbox
                    class="border-input rounded-input border !px-2.5 !py-3"
                    name="ai_chat_pro_prompt_navigator"
                    label="{{ __('Prompt Navigator') }}"
                    checked="{{ (bool) setting('ai_chat_pro_prompt_navigator', '1') }}"
                    tooltip="{{ __('Enable a navigation sidebar that lets users quickly jump to specific prompts in long conversations.') }}"
                />
            </x-form.group>
        </x-card>

        @if (\App\Helpers\Classes\MarketplaceHelper::isRegistered('ai-chat-pro-smart-image') || \App\Helpers\Classes\MarketplaceHelper::isRegistered('ai-chat-pro-entity-highlight'))
            <x-form-step
                auto-increment
                label="{{ __('Feature Settings') }}"
            >
            </x-form-step>
            <x-card
                class="mb-2 max-md:text-center"
                szie="lg"
            >
                <x-form.group class="flex flex-col gap-4">
                    @if (\App\Helpers\Classes\MarketplaceHelper::isRegistered('ai-chat-pro-smart-image'))
                        <x-forms.input
                            class="form-control"
                            label="{{ __('Smart Image — Max Images Per Response') }}"
                            type="number"
                            name="ai_chat_pro_smart_image_max_images"
                            min="1"
                            max="10"
                            tooltip="{{ __('Maximum number of images to display per AI response.') }}"
                            value="{{ setting('ai_chat_pro_smart_image_max_images', '5') }}"
                        />
                    @endif

                    @if (\App\Helpers\Classes\MarketplaceHelper::isRegistered('ai-chat-pro-entity-highlight'))
                        <x-forms.input
                            class="form-control"
                            label="{{ __('Entity Highlights — Max Per Response') }}"
                            type="number"
                            name="ai_chat_pro_entity_highlight_max"
                            min="1"
                            max="10"
                            tooltip="{{ __('Maximum number of entities to highlight in a single AI response.') }}"
                            value="{{ setting('ai_chat_pro_entity_highlight_max', '3') }}"
                        />

                        <x-forms.input
                            class="form-control"
                            label="{{ __('Entity Highlights — Confidence Threshold') }}"
                            type="number"
                            name="ai_chat_pro_entity_highlight_threshold"
                            min="0.5"
                            max="1.0"
                            step="0.05"
                            tooltip="{{ __('Minimum confidence score (0.5-1.0). Higher = fewer but more accurate highlights.') }}"
                            value="{{ setting('ai_chat_pro_entity_highlight_threshold', '0.8') }}"
                        />
                    @endif
                </x-form.group>
            </x-card>
        @endif

        @includeIf('ai-chat-pro-deep-research::settings.index')

        @includeIf('model-council::settings')

        @includeIf('ai-chat-pro-skills::settings.manage-skills')

        <x-form-step
            auto-increment
            label="{{ __('AI Chat Pro Settings') }}"
        >
        </x-form-step>
        <x-card class="mb-2 max-md:text-center">
            <label class="form-label">
                {{ __('AI Chat Pro Display Type') }}
                <x-info-tooltip :text='__(
                    "If you want to make AI Chat Pro as a separate menu option choose menu option, and if you want \"AI Chat\" to be in pro edition then choose AI Chat option. If you want both then choose both option.",
                )' />
            </label>
            <select
                class="form-select"
                id="ai_chat_display_type"
                name="ai_chat_display_type"
            >
                <option
                    value="menu"
                    {{ setting('ai_chat_display_type', 'menu') === 'menu' ? 'selected' : '' }}
                >
                    {{ __('Dashboard Side Menu') }}
                </option>
                <option
                    value="ai_chat"
                    {{ setting('ai_chat_display_type', 'menu') === 'ai_chat' ? 'selected' : '' }}
                >
                    {{ __('AI Chat') }}
                </option>
                <option
                    value="frontend"
                    {{ setting('ai_chat_display_type', 'menu') === 'frontend' ? 'selected' : '' }}
                >
                    {{ __('Site Front End') }}
                </option>
                <option
                    value="both_fm"
                    {{ setting('ai_chat_display_type', 'menu') === 'both_fm' ? 'selected' : '' }}
                >
                    {{ __('Site Front End & Dashboard Side Menu') }}
                </option>
            </select>
        </x-card>

        <x-card class="mb-2 max-md:text-center">
            <label class="form-label">
                {{ __('Follow-up Suggestion Style') }}
                <x-info-tooltip :text='__(
                    "Choose how follow-up suggestions appear after AI responses. \"Pill\" displays them as horizontal buttons below the chat area. \"Inline\" embeds them within the AI message content.",
                )' />
            </label>
            <select
                class="form-select"
                id="ai_chat_pro_suggestion_style"
                name="ai_chat_pro_suggestion_style"
            >
                <option
                    value="pill"
                    {{ setting('ai_chat_pro_suggestion_style', 'pill') === 'pill' ? 'selected' : '' }}
                >
                    {{ __('Pill') }}
                </option>
                <option
                    value="inline"
                    {{ setting('ai_chat_pro_suggestion_style', 'pill') === 'inline' ? 'selected' : '' }}
                >
                    {{ __('Inline') }}
                </option>
            </select>
        </x-card>

        <x-card class="mb-2 max-md:text-center">
            <label class="form-label">
                {{ __('Default Chat Screen') }}
                <x-info-tooltip text="{{ __('Select which screen should open by default when AI Chat Pro is launched.') }}" />
            </label>

            <select
                class="form-select"
                id="ai_chat_pro_default_screen"
                name="ai_chat_pro_default_screen"
            >
                <option
                    value="pinned"
                    {{ setting('ai_chat_pro_default_screen', 'new') === 'pinned' ? 'selected' : '' }}
                >
                    {{ __('Pinned Conversation') }}
                </option>
                <option
                    value="last"
                    {{ setting('ai_chat_pro_default_screen', 'new') === 'last' ? 'selected' : '' }}
                >
                    {{ __('Last Conversation') }}
                </option>
                <option
                    value="new"
                    {{ setting('ai_chat_pro_default_screen', 'new') === 'new' ? 'selected' : '' }}
                >
                    {{ __('New Blank Chat') }}
                </option>
            </select>
        </x-card>

        <x-card class="mb-2 max-md:text-center">
            <label class="form-label">
                {{ __('Daily Message Limit Count for Guest User') }}
                <x-info-tooltip text="{{ __('This is the daily message limit count for unlogged in users.') }}" />
            </label>
            <input
                class="form-control"
                type="number"
                name="guest_user_daily_message_limit"
                value="{{ setting('guest_user_daily_message_limit', '10') }}"
            />
        </x-card>

        <x-card class="mb-2 max-md:text-center">
            <x-forms.input
                class="form-control"
                label="{{ __('Guest User Footer Text') }}"
                size="lg"
                name="guest_user_bottom_text"
                tooltip="{{ __('This is the text that will be shown at the bottom of the chat for guest users.') }}"
                value="{{ setting('guest_user_bottom_text', 'Login to save your current session. © DZEVA 2026. All rights reserved.') }}"
            />
        </x-card>

        <x-form-step
            auto-increment
            label="{{ __('Mobile Settings') }}"
        >
        </x-form-step>
        <x-card class="mb-2">
            <x-form.group class="grid grid-cols-1 gap-2">
                <x-form.checkbox
                    class="border-input rounded-input border !px-2.5 !py-3"
                    name="ai_chat_pro_show_mobile_nav_header"
                    label="{{ __('Show Mobile Navigation Header') }}"
                    checked="{{ (bool) setting('ai_chat_pro_show_mobile_nav_header', '1') }}"
                    tooltip="{{ __('Show or hide the top navigation header on mobile devices in AI Chat Pro.') }}"
                />
                <x-form.checkbox
                    class="border-input rounded-input border !px-2.5 !py-3"
                    name="ai_chat_pro_show_mobile_bottom_bar"
                    label="{{ __('Show Mobile Bottom Bar') }}"
                    checked="{{ (bool) setting('ai_chat_pro_show_mobile_bottom_bar', '0') }}"
                    tooltip="{{ __('Show or hide the bottom navigation bar on mobile devices in AI Chat Pro.') }}"
                />
            </x-form.group>
        </x-card>

        @includeIf('ai-chat-pro::settings.customizer.typography')

        <div x-data="suggestionManager">
            <x-form-step
                class="py-2"
                auto-increment
                label="{{ __('Scrolling Buttons') }}"
            >
                <x-button
                    class="ms-auto inline-flex size-8 items-center justify-center rounded-full bg-background text-foreground transition-all"
                    size="none"
                    type="button"
                    variant="ghost-shadow"
                    @click="addItem()"
                >
                    <x-tabler-plus class="size-5" />
                </x-button>
            </x-form-step>

            <div class="lqd-chat-pro-suggestions-container flex flex-col gap-2">
                <template
                    x-for="(item, index) in items"
                    :key="index"
                >
                    <x-card
                        class="lqd-chat-pro-suggestion-accordion-item"
                        class:body="!p-0"
                        x-data="{ open: !item.name }"
                    >
                        <div class="flex items-center gap-2 pe-3">
                            <button
                                class="flex grow items-center gap-2 overflow-hidden p-3 text-start text-sm font-medium"
                                type="button"
                                @click="open = !open"
                            >
                                <x-tabler-chevron-down
                                    class="size-4 shrink-0 transition-transform"
                                    ::class="{ 'lqd-is-open -rotate-180': open }"
                                />
                                <span
                                    class="w-full truncate"
                                    x-text="item.name || '{{ __('New button') }}'"
                                ></span>
                            </button>

                            <x-button
                                class="size-6 shrink-0"
                                size="none"
                                variant="danger"
                                type="button"
                                @click="removeItem(index)"
                            >
                                <x-tabler-trash class="size-4" />
                            </x-button>
                        </div>

                        <div
                            class="border-t"
                            x-show="open"
                            x-collapse
                        >
                            <div class="flex flex-col gap-4 px-3 py-4">
                                <x-forms.input
                                    size="lg"
                                    label="{{ __('Button Title') }}"
                                    name="input_name[]"
                                    tooltip="{{ __('The text shown on the scrolling button.') }}"
                                    x-model="item.name"
                                />

                                <x-forms.input
                                    type="text"
                                    size="lg"
                                    name="input_prompt[]"
                                    label="{{ __('Button URL') }}"
                                    placeholder="/chat or https://example.com"
                                    tooltip="{{ __('Use an internal path or a complete http/https URL.') }}"
                                    x-model="item.prompt"
                                />
                            </div>
                        </div>
                    </x-card>
                </template>
            </div>
        </div>

        <x-button
            class="mt-5 w-full"
            type="submit"
            size="lg"
        >
            {{ __('Save') }}
        </x-button>
    </form>
@endsection

@push('script')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('suggestionManager', () => ({
                items: @json(json_decode($suggestions ?? '[]', true)),

                init() {
                    this.addItem = this.addItem.bind(this);
                    this.removeItem = this.removeItem.bind(this);
                },

                addItem() {
                    if (this.items.some(item => !String(item.name || '').trim() || !String(item.prompt || '').trim())) {
                        return toastr.error("{{ __('Please fill all fields before adding a new one.') }}");
                    }

                    this.items.push({
                        name: '',
                        prompt: ''
                    });

                    this.$nextTick(() => {
                        const cards = this.$el.querySelectorAll('.lqd-chat-pro-suggestion-accordion-item');
                        const lastCard = cards[cards.length - 1];
                        lastCard?.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    });
                },

                removeItem(index) {
                    this.items.splice(index, 1);

                    if (this.items.length === 0) {
                        this.items.push({
                            name: '',
                            prompt: ''
                        });
                    }
                },
            }));
        });
    </script>
@endpush
