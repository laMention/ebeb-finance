<?php
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
