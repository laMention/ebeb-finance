<?php

use App\Models\ParametreGeneral;
use Carbon\Carbon;
use Illuminate\Support\Str;
//  Methode pour mettre en majuscule
if (!function_exists('mettre_en_majuscule')) {
    function mettre_en_majuscule(?string $string): ?string
    {
        return mb_strtoupper($string);
    }
}

// Ajouter +225 devant les numero de telpehone
if (!function_exists('ajout_prefix_telephone')) {
    function ajout_prefix_telephone(?string $telephone): ?string
    {
        return  '+225' .$telephone;
    }
}

// Formater les date en francais en chiffre (JOUR/MOIS/ANNEE)
if (!function_exists('format_date_fr_chiffre')) {
    function format_date_fr_chiffre(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        return Carbon::parse($date)->format('d/m/Y H:i:s');
    }
}
// Formater les date en francais en lettre (JOUR/MOIS/ANNEE)
if (!function_exists('format_date_fr_lettre')) {
    function format_date_fr_lettre($date, ?string $format = 'D MMMM YYYY'): ?string
    {
        if (empty($date)) {
            return '';
        }

        return Carbon::parse($date)->locale('fr')->isoFormat($format);
    }
}
if (!function_exists('storage_public_path')) {
    /**
     * Retourne le chemin absolu d'un fichier stocké dans public/storage.
     *
     * @param  string  $path  Le chemin relatif de l'image (ex: 'avatars/user.jpg').
     * @return string
     */
    function storage_public_path(?string $path = ''): ?string
    {
        if (empty($path)) {
            return '';
        }

        // Si le chemin est déjà une URL valide (commence par http:// ou https://), on la retourne telle quelle
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        // Nettoie les préfixes redondants si le chemin contient déjà 'storage/' ou '/storage/'
        $path = ltrim($path, '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, 8); // Supprime 'storage/' du début
        }

        // Retourne l'URL HTTP complète (ex: http://localhost:8000/storage/chemin/image.jpg)
        return asset('storage/' . $path);
    }
}


if (!function_exists('verifier_validite')) {
    /**
     * Vérifie si une période (pièce d'identité, abonnement, etc.) est valide
     * 
     * @param string|Carbon $date_debut Date de début
     * @param string|Carbon|null $date_fin Date de fin (null = valide indéfiniment)
     * @return bool
     */
    function verifier_validite($date_debut, $date_fin = null)
    {
        try {
            $debut = $date_debut instanceof Carbon ? $date_debut : Carbon::parse($date_debut);
            $maintenant = Carbon::now();
            
            // Vérifier que la date de début n'est pas dans le futur
            if ($debut->isFuture()) {
                return false;
            }
            
            // Si une date de fin est fournie, vérifier qu'elle n'est pas dépassée
            if ($date_fin) {
                $fin = $date_fin instanceof Carbon ? $date_fin : Carbon::parse($date_fin);
                
                // Vérifier que la date de fin est dans le futur
                if ($fin->isPast()) {
                    return false;
                }
                
                // Vérifier que début < fin
                if ($debut->greaterThanOrEqualTo($fin)) {
                    return false;
                }
            }
            
            return true;
            
        } catch (\Exception $e) {
            // En cas d'erreur de parsing, on considère invalide
            return false;
        }
    }
}

// generer la reference de l'adherent (utilisateur)

if (!function_exists('generer_reference_user')) {
    function generer_reference_user()
    {
        $reference = 'EBEB-' . strtoupper(Str::random(10));

        return $reference;

    }

}

// Recuperation des informations publiques de la plateforme
if (!function_exists('info_public_plateforme')) {
    function info_public_plateforme()
    {
        $p = ParametreGeneral::first();
        
        return [
            'logo_principal_url' => $p->logo_principal_url ?? "",
            'logo_favicon_url' => $p->logo_favicon_url ?? "",
            'logo_email_url' => $p->logo_email_url ?? "",
            'icone_application_url' => $p->icone_application_url ?? "",
            'seo_image_og_url' => $p->seo_image_og_url ?? "",
            'nom_plateforme' => $p->nom_plateforme ?? "",
            'slogan' => $p->slogan ?? "",
            'email_contact' => $p->email_contact ?? "",
            'telephone_contact' => $p->telephone_contact ?? "",
            'copyright' => $p->copyright ?? "",
            'site_web' => $p->site_web ?? "",
        ];
    }

}
