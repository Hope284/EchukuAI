<?php

declare(strict_types=1);

it('does not contain Google API keys in application or frontend source', function () {
    $roots = [
        app_path(),
        config_path(),
        resource_path(),
        public_path('themes/default'),
    ];

    $leaks = [];
    foreach ($roots as $root) {
        if (! is_dir($root)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getSize() > 2_000_000) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            if (is_string($contents) && preg_match('/AIza[0-9A-Za-z_-]{20,}/', $contents)) {
                $leaks[] = $file->getPathname();
            }
        }
    }

    expect($leaks)->toBe([]);
});

it('does not serialize saved Gemini keys into the settings view', function () {
    $view = file_get_contents(resource_path('views/default/panel/admin/settings/gemini.blade.php'));

    expect($view)->not->toContain('value="{{ $secret }}"')
        ->and($view)->not->toContain('>{{ $secret }}</option>');
});
