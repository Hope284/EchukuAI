<?php

declare(strict_types=1);

namespace App\Support\Dzeva;

use App\Domains\Engine\Enums\EngineEnum;
use App\Domains\Entity\Enums\EntityEnum;
use App\Domains\Entity\Models\Entity;
use App\Enums\AITokenType;

class DzevaModelCatalog
{
    public const EXCHANGE_RATE_NGN_PER_USD = 1600.0;

    public const MARKUP_MULTIPLIER = 3.0;

    public const SUBSCRIPTION_BUFFER = 0.75;

    public static function exchangeRateNgnPerUsd(): float
    {
        return (float) env('DZEVA_NGN_PER_USD', self::EXCHANGE_RATE_NGN_PER_USD);
    }

    /**
     * @return array<string, array{name: string, capability: string, icon: string, description: string, entity: EntityEnum}>
     */
    public static function capabilities(): array
    {
        return [
            'ogbon' => [
                'name'        => 'Ọgbọ́n',
                'capability'  => 'Smart Chat & Reasoning',
                'icon'        => 'Brain',
                'description' => 'Best for smart conversations, business reasoning, planning, explanations, customer replies, and general AI chat.',
                'entity'      => EntityEnum::GPT_5_MINI,
            ],
            'amamihe' => [
                'name'        => 'Amamihe',
                'capability'  => 'Research & Long Documents',
                'icon'        => 'BookOpen',
                'description' => 'Best for research, long documents, deep analysis, summaries, and document understanding.',
                'entity'      => EntityEnum::GEMINI_3_FLASH,
            ],
            'hikima' => [
                'name'        => 'Hikima',
                'capability'  => 'Writing & Professional Documents',
                'icon'        => 'FileText',
                'description' => 'Best for emails, reports, proposals, formal documents, summaries, and careful professional writing.',
                'entity'      => EntityEnum::CLAUDE_SONNET_4_6,
            ],
            'taswira' => [
                'name'        => 'Taswira',
                'capability'  => 'Image Generation',
                'icon'        => 'Image',
                'description' => 'Best for creating posters, flyers, product mockups, social media graphics, branding visuals, and concept art.',
                'entity'      => EntityEnum::GPT_IMAGE_1,
            ],
            'ohun' => [
                'name'        => 'Ohùn',
                'capability'  => 'Voice & Audio',
                'icon'        => 'Mic',
                'description' => 'Best for voiceover, narration, speech, audio replies, and voice assistant tasks.',
                'entity'      => EntityEnum::TTS_1_HD,
            ],
            'sani' => [
                'name'        => 'Sani',
                'capability'  => 'Search & Business Memory',
                'icon'        => 'Search',
                'description' => 'Best for document search, business memory, knowledge base answers, and retrieving stored company information.',
                'entity'      => EntityEnum::SERPER,
            ],
            'akili' => [
                'name'        => 'Akili',
                'capability'  => 'Code & Automation',
                'icon'        => 'Code',
                'description' => 'Best for coding, debugging, automation, API setup, workflow execution, and technical support.',
                'entity'      => EntityEnum::DEEPSEEK_CHAT,
            ],
            'sabi' => [
                'name'        => 'Sabí',
                'capability'  => 'Local Business Assistant',
                'icon'        => 'MessageCircle',
                'description' => 'Best for local African business conversations, customer replies, sales help, and simple support for non-technical users.',
                'entity'      => EntityEnum::GPT_5_NANO,
            ],
        ];
    }

    public static function publicLabelForEntity(EntityEnum|string|null $entity): ?string
    {
        $capability = self::publicModelForEntity($entity);

        return $capability['name'] ?? null;
    }

    public static function publicDescriptionForEntity(EntityEnum|string|null $entity): ?string
    {
        $capability = self::publicModelForEntity($entity);

        return $capability['description'] ?? null;
    }

    public static function publicCapabilityForEntity(EntityEnum|string|null $entity): ?string
    {
        $capability = self::publicModelForEntity($entity);

        return $capability['capability'] ?? null;
    }

    public static function publicIconForEntity(EntityEnum|string|null $entity): ?string
    {
        $capability = self::publicModelForEntity($entity);

        return $capability['icon'] ?? null;
    }

    /**
     * @return array{name: string, capability: string, icon: string, description: string, entity: EntityEnum}|null
     */
    public static function publicModelForEntity(EntityEnum|string|null $entity): ?array
    {
        $entity = self::normalizeEntity($entity);

        if (! $entity) {
            return null;
        }

        if ($presentation = self::modelPresentationMap()[$entity->value] ?? null) {
            return $presentation;
        }

        return self::capabilityForEntity($entity) ?? self::familyPresentation($entity);
    }

    /**
     * @return array{name: string, capability: string, icon: string, description: string, entity: EntityEnum}|null
     */
    public static function capabilityForEntity(EntityEnum|string|null $entity): ?array
    {
        $entity = self::normalizeEntity($entity);

        if (! $entity) {
            return null;
        }

        foreach (self::capabilities() as $capability) {
            if ($capability['entity'] === $entity) {
                return $capability;
            }
        }

        return null;
    }

    public static function capabilityKeyForEntity(EntityEnum|string|null $entity): ?string
    {
        $entity = self::normalizeEntity($entity);

        if (! $entity) {
            return null;
        }

        foreach (self::capabilities() as $key => $capability) {
            if ($capability['entity'] === $entity) {
                return $key;
            }
        }

        return null;
    }

    public static function publicSlugForEntity(EntityEnum|string|null $entity): ?string
    {
        return self::capabilityKeyForEntity($entity);
    }

    public static function entityValueForPublicSlug(?string $slug): ?string
    {
        $slug = trim((string) $slug);

        if ($slug === '') {
            return null;
        }

        $entity = self::entityMap()[$slug] ?? null;

        if ($entity instanceof EntityEnum) {
            return $entity->value;
        }

        return EntityEnum::tryFrom($slug)?->value;
    }

    /**
     * @return array<string, EntityEnum>
     */
    public static function entityMap(): array
    {
        return collect(self::capabilities())
            ->mapWithKeys(static fn (array $capability, string $key): array => [$key => $capability['entity']])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function publicEntityPayload(Entity $entity): array
    {
        $capability = self::publicModelForEntity($entity->key);

        return [
            'id'           => $entity->id,
            'key'          => self::capabilityKeyForEntity($entity->key),
            'name'         => $capability['name'] ?? (string) $entity->selected_title,
            'capability'   => $capability['capability'] ?? null,
            'icon'         => $capability['icon'] ?? null,
            'description'  => $capability['description'] ?? (string) $entity->title,
            'token_type'   => $entity->key->tokenType()->value,
            'is_selected'  => (bool) $entity->is_selected,
            'status'       => $entity->status->value,
            'tokens'       => $entity->tokens->first(),
        ];
    }

    public static function containsForbiddenProviderName(string $value): bool
    {
        return preg_match(self::forbiddenProviderPattern(), $value) === 1;
    }

    public static function forbiddenProviderPattern(): string
    {
        return '/\b(OpenAI|ChatGPT|GPT|GPT-3\.5|GPT-4|Gemini|Google AI|Google|Claude|Anthropic|Flux|DALL[·-]?E|Stable Diffusion|ElevenLabs|Deepseek|DeepSeek|Serper|TTS|Midjourney|Runway|Pika|Suno|Udio)\b/i';
    }

    /**
     * @return array<string, array{name: string, capability: string, icon: string, description: string, entity: EntityEnum}>
     */
    public static function modelPresentationMap(): array
    {
        $rows = [
            EntityEnum::GPT_5_MINI->value => ['Ọgbọ́n Ìmọ̀', 'Smart Chat & Reasoning', 'Brain', 'Fast everyday chat and simple answers.'],
            EntityEnum::GPT_5->value => ['Ọgbọ́n Ìjìnlẹ̀', 'Smart Chat & Reasoning', 'Brain', 'Deep reasoning and complex analysis.'],
            EntityEnum::GPT_5_PRO->value => ['Ọgbọ́n Àgbà', 'Smart Chat & Reasoning', 'Brain', 'Advanced reasoning for demanding work.'],
            EntityEnum::GPT_5_NANO->value => ['Sabí Sharp', 'Local Business Assistant', 'MessageCircle', 'Quick local business help and simple replies.'],
            EntityEnum::GPT_5_CHAT->value => ['Ọgbọ́n Ọ̀rọ̀', 'Smart Chat & Reasoning', 'Brain', 'Natural conversation and customer replies.'],
            EntityEnum::GPT_4_1->value => ['Ọgbọ́n Ìjìnlẹ̀', 'Smart Chat & Reasoning', 'Brain', 'Deep reasoning and business analysis.'],
            EntityEnum::GPT_4_1_MINI->value => ['Ọgbọ́n Ìmọ̀', 'Smart Chat & Reasoning', 'Brain', 'Fast everyday chat and business help.'],
            EntityEnum::GPT_4_1_NANO->value => ['Ọgbọ́n Ìrànwọ́', 'Smart Chat & Reasoning', 'Brain', 'Helpful support and guided assistance.'],
            EntityEnum::GPT_4_O->value => ['Ọgbọ́n Ọ̀rọ̀', 'Smart Chat & Reasoning', 'Brain', 'Natural conversation and multimodal support.'],
            EntityEnum::GPT_4_O_MINI->value => ['Ọgbọ́n Ìmọ̀', 'Smart Chat & Reasoning', 'Brain', 'Fast lightweight answers.'],
            EntityEnum::GPT_3_5_TURBO->value => ['Ọgbọ́n Ìmọ̀', 'Smart Chat & Reasoning', 'Brain', 'Fast everyday chat and simple answers.'],
            EntityEnum::GPT_3_5_TURBO_0125->value => ['Ọgbọ́n Ìmọ̀', 'Smart Chat & Reasoning', 'Brain', 'Fast everyday chat and simple answers.'],
            EntityEnum::GPT_3_5_TURBO_1106->value => ['Ọgbọ́n Ìrànwọ́', 'Smart Chat & Reasoning', 'Brain', 'Helpful support and guided assistance.'],
            EntityEnum::GPT_4->value => ['Ọgbọ́n Ìjìnlẹ̀', 'Smart Chat & Reasoning', 'Brain', 'Complex analysis and reasoning.'],
            EntityEnum::GPT_4_TURBO->value => ['Ọgbọ́n Akọ́wé', 'Smart Chat & Reasoning', 'Brain', 'Writing, drafting, and business content.'],
            EntityEnum::GPT_4_1106_PREVIEW->value => ['Ọgbọ́n Akọ́wé', 'Smart Chat & Reasoning', 'Brain', 'Writing, drafting, and business content.'],
            EntityEnum::GPT_4_0125_PREVIEW->value => ['Ọgbọ́n Ìjìnlẹ̀', 'Smart Chat & Reasoning', 'Brain', 'Complex analysis and planning.'],
            EntityEnum::GPT_4_O_SEARCH_PREVIEW->value => ['Sani Funa', 'Search & Business Memory', 'Search', 'Document search and retrieval.'],
            EntityEnum::GPT_4_O_MINI_SEARCH_PREVIEW->value => ['Sani Funa', 'Search & Business Memory', 'Search', 'Fast search and retrieval.'],
            EntityEnum::O3_DEEP_RESEARCH->value => ['Amamihe Nchọpụta', 'Research & Long Documents', 'BookOpen', 'Research and discovery.'],
            EntityEnum::O4_MINI_DEEP_RESEARCH->value => ['Amamihe Ọsọ', 'Research & Long Documents', 'BookOpen', 'Fast research and quick answers.'],
            EntityEnum::TEXT_EMBEDDING_ADA_002->value => ['Sani Ulwazi', 'Search & Business Memory', 'Search', 'Business memory and knowledge indexing.'],
            EntityEnum::TEXT_EMBEDDING_3_SMALL->value => ['Sani Qonda', 'Search & Business Memory', 'Search', 'Understand company data for retrieval.'],
            EntityEnum::TEXT_EMBEDDING_3_LARGE->value => ['Sani Khumbula', 'Search & Business Memory', 'Search', 'Remember stored information.'],
            EntityEnum::DALL_E_2->value => ['Taswira Picha', 'Image Generation', 'Image', 'Simple image generation.'],
            EntityEnum::DALL_E_3->value => ['Taswira Sanaa', 'Image Generation', 'Image', 'Creative image generation.'],
            EntityEnum::GPT_IMAGE_1->value => ['Taswira Rangi', 'Image Generation', 'Image', 'Posters, flyers, and branding visuals.'],
            EntityEnum::GPT_IMAGE_1_5->value => ['Taswira Ubunifu', 'Image Generation', 'Image', 'Concept art and creative design.'],
            EntityEnum::GPT_IMAGE_2->value => ['Taswira Mwonekano', 'Image Generation', 'Image', 'Product mockups and appearance.'],
            EntityEnum::SORA_2->value => ['Taswira Mwendo', 'Video Generation', 'Video', 'Text-to-video creation.'],
            EntityEnum::SORA_2_PRO->value => ['Taswira Sinema', 'Video Generation', 'Video', 'High-quality video generation.'],
            EntityEnum::TTS_1->value => ['Ohùn Kasa', 'Voice & Audio', 'Mic', 'Speech and voiceover.'],
            EntityEnum::TTS_1_HD->value => ['Ohùn Nne', 'Voice & Audio', 'Mic', 'Voice assistant and narration.'],
            EntityEnum::WHISPER_1->value => ['Ohùn Tie', 'Voice & Audio', 'Mic', 'Speech-to-text and listening.'],

            EntityEnum::GEMINI_3_FLASH->value => ['Amamihe Ọsọ', 'Research & Long Documents', 'BookOpen', 'Fast research and quick answers.'],
            EntityEnum::GEMINI_3_PRO_PREVIEW->value => ['Amamihe Nghọta', 'Research & Long Documents', 'BookOpen', 'Long document understanding.'],
            EntityEnum::GEMINI_3_1_PRO_PREVIEW->value => ['Amamihe Echiche', 'Research & Long Documents', 'BookOpen', 'Ideas, planning, and explanation.'],
            EntityEnum::GEMINI_2_5_FLASH_PREVIEW_05_20->value => ['Amamihe Ọsọ', 'Research & Long Documents', 'BookOpen', 'Fast research and quick answers.'],
            EntityEnum::GEMINI_2_5_PRO->value => ['Amamihe Nghọta', 'Research & Long Documents', 'BookOpen', 'Long document understanding.'],
            EntityEnum::GEMINI_DEEP_RESEARCH->value => ['Amamihe Nchọpụta', 'Research & Long Documents', 'BookOpen', 'Research and discovery.'],
            EntityEnum::GEMINI_2_0_FLASH->value => ['Amamihe Ọsọ', 'Research & Long Documents', 'BookOpen', 'Fast analysis and summaries.'],
            EntityEnum::GEMINI_2_0_FLASH_LITE->value => ['Amamihe Ọsọ', 'Research & Long Documents', 'BookOpen', 'Fast lightweight research.'],
            EntityEnum::GEMINI_1_5_PRO->value => ['Amamihe Ọgụgụ', 'Research & Long Documents', 'BookOpen', 'Reading and document analysis.'],
            EntityEnum::GEMINI_1_5_FLASH->value => ['Amamihe Ọsọ', 'Research & Long Documents', 'BookOpen', 'Fast research and summaries.'],
            EntityEnum::GEMINI_EMBEDDING_EXP->value => ['Sani Qonda', 'Search & Business Memory', 'Search', 'Understand company data for retrieval.'],
            EntityEnum::GEMINI_TEXT_EMBEDDING_004->value => ['Sani Ulwazi', 'Search & Business Memory', 'Search', 'Business memory and knowledge indexing.'],
            EntityEnum::GEMINI_3_1_FLASH_LIVE_PREVIEW->value => ['Ohùn Kasa', 'Voice & Audio', 'Mic', 'Realtime speech and voice support.'],
            EntityEnum::LYRIA_3_CLIP->value => ['Ohùn Nnwom', 'Voice & Audio', 'Music', 'Music and audio creativity.'],
            EntityEnum::LYRIA_3_PRO->value => ['Ohùn Nnwom', 'Voice & Audio', 'Music', 'Music and audio creativity.'],

            EntityEnum::CLAUDE_FABLE_5->value => ['Hikima Nazari', 'Writing & Professional Documents', 'FileText', 'Careful analysis and professional review.'],
            EntityEnum::CLAUDE_3_5_HAIKU->value => ['Hikima Rubutu', 'Writing & Professional Documents', 'FileText', 'Professional writing and reports.'],
            EntityEnum::CLAUDE_3_HAIKU->value => ['Hikima Takaita', 'Writing & Professional Documents', 'FileText', 'Summaries and briefs.'],
            EntityEnum::CLAUDE_3_5_SONNET->value => ['Hikima Nazari', 'Writing & Professional Documents', 'FileText', 'Careful analysis and review.'],
            EntityEnum::CLAUDE_3_5_SONNET_V2->value => ['Hikima Nazari', 'Writing & Professional Documents', 'FileText', 'Careful analysis and review.'],
            EntityEnum::CLAUDE_3_7_SONNET->value => ['Hikima Auna', 'Writing & Professional Documents', 'ShieldCheck', 'Risk, policy, and careful reasoning.'],
            EntityEnum::CLAUDE_SONNET_4->value => ['Hikima Tattauna', 'Writing & Professional Documents', 'FileText', 'Balanced conversation and advice.'],
            EntityEnum::CLAUDE_SONNET_4_5->value => ['Hikima Nazari', 'Writing & Professional Documents', 'FileText', 'Careful analysis and review.'],
            EntityEnum::CLAUDE_SONNET_4_6->value => ['Hikima Rubutu', 'Writing & Professional Documents', 'FileText', 'Professional writing and reports.'],
            EntityEnum::CLAUDE_3_OPUS->value => ['Hikima Auna', 'Writing & Professional Documents', 'ShieldCheck', 'Risk, policy, and careful reasoning.'],
            EntityEnum::CLAUDE_OPUS_4->value => ['Hikima Auna', 'Writing & Professional Documents', 'ShieldCheck', 'Risk, policy, and careful reasoning.'],
            EntityEnum::CLAUDE_OPUS_4_1->value => ['Hikima Auna', 'Writing & Professional Documents', 'ShieldCheck', 'Risk, policy, and careful reasoning.'],
            EntityEnum::CLAUDE_OPUS_4_5->value => ['Hikima Auna', 'Writing & Professional Documents', 'ShieldCheck', 'Risk, policy, and careful reasoning.'],
            EntityEnum::CLAUDE_OPUS_4_6->value => ['Hikima Auna', 'Writing & Professional Documents', 'ShieldCheck', 'Risk, policy, and careful reasoning.'],
            EntityEnum::CLAUDE_OPUS_4_7->value => ['Hikima Auna', 'Writing & Professional Documents', 'ShieldCheck', 'Risk, policy, and careful reasoning.'],
            EntityEnum::CLAUDE_2_0->value => ['Hikima Takaita', 'Writing & Professional Documents', 'FileText', 'Summaries and briefs.'],
            EntityEnum::CLAUDE_2_1->value => ['Hikima Takaita', 'Writing & Professional Documents', 'FileText', 'Summaries and briefs.'],
            EntityEnum::VOYAGE_2->value => ['Sani Ulwazi', 'Search & Business Memory', 'Search', 'Business memory and knowledge indexing.'],
            EntityEnum::VOYAGE_LARGE_2->value => ['Sani Khumbula', 'Search & Business Memory', 'Search', 'Remember stored information.'],
            EntityEnum::VOYAGE_CODE_2->value => ['Akili Ongorora', 'Code & Automation', 'Code', 'Debugging and code review.'],

            EntityEnum::DEEPSEEK_CHAT->value => ['Akili Basa', 'Code & Automation', 'Code', 'Task execution and automation.'],
            EntityEnum::DEEPSEEK_REASONER->value => ['Akili Ongorora', 'Code & Automation', 'Code', 'Debugging and code review.'],
            EntityEnum::SERPER->value => ['Sani Funa', 'Search & Business Memory', 'Search', 'Document search and retrieval.'],
            EntityEnum::PERPLEXITY->value => ['Sani Funa', 'Search & Business Memory', 'Search', 'Search and answer retrieval.'],
            EntityEnum::GOOGLE->value => ['Sani Funa', 'Search & Business Memory', 'Search', 'Web and business search.'],
            EntityEnum::UNSPLASH->value => ['Taswira Rasilimali', 'Image Generation', 'Image', 'Visual asset search.'],
            EntityEnum::PEXELS->value => ['Taswira Rasilimali', 'Image Generation', 'Image', 'Visual asset search.'],
            EntityEnum::PIXABAY->value => ['Taswira Rasilimali', 'Image Generation', 'Image', 'Visual asset search.'],
            EntityEnum::ELEVENLABS->value => ['Ohùn Kasa', 'Voice & Audio', 'Mic', 'Speech and voiceover.'],
            EntityEnum::ELEVENLABS_V3->value => ['Ohùn Nne', 'Voice & Audio', 'Mic', 'Voice assistant and narration.'],
            EntityEnum::ELEVENLABS_VOICE_CHATBOT->value => ['Ohùn Nne', 'Voice & Audio', 'Mic', 'Voice assistant and narration.'],
            EntityEnum::ELEVENLABS_AI_MUSIC->value => ['Ohùn Nnwom', 'Voice & Audio', 'Music', 'Music and audio creativity.'],
            EntityEnum::ISOLATOR->value => ['Ohùn Tie', 'Voice & Audio', 'Mic', 'Speech cleanup and listening.'],
            EntityEnum::Speechify->value => ['Ohùn Kasa', 'Voice & Audio', 'Mic', 'Speech and voiceover.'],
            EntityEnum::MIDJOURNEY->value => ['Taswira Sanaa', 'Image Generation', 'Image', 'Creative image generation.'],
            EntityEnum::MUSIC_01->value => ['Ohùn Nnwom', 'Voice & Audio', 'Music', 'Music and audio creativity.'],
        ];

        return collect($rows)
            ->mapWithKeys(static fn (array $row, string $entityValue): array => [$entityValue => [
                'name'        => $row[0],
                'capability'  => $row[1],
                'icon'        => $row[2],
                'description' => $row[3],
                'entity'      => EntityEnum::from($entityValue),
            ]])
            ->all();
    }

    /**
     * @return array{name: string, capability: string, icon: string, description: string, entity: EntityEnum}
     */
    private static function familyPresentation(EntityEnum $entity): array
    {
        $tokenType = $entity->tokenType();
        $engine = $entity->engine();

        if (in_array($tokenType, [AITokenType::IMAGE, AITokenType::IMAGE_TO_VIDEO, AITokenType::TEXT_TO_VIDEO, AITokenType::VIDEO_TO_VIDEO], true)) {
            return self::presentation($entity, 'Taswira Ubunifu', 'Image & Video Generation', 'Image', 'Concept art, creative design, and video visuals.');
        }

        if (in_array($tokenType, [AITokenType::TEXT_TO_SPEECH, AITokenType::SPEECH_TO_TEXT, AITokenType::SECOND, AITokenType::MINUTE], true)) {
            return self::presentation($entity, 'Ohùn Kasa', 'Voice & Audio', 'Mic', 'Speech, narration, audio, and voice assistant tasks.');
        }

        return match ($engine) {
            EngineEnum::OPEN_AI => self::presentation($entity, 'Ọgbọ́n Ìrànwọ́', 'Smart Chat & Reasoning', 'Brain', 'Helpful support and guided assistance.'),
            EngineEnum::GEMINI => self::presentation($entity, 'Amamihe Nghọta', 'Research & Long Documents', 'BookOpen', 'Long document understanding and analysis.'),
            EngineEnum::ANTHROPIC => self::presentation($entity, 'Hikima Nazari', 'Writing & Professional Documents', 'FileText', 'Careful analysis and review.'),
            EngineEnum::DEEP_SEEK => self::presentation($entity, 'Akili Ongorora', 'Code & Automation', 'Code', 'Debugging and code review.'),
            EngineEnum::SERPER, EngineEnum::PERPLEXITY, EngineEnum::GOOGLE => self::presentation($entity, 'Sani Funa', 'Search & Business Memory', 'Search', 'Document search and retrieval.'),
            EngineEnum::ELEVENLABS, EngineEnum::Speechify => self::presentation($entity, 'Ohùn Kasa', 'Voice & Audio', 'Mic', 'Speech and voiceover.'),
            default => self::presentation($entity, 'Sabí Sharp', 'Local Business Assistant', 'MessageCircle', 'Quick local business help.'),
        };
    }

    /**
     * @return array{name: string, capability: string, icon: string, description: string, entity: EntityEnum}
     */
    private static function presentation(EntityEnum $entity, string $name, string $capability, string $icon, string $description): array
    {
        return compact('name', 'capability', 'icon', 'description', 'entity');
    }

    private static function normalizeEntity(EntityEnum|string|null $entity): ?EntityEnum
    {
        if ($entity instanceof EntityEnum) {
            return $entity;
        }

        if (is_string($entity)) {
            return EntityEnum::tryFrom($entity);
        }

        return null;
    }
}
