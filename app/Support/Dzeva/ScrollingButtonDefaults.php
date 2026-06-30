<?php

declare(strict_types=1);

namespace App\Support\Dzeva;

final class ScrollingButtonDefaults
{
    /**
     * @return array<int, array{name: string, prompt: string}>
     */
    public static function items(): array
    {
        return [
            ['name' => 'Start DZEVA Chat', 'prompt' => '/chat'],
            ['name' => 'Open Dashboard', 'prompt' => '/dashboard/user'],
            ['name' => 'View Plans', 'prompt' => '/dashboard/user/payment'],
            ['name' => 'Read DZEVA Blog', 'prompt' => '/blog'],
        ];
    }
}
