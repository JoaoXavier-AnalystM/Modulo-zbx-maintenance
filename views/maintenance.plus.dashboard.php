<?php
/**
 * Dashboard view.
 *
 * @var CView $this
 * @var array $data
 */

?>

<div class="mp-page mp-dashboard">

    <!-- Breadcrumb -->
    <nav class="mp-breadcrumb">
        <span><?= _('Maintenance Plus') ?></span>
    </nav>

    <div class="mp-page-header">
        <div class="mp-page-title">
            <h1><?= _('Maintenance Plus') ?></h1>
            <span class="mp-version-badge">v1.0</span>
        </div>
        <div class="mp-page-actions">
            <?php if ($data['can_create']): ?>
            <a href="zabbix.php?action=maintenance.plus.create" class="mp-btn mp-btn-primary">
                + <?= _('New Maintenance') ?>
            </a>
            <?php endif; ?>
            <a href="zabbix.php?action=maintenance.plus.list" class="mp-btn mp-btn-secondary">
                <?= _('All Maintenances') ?>
            </a>
        </div>
    </div>

    <!-- Stats cards -->
    <div class="mp-stats-grid" role="region" aria-label="<?= _('Maintenance statistics') ?>">

        <div class="mp-stat-card mp-stat-active">
            <div class="mp-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <div class="mp-stat-body">
                <div class="mp-stat-value"><?= (int)$data['active_count'] ?></div>
                <div class="mp-stat-label"><?= _('Active Now') ?></div>
            </div>
        </div>

        <div class="mp-stat-card mp-stat-upcoming">
            <div class="mp-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
            </div>
            <div class="mp-stat-body">
                <div class="mp-stat-value"><?= (int)$data['upcoming_count'] ?></div>
                <div class="mp-stat-label"><?= _('Upcoming') ?></div>
            </div>
        </div>

        <div class="mp-stat-card mp-stat-expired">
            <div class="mp-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
            <div class="mp-stat-body">
                <div class="mp-stat-value"><?= (int)$data['expired_count'] ?></div>
                <div class="mp-stat-label"><?= _('Expired') ?></div>
            </div>
        </div>

        <div class="mp-stat-card mp-stat-total">
            <div class="mp-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                </svg>
            </div>
            <div class="mp-stat-body">
                <div class="mp-stat-value"><?= (int)$data['total_count'] ?></div>
                <div class="mp-stat-label"><?= _('Total') ?></div>
            </div>
        </div>

        <div class="mp-stat-card mp-stat-hosts">
            <div class="mp-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>
                </svg>
            </div>
            <div class="mp-stat-body">
                <div class="mp-stat-value"><?= (int)$data['total_hosts'] ?></div>
                <div class="mp-stat-label"><?= _('Hosts Affected') ?></div>
            </div>
        </div>

    </div><!-- .mp-stats-grid -->

    <!-- Main grid -->
    <div class="mp-dashboard-grid">

        <!-- Calendar -->
        <div class="mp-card mp-card-calendar">
            <div class="mp-card-header">
                <h2><?= _('Maintenance Calendar') ?></h2>
                <div class="mp-calendar-nav" role="group" aria-label="<?= _('Calendar navigation') ?>">
                    <button id="mp-cal-prev" class="mp-btn-icon-only" aria-label="<?= _('Previous month') ?>">&#8249;</button>
                    <span id="mp-cal-month-label" aria-live="polite"><?= date('F Y') ?></span>
                    <button id="mp-cal-next" class="mp-btn-icon-only" aria-label="<?= _('Next month') ?>">&#8250;</button>
                </div>
            </div>
            <div class="mp-card-body">
                <div
                    id="mp-calendar"
                    data-events="<?= htmlspecialchars(json_encode($data['calendar_data']), ENT_QUOTES, 'UTF-8') ?>"
                ></div>
            </div>
        </div>

        <!-- Active maintenances -->
        <div class="mp-card">
            <div class="mp-card-header">
                <h2><?= _('Active') ?></h2>
                <?php if ($data['active_count'] > 0): ?>
                <span class="mp-badge mp-badge-active"><?= (int)$data['active_count'] ?></span>
                <?php endif; ?>
            </div>
            <div class="mp-card-body">
                <?php if (empty($data['active_list'])): ?>
                <div class="mp-empty-state">
                    <div class="mp-empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <h3><?= _('No active maintenances') ?></h3>
                    <p><?= _('Create a new maintenance to get started.') ?></p>
                </div>
                <?php else: ?>
                <ul class="mp-maintenance-list">
                    <?php foreach ($data['active_list'] as $m): ?>
                    <li class="mp-maintenance-item">
                        <div class="mp-mi-header">
                            <span class="mp-status-dot mp-dot-active" aria-hidden="true"></span>
                            <a href="zabbix.php?action=maintenance.plus.edit&maintenanceid=<?= htmlspecialchars($m['maintenanceid'], ENT_QUOTES) ?>">
                                <?= htmlspecialchars($m['name'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </div>
                        <div class="mp-mi-meta">
                            <span><?= date('d/m/Y H:i', (int)$m['active_since']) ?> → <?= date('d/m/Y H:i', (int)$m['active_till']) ?></span>
                            <span><?= count($m['hosts'] ?? []) ?> <?= _('hosts') ?></span>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($data['active_count'] > 5): ?>
                <div class="mp-card-footer-link">
                    <a href="zabbix.php?action=maintenance.plus.list&status=active"><?= _('View all active') ?> →</a>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Upcoming maintenances -->
        <div class="mp-card">
            <div class="mp-card-header">
                <h2><?= _('Upcoming') ?></h2>
                <?php if ($data['upcoming_count'] > 0): ?>
                <span class="mp-badge mp-badge-upcoming"><?= (int)$data['upcoming_count'] ?></span>
                <?php endif; ?>
            </div>
            <div class="mp-card-body">
                <?php if (empty($data['upcoming_list'])): ?>
                <div class="mp-empty-state">
                    <div class="mp-empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg>
                    </div>
                    <h3><?= _('No upcoming maintenances') ?></h3>
                    <p><?= _('All maintenances are either active or expired.') ?></p>
                </div>
                <?php else: ?>
                <ul class="mp-maintenance-list">
                    <?php foreach ($data['upcoming_list'] as $m): ?>
                    <li class="mp-maintenance-item">
                        <div class="mp-mi-header">
                            <span class="mp-status-dot mp-dot-upcoming" aria-hidden="true"></span>
                            <a href="zabbix.php?action=maintenance.plus.edit&maintenanceid=<?= htmlspecialchars($m['maintenanceid'], ENT_QUOTES) ?>">
                                <?= htmlspecialchars($m['name'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </div>
                        <div class="mp-mi-meta">
                            <span><?= date('d/m/Y H:i', (int)$m['active_since']) ?></span>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($data['upcoming_count'] > 5): ?>
                <div class="mp-card-footer-link">
                    <a href="zabbix.php?action=maintenance.plus.list&status=upcoming"><?= _('View all upcoming') ?> →</a>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- .mp-dashboard-grid -->
</div>
