<?php

declare(strict_types=1);

namespace Modules\MaintenancePlus\Actions;

use CController;
use CControllerResponseData;
use Modules\MaintenancePlus\Includes\CMaintenancePlusService;

/**
 * AJAX endpoint: returns live preview of hosts matching the given tags.
 * Called on every tag change in the create/edit form.
 * Response: layout.json via main_block.
 */
class MaintenancePlusApiPreview extends CController {

    protected function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        $fields = [
            'tags'      => 'array',
            'eval_type' => 'in and,or',
        ];

        $ret = $this->validateInput($fields);

        if (!$ret) {
            $this->setResponse(new CControllerResponseData([
                'main_block' => json_encode(['error' => 'Invalid input']),
            ]));
        }

        return $ret;
    }

    protected function checkPermissions(): bool {
        return true;
    }

    protected function doAction(): void {
        $tags     = $this->getInput('tags', []);
        $evalType = $this->getInput('eval_type', 'and');

        if (empty($tags)) {
            $this->setResponse(new CControllerResponseData([
                'main_block' => json_encode([
                    'host_count'  => 0,
                    'group_count' => 0,
                    'hosts'       => [],
                    'groups'      => [],
                    'truncated'   => false,
                ]),
            ]));
            return;
        }

        $svc   = CMaintenancePlusService::getInstance();
        $hosts = $svc->getHostsByTags($tags, $evalType);

        // Deduplicate groups
        $groups = [];
        foreach ($hosts as $host) {
            foreach (($host['groups'] ?? []) as $group) {
                $groups[$group['groupid']] = $group['name'];
            }
        }
        asort($groups);

        $hostPreview = array_slice($hosts, 0, 50);

        $this->setResponse(new CControllerResponseData([
            'main_block' => json_encode([
                'host_count'  => count($hosts),
                'group_count' => count($groups),
                'hosts'       => array_map(static fn($h) => [
                    'hostid' => $h['hostid'],
                    'name'   => $h['name'],
                    'groups' => array_column($h['groups'] ?? [], 'name'),
                ], $hostPreview),
                'groups'      => array_values(
                    array_map(
                        static fn($gid, $gname) => ['groupid' => $gid, 'name' => $gname],
                        array_keys($groups),
                        $groups
                    )
                ),
                'truncated'   => count($hosts) > 50,
            ]),
        ]));
    }
}
