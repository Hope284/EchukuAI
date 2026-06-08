<?php

declare(strict_types=1);

namespace App\Support\Dzeva;

use App\Domains\Entity\Enums\EntityEnum;
use App\Domains\Entity\Models\Entity;

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
                'entity'      => EntityEnum::CLAUDE_3_5_HAIKU,
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
        $capability = self::capabilityForEntity($entity);

        return $capability['name'] ?? null;
    }

    public static function publicDescriptionForEntity(EntityEnum|string|null $entity): ?string
    {
        $capability = self::capabilityForEntity($entity);

        return $capability['description'] ?? null;
    }

    public static function publicCapabilityForEntity(EntityEnum|string|null $entity): ?string
    {
        $capability = self::capabilityForEntity($entity);

        return $capability['capability'] ?? null;
    }

    public static function publicIconForEntity(EntityEnum|string|null $entity): ?string
    {
        $capability = self::capabilityForEntity($entity);

        return $capability['icon'] ?? null;
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
        $capability = self::capabilityForEntity($entity->key);

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
        return '/\b(OpenAI|ChatGPT|GPT|Gemini|Google AI|Google|Claude|Anthropic|Flux|DALL[·-]?E|Stable Diffusion|ElevenLabs|Deepseek|DeepSeek|Serper|TTS)\b/i';
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
