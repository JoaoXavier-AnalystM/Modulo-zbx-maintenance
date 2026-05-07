<?php

declare(strict_types=1);

namespace Modules\MaintenancePlus\Actions;

use CController;
use CControllerResponseData;
use CControllerResponseFatal;
use Modules\MaintenancePlus\Includes\CMaintenancePlusService;

class MaintenancePlusExport extends CController {

    protected function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        return true;
    }

    protected function checkPermissions(): bool {
        return true;
    }

    protected function doAction(): void {
        $svc  = CMaintenancePlusService::getInstance();
        $now  = time();
        $all  = $svc->getMaintenances(['limit' => 5000]);

        $rows = [];
        foreach ($all as $m) {
            $since = (int) $m['active_since'];
            $till  = (int) $m['active_till'];

            $status = match(true) {
                $now < $since         => 'Upcoming',
                $now >= $since && $now <= $till => 'Active',
                default               => 'Expired',
            };

            $durationSecs = max(0, $till - $since);
            $duration = $this->formatDuration($durationSecs);

            $tags = array_map(static fn($t) => $t['tag'] . (!empty($t['value']) ? '=' . $t['value'] : ''), $m['tags'] ?? []);

            $hosts = array_map(static fn($h) => $h['name'], $m['hosts'] ?? []);
            $groups = array_map(static fn($g) => $g['name'], $m['groups'] ?? []);

            // Try to extract creator from name
            $creator = '';
            if (preg_match('/criado\s+por\s+(.+?)$/u', $m['name'], $match)) {
                $creator = trim($match[1]);
            }

            $rows[] = [
                $m['name'],
                $creator,
                $status,
                date('Y-m-d H:i', $since),
                date('Y-m-d H:i', $till),
                $duration,
                (int)($m['maintenance_type'] ?? 0) === 1 ? 'No data collection' : 'With data collection',
                implode('; ', $tags),
                implode('; ', $hosts),
                implode('; ', $groups),
                $m['description'] ?? '',
            ];
        }

        $csv = $this->buildCsv($rows);
        $filename = 'maintenances_export_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($csv));

        echo $csv;
        exit;
    }

    private function formatDuration(int $seconds): string {
        if ($seconds < 3600) {
            return round($seconds / 60) . 'min';
        }
        if ($seconds < 86400) {
            return round($seconds / 3600, 1) . 'h';
        }
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        return $hours > 0 ? "{$days}d {$hours}h" : "{$days}d";
    }

    private function buildCsv(array $rows): string {
        $headers = [
            'Name', 'Creator', 'Status', 'Start', 'End', 'Duration',
            'Type', 'Tags', 'Hosts', 'Groups', 'Description',
        ];

        $fh = fopen('php://temp', 'r+');

        fputcsv($fh, $headers);
        foreach ($rows as $row) {
            fputcsv($fh, $row);
        }

        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        // Add BOM for Excel compatibility
        return "\xEF\xBB\xBF" . $csv;
    }
}
