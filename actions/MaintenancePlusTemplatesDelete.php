<?php

declare(strict_types=1);

namespace Modules\MaintenancePlus\Actions;

use CController;
use CControllerResponseData;
use Modules\MaintenancePlus\Includes\CTemplateService;

class MaintenancePlusTemplatesDelete extends CController {

    protected function checkInput(): bool {
        $fields = [
            'id' => 'required|string|not_empty',
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
        $svc = new CTemplateService();
        $id  = $this->getInput('id');

        if ($svc->delete($id)) {
            $this->setResponse(new CControllerResponseData([
                'main_block' => json_encode(['success' => _('Template deleted.')]),
            ]));
        } else {
            $this->setResponse(new CControllerResponseData([
                'main_block' => json_encode(['error' => _('Template not found.')]),
            ]));
        }
    }
}
