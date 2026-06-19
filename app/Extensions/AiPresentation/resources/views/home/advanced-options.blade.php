<div
    @class([
        'lqd-adv-img-editor-advanced-options group/advanced-options py-9 closed',
    ])
    :class="{ opened: open, closed: !open }"
    x-data="{
        open: false,
        selectedTextMode: 'generate',
        selectedFormat: '',
        selectedCardSplit: '',
   
        selectedExportAs: 'pptx',
        selectedTextAmount: '',
        selectedLanguage: '',
        selectedImageSourceLabel: '',
        selectedImageSourceValue: '',
        selectedAiImageModel: '',
        selectedAiImageModelLabel: '',
        selectedCardDimensions: '',
        selectedWorkspaceAccess: '',
        selectedExternalAccess: '',
        selectedTextTone: '',
        selectedTextAudience: '',
        selectedImageStyle: '',
        additionalInstructions: ''
    }"
>
    <button
        class="mb-14 flex w-full items-center gap-7 text-2xs font-medium"
        type="button"
        @click.prevent="open = !open"
    >
        <span class="h-px grow bg-heading-foreground/5"></span>
        <span class="flex items-center gap-2">
            @lang('Advanced Options')
            <x-tabler-chevron-down class="size-4 transition-transform group-[&.opened]/advanced-options:rotate-180" />
        </span>
        <span class="h-px grow bg-heading-foreground/5"></span>
    </button>

    <div
        class="flex flex-wrap gap-3"
        x-show="open"
        x-collapse
        x-cloak
    >
        <!-- Text Mode -->
        <div class="flex-shrink-0">
            <x-dropdown.dropdown
                anchor="start"
                offsetY="15px"
            >
                <x-slot:trigger
                    variant="outline"
                >
                    <x-tabler-text-size class="size-4" />
                    <span x-text="selectedTextMode || '@lang('Text Mode')'"></span>
                </x-slot:trigger>
                <x-slot:dropdown
                    class="min-w-[150px] rounded-lg bg-background p-2 shadow-lg"
                >
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedTextMode = 'generate'"
                    >
                        @lang('Generate')
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedTextMode = 'condense'"
                    >
                        @lang('Condense')
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedTextMode = 'preserve'"
                    >
                        @lang('Preserve')
                    </a>
                </x-slot:dropdown>
            </x-dropdown.dropdown>
            <input
                type="hidden"
                name="textMode"
                x-model="selectedTextMode"
            >
        </div>

        <!-- Format -->
        <div class="flex-shrink-0">
            <x-dropdown.dropdown
                anchor="start"
                offsetY="15px"
            >
                <x-slot:trigger
                    variant="outline"
                >
                    <x-tabler-layout class="size-4" />
                    <span x-text="selectedFormat || '@lang('Format')'"></span>
                </x-slot:trigger>
                <x-slot:dropdown
                    class="min-w-[150px] rounded-lg bg-background p-2 shadow-lg"
                >
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedFormat = 'presentation'"
                    >
                        @lang('Presentation')
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedFormat = 'document'"
                    >
                        @lang('Document')
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedFormat = 'social'"
                    >
                        @lang('Social')
                    </a>
                </x-slot:dropdown>
            </x-dropdown.dropdown>
            <input
                type="hidden"
                name="format"
                x-model="selectedFormat"
            >
        </div>

        <div class="flex-shrink-0">
            <x-dropdown.dropdown
                anchor="start"
                offsetY="15px"
            >
                <x-slot:trigger
                    variant="outline"
                >
                    <x-tabler-file-download class="size-4" />
                    <span x-text="selectedExportAs || '@lang('Export As')'"></span>
                </x-slot:trigger>
                <x-slot:dropdown
                    class="min-w-[150px] rounded-lg bg-background p-2 shadow-lg"
                >
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedExportAs = 'pptx'"
                    >
                        @lang('PPTX')
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedExportAs = 'pdf'"
                    >
                        @lang('PDF')
                    </a>
                </x-slot:dropdown>
            </x-dropdown.dropdown>
            <input
                type="hidden"
                name="exportAs"
                x-model="selectedExportAs"
            >
        </div>

        <!-- Card Split -->
        <div class="flex-shrink-0">
            <x-dropdown.dropdown
                anchor="start"
                offsetY="15px"
            >
                <x-slot:trigger
                    variant="outline"
                >
                    <x-tabler-layout-columns class="size-4" />
                    <span x-text="selectedCardSplit || '@lang('Card Split')'"></span>
                </x-slot:trigger>
                <x-slot:dropdown
                    class="min-w-[150px] rounded-lg bg-background p-2 shadow-lg"
                >
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedCardSplit = 'auto'"
                    >
                        @lang('Auto')
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedCardSplit = 'inputTextBreaks'"
                    >
                        @lang('AI will look for text breaks \n---\n in your prompt ')
                    </a>
                </x-slot:dropdown>
            </x-dropdown.dropdown>
            <input
                type="hidden"
                name="cardSplit"
                x-model="selectedCardSplit"
            >
        </div>

        <!-- Text Tone -->
        <div class="flex-shrink-0">
            <x-dropdown.dropdown
                anchor="start"
                offsetY="15px"
            >
                <x-slot:trigger
                    variant="outline"
                >
                    <x-tabler-mood-smile class="size-4" />
                    <span x-text="selectedTextTone || '@lang('Text Tone')'"></span>
                </x-slot:trigger>
                <x-slot:dropdown
                    class="min-w-[150px] rounded-lg bg-background p-2 shadow-lg"
                >
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedTextTone = 'professional'"
                    >
                        @lang('Professional')
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedTextTone = 'upbeat'"
                    >
                        @lang('Upbeat')
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedTextTone = 'inspiring'"
                    >
                        @lang('Inspiring')
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedTextTone = 'casual'"
                    >
                        @lang('Casual')
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedTextTone = 'formal'"
                    >
                        @lang('Formal')
                    </a>
                </x-slot:dropdown>
            </x-dropdown.dropdown>
            <input
                type="hidden"
                name="textOptions[tone]"
                x-model="selectedTextTone"
            >
        </div>

        <!-- Text Audience -->
        <div class="flex-shrink-0">
            <x-dropdown.dropdown
                anchor="start"
                offsetY="15px"
            >
                <x-slot:trigger
                    variant="outline"
                >
                    <x-tabler-users class="size-4" />
                    <span x-text="selectedTextAudience || '@lang('Text Audience')'"></span>
                </x-slot:trigger>
                <x-slot:dropdown
                    class="min-w-[150px] rounded-lg bg-background p-2 shadow-lg"
                >
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedTextAudience = 'general'"
                    >
                        @lang('General')
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedTextAudience = 'business'"
                    >
                        @lang('Business')
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedTextAudience = 'students'"
                    >
                        @lang('Students')
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedTextAudience = 'technical'"
                    >
                        @lang('Technical')
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedTextAudience = 'creative'"
                    >
                        @lang('Creative')
                    </a>
                </x-slot:dropdown>
            </x-dropdown.dropdown>
            <input
                type="hidden"
                name="textOptions[audience]"
                x-model="selectedTextAudience"
            >
        </div>

        <!-- Image Source -->
        <div class="flex-shrink-0">
            <x-dropdown.dropdown
                anchor="start"
                offsetY="15px"
            >
                <x-slot:trigger
                    variant="outline"
                >
                    <x-tabler-photo class="size-4" />
                    <span x-text="selectedImageSourceLabel || '@lang('Image Source')'"></span>
                </x-slot:trigger>

                <x-slot:dropdown
                    class="max-h-60 min-w-[200px] overflow-y-auto rounded-lg bg-background p-2 shadow-lg"
                >
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="
                   selectedImageSourceLabel = '{{ __('AI-generated images') }}';
                   selectedImageSourceValue = 'aiGenerated';

               "
                    >
                        @lang('AI-generated images')
                    </a>

                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="
                   selectedImageSourceLabel = '{{ __('Pull images from Pictographic library') }}';
                   selectedImageSourceValue = 'pictographic';
               "
                    >
                        @lang('Pull images from Pictographic library')
                    </a>

                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="
                   selectedImageSourceLabel = '{{ __('Get photos from Unsplash') }}';
                   selectedImageSourceValue = 'unsplash';
               "
                    >
                        @lang('Get photos from Unsplash')
                    </a>

                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="
                   selectedImageSourceLabel = '{{ __('Get animated GIFs from Giphy') }}';
                   selectedImageSourceValue = 'giphy';
               "
                    >
                        @lang('Get animated GIFs from Giphy')
                    </a>

                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="
                   selectedImageSourceLabel = '{{ __('Find relevant images from the web (any license)') }}';
                   selectedImageSourceValue = 'webAllImages';
               "
                    >
                        @lang('Find relevant images from the web (any license)')
                    </a>

                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="
                   selectedImageSourceLabel = '{{ __('Find images free for personal use') }}';
                   selectedImageSourceValue = 'webFreeToUse';
               "
                    >
                        @lang('Find images free for personal use')
                    </a>

                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="
                   selectedImageSourceLabel = '{{ __('Find images licensed for commercial use') }}';
                   selectedImageSourceValue = 'webFreeToUseCommercially';
               "
                    >
                        @lang('Find images licensed for commercial use')
                    </a>

                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="
                   selectedImageSourceLabel = '{{ __('Add blank image placeholders') }}';
                   selectedImageSourceValue = 'placeholder';
               "
                    >
                        @lang('Add blank image placeholders')
                    </a>

                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="
                   selectedImageSourceLabel = '{{ __('Create content with no images') }}';
                   selectedImageSourceValue = 'noImages';
               "
                    >
                        @lang('Create content with no images')
                    </a>
                </x-slot:dropdown>
            </x-dropdown.dropdown>

            <!-- Hidden input for form submission -->
            <input
                type="hidden"
                name="imageOptions[source]"
                x-model="selectedImageSourceValue"
            >
        </div>

        <!-- AI Image Model (only shown when aiGenerated is selected) -->
        <div
            class="flex-shrink-0"
            x-show="selectedImageSourceValue === 'aiGenerated'"
        >
            <x-dropdown.dropdown
                anchor="start"
                offsetY="15px"
            >
                <x-slot:trigger
                    variant="outline"
                >
                    <x-tabler-brain class="size-4" />
                    <span x-text="selectedAiImageModelLabel || '@lang('AI Image Model')'"></span>
                </x-slot:trigger>
                <x-slot:dropdown
                    class="max-h-60 min-w-[150px] overflow-y-auto rounded-lg bg-background p-2 shadow-lg"
                >
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedAiImageModel = ''; selectedAiImageModelLabel = '{{ __('Auto') }}'"
                    >
                        @lang('Auto')
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedAiImageModel = 'flux-1-quick'; selectedAiImageModelLabel = 'Taswira Haraka'"
                    >
                        Taswira Haraka
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedAiImageModel = 'flux-kontext-fast'; selectedAiImageModelLabel = 'Taswira Muktadha'"
                    >
                        Taswira Muktadha
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedAiImageModel = 'imagen-3-flash'; selectedAiImageModelLabel = 'Taswira Picha'"
                    >
                        Taswira Picha
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedAiImageModel = 'luma-photon-flash-1'; selectedAiImageModelLabel = 'Taswira Mwanga'"
                    >
                        Taswira Mwanga
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedAiImageModel = 'flux-1-pro'; selectedAiImageModelLabel = 'Taswira Sanaa'"
                    >
                        Taswira Sanaa
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedAiImageModel = 'imagen-3-pro'; selectedAiImageModelLabel = 'Taswira Ubunifu'"
                    >
                        Taswira Ubunifu
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedAiImageModel = 'ideogram-v3-turbo'; selectedAiImageModelLabel = 'Taswira Nembo'"
                    >
                        Taswira Nembo
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedAiImageModel = 'luma-photon-1'; selectedAiImageModelLabel = 'Taswira Mwanga Pro'"
                    >
                        Taswira Mwanga Pro
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedAiImageModel = 'leonardo-phoenix'; selectedAiImageModelLabel = 'Taswira Kipekee'"
                    >
                        Taswira Kipekee
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedAiImageModel = 'flux-kontext-pro'; selectedAiImageModelLabel = 'Taswira Muktadha Pro'"
                    >
                        Taswira Muktadha Pro
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedAiImageModel = 'ideogram-v3'; selectedAiImageModelLabel = 'Taswira Nembo Pro'"
                    >
                        Taswira Nembo Pro
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedAiImageModel = 'imagen-4-pro'; selectedAiImageModelLabel = 'Taswira Mwonekano'"
                    >
                        Taswira Mwonekano
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedAiImageModel = 'recraft-v3'; selectedAiImageModelLabel = 'Taswira Usanifu'"
                    >
                        Taswira Usanifu
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedAiImageModel = 'gpt-image-1-medium'; selectedAiImageModelLabel = 'Taswira Rangi'"
                    >
                        Taswira Rangi
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedAiImageModel = 'flux-1-ultra'; selectedAiImageModelLabel = 'Taswira Sanaa Max'"
                    >
                        Taswira Sanaa Max
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedAiImageModel = 'imagen-4-ultra'; selectedAiImageModelLabel = 'Taswira Mwonekano Max'"
                    >
                        Taswira Mwonekano Max
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedAiImageModel = 'dall-e-3'; selectedAiImageModelLabel = 'Taswira Picha Pro'"
                    >
                        Taswira Picha Pro
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedAiImageModel = 'flux-kontext-max'; selectedAiImageModelLabel = 'Taswira Muktadha Max'"
                    >
                        Taswira Muktadha Max
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedAiImageModel = 'recraft-v3-svg'; selectedAiImageModelLabel = 'Taswira Nembo Vector'"
                    >
                        Taswira Nembo Vector
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedAiImageModel = 'ideogram-v3-quality'; selectedAiImageModelLabel = 'Taswira Nembo Max'"
                    >
                        Taswira Nembo Max
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedAiImageModel = 'gpt-image-1-high'; selectedAiImageModelLabel = 'Taswira Rangi Max'"
                    >
                        Taswira Rangi Max
                    </a>
                </x-slot:dropdown>
            </x-dropdown.dropdown>
            <input
                type="hidden"
                name="imageOptions[model]"
                x-model="selectedAiImageModel"
            >
        </div>

        <!-- AI Image Style (only shown when aiGenerated is selected) -->
        <div
            class="flex-shrink-0"
            x-show="selectedImageSourceValue === 'aiGenerated'"
        >
            <x-dropdown.dropdown
                anchor="start"
                offsetY="15px"
            >
                <x-slot:trigger
                    variant="outline"
                >
                    <x-tabler-palette class="size-4" />
                    <span x-text="selectedImageStyle || '@lang('AI Image Style')'"></span>
                </x-slot:trigger>
                <x-slot:dropdown
                    class="min-w-[150px] rounded-lg bg-background p-2 shadow-lg"
                >
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedImageStyle = 'photorealistic'"
                    >
                        @lang('Photorealistic')
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedImageStyle = 'minimal'"
                    >
                        @lang('Minimal')
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedImageStyle = 'black and white'"
                    >
                        @lang('Black and white')
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedImageStyle = 'artistic'"
                    >
                        @lang('Artistic')
                    </a>
                    <a
                        class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                        href="#"
                        @click.prevent="selectedImageStyle = 'cartoon'"
                    >
                        @lang('Cartoon')
                    </a>
                </x-slot:dropdown>
            </x-dropdown.dropdown>
            <input
                type="hidden"
                name="imageOptions[style]"
                x-model="selectedImageStyle"
            >
        </div>

        <!-- Card Dimensions -->
        <div
            class="flex-shrink-0"
            x-show="selectedFormat === 'presentation'"
        >
            <x-dropdown.dropdown
                anchor="start"
                offsetY="15px"
            >
                <x-slot:trigger
                    variant="outline"
                >
                    <x-tabler-aspect-ratio class="size-4" />
                    <span x-text="selectedCardDimensions || '@lang('Card Dimensions')'"></span>
                </x-slot:trigger>
                <x-slot:dropdown
                    class="min-w-[150px] rounded-lg bg-background p-2 shadow-lg"
                >
                    <template x-if="selectedFormat === 'presentation'">
                        <div>
                            <a
                                class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                                href="#"
                                @click.prevent="selectedCardDimensions = 'fluid'"
                            >
                                @lang('Fluid')
                            </a>
                            <a
                                class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                                href="#"
                                @click.prevent="selectedCardDimensions = '16x9'"
                            >
                                @lang('16x9')
                            </a>
                            <a
                                class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                                href="#"
                                @click.prevent="selectedCardDimensions = '4x3'"
                            >
                                @lang('4x3')
                            </a>
                        </div>
                    </template>
                    <template x-if="selectedFormat === 'document'">
                        <div>
                            <a
                                class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                                href="#"
                                @click.prevent="selectedCardDimensions = 'fluid'"
                            >
                                @lang('Fluid')
                            </a>
                            <a
                                class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                                href="#"
                                @click.prevent="selectedCardDimensions = 'pageless'"
                            >
                                @lang('Pageless')
                            </a>
                            <a
                                class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                                href="#"
                                @click.prevent="selectedCardDimensions = 'letter'"
                            >
                                @lang('Letter')
                            </a>
                            <a
                                class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                                href="#"
                                @click.prevent="selectedCardDimensions = 'a4'"
                            >
                                @lang('a4')
                            </a>
                        </div>
                    </template>
                    <template x-if="selectedFormat === 'social'">
                        <div>
                            <a
                                class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                                href="#"
                                @click.prevent="selectedCardDimensions = '1x1'"
                            >
                                @lang('1x1')
                            </a>
                            <a
                                class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                                href="#"
                                @click.prevent="selectedCardDimensions = '4x5'"
                            >
                                @lang('4x5')
                            </a>
                            <a
                                class="block border-b px-3 py-2 text-2xs font-medium text-heading-foreground transition-colors last:border-b-0 hover:bg-foreground/5"
                                href="#"
                                @click.prevent="selectedCardDimensions = '9x16'"
                            >
                                @lang('9x16')
                            </a>
                        </div>
                    </template>
                </x-slot:dropdown>
            </x-dropdown.dropdown>
            <input
                type="hidden"
                name="cardOptions[dimensions]"
                x-model="selectedCardDimensions"
            >
        </div>

        <!-- Additional Instructions Dropdown -->
        <div class="flex-shrink-0">
            <x-dropdown.dropdown
                anchor="start"
                offsetY="15px"
            >
                <x-slot:trigger
                    variant="outline"
                >
                    <x-tabler-message class="size-4" />
                    <span>@lang('Instructions')</span>
                </x-slot:trigger>
                <x-slot:dropdown
                    class="w-80 rounded-lg bg-background p-4 shadow-lg"
                >
                    <label
                        class="mb-2 block text-xs font-medium"
                        for="additional_instructions"
                    >
                        @lang('Additional Instructions')
                    </label>
                    <x-forms.input
                        id="additional_instructions"
                        name="additionalInstructions"
                        x-model="additionalInstructions"
                        type="textarea"
                        rows="4"
                        maxlength="500"
                        placeholder="{{ __('e.g., Make the titles catchy and humorous') }}"
                    />
                </x-slot:dropdown>
            </x-dropdown.dropdown>
        </div>
    </div>
</div>
