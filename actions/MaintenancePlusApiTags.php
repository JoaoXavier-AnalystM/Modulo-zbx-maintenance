<?php

declare(strict_types=1);

namespace Modules\MaintenancePlus\Actions;

use CController;
use CControllerResponseData;
use Modules\MaintenancePlus\Includes\CMaintenancePlusService;

/**
 * Returns tag autocomplete suggestions.
 * Response: layout.json via main_block.
 */
class MaintenancePlusApiTags extends CController {

    protected function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        $fields = [
            'search' => 'string',
            'limit'  => 'ge 1|le 200',
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
        $limit  = (int) $this->getInput('limit', 50);

        $svc  = CMaintenancePlusService::getInstance();
        $tags = $svc->getAvailableTags($search, $limit);

        $this->setResponse(new CControllerResponseData([
            'main_block' => json_encode([
                'tags'  => $tags,
                'total' => count($tags),
            ]),
        ]));
    }
}
