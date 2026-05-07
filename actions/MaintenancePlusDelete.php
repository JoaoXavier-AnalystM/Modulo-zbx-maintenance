<?php

declare(strict_types=1);

namespace Modules\MaintenancePlus\Actions;

use CController;
use CControllerResponseData;
use CControllerResponseFatal;
use Modules\MaintenancePlus\Includes\CAuditLogService;
use Modules\MaintenancePlus\Includes\CMaintenancePlusService;

class MaintenancePlusDelete extends CController {

    protected function checkInput(): bool {
        $fields = [
            'maintenanceids' => 'required|array_db maintenances.maintenanceid',
        ];

        $ret = $this->validateInput($fields);

        if (!$ret) {
            $this->setResponse(new CControllerResponseData([
                'main_block' => json_encode(['error' => _('Invalid input.')]),
            ]));
        }

        return $ret;
    }

    protected function checkPermissions(): bool {
        return true;
    }

    protected function doAction(): void {
        $ids = $this->getInput('maintenanceids');
        $svc = CMaintenancePlusService::getInstance();

        try {
            $svc->deleteMaintenance($ids);

            $audit = new CAuditLogService();
            foreach ($ids as $id) {
                $audit->log(CAuditLogService::ACTION_DELETE, 'maintenance', (string) $id);
            }

            $this->setResponse(new CControllerResponseData([
                'main_block' => json_encode([
                    'success' => sprintf(_('%d maintenance(s) deleted.'), count($ids)),
                ]),
            ]));
        } catch (\Throwable $e) {
            $this->setResponse(new CControllerResponseData([
                'main_block' => json_encode(['error' => $e->getMessage()]),
            ]));
        }
    }
}
