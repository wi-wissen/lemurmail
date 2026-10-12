@if (config('friendlycaptcha.enabled'))
    <div>
        <div class="frc-captcha" data-sitekey="{{ config('friendlycaptcha.sitekey') }}"
             data-puzzle-endpoint="{{ config('friendlycaptcha.puzzle_endpoint') }}"
             data-lang="{{ app()->getLocale() }}" data-solution-field-name="frc-captcha-solution"></div>
        <x-input-error :messages="$errors->get('frc-captcha-solution')" class="mt-2" />
    </div>
@endif
