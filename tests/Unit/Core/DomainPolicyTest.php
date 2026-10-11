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
