<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'titre', 'description', 'slug',
    'est_active',
])]
class ConditionGenerale extends Model
{
    use HasUuids, SoftDeletes;
}
