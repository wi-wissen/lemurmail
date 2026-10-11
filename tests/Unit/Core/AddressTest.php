<?php

use App\Core\Address\AddressSeries;
use App\Core\Address\LocalPart;

it('validates local parts', function (string $value, bool $valid) {
    expect(LocalPart::isValid($value))->toBe($valid);
})->with([
    ['klasse7a-01', true],
    ['a', true],
    ['max.mustermann', true],
    ['a_b-c.d', true],
    ['Klasse', false],
    ['', false],
    ['-abc', false],
    ['abc-', false],
    ['.abc', false],
    ['abc.', false],
    ['a..b', false],
    ['a b', false],
    ['äbc', false],
    ['a@b', false],
    [str_repeat('a', 64), true],
    [str_repeat('a', 65), false],
]);

it('normalizes local parts', function () {
    expect(LocalPart::normalize('  Klasse7A-01 '))->toBe('klasse7a-01');
});

it('builds a zero padded series', function () {
    $s = AddressSeries::generate('klasse7a-', 30);
    expect($s)->toHaveCount(30)->and($s[0])->toBe('klasse7a-01')->and($s[29])->toBe('klasse7a-30');
});

it('pads wider for big series and honours start', function () {
    $s = AddressSeries::generate('x', 3, 99);
    expect($s)->toBe(['x099', 'x100', 'x101']);
    expect(AddressSeries::generate('x', 1))->toBe(['x01']);
});

it('normalizes the prefix', function () {
    expect(AddressSeries::generate('Klasse-', 2))->toBe(['klasse-01', 'klasse-02']);
});

it('rejects invalid input', function (string $prefix, int $count, int $start) {
    AddressSeries::generate($prefix, $count, $start);
})->with([
    ['ok-', 0, 1],
    ['ok-', -1, 1],
    ['ok-', 1, -1],
    ['bad prefix ', 2, 1],
    ['', 2, 1],
    ['.bad', 2, 1],
])->throws(InvalidArgumentException::class);
