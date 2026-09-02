<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'statut'      => ['sometimes', Rule::in(Ticket::$STATUTS)],
            'severite'    => ['sometimes', Rule::in(Ticket::$SEVERITES)],
            'commentaire' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'statut.in'   => 'Statut invalide.',
            'severite.in' => 'Niveau de sévérité invalide.',
        ];
    }
}
