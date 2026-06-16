<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentKYCResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "uuid" => $this->id,
            "user_id" => $this->user_id,
            "type_document" => $this->type_document,
            "numero_document" => $this->numero_document,
            "document_etablie_le" => format_date_fr_lettre($this->document_etablie_le),
            "document_expire_le" => format_date_fr_lettre($this->document_expire_le),
            "url_recto" => storage_public_path($this->url_recto), 
            "url_verso" => storage_public_path($this->url_verso), 
            "url_selfie" => storage_public_path($this->url_selfie), 
            "statut" => $this->statut, 
            "motif_rejet" => $this->motif_rejet, 
            "validated_at" => format_date_fr_chiffre($this->validated_at), 
        ];
    }
}
