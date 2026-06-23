<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;

class SystemeService
{
    private function admin(): ?\App\Models\Administrateur
    {
        $user = Auth::guard('admin')->user();
        return $user instanceof \App\Models\Administrateur ? $user : null;
    }

    private string $logPath;
    private string $backupPath;

    public function __construct()
    {
        $this->logPath    = storage_path('logs/laravel.log');
        $this->backupPath = storage_path('app/backups');

        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Logs système
    // ─────────────────────────────────────────────────────────────────────────

    public function listerLogs(array $params): array
    {
        $content = $this->lireContenuLog();
        $entries = $this->parseLogs($content);

        // Filtres
        if (!empty($params['niveau'])) {
            $niveau  = strtoupper($params['niveau']);
            $entries = array_values(array_filter($entries, fn($e) => $e['niveau'] === $niveau));
        }

        if (!empty($params['search'])) {
            $search  = mb_strtolower($params['search']);
            $entries = array_values(array_filter($entries, fn($e) =>
                str_contains(mb_strtolower($e['message']), $search)
            ));
        }

        if (!empty($params['date_debut'])) {
            $entries = array_values(array_filter($entries, fn($e) =>
                substr($e['date'], 0, 10) >= $params['date_debut']
            ));
        }

        if (!empty($params['date_fin'])) {
            $entries = array_values(array_filter($entries, fn($e) =>
                substr($e['date'], 0, 10) <= $params['date_fin']
            ));
        }

        // Tri décroissant (plus récent en premier)
        usort($entries, fn($a, $b) => strcmp($b['date'], $a['date']));

        $total   = count($entries);
        $perPage = min((int) ($params['per_page'] ?? 50), 500);
        $page    = max(1, (int) ($params['page'] ?? 1));
        $items   = array_slice($entries, ($page - 1) * $perPage, $perPage);

        return [
            'entries' => $items,
            'meta'    => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => max(1, (int) ceil($total / $perPage)),
            ],
        ];
    }

    public function infoFichierLog(): array
    {
        if (!file_exists($this->logPath)) {
            return ['existe' => false, 'taille' => 0, 'derniere_modification' => null];
        }

        return [
            'existe'                => true,
            'taille'                => filesize($this->logPath),
            'derniere_modification' => date('Y-m-d H:i:s', filemtime($this->logPath)),
        ];
    }

    public function viderLogs(): void
    {
        $taille = file_exists($this->logPath) ? filesize($this->logPath) : 0;

        if (file_exists($this->logPath)) {
            file_put_contents($this->logPath, '');
        }

        AuditLogger::log('SYSTEM.LOG_CLEAR', $this->admin(), 'systeme', null,
            ['taille_avant' => $taille],
            ['taille_apres' => 0]
        );
    }

    public function cheminLog(): string
    {
        return $this->logPath;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Sauvegardes BDD
    // ─────────────────────────────────────────────────────────────────────────

    public function listerSauvegardes(): array
    {
        $files = glob($this->backupPath . '/*.sql') ?: [];
        usort($files, fn($a, $b) => filemtime($b) - filemtime($a));

        return array_map(fn($file) => [
            'nom'        => basename($file),
            'taille'     => filesize($file),
            'created_at' => date('Y-m-d H:i:s', filemtime($file)),
        ], $files);
    }

    public function creerSauvegarde(): array
    {
        $config   = config('database.connections.' . config('database.default'));
        $host     = $config['host']     ?? '127.0.0.1';
        $port     = $config['port']     ?? '3306';
        $database = $config['database'] ?? '';
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';

        $filename = 'backup_' . now()->format('Y-m-d_H-i-s') . '.sql';
        $filepath = $this->backupPath . DIRECTORY_SEPARATOR . $filename;

        // Écriture du fichier de credentials temporaire pour éviter le mot de passe en ligne de commande
        $tmpFile = tempnam(sys_get_temp_dir(), 'mysql_bak_');
        file_put_contents($tmpFile, "[client]\npassword={$password}\n");
        chmod($tmpFile, 0600);

        try {
            $command = sprintf(
                'mysqldump --defaults-extra-file=%s -h %s -P %s -u %s %s > %s 2>&1',
                escapeshellarg($tmpFile),
                escapeshellarg($host),
                escapeshellarg((string) $port),
                escapeshellarg($username),
                escapeshellarg($database),
                escapeshellarg($filepath)
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($filepath) || filesize($filepath) === 0) {
                if (file_exists($filepath)) unlink($filepath);
                throw new \RuntimeException('Échec mysqldump : ' . implode(' | ', $output));
            }
        } finally {
            @unlink($tmpFile);
        }

        $taille = filesize($filepath);

        AuditLogger::log('SYSTEM.BACKUP_CREATE', $this->admin(), 'systeme', $filename,
            null,
            ['nom' => $filename, 'taille' => $taille]
        );

        return [
            'nom'        => $filename,
            'taille'     => $taille,
            'created_at' => now()->format('Y-m-d H:i:s'),
        ];
    }

    public function cheminSauvegarde(string $filename): string
    {
        // Sanitize — basename only
        $safe = basename($filename);
        $path = $this->backupPath . DIRECTORY_SEPARATOR . $safe;

        if (!file_exists($path)) {
            throw new \RuntimeException("Sauvegarde introuvable : {$safe}");
        }

        return $path;
    }

    public function supprimerSauvegarde(string $filename): void
    {
        $safe   = basename($filename);
        $path   = $this->backupPath . DIRECTORY_SEPARATOR . $safe;
        $taille = file_exists($path) ? filesize($path) : null;

        if (file_exists($path)) {
            unlink($path);
        }

        AuditLogger::log('SYSTEM.BACKUP_DELETE', $this->admin(), 'systeme', $safe,
            ['nom' => $safe, 'taille' => $taille],
            null
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Parsing interne
    // ─────────────────────────────────────────────────────────────────────────

    private function lireContenuLog(int $maxBytes = 2 * 1024 * 1024): string
    {
        if (!file_exists($this->logPath)) return '';

        $taille = filesize($this->logPath);
        if ($taille <= $maxBytes) {
            return file_get_contents($this->logPath);
        }

        // Lire les derniers $maxBytes
        $fp = fopen($this->logPath, 'rb');
        fseek($fp, -$maxBytes, SEEK_END);
        $content = fread($fp, $maxBytes);
        fclose($fp);

        // Trouver la première entrée complète (commençant par [YYYY-MM-DD)
        if (preg_match('/\[\d{4}-\d{2}-\d{2}/', $content, $m, PREG_OFFSET_CAPTURE)) {
            $content = substr($content, $m[0][1]);
        }

        return $content;
    }

    private function parseLogs(string $content): array
    {
        if (!$content) return [];

        $entries = [];
        $blocks  = preg_split('/(?=\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\])/m', $content, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($blocks as $block) {
            $block = trim($block);
            if (!$block) continue;

            if (!preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \S+\.(\w+): (.*)$/ms', $block, $m)) {
                continue;
            }

            $date   = $m[1];
            $niveau = strtoupper($m[2]);
            $body   = trim($m[3]);

            // Premier ligne = message. On retire le contexte JSON final et le []
            $firstNl = strpos($body, "\n");
            $firstLine = $firstNl !== false ? substr($body, 0, $firstNl) : $body;

            // Supprime trailing {context} []
            $message = preg_replace('/\s*\{.*\}\s*\[\]\s*$/s', '', $firstLine);
            // Si pas de correspondance, retire à partir du premier " {"
            if ($message === null || strlen($message) === strlen($firstLine)) {
                $bracePos = strpos($firstLine, ' {');
                if ($bracePos !== false) {
                    $message = substr($firstLine, 0, $bracePos);
                } else {
                    $message = $firstLine;
                }
            }
            $message = trim($message);

            $entries[] = [
                'date'    => $date,
                'niveau'  => $niveau,
                'message' => $message ?: '(no message)',
                'raw'     => $block,
            ];
        }

        return $entries;
    }
}
