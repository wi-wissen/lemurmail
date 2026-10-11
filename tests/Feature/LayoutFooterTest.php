<?php

use App\Models\User;

it('shows imprint and privacy links in the footer only when configured', function () {
    config(['lemurmail.imprint_url' => null, 'lemurmail.privacy_url' => null]);
    $this->get('/')->assertDontSee(__('common.imprint'))->assertDontSee(__('common.privacy'));

    config(['lemurmail.imprint_url' => 'https://example.org/impressum', 'lemurmail.privacy_url' => 'https://example.org/datenschutz']);
    $html = $this->get('/')->assertSee('https://example.org/impressum', false)->assertSee('https://example.org/datenschutz', false)->getContent();

    expect($html)->toMatch('~<footer.*https://example.org/impressum.*</footer>~s');
});

it('keeps the language switch in the header, not in the footer', function () {
    $html = $this->get('/')->getContent();

    expect($html)->toMatch('~<header.*/lang/de.*</header>~s')->not->toMatch('~<footer.*/lang/de.*</footer>~s');
});

it('offers the language switch in the teacher header too', function () {
    $this->actingAs(User::factory()->create())->get(route('groups.index'))->assertSee(route('locale', 'de'), false);
});

it('links the complete icon set and every linked file exists', function () {
    $html = $this->get('/')->getContent();
    foreach (['favicon.ico', 'icon.svg', 'apple-touch-icon.png', 'manifest.webmanifest'] as $file) {
        expect($html)->toContain($file);
        expect(file_exists(public_path($file)))->toBeTrue();
    }

    $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);
    foreach ($manifest['icons'] as $icon) {
        expect(file_exists(public_path(ltrim($icon['src'], '/'))))->toBeTrue();
    }
    expect(collect($manifest['icons'])->pluck('purpose')->all())->toContain('maskable');
});
