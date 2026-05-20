<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
// use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['nom', 'prenom', 'email', 'password','date_naissance','lieu_naissance','telephone','profession','numero_cnps','numero_cmu','statut','type_carte','pays','ville','quartier','village','adresse_postale','sexe','situation_familiale','nombre_enfants','date_activation','photo_profil','derniere_connexion'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasApiTokens, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'date_naissance' => 'date',
            'date_activation' => 'datetime',
            'derniere_connexion' => 'datetime',
        ];
    }

    public function cotisations()
    {
        return $this->hasMany(Cotisation::class);
    }

    public function compteMobileMoneys()
    {
        return $this->hasMany(CompteMobileMoney::class);
    }

    public function documentKYCs()
    {
        return $this->hasMany(DocumentKYC::class);
    }

    public function escrows()
    {
        return $this->hasMany(Escrow::class);
    }

    public function enfants()
    {
        return $this->hasMany(Enfant::class);
    }

    public function informationProfessionnelle()
    {
        return $this->hasOne(InformationProfessionnelle::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function objectifEpargnes()
    {
        return $this->hasMany(ObjectifEpargne::class);
    }

    public function operations()
    {
        return $this->hasMany(Operation::class);
    }

    public function paiementsEntrants()
    {
        return $this->hasMany(PaiementEntrant::class);
    }

    public function reglePrelevements()
    {
        return $this->hasMany(ReglePrelevement::class);
    }

    public function sessionOtps()
    {
        return $this->hasMany(SessionOtp::class);
    }

    public function declarationRevenu()
    {
        return $this->hasOne(DeclarationRevenu::class);
    }
}
