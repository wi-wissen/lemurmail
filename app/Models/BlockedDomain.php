<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['domain'])]
class BlockedDomain extends Model
{
    public static function normalize(string $domain): string
    {
        return strtolower(trim(ltrim(trim($domain), '@')));
    }
}
