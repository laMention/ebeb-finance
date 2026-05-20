<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id','code_otp','expire_at','est_utilise','contexte'])]
class SessionOtp extends Model
{
    //
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
