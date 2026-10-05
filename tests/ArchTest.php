<?php

arch('no debugging statements are left behind')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r'])
    ->not->toBeUsed();

arch('service concerns are traits')
    ->expect('Siberfx\LaravelTryoto\app\Services\Concerns')
    ->toBeTraits();

arch('exceptions extend RuntimeException')
    ->expect('Siberfx\LaravelTryoto\app\Exceptions')
    ->toExtend(RuntimeException::class);
