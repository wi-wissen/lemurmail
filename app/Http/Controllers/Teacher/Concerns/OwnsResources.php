<?php

namespace App\Http\Controllers\Teacher\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait OwnsResources
{
    /** Aborts with 404 unless the model belongs to the signed-in teacher; returns the model for chaining. */
    protected function owned(Request $request, Model $model): Model
    {
        abort_unless($model->getAttribute('user_id') === $request->user()->id, 404);

        return $model;
    }
}
