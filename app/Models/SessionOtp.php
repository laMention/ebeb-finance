<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id','code_otp','expire_at','est_utilise','contexte','tentatives'])]
class SessionOtp extends Model
{
    //
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
