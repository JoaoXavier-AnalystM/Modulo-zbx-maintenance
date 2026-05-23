<?php

declare(strict_types=1);

namespace Modules\MaintenancePlus\Actions;

use API;
use CController;
use CControllerResponseData;

/**
 * Returns host search results for manual host picker.
 * Response: layout.json via main_block.
 */
class MaintenancePlusApiHosts extends CController {

    protected function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        $fields = [
            'search' => 'string',
            'limit'  => 'ge 1|le 500',
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
        $search = $this->getInput('search', '');
        $limit  = (int) $this->getInput('limit', 100);

        $result = API::Host()->get([
            'output'          => ['hostid', 'name', 'status'],
            'selectGroups'    => ['groupid', 'name'],
            'monitored_hosts' => true,
            'sortfield'       => 'name',
        ]);
        $hosts = is_array($result) ? $result : [];

        // PHP-side filtering — Zabbix API 'search' param unreliable in some versions
        if ($search !== '') {
            $searchLower = strtolower($search);
            $hosts = array_values(array_filter($hosts, static fn($h) =>
                strpos(strtolower($h['name']), $searchLower) !== false
            ));
        }

        $hosts = array_slice($hosts, 0, $limit);

        $this->setResponse(new CControllerResponseData([
            'main_block' => json_encode([
                'hosts' => $hosts,
                'total' => count($hosts),
            ]),
        ]));
    }
}
