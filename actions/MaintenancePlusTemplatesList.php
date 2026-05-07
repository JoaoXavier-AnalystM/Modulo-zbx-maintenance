<?php

declare(strict_types=1);

namespace Modules\MaintenancePlus\Actions;

use CController;
use CControllerResponseData;
use Modules\MaintenancePlus\Includes\CTemplateService;

class MaintenancePlusTemplatesList extends CController {

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
        $svc       = new CTemplateService();
        $templates = $svc->getAll();

        $this->setResponse(new CControllerResponseData([
            'main_block' => json_encode([
                'templates' => $templates,
                'total'     => count($templates),
            ]),
        ]));
    }
}
