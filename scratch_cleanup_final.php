<?php
use App\Models\LogAudit;
use App\Models\Notification;

LogAudit::where('action', 'REMBOURSEMENT.CREATE')->forceDelete();
echo "log_audits nettoyé\n";

$notifs = Notification::where('type', 'REMBOURSEMENT_COTISATION')->get();
echo "Notifications trouvées: " . $notifs->count() . "\n";
foreach ($notifs as $n) echo json_encode(['titre' => $n->titre, 'user_id' => $n->user_id, 'created_at' => $n->created_at]) . "\n";
Notification::where('type', 'REMBOURSEMENT_COTISATION')->forceDelete();
echo "notifications nettoyées\n";
