@extends('layouts.app')
@section('content')
    @section('content')
        <!-- HERO -->
        @include('landing-page.sections.hero')

        <!-- PROBLEMATIQUE -->
        @include('landing-page.sections.problematique')


        <!-- COMMENT CA FONCTIONNE — SIGNATURE -->
        @include('landing-page.sections.comment-ca-fonctionne')


        <!-- FONCTIONNALITES -->
        @include('landing-page.sections.fonctionnalite')


        <!-- AVANTAGES -->
        @include('landing-page.sections.avantage')


        <!-- STATISTIQUES -->
        @include('landing-page.sections.statistique')


        <!-- TEMOIGNAGES -->
        @include('landing-page.sections.temoignage')


        <!-- FAQ -->
        @include('landing-page.sections.faq')


        <!-- CTA FINAL -->
        @include('landing-page.sections.cta')

    
    @endsection
@endsection