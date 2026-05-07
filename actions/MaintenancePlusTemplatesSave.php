<?php

declare(strict_types=1);

namespace Modules\MaintenancePlus\Actions;

use CController;
use CControllerResponseData;
use CControllerResponseFatal;
use Modules\MaintenancePlus\Includes\CTemplateService;

class MaintenancePlusTemplatesSave extends CController {

    protected function checkInput(): bool {
        $fields = [
            'id'          => 'string',
            'label'       => 'required|string|not_empty',
            'description' => 'string',
            'tags'        => 'array',
            'eval_type'   => 'in and,or',
            'period'      => 'ge 300',
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

        try {
            $template = [
                'id'          => $this->getInput('id', ''),
                'label'       => $this->getInput('label'),
                'description' => $this->getInput('description', ''),
                'tags'        => $this->getInput('tags', []),
                'eval_type'   => $this->getInput('eval_type', 'and'),
                'period'      => (int) $this->getInput('period', 3600),
            ];

            $saved = $svc->save($template);

            $this->setResponse(new CControllerResponseData([
                'main_block' => json_encode([
                    'success'  => _('Template saved.'),
                    'template' => $saved,
                ]),
            ]));
        } catch (\Throwable $e) {
            $this->setResponse(new CControllerResponseData([
                'main_block' => json_encode(['error' => $e->getMessage()]),
            ]));
        }
    }
}
