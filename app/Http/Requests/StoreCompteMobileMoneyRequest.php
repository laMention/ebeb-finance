<?php

namespace App\Http\Requests;

use App\Models\MoyenPaiement;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCompteMobileMoneyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'moyen_paiement_id' => [
                'required',
                'uuid',
                'exists:moyen_paiements,id',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $moyen = MoyenPaiement::find($value);
                    if ($moyen && !$moyen->est_actif) {
                        $fail("Ce moyen de paiement n'est pas actif.");
                    }
                },
            ],
            'numero_compte' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'moyen_paiement_id.required' => 'Le moyen de paiement est obligatoire.',
            'moyen_paiement_id.uuid'     => 'L\'identifiant du moyen de paiement est invalide.',
            'moyen_paiement_id.exists'   => 'Le moyen de paiement sélectionné n\'existe pas.',

            'numero_compte.string' => 'Le numéro de compte doit être une chaîne de caractères.',
            'numero_compte.max'    => 'Le numéro de compte ne peut pas dépasser 30 caractères.',
        ];
    }
}
