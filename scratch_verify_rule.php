<?php
use App\Models\CguAcceptation;
use App\Models\CguVersion;
use Illuminate\Support\Facades\Validator;

$version = CguVersion::where('est_active', true)->first();
$acceptation = CguAcceptation::create([
    'cgu_version_id' => $version->id,
    'user_id' => null,
    'accepte_le' => now(),
]);

$rules = ['cgu_acceptation_id' => ['required', 'uuid', 'exists:cgu_acceptations,id']];
$messages = [
    'cgu_acceptation_id.required' => "L'acceptation des CGU est obligatoire.",
    'cgu_acceptation_id.exists' => "L'acceptation des CGU est introuvable ou invalide.",
];

echo "== Cas valide ==\n";
$v1 = Validator::make(['cgu_acceptation_id' => $acceptation->id], $rules, $messages);
echo "passe: " . json_encode(!$v1->fails()) . " (attendu true)\n";

echo "== Cas manquant ==\n";
$v2 = Validator::make([], $rules, $messages);
echo "passe: " . json_encode(!$v2->fails()) . " (attendu false)\n";
echo "message: " . $v2->errors()->first('cgu_acceptation_id') . "\n";

echo "== Cas id inexistant ==\n";
$v3 = Validator::make(['cgu_acceptation_id' => '00000000-0000-0000-0000-000000000000'], $rules, $messages);
echo "passe: " . json_encode(!$v3->fails()) . " (attendu false)\n";
echo "message: " . $v3->errors()->first('cgu_acceptation_id') . "\n";

echo "== Nettoyage ==\n";
$acceptation->forceDelete();
echo "done\n";
