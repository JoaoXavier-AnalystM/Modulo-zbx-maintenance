<?php

declare(strict_types=1);

namespace Modules\MaintenancePlus\Actions;

use CController;
use CControllerResponseData;
use CControllerResponseFatal;
use Modules\MaintenancePlus\Includes\CMaintenancePlusService;

class MaintenancePlusList extends CController {

    protected function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        $fields = [
            'page'      => 'ge 1',
            'search'    => 'string',
            'sort'      => 'in name,active_since,active_till',
            'sortorder' => 'in ASC,DESC',
            'status'    => 'in active,upcoming,expired,all',
        ];

        $ret = $this->validateInput($fields);

        if (!$ret) {
            $this->setResponse(new CControllerResponseFatal());
        }

        return $ret;
    }

    protected function checkPermissions(): bool {
        return true;
    }

    protected function doAction(): void {
        $svc      = CMaintenancePlusService::getInstance();
        $now      = time();
        $page     = (int) $this->getInput('page', 1);
        $search   = $this->getInput('search', '');
        $sort     = $this->getInput('sort', 'name');
        $order    = $this->getInput('sortorder', 'ASC');
        $status   = $this->getInput('status', 'all');
        $perPage  = 25;

        $options = [
            'sortfield' => $sort,
            'sortorder' => ($order === 'DESC') ? ZBX_SORT_DOWN : ZBX_SORT_UP,
            'limit'     => $perPage + 1,
        ];

        if ($search !== '') {
            $options['search']                = ['name' => $search];
            $options['searchWildcardsEnabled'] = true;
        }

        $maintenances = $svc->getMaintenances($options);

        $hasMore = count($maintenances) > $perPage;
        if ($hasMore) {
            array_pop($maintenances);
        }

        foreach ($maintenances as &$m) {
            $since = (int) $m['active_since'];
            $till  = (int) $m['active_till'];

            $m['computed_status'] = match(true) {
                $now < $since         => 'upcoming',
                $now >= $since && $now <= $till => 'active',
                default               => 'expired',
            };

            $m['host_count']  = count($m['hosts'] ?? []);
            $m['group_count'] = count($m['groups'] ?? []);

            // Extract creator from name suffix "criado por USER"
            $m['creator'] = '';
            if (preg_match('/criado\s+por\s+(.+?)$/u', $m['name'], $match)) {
                $m['creator'] = trim($match[1]);
            }

            // Compute duration
            $durationSecs = max(0, $till - $since);
            $m['duration_formatted'] = $this->formatDuration($durationSecs);

            // Format tags for display
            $m['tags_formatted'] = array_map(static fn($t) => [
                'name'  => $t['tag'],
                'value' => $t['value'] ?? '',
            ], $m['tags'] ?? []);
        }
        unset($m);

        if ($status !== 'all') {
            $maintenances = array_values(
                array_filter($maintenances, fn($m) => $m['computed_status'] === $status)
            );
        }

        $this->setResponse(new CControllerResponseData([
            'maintenances' => $maintenances,
            'page'         => $page,
            'has_more'     => $hasMore,
            'search'       => $search,
            'sort'         => $sort,
            'sortorder'    => $order,
            'status'       => $status,
            'now'          => $now,
            'can_manage'   => true,
        ]));
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
}
