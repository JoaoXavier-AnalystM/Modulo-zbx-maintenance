<?php

declare(strict_types=1);

namespace Modules\MaintenancePlus\Actions;

use API;
use CController;
use CControllerResponseData;
use CControllerResponseFatal;
use CWebUser;
use Modules\MaintenancePlus\Includes\CMaintenancePlusService;

class MaintenancePlusDashboard extends CController {

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
        $svc = CMaintenancePlusService::getInstance();
        $now = time();

        $all      = $svc->getMaintenances(['limit' => 1000]);
        $active   = array_values(array_filter($all, fn($m) => (int)$m['active_since'] <= $now && (int)$m['active_till'] >= $now));
        $upcoming = array_values(array_filter($all, fn($m) => (int)$m['active_since'] > $now));
        $expired  = array_values(array_filter($all, fn($m) => (int)$m['active_till'] < $now));

        usort($upcoming, fn($a, $b) => (int)$a['active_since'] <=> (int)$b['active_since']);

        $totalHosts = 0;
        foreach ($all as $m) {
            $totalHosts += count($m['hosts'] ?? []);
        }

        $user = CWebUser::$data;

        $this->setResponse(new CControllerResponseData([
            'active_count'   => count($active),
            'upcoming_count' => count($upcoming),
            'expired_count'  => count($expired),
            'total_count'    => count($all),
            'total_hosts'    => $totalHosts,
            'active_list'    => array_slice($active, 0, 5),
            'upcoming_list'  => array_slice($upcoming, 0, 5),
            'calendar_data'  => $this->buildCalendarData($all),
            'now'            => $now,
            'user_name'      => trim(($user['name'] ?? '') . ' ' . ($user['surname'] ?? '')),
            'can_create'     => true,
        ]));
    }

    private function buildCalendarData(array $maintenances): array {
        return array_map(static fn($m) => [
            'id'    => $m['maintenanceid'],
            'title' => $m['name'],
            'start' => (int) $m['active_since'],
            'end'   => (int) $m['active_till'],
            'type'  => (int) $m['maintenance_type'],
        ], $maintenances);
    }
}
