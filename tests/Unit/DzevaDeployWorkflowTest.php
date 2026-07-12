<?php

declare(strict_types=1);

it('keeps production upload and realtime voice streaming runtime setup in the deploy workflow', function () {
    $workflow = file_get_contents(base_path('.github/workflows/deploy.yml'));
    $nginxRuntime = file_get_contents(base_path('tools/deploy/configure_dzeva_nginx_runtime.py'));

    expect($workflow)->toContain('upload_max_filesize = 100M')
        ->and($workflow)->toContain('post_max_size = 100M')
        ->and($workflow)->toContain('sudo -u www-data test -w "$writable_dir"')
        ->and($workflow)->toContain('phone-call-agent:relay-server --host=127.0.0.1 --port=8090')
        ->and($workflow)->toContain('tools/deploy/configure_dzeva_nginx_runtime.py')
        ->and($workflow)->toContain('supervisorctl status dzeva-phone-call-agent-relay')
        ->and($workflow)->toContain('php artisan dzeva:auth-smoke')
        ->and($nginxRuntime)->toContain('client_max_body_size 100M')
        ->and($nginxRuntime)->toContain('/api/phone-call-agent/ws/twilio/')
        ->and($nginxRuntime)->toContain('proxy_set_header Upgrade $http_upgrade');
});
