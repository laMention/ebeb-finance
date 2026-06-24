<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable(['nom_plateforme', 'slogan', 'description_courte', 'site_web', 'email_contact','telephone_contact','copyright','logo_principal',
    'logo_favicon','logo_email','icone_application','facebook','instagram','linkedin','x_twitter','tiktok','youtube','whatsapp','seo_titre','seo_description',
    'seo_mots_cles','seo_image_og','seo_url_canonique','modifie_par'
])]

class ParametreGeneral extends Model
{
    /** Champs contenant des chemins fichiers — exposés comme URLs complètes via les appends. */
    protected $hidden = [
        'logo_principal', 'logo_favicon', 'logo_email',
        'icone_application', 'seo_image_og',
    ];

    protected $appends = [
        'logo_principal_url', 'logo_favicon_url', 'logo_email_url',
        'icone_application_url', 'seo_image_og_url',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // Accessors — URLs publiques des fichiers
    // ─────────────────────────────────────────────────────────────────────────

    public function getLogoPrincipalUrlAttribute(): ?string
    {
        return $this->attributes['logo_principal']
            ? Storage::disk('public')->url($this->attributes['logo_principal'])
            : null;
    }

    public function getLogoFaviconUrlAttribute(): ?string
    {
        return $this->attributes['logo_favicon']
            ? Storage::disk('public')->url($this->attributes['logo_favicon'])
            : null;
    }

    public function getLogoEmailUrlAttribute(): ?string
    {
        return $this->attributes['logo_email']
            ? Storage::disk('public')->url($this->attributes['logo_email'])
            : null;
    }

    public function getIconeApplicationUrlAttribute(): ?string
    {
        return $this->attributes['icone_application']
            ? Storage::disk('public')->url($this->attributes['icone_application'])
            : null;
    }

    public function getSeoImageOgUrlAttribute(): ?string
    {
        return $this->attributes['seo_image_og']
            ? Storage::disk('public')->url($this->attributes['seo_image_og'])
            : null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Chemins bruts (pour suppression storage)
    // ─────────────────────────────────────────────────────────────────────────

    public static array $CHAMPS_FICHIERS = [
        'logo_principal', 'logo_favicon', 'logo_email',
        'icone_application', 'seo_image_og',
    ];
}
