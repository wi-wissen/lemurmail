<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\Attributes\ErrorBag;

#[ErrorBag('address_sender')]
class AddressSenderStoreRequest extends SenderStoreRequest {}
