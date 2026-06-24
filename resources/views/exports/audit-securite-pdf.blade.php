<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a2e; margin: 0; padding: 20px; }
  .header { background: linear-gradient(135deg, #1a3a5c, #2563eb); color: #fff; padding: 20px; border-radius: 6px; margin-bottom: 20px; }
  .header h1 { margin: 0 0 4px; font-size: 18px; }
  .header p  { margin: 0; font-size: 10px; opacity: .8; }
  .meta-grid { display: flex; gap: 12px; margin-bottom: 20px; }
  .meta-box  { flex: 1; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px; text-align: center; }
  .meta-box .val { font-size: 22px; font-weight: 700; }
  .meta-box .lbl { font-size: 9px; color: #6b7280; margin-top: 2px; }
  .critique { color: #dc2626; } .eleve { color: #ea580c; }
  .moyen    { color: #d97706; } .faible { color: #16a34a; } .info { color: #2563eb; }
  .bg-critique { background: #fef2f2; } .bg-eleve { background: #fff7ed; }
  .bg-moyen    { background: #fffbeb; } .bg-faible { background: #f0fdf4; }
  .bg-info     { background: #eff6ff; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
  th { background: #1a3a5c; color: #fff; padding: 6px 8px; text-align: left; font-size: 9px; }
  td { padding: 5px 8px; border-bottom: 1px solid #f3f4f6; font-size: 9px; vertical-align: top; }
  tr:nth-child(even) td { background: #f9fafb; }
  .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 8px; font-weight: 700; }
  .corrige-badge { background: #d1fae5; color: #065f46; }
  .detecte-badge { background: #fee2e2; color: #991b1b; }
  .en-cours-badge { background: #fef3c7; color: #92400e; }
  .section { font-size: 13px; font-weight: 700; color: #1a3a5c; border-bottom: 2px solid #1a3a5c; padding-bottom: 4px; margin: 16px 0 10px; }
  .footer { margin-top: 30px; border-top: 1px solid #e5e7eb; padding-top: 10px; color: #9ca3af; font-size: 8px; text-align: center; }
</style>
</head>
<body>

<div class="header">
  <h1>Rapport d'Audit de Sécurité</h1>
  <p>{{ $rapport->titre }} — Version {{ $rapport->version }} — Généré le {{ $date_generation }}</p>
</div>

{{-- Compteurs --}}
<div class="meta-grid">
  <div class="meta-box"><div class="val critique">{{ $rapport->nb_critique }}</div><div class="lbl">CRITIQUE</div></div>
  <div class="meta-box"><div class="val eleve">{{ $rapport->nb_eleve }}</div><div class="lbl">ÉLEVÉ</div></div>
  <div class="meta-box"><div class="val moyen">{{ $rapport->nb_moyen }}</div><div class="lbl">MOYEN</div></div>
  <div class="meta-box"><div class="val faible">{{ $rapport->nb_faible }}</div><div class="lbl">FAIBLE</div></div>
  <div class="meta-box"><div class="val info">{{ $rapport->nb_info }}</div><div class="lbl">INFO</div></div>
  <div class="meta-box"><div class="val" style="color:#16a34a">{{ $stats['taux_correction'] }}%</div><div class="lbl">TAUX CORRECTION</div></div>
</div>

{{-- Infos audit --}}
<div class="section">Informations de l'audit</div>
<table>
  <tr><td><strong>Date de l'audit</strong></td><td>{{ $rapport->date_audit?->format('d/m/Y H:i') }}</td>
      <td><strong>Réalisé par</strong></td><td>{{ $rapport->realisePar ? $rapport->realisePar->prenom.' '.$rapport->realisePar->nom : 'Système' }}</td></tr>
  <tr><td><strong>Statut</strong></td><td>{{ $rapport->statut }}</td>
      <td><strong>Total vulnérabilités</strong></td><td>{{ $stats['total'] }}</td></tr>
  <tr><td colspan="4">{{ $rapport->notes }}</td></tr>
</table>

{{-- Vulnérabilités par criticité --}}
@foreach(['CRITIQUE','ELEVE','MOYEN','FAIBLE','INFO'] as $niveau)
@php $vlist = $vulnerabilites->where('criticite', $niveau); @endphp
@if($vlist->isNotEmpty())
<div class="section {{ strtolower($niveau) }}">{{ $niveau }} ({{ $vlist->count() }})</div>
<table>
  <thead>
    <tr>
      <th width="5%">Code</th><th width="25%">Titre</th><th width="10%">Catégorie</th>
      <th width="8%">Statut</th><th width="30%">Description</th><th width="22%">Recommandation</th>
    </tr>
  </thead>
  <tbody>
    @foreach($vlist as $v)
    <tr class="bg-{{ strtolower($niveau) }}">
      <td>{{ $v->code }}</td>
      <td>{{ $v->titre }}</td>
      <td>{{ $v->categorie }}</td>
      <td>
        <span class="badge {{ $v->statut === 'CORRIGE' ? 'corrige-badge' : ($v->statut === 'EN_COURS' ? 'en-cours-badge' : 'detecte-badge') }}">
          {{ $v->statut }}
        </span>
      </td>
      <td>{{ Str::limit($v->description, 200) }}</td>
      <td>{{ Str::limit($v->recommandation, 150) }}</td>
    </tr>
    @if($v->notes_correction)
    <tr><td colspan="6" style="background:#f0fdf4;color:#065f46;font-style:italic;">
      ✓ Correction : {{ $v->notes_correction }}
    </td></tr>
    @endif
    @endforeach
  </tbody>
</table>
@endif
@endforeach

<div class="footer">
  Rapport confidentiel — E-BEB Finance — {{ $date_generation }} — Ne pas distribuer sans autorisation
</div>
</body>
</html>
