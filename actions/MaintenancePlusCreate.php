<?php

declare(strict_types=1);

namespace Modules\MaintenancePlus\Actions;

use CCsrfTokenHelper;
use CController;
use CControllerResponseData;
use CControllerResponseFatal;
use CControllerResponseRedirect;
use CWebUser;
use Modules\MaintenancePlus\Includes\CAuditLogService;
use Modules\MaintenancePlus\Includes\CMaintenancePlusService;

class MaintenancePlusCreate extends CController {

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
        // GET — render blank form
        if (!$this->hasInput('active_since')) {
            $this->setResponse(new CControllerResponseData([
                'mode'        => 'create',
                'maintenance' => $this->defaultFormData(),
                'can_manage'  => true,
                'csrf_token'  => CCsrfTokenHelper::get('maintenance.plus.create'),
            ]));
            return;
        }

        // POST — persist
        try {
            $svc  = CMaintenancePlusService::getInstance();
            $data = $this->collectFormData();

            // Resolve tag-matched hosts
            $filterTags = $data['filter_tags'] ?? [];
            if (!empty($filterTags)) {
                $evalType   = ($data['tags_evaltype'] == 2) ? 'or' : 'and';
                $tagHosts   = $svc->getHostsByTags($filterTags, $evalType);
                $tagHostIds = array_column($tagHosts, 'hostid');
                $data['hostids'] = array_unique(array_merge($data['hostids'] ?? [], $tagHostIds));
            }

            $result = $svc->createMaintenance($data);
            $newId  = (string) reset($result['maintenanceids']);

            (new CAuditLogService())->log(
                CAuditLogService::ACTION_CREATE,
                'maintenance',
                $newId,
                ['name' => $data['name']]
            );

            $this->setResponse(
                (new CControllerResponseRedirect('zabbix.php?action=maintenance.plus.list'))
                    ->setFormData(['success' => _('Maintenance created successfully.')])
            );
        } catch (\Throwable $e) {
            $this->setResponse(new CControllerResponseData([
                'mode'        => 'create',
                'maintenance' => $this->defaultFormData(),
                'error'       => $e->getMessage(),
                'can_manage'  => true,
                'csrf_token'  => CCsrfTokenHelper::get('maintenance.plus.create'),
            ]));
        }
    }

    private function collectFormData(): array {
        $userData = CWebUser::$data;
        $autoName = (bool) $this->getInput('auto_name', 1);
        $fullName = trim(($userData['name'] ?? '') . ' ' . ($userData['surname'] ?? ''));
        $suffix   = $fullName !== '' ? ' - criado por ' . $fullName : '';

        if ($autoName) {
            $name = 'Manutenção programada - ' . date('Y-m-d H:i') . $suffix;
        } else {
            $raw   = trim($this->getInput('name', ''));
            $name  = $raw !== '' ? $raw . $suffix : '';
        }

        return [
            'name'             => $name,
            'description'      => $this->getInput('description', ''),
            'active_since'     => strtotime($this->getInput('active_since')),
            'active_till'      => strtotime($this->getInput('active_till')),
            'period'           => (int) $this->getInput('period', 3600),
            'maintenance_type' => (int) $this->getInput('maintenance_type', MAINTENANCE_TYPE_NORMAL),
            'tags_evaltype'    => (int) $this->getInput('tags_evaltype', MAINTENANCE_TAG_EVAL_TYPE_AND_OR),
            'hostids'          => $this->getInput('hostids', []),
            'groupids'         => $this->getInput('groupids', []),
            'filter_tags'      => $this->normalizeFilterTags($this->getInput('filter_tags', [])),
        ];
    }

    private function normalizeFilterTags(array $raw): array {
        $tags = [];
        foreach ($raw as $tag) {
            if (!empty($tag['name'])) {
                $tags[] = [
                    'name'     => $tag['name'],
                    'value'    => $tag['value'] ?? '',
                    'operator' => (int) ($tag['operator'] ?? TAG_OPERATOR_EQUAL),
                ];
            }
        }
        return $tags;
    }

    private function defaultFormData(): array {
        $userData = CWebUser::$data;
        $now      = time();
        $suffix   = ' - criado por ' . trim(($userData['name'] ?? '') . ' ' . ($userData['surname'] ?? ''));

        return [
            'maintenanceid'    => '',
            'name'             => 'Manutenção programada - ' . date('Y-m-d H:i') . $suffix,
            'description'      => '',
            'active_since'     => date('Y-m-d\TH:i', $now),
            'active_till'      => date('Y-m-d\TH:i', $now + 3600),
            'period'           => 3600,
            'maintenance_type' => MAINTENANCE_TYPE_NORMAL,
            'tags_evaltype'    => MAINTENANCE_TAG_EVAL_TYPE_AND_OR,
            'hosts'            => [],
            'groups'           => [],
            'tags'             => [],
            'user_name'        => trim(($userData['name'] ?? '') . ' ' . ($userData['surname'] ?? '')),
            'user_alias'       => $userData['alias'] ?? '',
        ];
    }
}
