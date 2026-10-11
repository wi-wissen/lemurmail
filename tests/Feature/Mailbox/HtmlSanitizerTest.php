<?php

use App\Services\HtmlSanitizer;

it('removes scripts, event handlers, forms, iframes, objects and remote resources', function (string $dirty, string $absent) {
    expect(app(HtmlSanitizer::class)->clean($dirty))->not->toContain($absent);
})->with([
    ['<p>x</p><script>alert(1)</script>', 'script'],
    ['<p onmouseover="x()">x</p>', 'onmouseover'],
    ['<iframe src="https://evil.example"></iframe>', 'iframe'],
    ['<object data="x"></object>', 'object'],
    ['<form action="https://evil.example"><input></form>', 'form'],
    ['<img src="https://track.example/p.gif">', 'track.example'],
    ['<link rel="stylesheet" href="https://evil.example/x.css">', 'evil.example'],
    ['<a href="javascript:alert(1)">x</a>', 'javascript:'],
    ['<style>@import url(https://evil.example/x.css);</style>x', 'evil.example'],
    ['<p style="background:url(https://evil.example/x.png)">x</p>', 'evil.example'],
    ['<meta http-equiv="refresh" content="0;url=https://evil.example">x', 'refresh'],
    ['<base href="https://evil.example/">x', 'evil.example'],
]);

it('keeps harmless formatting and https links and adds rel=noopener noreferrer', function () {
    $out = app(HtmlSanitizer::class)->clean('<h1>T</h1><p>a <b>b</b> <i>c</i></p><ul><li>x</li></ul><a href="https://ok.example/c">link</a>');
    expect($out)->toContain('<h1>T</h1>')->toContain('<b>b</b>')->toContain('<li>x</li>')->toContain('href="https://ok.example/c"')->toContain('noopener');
});

it('keeps inline data images', function () {
    $px = 'data:image/png;base64,iVBORw0KGgo=';
    expect(app(HtmlSanitizer::class)->clean('<img src="'.$px.'" alt="x">'))->toContain($px);
});

it('returns an empty string for empty input and survives broken markup', function () {
    expect(app(HtmlSanitizer::class)->clean(''))->toBe('');
    expect(app(HtmlSanitizer::class)->clean('<p><b>unclosed <i>tags'))->toBeString()->not->toContain('<script');
});

it('builds the sandbox document with CSP and target=_blank', function () {
    $doc = app(HtmlSanitizer::class)->document('<p>x</p>');
    expect($doc)->toContain('<!DOCTYPE html>')->toContain('Content-Security-Policy')->toContain("default-src 'none'")
        ->toContain("style-src 'unsafe-inline'")->toContain('img-src data:')->toContain('<base target="_blank">')->toContain('<p>x</p>');
});
