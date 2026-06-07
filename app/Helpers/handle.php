<?php

use Carbon\Carbon;
//  Methode pour mettre en majuscule
if (!function_exists('mettre_en_majuscule')) {
    function mettre_en_majuscule(string $string){
        return mb_strtoupper($string);
    }
}

// Ajouter +225 devant les numero de telpehone
if (!function_exists('ajout_prefix_telephone')) {
    function ajout_prefix_telephone(string $telephone){
        return  '+225' .$telephone;
    }
}

// Formater les date en francais en chiffre (JOUR/MOIS/ANNEE)
if (!function_exists('format_date_fr_chiffre')) {
    function format_date_fr_chiffre(string $date){
        if (empty($date)) {
            return '';
        }

        return Carbon::parse($date)->format('d/m/Y');
    }
}
// Formater les date en francais en lettre (JOUR/MOIS/ANNEE)
if (!function_exists('format_date_fr_lettre')) {
    function format_date_fr_lettre($date, string $format = 'D MMMM YYYY'){
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
    function storage_public_path(string $path = ''): string
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