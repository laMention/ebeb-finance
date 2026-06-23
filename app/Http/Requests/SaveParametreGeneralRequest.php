<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveParametreGeneralRequest extends FormRequest
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
            // Informations générales
            'nom_plateforme'     => ['nullable', 'string', 'max:100'],
            'slogan'             => ['nullable', 'string', 'max:200'],
            'description_courte' => ['nullable', 'string', 'max:500'],
            'site_web'           => ['nullable', 'url', 'max:255'],
            'email_contact'      => ['nullable', 'email', 'max:255'],
            'telephone_contact'  => ['nullable', 'string', 'max:30'],
            'copyright'          => ['nullable', 'string', 'max:200'],
            // Identité visuelle (fichiers)
            'logo_principal'     => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'logo_favicon'       => ['nullable', 'file', 'mimes:png,jpg,jpeg,ico,svg', 'max:512'],
            'logo_email'         => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:1024'],
            'icone_application'  => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:1024'],
            // Réseaux sociaux
            'facebook'           => ['nullable', 'url', 'max:255'],
            'instagram'          => ['nullable', 'url', 'max:255'],
            'linkedin'           => ['nullable', 'url', 'max:255'],
            'x_twitter'          => ['nullable', 'url', 'max:255'],
            'tiktok'             => ['nullable', 'url', 'max:255'],
            'youtube'            => ['nullable', 'url', 'max:255'],
            'whatsapp'           => ['nullable', 'string', 'max:30'],
            // SEO
            'seo_titre'          => ['nullable', 'string', 'max:160'],
            'seo_description'    => ['nullable', 'string', 'max:320'],
            'seo_mots_cles'      => ['nullable', 'string', 'max:500'],
            'seo_image_og'       => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'seo_url_canonique'  => ['nullable', 'url', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom_plateforme.max'      => 'Le nom de la plateforme ne peut pas dépasser 100 caractères.',
            'slogan.max'              => 'Le slogan ne peut pas dépasser 200 caractères.',
            'description_courte.max'  => 'La description courte ne peut pas dépasser 500 caractères.',
            'site_web.url'            => 'Le site web doit être une URL valide (ex : https://exemple.com).',
            'email_contact.email'     => "L'email de contact doit être une adresse email valide.",
            'telephone_contact.max'   => 'Le téléphone ne peut pas dépasser 30 caractères.',
            'copyright.max'           => 'Le copyright ne peut pas dépasser 200 caractères.',

            'logo_principal.file'     => 'Le logo principal doit être un fichier.',
            'logo_principal.mimes'    => 'Le logo principal doit être au format PNG, JPG, JPEG, SVG ou WebP.',
            'logo_principal.max'      => 'Le logo principal ne peut pas dépasser 2 Mo.',

            'logo_favicon.file'       => 'Le favicon doit être un fichier.',
            'logo_favicon.mimes'      => 'Le favicon doit être au format PNG, JPG, JPEG, ICO ou SVG.',
            'logo_favicon.max'        => 'Le favicon ne peut pas dépasser 512 Ko.',

            'logo_email.file'         => "Le logo email doit être un fichier.",
            'logo_email.mimes'        => 'Le logo email doit être au format PNG, JPG, JPEG ou WebP.',
            'logo_email.max'          => 'Le logo email ne peut pas dépasser 1 Mo.',

            'icone_application.file'  => "L'icône application doit être un fichier.",
            'icone_application.mimes' => "L'icône application doit être au format PNG, JPG, JPEG ou WebP.",
            'icone_application.max'   => "L'icône application ne peut pas dépasser 1 Mo.",

            'facebook.url'            => 'L\'URL Facebook doit être valide.',
            'instagram.url'           => 'L\'URL Instagram doit être valide.',
            'linkedin.url'            => 'L\'URL LinkedIn doit être valide.',
            'x_twitter.url'           => 'L\'URL X (Twitter) doit être valide.',
            'tiktok.url'              => 'L\'URL TikTok doit être valide.',
            'youtube.url'             => 'L\'URL YouTube doit être valide.',
            'whatsapp.max'            => 'Le numéro WhatsApp ne peut pas dépasser 30 caractères.',

            'seo_titre.max'           => 'Le titre SEO ne peut pas dépasser 160 caractères.',
            'seo_description.max'     => 'La description SEO ne peut pas dépasser 320 caractères.',
            'seo_mots_cles.max'       => 'Les mots-clés SEO ne peuvent pas dépasser 500 caractères.',
            'seo_image_og.file'       => "L'image Open Graph doit être un fichier.",
            'seo_image_og.mimes'      => "L'image Open Graph doit être au format PNG, JPG, JPEG ou WebP.",
            'seo_image_og.max'        => "L'image Open Graph ne peut pas dépasser 2 Mo.",
            'seo_url_canonique.url'   => "L'URL canonique doit être une URL valide.",
        ];
    }
}
