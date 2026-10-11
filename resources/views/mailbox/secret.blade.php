<x-mailbox-layout>
    <div class="flex flex-1 items-center justify-center px-4 pb-16 pt-6">
        <x-card class="w-full max-w-[560px] space-y-6 !p-8">
            @include('mailbox._secret_form', [
                'address' => $address,
                'action' => route('mailbox.secret.update'),
                'cancelUrl' => route('mailbox.inbox'),
            ])
        </x-card>
    </div>
</x-mailbox-layout>
