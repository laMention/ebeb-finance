<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9pt; color: #1e1e2e; }
  .header { padding: 16px 20px 12px; border-bottom: 2px solid #6d28d9; margin-bottom: 16px; }
  .logo { font-size: 16pt; font-weight: bold; color: #6d28d9; }
  .subtitle { font-size: 8pt; color: #666; margin-top: 2px; }
  h1 { font-size: 13pt; font-weight: bold; margin-bottom: 4px; }
  .meta { font-size: 8pt; color: #888; margin-bottom: 16px; padding: 0 20px; }
  table { width: 100%; border-collapse: collapse; font-size: 8pt; }
  thead tr th { background: #6d28d9; color: #fff; padding: 6px 8px; text-align: left; font-weight: bold; white-space: nowrap; }
  tbody tr td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
  tbody tr:nth-child(even) td { background: #f9fafb; }
  .footer { margin-top: 20px; padding: 10px 20px 0; border-top: 1px solid #e5e7eb; font-size: 7.5pt; color: #aaa; text-align: right; }
</style>
</head>
<body>
<div class="header">
  <div class="logo">E-BEB FINANCE</div>
  <div class="subtitle">Plateforme de gestion financière</div>
</div>
<div class="meta">
  <h1>{{ $titre }}</h1>
  <div>Exporté le {{ $date }} &mdash; {{ count($rows) }} ligne(s)</div>
</div>
<table>
  <thead>
    <tr>
      @foreach($headings as $h)
        <th>{{ $h }}</th>
      @endforeach
    </tr>
  </thead>
  <tbody>
    @foreach($rows as $row)
      <tr>
        @foreach($row as $cell)
          <td>{{ $cell }}</td>
        @endforeach
      </tr>
    @endforeach
  </tbody>
</table>
<div class="footer">E-BEB Finance &mdash; Document généré automatiquement</div>
</body>
</html>
