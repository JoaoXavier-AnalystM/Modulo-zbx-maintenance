<?php

declare(strict_types=1);

namespace Modules\MaintenancePlus\Actions;

use CCsrfTokenHelper;
use CController;
use CControllerResponseData;
use CControllerResponseFatal;
use CControllerResponseRedirect;
use Modules\MaintenancePlus\Includes\CAuditLogService;
use Modules\MaintenancePlus\Includes\CMaintenancePlusService;

class MaintenancePlusEdit extends CController {

    protected function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        $fields = [
            'maintenanceid' => 'required|db maintenances.maintenanceid',
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
        $svc           = CMaintenancePlusService::getInstance();
        $maintenanceid = $this->getInput('maintenanceid');
        $maintenance   = $svc->getMaintenanceById($maintenanceid);

        if ($maintenance === null) {
            $this->setResponse(new CControllerResponseFatal());
            return;
        }

        // GET — render pre-populated form
        if (!$this->hasInput('active_since')) {
            $this->setResponse(new CControllerResponseData([
                'mode'        => 'edit',
                'maintenance' => $this->normalizeForForm($maintenance),
                'can_manage'  => true,
                'csrf_token'  => CCsrfTokenHelper::get('maintenance.plus.edit'),
            ]));
            return;
        }

        // POST — update
        try {
            $filterTags = $svc->normalizeFilterTags($this->getInput('filter_tags', []));
            $hostids    = $this->getInput('hostids', []);

            if (!empty($filterTags)) {
                $evalType   = ((int) $this->getInput('tags_evaltype', '0') === 2) ? 'or' : 'and';
                $tagHosts   = $svc->getHostsByTags($filterTags, $evalType, $hostids ?: null);
                $hostids    = array_map('strval', array_column($tagHosts, 'hostid'));
            }

            $data = [
                'name'             => trim($this->getInput('name', $maintenance['name'])),
                'description'      => $this->getInput('description', $maintenance['description'] ?? ''),
                'active_since'     => strtotime($this->getInput('active_since')),
                'active_till'      => strtotime($this->getInput('active_till')),
                'period'           => (int) $this->getInput('period', 3600),
                'maintenance_type' => (int) $this->getInput('maintenance_type', MAINTENANCE_TYPE_NORMAL),
                'tags_evaltype'    => (int) $this->getInput('tags_evaltype', MAINTENANCE_TAG_EVAL_TYPE_AND_OR),
                'hostids'          => array_values($hostids),
                'groupids'         => $this->getInput('groupids', []),
            ];

            $svc->updateMaintenance($maintenanceid, $data);

            (new CAuditLogService())->log(
                CAuditLogService::ACTION_UPDATE,
                'maintenance',
                $maintenanceid,
                ['name' => $data['name']]
            );

            $this->setResponse(
                (new CControllerResponseRedirect('zabbix.php?action=maintenance.plus.list'))
                    ->setFormData(['success' => _('Maintenance updated successfully.')])
            );
        } catch (\Throwable $e) {
            $this->setResponse(new CControllerResponseData([
                'mode'        => 'edit',
                'maintenance' => $this->normalizeForForm($maintenance),
                'error'       => $e->getMessage(),
                'can_manage'  => true,
                'csrf_token'  => CCsrfTokenHelper::get('maintenance.plus.edit'),
            ]));
        }
    }

    private function normalizeForForm(array $m): array {
        return [
            'maintenanceid'    => $m['maintenanceid'],
            'name'             => $m['name'],
            'description'      => $m['description'] ?? '',
            'active_since'     => date('Y-m-d\TH:i', (int) $m['active_since']),
            'active_till'      => date('Y-m-d\TH:i', (int) $m['active_till']),
            'period'           => (int) ($m['timeperiods'][0]['period'] ?? 3600),
            'maintenance_type' => (int) $m['maintenance_type'],
            'tags_evaltype'    => (int) $m['tags_evaltype'],
            'hosts'            => $m['hosts'] ?? [],
            'groups'           => $m['groups'] ?? [],
            'tags'             => array_map(static fn($t) => [
                'name'     => $t['tag'],
                'value'    => $t['value'] ?? '',
                'operator' => (int) ($t['operator'] ?? TAG_OPERATOR_EQUAL),
            ], $m['tags'] ?? []),
        ];
    }

}
