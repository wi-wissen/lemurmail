<?php

namespace App\Http\Requests\Admin;

use Illuminate\Support\Arr;

class FreemailRequest extends DomainRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return Arr::only(parent::rules(), ['domain']);
    }
}
