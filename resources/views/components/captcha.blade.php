@if (config('altcha.enabled'))
    <div x-data="captcha" data-locale="{{ app()->getLocale() }}" data-strings='@json(__('captcha.widget'))'>
        <altcha-widget challenge="{{ route('captcha.challenge') }}" name="altcha" type="checkbox" auto="onfocus"
                       language="{{ app()->getLocale() }}"></altcha-widget>
        <x-input-error :messages="$errors->get('altcha')" class="mt-2" />
    </div>
@endif
