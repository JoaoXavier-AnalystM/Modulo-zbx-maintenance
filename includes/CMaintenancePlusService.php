<?php

declare(strict_types=1);

namespace Modules\MaintenancePlus\Includes;

use API;

/**
 * Core service layer — all Zabbix API interactions go through here.
 */
class CMaintenancePlusService {

    private static ?self $instance = null;

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    // ── Read ──────────────────────────────────────────────────────────────────

    public function getMaintenances(array $options = []): array {
        $result = API::Maintenance()->get(array_merge([
            'output'             => ['maintenanceid', 'name', 'maintenance_type',
                                     'active_since', 'active_till', 'description', 'tags_evaltype'],
            'selectHosts'        => ['hostid', 'name'],
            'selectGroups'       => ['groupid', 'name'],
            'selectTimeperiods'  => 'extend',
            'selectTags'         => 'extend',
            'sortfield'          => 'name',
            'sortorder'          => ZBX_SORT_UP,
        ], $options));

        return is_array($result) ? $result : [];
    }

    public function getMaintenanceById(string $maintenanceid): ?array {
        $result = API::Maintenance()->get([
            'output'            => 'extend',
            'maintenanceids'    => [$maintenanceid],
            'selectHosts'       => ['hostid', 'name'],
            'selectGroups'      => ['groupid', 'name'],
            'selectTimeperiods' => 'extend',
            'selectTags'        => 'extend',
        ]);

        return (!empty($result) && is_array($result)) ? reset($result) : null;
    }

    // ── Write ─────────────────────────────────────────────────────────────────

    public function createMaintenance(array $data): array {
        $result = API::Maintenance()->create($this->buildPayload($data));

        if ($result === false) {
            throw new \RuntimeException(
                API::getWrapper()->getLastError()['data'] ?? _('Failed to create maintenance.')
            );
        }

        return $result;
    }

    public function updateMaintenance(string $maintenanceid, array $data): array {
        $payload                    = $this->buildPayload($data);
        $payload['maintenanceid']   = $maintenanceid;

        $result = API::Maintenance()->update($payload);

        if ($result === false) {
            throw new \RuntimeException(
                API::getWrapper()->getLastError()['data'] ?? _('Failed to update maintenance.')
            );
        }

        return $result;
    }

    public function deleteMaintenance(array $maintenanceids): void {
        $result = API::Maintenance()->delete($maintenanceids);

        if ($result === false) {
            throw new \RuntimeException(
                API::getWrapper()->getLastError()['data'] ?? _('Failed to delete maintenance.')
            );
        }
    }

    // ── Tag-based host resolution ─────────────────────────────────────────────

    /**
     * Returns all monitored hosts whose tags match the given filter.
     *
     * @param array  $tags     [['name' => ..., 'value' => ..., 'operator' => ...], ...]
     * @param string $evalType 'and' | 'or'
     */
    public function getHostsByTags(array $tags, string $evalType = 'and'): array {
        if (empty($tags)) {
            return [];
        }

        $zbxTags = [];
        foreach ($tags as $tag) {
            $entry = ['tag' => $tag['name']];
            if (!empty($tag['value'])) {
                $entry['value']    = $tag['value'];
                $entry['operator'] = (int) ($tag['operator'] ?? TAG_OPERATOR_EQUAL);
            }
            $zbxTags[] = $entry;
        }

        $result = API::Host()->get([
            'output'          => ['hostid', 'name', 'status'],
            'selectGroups'    => ['groupid', 'name'],
            'selectTags'      => 'extend',
            'tags'            => $zbxTags,
            'evaltype'        => ($evalType === 'or') ? TAG_EVAL_TYPE_OR : TAG_EVAL_TYPE_AND_OR,
            'monitored_hosts' => true,
            'sortfield'       => 'name',
        ]);

        return is_array($result) ? $result : [];
    }

    /**
     * Returns deduplicated tag name/value pairs used across monitored hosts.
     * Powers the autocomplete in the tag selector.
     */
    public function getAvailableTags(string $search = '', int $limit = 50): array {
        $params = [
            'output'          => [],
            'selectTags'      => ['tag', 'value'],
            'monitored_hosts' => true,
            'limit'           => 5000,
        ];

        $hosts = API::Host()->get($params);

        if (!is_array($hosts)) {
            return [];
        }

        $seen = [];
        $tags = [];

        foreach ($hosts as $host) {
            foreach (($host['tags'] ?? []) as $t) {
                $key = $t['tag'] . '=' . $t['value'];

                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;

                if ($search === ''
                    || stripos($t['tag'], $search) !== false
                    || stripos($t['value'], $search) !== false
                ) {
                    $tags[] = ['name' => $t['tag'], 'value' => $t['value']];
                }
            }
        }

        usort($tags, static fn($a, $b) => strcmp($a['name'], $b['name']));

        return array_slice($tags, 0, $limit);
    }

    // ── Payload builder ───────────────────────────────────────────────────────

    private function buildPayload(array $data): array {
        $payload = [
            'name'             => $data['name'],
            'active_since'     => (int) $data['active_since'],
            'active_till'      => (int) $data['active_till'],
            'maintenance_type' => (int) ($data['maintenance_type'] ?? MAINTENANCE_TYPE_NORMAL),
            'description'      => $data['description'] ?? '',
            'tags_evaltype'    => (int) ($data['tags_evaltype'] ?? MAINTENANCE_TAG_EVAL_TYPE_AND_OR),
            'timeperiods'      => [[
                'timeperiod_type' => TIMEPERIOD_TYPE_ONETIME,
                'start_date'      => (int) $data['active_since'],
                'period'          => (int) ($data['period'] ?? 3600),
            ]],
        ];

        if (!empty($data['hostids'])) {
            $payload['hostids'] = array_values(array_unique(array_map('strval', $data['hostids'])));
        }

        if (!empty($data['groupids'])) {
            $payload['groupids'] = array_values(array_unique(array_map('strval', $data['groupids'])));
        }

        return $payload;
    }
}
