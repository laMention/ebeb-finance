<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'nom', 'prenom', 'date_naissance', 'lieu_naissance'])]
class Enfant extends Model
{
    //
    use SoftDeletes,HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    public function casts(): array
    {
        return [
            'date_naissance' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
