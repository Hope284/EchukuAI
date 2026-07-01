<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ __('DZEVA Update Status') }}</title>
    <style>
        :root { color-scheme: light dark; font-family: Arial, sans-serif; }
        body { margin: 0; background: #f7f8fa; color: #17191f; }
        main { min-height: 100vh; display: grid; place-items: center; padding: 24px; box-sizing: border-box; }
        section { width: min(680px, 100%); padding: 32px; border: 1px solid #dfe3ea; border-radius: 8px; background: #fff; box-sizing: border-box; }
        h1 { margin: 0 0 8px; font-size: 28px; }
        p { margin: 0; line-height: 1.6; color: #5d6470; }
        dl { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin: 28px 0 0; }
        div { padding-top: 16px; border-top: 1px solid #e6e9ee; }
        dt { margin-bottom: 6px; font-size: 12px; font-weight: 700; text-transform: uppercase; color: #717885; }
        dd { margin: 0; font-weight: 700; }
        .status { margin-top: 24px; padding: 14px 16px; border-left: 4px solid #18864b; background: #edf9f2; color: #155d38; }
        @media (max-width: 560px) { section { padding: 24px; } dl { grid-template-columns: 1fr; } }
        @media (prefers-color-scheme: dark) {
            body { background: #111318; color: #f5f7fa; }
            section { background: #1a1d24; border-color: #343943; }
            p, dt { color: #aab1bd; }
            div { border-color: #343943; }
            .status { background: #173c2a; color: #b9f5d3; }
        }
    </style>
</head>
<body>
<main>
    <section>
        <h1>{{ __('DZEVA Update Status') }}</h1>
        <p>{{ __('Application updates are installed through the protected production deployment workflow.') }}</p>

        <p class="status">{{ __('DZEVA is installed and the update route is healthy.') }}</p>

        <dl>
            <div>
                <dt>{{ __('Installed version') }}</dt>
                <dd>{{ dzeva_version_label() }}</dd>
            </div>
            <div>
                <dt>{{ __('Deployment method') }}</dt>
                <dd>{{ __('GitHub production workflow') }}</dd>
            </div>
        </dl>
    </section>
</main>
</body>
</html>
