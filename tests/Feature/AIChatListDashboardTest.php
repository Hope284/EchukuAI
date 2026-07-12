<?php

declare(strict_types=1);

use App\Enums\Roles;
use App\Http\Controllers\AIChatController;
use App\Models\ChatCategory;
use App\Models\OpenaiGeneratorChatCategory;
use App\Models\Setting;
use App\Models\SettingTwo;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    Setting::factory()->create([
        'feature_ai_chat'    => true,
        'free_open_ai_items' => ['ai_chat_all'],
    ]);
    SettingTwo::factory()->create([
        'openai_default_stream_server' => 'backend',
    ]);
});

it('provides stream settings required by the legacy AI chat list dashboard', function () {
    $user = User::factory()->create(['type' => Roles::USER->value]);
    Auth::login($user);

    ChatCategory::query()->create([
        'name'    => 'general',
        'user_id' => $user->id,
    ]);

    OpenaiGeneratorChatCategory::query()->create([
        'name'        => 'General Chat',
        'short_name'  => 'GC',
        'slug'        => 'general-chat',
        'description' => 'General DZEVA assistant',
        'role'        => 'default',
        'category'    => 'general',
        'plan'        => 'legacy',
        'color'       => '#ffffff',
    ]);

    $view = app(AIChatController::class)->openAIChatList();
    $data = $view->getData();
    $html = view('panel.user.openai_chat.components.list', [
        'aiList'  => $data['aiList'],
        'favData' => $data['favData'],
    ])->render();

    expect($view->name())->toBe('panel.user.openai_chat.list')
        ->and($data)->toHaveKey('settings_two')
        ->and($data['settings_two'])->toBeInstanceOf(SettingTwo::class)
        ->and($data['aiList']->pluck('name')->all())->toContain('General Chat')
        ->and($html)->toContain('General Chat');
});
