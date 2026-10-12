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

it('falls back to the product name when no APP_NAME is set', function () {
    $config = require config_path('app.php');
    expect($config['name'])->toBe(env('APP_NAME', 'Lemur Mail'));
    expect(file_get_contents(config_path('app.php')).file_get_contents(config_path('mail.php')).file_get_contents(config_path('logging.php')))
        ->not->toContain("'APP_NAME', 'Laravel'");
});

it('puts the privacy link before the imprint, so the imprint is the rightmost link', function () {
    config(['lemurmail.imprint_url' => 'https://example.org/impressum', 'lemurmail.privacy_url' => 'https://example.org/datenschutz']);
    $html = $this->get('/')->getContent();

    expect(strpos($html, 'https://example.org/datenschutz'))->toBeLessThan(strpos($html, 'https://example.org/impressum'));
});

it('puts the privacy link before the imprint on the static About pages too', function () {
    foreach (['site/index.html' => ['datenschutz.html', 'impressum.html'], 'site/en/index.html' => ['privacy.html', 'imprint.html']] as $file => [$privacy, $imprint]) {
        $html = file_get_contents(base_path($file));
        expect(strrpos($html, 'href="'.$privacy.'"'))->toBeLessThan(strrpos($html, 'href="'.$imprint.'"'));
    }
});
