<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['nom', 'prenom', 'ville', 'adresse', 'email', 'telephone', 'password', 'photo_profil', 'statut_compte'])]
#[Hidden(['password', 'remember_token'])]
class Administrateur extends Authenticatable
{
    /** @use HasFactory<AdministrateurFactory>; */
    use SoftDeletes, HasFactory, HasApiTokens;
    //

     /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
    ];

    public function logAudits()
    {
        return $this->hasMany(LogAudit::class);
    }

    public function parametresGlobaux()
    {
        return $this->hasMany(ParametreGlobal::class, 'administrateur_id');
    }
}
