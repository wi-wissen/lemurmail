<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['domain', 'local_part_pattern'])]
class OrganizationDomain extends Model
{
    public static function normalize(string $domain): string
    {
        return strtolower(trim(ltrim(trim($domain), '@')));
    }
}
