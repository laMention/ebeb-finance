<?php
use App\Models\CguVersion;
use App\Models\Page;

$page = Page::where('type_page', 'CGU')->where('statut', 'PUBLIE')->first();
echo "Page CGU publiée trouvée: " . ($page ? 'OUI (' . $page->id . ')' : 'NON') . "\n";

$versions = CguVersion::all();
echo "Nombre de CguVersion: " . $versions->count() . "\n";
foreach ($versions as $v) {
    echo json_encode(['id'=>$v->id,'numero'=>$v->numero_version,'titre'=>$v->titre,'active'=>$v->est_active,'longueur_contenu'=>strlen($v->contenu ?? '')]) . "\n";
}
