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

        $params = [
            'output'          => ['hostid', 'name', 'status'],
            'selectGroups'    => ['groupid', 'name'],
            'monitored_hosts' => true,
            'sortfield'       => 'name',
            'limit'           => $limit,
        ];

        if ($search !== '') {
            $params['search']                 = ['name' => $search];
            $params['searchWildcardsEnabled'] = true;
        }

        $result = API::Host()->get($params);
        $hosts  = is_array($result) ? $result : [];

        $this->setResponse(new CControllerResponseData([
            'main_block' => json_encode([
                'hosts' => $hosts,
                'total' => count($hosts),
            ]),
        ]));
    }
}
