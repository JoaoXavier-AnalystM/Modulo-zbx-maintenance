<?php
/**
 * Maintenance list view.
 *
 * @var CView $this
 * @var array $data
 */

// Pass PHP data to JS
$jsConfig = json_encode([
    'canManage' => (bool) $data['can_manage'],
], JSON_HEX_TAG);
?>

<div class="mp-page mp-list-page">

    <div class="mp-page-header">
        <div class="mp-page-title">
            <h1><?= _('Maintenances') ?></h1>
        </div>
        <div class="mp-page-actions">
            <?php if ($data['can_manage']): ?>
            <a href="zabbix.php?action=maintenance.plus.create" class="mp-btn mp-btn-primary">
                + <?= _('Create Maintenance') ?>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="mp-toolbar">
        <form method="get" action="zabbix.php" id="mp-search-form" class="mp-toolbar-form" role="search">
            <input type="hidden" name="action" value="maintenance.plus.list">

            <div class="mp-search-box">
                <svg class="mp-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input
                    type="text"
                    name="search"
                    value="<?= htmlspecialchars($data['search'], ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="<?= _('Search maintenances…') ?>"
                    class="mp-search-input"
                    autocomplete="off"
                    aria-label="<?= _('Search maintenances') ?>"
                >
            </div>

            <div class="mp-filter-tabs" role="group" aria-label="<?= _('Filter by status') ?>">
                <?php foreach (['all' => _('All'), 'active' => _('Active'), 'upcoming' => _('Upcoming'), 'expired' => _('Expired')] as $val => $label): ?>
                <label class="mp-filter-tab <?= ($data['status'] === $val) ? 'active' : '' ?>">
                    <input type="radio" name="status" value="<?= $val ?>" <?= ($data['status'] === $val) ? 'checked' : '' ?>>
                    <?= $label ?>
                </label>
                <?php endforeach; ?>
            </div>
        </form>

        <?php if ($data['can_manage']): ?>
        <button id="mp-bulk-delete" class="mp-btn mp-btn-danger mp-btn-sm" disabled aria-label="<?= _('Delete selected maintenances') ?>">
            <?= _('Delete selected') ?>
        </button>
        <?php endif; ?>
        <a href="zabbix.php?action=maintenance.plus.export" class="mp-btn mp-btn-secondary mp-btn-sm" title="<?= _('Export as CSV') ?>">
            &#x21E9; <?= _('Export') ?>
        </a>
    </div>

    <!-- Table -->
    <div class="mp-card">
        <div class="mp-table-wrapper" role="region" aria-label="<?= _('Maintenance list') ?>">
            <table class="mp-table" id="mp-maintenances-table">
                <thead>
                    <tr>
                        <?php if ($data['can_manage']): ?>
                        <th class="mp-th-check">
                            <input type="checkbox" id="mp-select-all" aria-label="<?= _('Select all') ?>">
                        </th>
                        <?php endif; ?>
                        <th class="mp-th-status"><?= _('Status') ?></th>
                        <th>
                            <?php $sortHref = fn($field) => sprintf(
                                'zabbix.php?action=maintenance.plus.list&sort=%s&sortorder=%s&search=%s&status=%s',
                                $field,
                                ($data['sort'] === $field && $data['sortorder'] === 'ASC') ? 'DESC' : 'ASC',
                                urlencode($data['search']),
                                $data['status']
                            ); ?>
                            <a href="<?= $sortHref('name') ?>" class="mp-sort-link <?= $data['sort'] === 'name' ? 'active' : '' ?>">
                                <?= _('Name') ?>
                                <span class="mp-sort-arrow"><?= $data['sort'] === 'name' ? ($data['sortorder'] === 'ASC' ? '↑' : '↓') : '↕' ?></span>
                            </a>
                        </th>
                        <th><?= _('Creator') ?></th>
                        <th><?= _('Duration') ?></th>
                        <th><?= _('Type') ?></th>
                        <th>
                            <a href="<?= $sortHref('active_since') ?>" class="mp-sort-link <?= $data['sort'] === 'active_since' ? 'active' : '' ?>">
                                <?= _('Start') ?>
                                <span class="mp-sort-arrow"><?= $data['sort'] === 'active_since' ? ($data['sortorder'] === 'ASC' ? '↑' : '↓') : '↕' ?></span>
                            </a>
                        </th>
                        <th>
                            <a href="<?= $sortHref('active_till') ?>" class="mp-sort-link <?= $data['sort'] === 'active_till' ? 'active' : '' ?>">
                                <?= _('End') ?>
                                <span class="mp-sort-arrow"><?= $data['sort'] === 'active_till' ? ($data['sortorder'] === 'ASC' ? '↑' : '↓') : '↕' ?></span>
                            </a>
                        </th>
                        <th><?= _('Tags') ?></th>
                        <th><?= _('Hosts') ?></th>
                        <?php if ($data['can_manage']): ?>
                        <th><?= _('Actions') ?></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($data['maintenances'])): ?>
                    <tr>
                        <td colspan="<?= $data['can_manage'] ? 11 : 9 ?>" class="mp-td-empty">
                            <?= _('No maintenances found.') ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($data['maintenances'] as $m): ?>
                    <tr
                        class="mp-row mp-row-<?= htmlspecialchars($m['computed_status'], ENT_QUOTES) ?>"
                        data-id="<?= htmlspecialchars($m['maintenanceid'], ENT_QUOTES) ?>"
                    >
                        <?php if ($data['can_manage']): ?>
                        <td class="mp-td-check">
                            <input
                                type="checkbox"
                                class="mp-row-check"
                                value="<?= htmlspecialchars($m['maintenanceid'], ENT_QUOTES) ?>"
                                aria-label="<?= htmlspecialchars(_('Select') . ' ' . $m['name'], ENT_QUOTES, 'UTF-8') ?>"
                            >
                        </td>
                        <?php endif; ?>
                        <td>
                            <span class="mp-status-badge mp-status-<?= htmlspecialchars($m['computed_status'], ENT_QUOTES) ?>">
                                <?= match($m['computed_status']) {
                                    'active'   => _('Active'),
                                    'upcoming' => _('Upcoming'),
                                    default    => _('Expired'),
                                } ?>
                            </span>
                        </td>
                        <td class="mp-td-name">
                            <a href="zabbix.php?action=maintenance.plus.edit&maintenanceid=<?= htmlspecialchars($m['maintenanceid'], ENT_QUOTES) ?>">
                                <?= htmlspecialchars($m['name'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                            <?php if (!empty($m['description'])): ?>
                            <span class="mp-row-desc">
                                <?= htmlspecialchars(mb_substr($m['description'], 0, 90), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="mp-td-creator">
                            <?php if (!empty($m['creator'])): ?>
                            <span class="mp-creator-badge"><?= htmlspecialchars($m['creator'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php else: ?>
                            <span class="mp-text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="mp-td-duration">
                            <span class="mp-duration-badge"><?= htmlspecialchars($m['duration_formatted'], ENT_QUOTES) ?></span>
                        </td>
                        <td>
                            <span class="mp-type-badge mp-type-<?= (int)$m['maintenance_type'] ?>">
                                <?= ((int)$m['maintenance_type'] === 1) ? _('No data') : _('With data') ?>
                            </span>
                        </td>
                        <td class="mp-td-date"><?= date('d/m/Y H:i', (int)$m['active_since']) ?></td>
                        <td class="mp-td-date"><?= date('d/m/Y H:i', (int)$m['active_till']) ?></td>
                        <td class="mp-td-tags">
                            <?php if (!empty($m['tags_formatted'])): ?>
                            <div class="mp-tags-inline">
                                <?php foreach (array_slice($m['tags_formatted'], 0, 3) as $tag): ?>
                                <span class="mp-mini-badge"><?= htmlspecialchars($tag['name'], ENT_QUOTES, 'UTF-8') ?><?= !empty($tag['value']) ? '=' . htmlspecialchars($tag['value'], ENT_QUOTES, 'UTF-8') : '' ?></span>
                                <?php endforeach; ?>
                                <?php if (count($m['tags_formatted']) > 3): ?>
                                <span class="mp-tags-more">+<?= count($m['tags_formatted']) - 3 ?></span>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <span class="mp-text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="mp-host-count" title="<?= _('Hosts') ?>"><?= (int)$m['host_count'] ?></span>
                            <?php if ($m['group_count'] > 0): ?>
                            <span class="mp-group-count" title="<?= _('Groups') ?>">+<?= (int)$m['group_count'] ?>g</span>
                            <?php endif; ?>
                        </td>
                        <?php if ($data['can_manage']): ?>
                        <td class="mp-td-actions">
                            <a
                                href="zabbix.php?action=maintenance.plus.edit&maintenanceid=<?= htmlspecialchars($m['maintenanceid'], ENT_QUOTES) ?>"
                                class="mp-action-btn"
                                title="<?= _('Edit') ?>"
                                aria-label="<?= htmlspecialchars(_('Edit') . ' ' . $m['name'], ENT_QUOTES, 'UTF-8') ?>"
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </a>
                            <button
                                class="mp-action-btn mp-action-delete"
                                data-id="<?= htmlspecialchars($m['maintenanceid'], ENT_QUOTES) ?>"
                                data-name="<?= htmlspecialchars($m['name'], ENT_QUOTES, 'UTF-8') ?>"
                                title="<?= _('Delete') ?>"
                                aria-label="<?= htmlspecialchars(_('Delete') . ' ' . $m['name'], ENT_QUOTES, 'UTF-8') ?>"
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                    <path d="M10 11v6"/><path d="M14 11v6"/>
                                </svg>
                            </button>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($data['has_more']): ?>
        <div class="mp-load-more-bar">
            <button class="mp-btn mp-btn-secondary" id="mp-load-more" data-page="<?= (int)$data['page'] + 1 ?>">
                <?= _('Load more') ?>
            </button>
        </div>
        <?php endif; ?>
    </div>

</div><!-- .mp-list-page -->

<!-- Confirm delete modal -->
<div id="mp-delete-modal" class="mp-modal" role="dialog" aria-modal="true" aria-labelledby="mp-del-title" hidden>
    <div class="mp-modal-backdrop"></div>
    <div class="mp-modal-box">
        <div class="mp-modal-header">
            <h3 id="mp-del-title"><?= _('Confirm Delete') ?></h3>
            <button class="mp-modal-close" aria-label="<?= _('Close') ?>">&times;</button>
        </div>
        <div class="mp-modal-body">
            <p id="mp-del-message"></p>
        </div>
        <div class="mp-modal-footer">
            <button id="mp-del-confirm" class="mp-btn mp-btn-danger"><?= _('Delete') ?></button>
            <button class="mp-modal-close mp-btn mp-btn-secondary"><?= _('Cancel') ?></button>
        </div>
    </div>
</div>

<script>document.addEventListener('DOMContentLoaded', function() { window.MP_LIST_CONFIG = <?= $jsConfig ?>; });</script>
