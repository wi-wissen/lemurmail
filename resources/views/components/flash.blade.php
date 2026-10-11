@if (session('status') || session('warning'))
    <div class="mb-6 space-y-3" role="status">
        @if (session('status'))
            <x-alert type="success">{{ session('status') }}</x-alert>
        @endif
        @if (session('warning'))
            <x-alert type="warn">{{ session('warning') }}</x-alert>
        @endif
    </div>
@endif
