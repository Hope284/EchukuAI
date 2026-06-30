<?php

declare(strict_types=1);

use App\Models\Team\Team;

it('casts the unlimited team seat sentinel as an integer', function () {
    $team = new Team(['allow_seats' => '-1']);

    expect($team->allow_seats)->toBe(-1);
});
