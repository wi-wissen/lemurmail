@if (config('friendlycaptcha.enabled'))
    <div class="rounded-lg border-[1.5px] border-dashed border-line-strong bg-paper p-3">
        <div class="frc-captcha" data-sitekey="{{ config('friendlycaptcha.sitekey') }}"
             data-puzzle-endpoint="{{ config('friendlycaptcha.puzzle_endpoint') }}"
             data-lang="{{ app()->getLocale() }}" data-solution-field-name="frc-captcha-solution"></div>
        <x-input-error :messages="$errors->get('frc-captcha-solution')" class="mt-2" />
    </div>
@endif
