<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Page extends Model
{
    use HasUuids, SoftDeletes;

    public $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable = [
        'titre', 'contenu', 'slug', 'statut', 'type_page',
        'meta_titre', 'meta_description', 'ordre', 'publie_le',
        'cree_par', 'modifie_par',
    ];

    protected $casts = [
        'publie_le' => 'datetime',
        'ordre'     => 'integer',
    ];

    public static array $TYPES = [
        'CGU'                    => 'Conditions Générales d\'Utilisation',
        'POLITIQUE_CONFIDENTIALITE' => 'Politique de confidentialité',
        'MENTIONS_LEGALES'       => 'Mentions légales',
        'A_PROPOS'               => 'À propos',
        'FAQ'                    => 'FAQ',
        'PERSONNALISEE'          => 'Page personnalisée',
    ];

    public static function genererSlug(string $titre, ?string $excludeId = null): string
    {
        $base = Str::slug($titre);
        $slug = $base;
        $i    = 1;

        while (
            static::withTrashed()
                ->where('slug', $slug)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(Administrateur::class, 'cree_par');
    }

    public function modificateur(): BelongsTo
    {
        return $this->belongsTo(Administrateur::class, 'modifie_par');
    }
}
