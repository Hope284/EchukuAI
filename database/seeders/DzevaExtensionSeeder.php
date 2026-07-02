<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Extension;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DzevaExtensionSeeder extends Seeder
{
    /** @var array<string, string> */
    private array $extensions = [
        'ai-chat-pro'                 => '3.7',
        'ai-agent'                    => '1.1',
        'phone-call-agent'            => '1.0',
        'ai-chat-pro-gmail'           => '1.0',
        'ai-chat-pro-google-calendar' => '1.0',
        'ai-chat-pro-google-drive'    => '1.0',
        'ai-chat-pro-notion'          => '1.0',
        'ai-chat-pro-outlook'         => '1.0',
    ];

    public function run(): void
    {
        if (Schema::hasTable('extensions')) {
            foreach ($this->extensions as $slug => $version) {
                $attributes = ['installed' => true];

                if (Schema::hasColumn('extensions', 'version')) {
                    $attributes['version'] = $version;
                }
                if (Schema::hasColumn('extensions', 'is_theme')) {
                    $attributes['is_theme'] = false;
                }

                Extension::query()->updateOrCreate(['slug' => $slug], $attributes);
            }

            Extension::forgetCache();
        }

        if (Schema::hasTable('settings') && Schema::hasColumn('settings', 'script_version')) {
            Setting::query()->first()?->update(['script_version' => '10.90']);
            Setting::forgetCache();
        }
    }
}
