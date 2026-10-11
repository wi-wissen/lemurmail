<?php

use App\Core\Pin\PinFormat;
use App\Core\Pin\PinGenerator;

it('generates 8 characters from the unambiguous alphabet', function () {
    $gen = new PinGenerator;
    for ($i = 0; $i < 200; $i++) {
        $pin = $gen->generate();
        expect($pin)->toHaveLength(8)->and(PinFormat::isWellFormed($pin))->toBeTrue();
        expect($pin)->not->toMatch('/[01OIL]/');
    }
});

it('uses the injected random source', function () {
    $calls = [];
    $gen = new PinGenerator(function (int $min, int $max) use (&$calls) {
        $calls[] = [$min, $max];

        return 0;
    });
    expect($gen->generate())->toBe(str_repeat('2', 8));
    expect($calls)->toHaveCount(8)->and($calls[0])->toBe([0, strlen(PinGenerator::ALPHABET) - 1]);
});

it('produces different PINs', function () {
    $gen = new PinGenerator;
    $set = [];
    for ($i = 0; $i < 100; $i++) {
        $set[$gen->generate()] = true;
    }
    expect(count($set))->toBeGreaterThan(95);
});

it('formats a PIN in groups of four', function () {
    expect(PinFormat::display('K7M4QX9R'))->toBe('K7M4 QX9R');
});

it('normalizes user input', function () {
    expect(PinFormat::normalize(' k7m4-qx9r '))->toBe('K7M4QX9R');
    expect(PinFormat::normalize("K7M4\tQX9R\n"))->toBe('K7M4QX9R');
    expect(PinFormat::normalize(''))->toBe('');
});

it('checks well-formedness', function () {
    expect(PinFormat::isWellFormed('K7M4QX9R'))->toBeTrue();
    expect(PinFormat::isWellFormed('K7M4QX9'))->toBeFalse();
    expect(PinFormat::isWellFormed('K7M4QX9RA'))->toBeFalse();
    expect(PinFormat::isWellFormed('K7M4QX90'))->toBeFalse();
    expect(PinFormat::isWellFormed('k7m4qx9r'))->toBeFalse();
});
