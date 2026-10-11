<x-app-layout>
    <div class="mx-auto w-full max-w-[560px]">
        <x-card class="space-y-6 !p-8">
            @include('mailbox._secret_form', [
                'address' => $address,
                'action' => route('addresses.secret.update', $address),
                'cancelUrl' => route('addresses.inbox', $address),
            ])
        </x-card>
    </div>
</x-app-layout>
