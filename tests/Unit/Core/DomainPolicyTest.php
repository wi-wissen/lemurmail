<?php

use App\Core\Registration\DomainClass;
use App\Core\Registration\DomainPolicy;

$policy = fn () => new DomainPolicy('tmp-mail.example.org', ['gmail.com', 'web.de'], ['schule.example.org']);

it('classifies addresses', function (string $email, DomainClass $expected) use ($policy) {
    expect($policy()->classify($email))->toBe($expected);
})->with([
    ['lehrer@schule.example.org', DomainClass::Approved],
    ['Lehrer@Schule.Example.ORG', DomainClass::Approved],
    ['lehrer@sub.schule.example.org', DomainClass::Unknown],
    ['lehrer@andere-schule.example', DomainClass::Unknown],
    ['x@gmail.com', DomainClass::Freemail],
    ['x@GMAIL.com', DomainClass::Freemail],
    ['x@mail.gmail.com', DomainClass::Freemail],
    ['x@notgmail.com', DomainClass::Unknown],
    ['klasse7a-01@tmp-mail.example.org', DomainClass::OwnMailDomain],
    ['x@a.tmp-mail.example.org', DomainClass::OwnMailDomain],
    ['not-an-email', DomainClass::Invalid],
    ['a@b@c.org', DomainClass::Invalid],
    ['@schule.example.org', DomainClass::Invalid],
    ['a@', DomainClass::Invalid],
    ['a@nodot', DomainClass::Invalid],
    ['', DomainClass::Invalid],
]);

it('gives own domain precedence over approved', function () {
    $p = new DomainPolicy('tmp.example.org', [], ['tmp.example.org']);
    expect($p->classify('a@tmp.example.org'))->toBe(DomainClass::OwnMailDomain);
});

it('extracts the domain', function () {
    expect(DomainPolicy::domainOf('A@Schule.Example.org'))->toBe('schule.example.org');
    expect(DomainPolicy::domainOf('nope'))->toBeNull();
});

it('accepts approved entries with a pattern for the name before the @', function () {
    $p = new DomainPolicy('tmp.example.org', ['gmail.com'], [['domain' => 'schule.example.org', 'pattern' => '[a-z]+\.[a-z]+']]);

    expect($p->classify('max.mustermann@schule.example.org'))->toBe(DomainClass::Approved);
    expect($p->classify('Max.Mustermann@Schule.Example.org'))->toBe(DomainClass::Approved);
    // pupils of the same school have digits in their names: needs an admin
    expect($p->classify('max.mustermann12@schule.example.org'))->toBe(DomainClass::Unknown);
    expect($p->classify('mustermann@schule.example.org'))->toBe(DomainClass::Unknown);
});

it('requires the pattern to match the whole name, not just a part', function () {
    $p = new DomainPolicy('tmp.example.org', [], [['domain' => 'schule.example.org', 'pattern' => 'lehrer']]);
    expect($p->classify('lehrer@schule.example.org'))->toBe(DomainClass::Approved);
    expect($p->classify('lehrer.klaus@schule.example.org'))->toBe(DomainClass::Unknown);
    expect($p->classify('xlehrer@schule.example.org'))->toBe(DomainClass::Unknown);
});

it('mixes plain domains and patterned entries, and several patterns for one domain', function () {
    $p = new DomainPolicy('tmp.example.org', [], [
        'plain.example',
        ['domain' => 'schule.example.org', 'pattern' => 'l\..+'],
        ['domain' => 'schule.example.org', 'pattern' => 'verwaltung'],
        ['domain' => 'offen.example', 'pattern' => null],
        ['domain' => 'leer.example', 'pattern' => ''],
    ]);

    expect($p->classify('irgendwer@plain.example'))->toBe(DomainClass::Approved);
    expect($p->classify('l.meier@schule.example.org'))->toBe(DomainClass::Approved);
    expect($p->classify('verwaltung@schule.example.org'))->toBe(DomainClass::Approved);
    expect($p->classify('s.meier@schule.example.org'))->toBe(DomainClass::Unknown);
    expect($p->classify('x@offen.example'))->toBe(DomainClass::Approved);
    expect($p->classify('x@leer.example'))->toBe(DomainClass::Approved);
});

it('never lets a broken pattern approve anyone or throw', function () {
    $p = new DomainPolicy('tmp.example.org', [], [['domain' => 'schule.example.org', 'pattern' => '[a-z']]);
    expect($p->classify('abc@schule.example.org'))->toBe(DomainClass::Unknown);
});

it('is safe against delimiter characters in a pattern', function () {
    $p = new DomainPolicy('tmp.example.org', [], [['domain' => 'schule.example.org', 'pattern' => 'a~b|c/d']]);
    expect($p->classify('a~b@schule.example.org'))->toBe(DomainClass::Approved);
    expect($p->classify('c/d@schule.example.org'))->toBe(DomainClass::Approved);
});

it('keeps freemail and own domain ahead of approved patterns', function () {
    $p = new DomainPolicy('tmp.example.org', ['gmail.com'], [['domain' => 'gmail.com', 'pattern' => '.+']]);
    expect($p->classify('x@gmail.com'))->toBe(DomainClass::Freemail);
});

it('validates patterns', function (string $pattern, bool $valid) {
    expect(DomainPolicy::isValidPattern($pattern))->toBe($valid);
})->with([
    ['[a-z]+\.[a-z]+', true],
    ['lehrer\..+', true],
    ['a|b', true],
    ['[a-z', false],
    ['(', false],
    ['*abc', false],
    ['', false],
    [str_repeat('a', 201), false],
]);
