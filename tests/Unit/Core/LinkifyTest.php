<?php

use App\Core\Text\Linkify;

it('returns plain text as one segment', function () {
    expect(Linkify::segments('Hallo Welt'))->toBe([['type' => 'text', 'value' => 'Hallo Welt']]);
    expect(Linkify::segments(''))->toBe([]);
});

it('splits out links', function () {
    expect(Linkify::segments('Klick https://a.example/x?t=1 jetzt'))->toBe([
        ['type' => 'text', 'value' => 'Klick '],
        ['type' => 'link', 'value' => 'https://a.example/x?t=1'],
        ['type' => 'text', 'value' => ' jetzt'],
    ]);
});

it('keeps trailing punctuation out of the url', function (string $in, string $url, string $rest, string $lead = 'see ') {
    $s = Linkify::segments($in);
    expect($s[0]['value'])->toBe($lead)->and($s[1])->toBe(['type' => 'link', 'value' => $url])->and($s[2]['value'])->toBe($rest);
})->with([
    ['see https://a.example/x.', 'https://a.example/x', '.'],
    ['see https://a.example/x, ok', 'https://a.example/x', ', ok'],
    ['see (https://a.example/x)', 'https://a.example/x', ')', 'see ('],
    ['see https://a.example/x!?', 'https://a.example/x', '!?'],
]);

it('keeps balanced parentheses in the url', function () {
    $s = Linkify::segments('https://a.example/w_(x)');
    expect($s)->toBe([['type' => 'link', 'value' => 'https://a.example/w_(x)']]);
});

it('only links http and https', function () {
    expect(Linkify::segments('javascript:alert(1) ftp://x.example'))->toBe([['type' => 'text', 'value' => 'javascript:alert(1) ftp://x.example']]);
});

it('round-trips the input and does not escape', function () {
    $in = "a <b> https://x.example/<script> & c\nhttp://y.example";
    $joined = implode('', array_column(Linkify::segments($in), 'value'));
    expect($joined)->toBe($in);
});

it('handles several links', function () {
    $s = Linkify::segments('http://a.example https://b.example');
    expect(array_column($s, 'type'))->toBe(['link', 'text', 'link']);
});
