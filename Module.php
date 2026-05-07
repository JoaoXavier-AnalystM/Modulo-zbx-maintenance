<?php

declare(strict_types=1);

namespace Modules\MaintenancePlus;

// Manual requires — garante carregamento independente do autoloader case-sensitive
require_once __DIR__ . '/includes/CMaintenancePlusService.php';
require_once __DIR__ . '/includes/CAuditLogService.php';
require_once __DIR__ . '/includes/CTemplateService.php';

use APP;
use CMenuItem;
use Zabbix\Core\CModule;

class Module extends CModule {

    public function init(): void {
        $menu = APP::Component()->get('menu.main');

        $monitoring = $menu->find(_('Monitoring'));

        if ($monitoring !== null && $monitoring->hasSubMenu()) {
            $monitoring->getSubMenu()->add(
                (new CMenuItem(_('Maintenance Plus')))
                    ->setAction('maintenance.plus.dashboard')
                    ->setAliases([
                        'maintenance.plus.list',
                        'maintenance.plus.create',
                        'maintenance.plus.edit',
                        'maintenance.plus.delete',
                        'maintenance.plus.api.tags',
                        'maintenance.plus.api.hosts',
                        'maintenance.plus.api.preview',
                        'maintenance.plus.templates.list',
                        'maintenance.plus.templates.save',
                        'maintenance.plus.templates.delete',
                        'maintenance.plus.export',
                    ])
            );
        } else {
            $menu->add(
                (new CMenuItem(_('Maintenance Plus')))
                    ->setAction('maintenance.plus.dashboard')
            );
        }
    }
}
