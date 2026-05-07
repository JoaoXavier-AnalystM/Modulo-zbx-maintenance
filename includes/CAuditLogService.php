<?php

declare(strict_types=1);

namespace Modules\MaintenancePlus\Includes;

use CProfile;
use CWebUser;

/**
 * Lightweight audit trail stored in Zabbix profile table.
 * Retains up to 500 entries per day, for up to 7 days.
 */
class CAuditLogService {

    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';

    private const MAX_ENTRIES_PER_DAY = 500;

    public function log(string $action, string $resource, string $resourceId, array $details = []): void {
        $entry = [
            'userid'     => (int) (CWebUser::$data['userid'] ?? 0),
            'username'   => CWebUser::$data['alias'] ?? '',
            'action'     => $action,
            'resource'   => $resource,
            'resourceid' => $resourceId,
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? '',
            'timestamp'  => time(),
            'details'    => $details,
        ];

        $key  = $this->keyFor('today');
        $log  = $this->readKey($key);

        array_unshift($log, $entry);
        $log = array_slice($log, 0, self::MAX_ENTRIES_PER_DAY);

        CProfile::update($key, json_encode($log, JSON_UNESCAPED_UNICODE), PROFILE_TYPE_STR);
    }

    public function getLog(int $days = 7): array {
        $result = [];

        for ($i = 0; $i < $days; $i++) {
            $key     = $this->keyFor(date('Ymd', strtotime("-{$i} days")));
            $entries = $this->readKey($key);
            $result  = array_merge($result, $entries);
        }

        usort($result, static fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return $result;
    }

    private function readKey(string $key): array {
        $raw = CProfile::get($key);

        if ($raw === null || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function keyFor(string $date): string {
        return 'web.maintenance_plus.audit.' . $date;
    }
}
