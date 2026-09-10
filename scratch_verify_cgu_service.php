<?php
use App\Models\Administrateur;
use App\Models\CguVersion;
use App\Models\Page;
use App\Services\PageService;

$admin = Administrateur::first();
$page = Page::where('type_page', 'CGU')->where('statut', 'PUBLIE')->first();
echo "avant: " . CguVersion::count() . " version(s)\n";

echo "== Republication SANS changement de contenu (ne doit pas créer de doublon) ==\n";
PageService::publier($page->id, $admin);
echo "apres republication identique: " . CguVersion::count() . " version(s) (attendu: toujours 1)\n";

echo "== Modification du contenu (doit créer une v2) ==\n";
$contenuOriginal = $page->contenu;
$titreOriginal = $page->titre;
PageService::modifier($page->id, ['contenu' => $contenuOriginal . "\n\n[TEST AJOUT VERIF]", 'statut' => 'PUBLIE'], $admin);
$versions = CguVersion::orderBy('numero_version')->get();
echo "apres modification: " . $versions->count() . " version(s) (attendu: 2)\n";
foreach ($versions as $v) {
    echo "  v{$v->numero_version} active=" . json_encode($v->est_active) . " longueur=" . strlen($v->contenu) . "\n";
}

echo "== Restauration du contenu original ==\n";
PageService::modifier($page->id, ['contenu' => $contenuOriginal, 'titre' => $titreOriginal, 'statut' => 'PUBLIE'], $admin);
echo "Restauré (nouvelle v3 attendue, contenu = original) : " . CguVersion::count() . " version(s) au total\n";
