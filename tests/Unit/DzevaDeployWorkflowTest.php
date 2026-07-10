<?php

declare(strict_types=1);

it('keeps production upload and realtime voice streaming runtime setup in the deploy workflow', function () {
    $workflow = file_get_contents(base_path('.github/workflows/deploy.yml'));

    expect($workflow)->toContain('upload_max_filesize = 100M')
        ->and($workflow)->toContain('post_max_size = 100M')
        ->and($workflow)->toContain('sudo -u www-data test -w "$writable_dir"')
        ->and($workflow)->toContain('client_max_body_size 100M')
        ->and($workflow)->toContain('phone-call-agent:relay-server --host=127.0.0.1 --port=8090')
        ->and($workflow)->toContain('/api/phone-call-agent/ws/twilio/')
        ->and($workflow)->toContain('proxy_set_header Upgrade $http_upgrade')
        ->and($workflow)->toContain('supervisorctl status dzeva-phone-call-agent-relay');
});
