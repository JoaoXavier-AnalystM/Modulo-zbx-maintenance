<?php
/**
 * Shared create/edit form view.
 *
 * @var CView  $this
 * @var array  $data
 *   - mode:        'create' | 'edit'
 *   - maintenance: array
 *   - error:       string|null
 *   - csrf_token:  string
 */

$isEdit      = ($data['mode'] === 'edit');
$m           = $data['maintenance'];
$action      = $isEdit ? 'maintenance.plus.edit' : 'maintenance.plus.create';
$pageTitle   = $isEdit ? _('Edit Maintenance') : _('Create Maintenance');
$submitLabel = $isEdit ? _('Update') : _('Create');

$userName = htmlspecialchars($m['user_name'] ?? '', ENT_QUOTES, 'UTF-8');
?>

<div class="mp-page mp-form-page">

    <!-- Breadcrumb -->
    <nav class="mp-breadcrumb">
        <a href="zabbix.php?action=maintenance.plus.dashboard"><?= _('Maintenance Plus') ?></a>
        <span class="mp-breadcrumb-sep">/</span>
        <a href="zabbix.php?action=maintenance.plus.list"><?= _('Maintenances') ?></a>
        <span class="mp-breadcrumb-sep">/</span>
        <span><?= $pageTitle ?></span>
    </nav>

    <div class="mp-page-header">
        <div class="mp-page-title">
            <h1><?= $pageTitle ?></h1>
        </div>
        <div class="mp-page-actions">
            <a href="zabbix.php?action=maintenance.plus.list" class="mp-btn mp-btn-secondary mp-btn-sm">
                &#x2190; <?= _('Back to list') ?>
            </a>
        </div>
    </div>

    <?php if (!empty($data['error'])): ?>
    <div class="mp-alert mp-alert-error" role="alert">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <?= htmlspecialchars($data['error'], ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php endif; ?>

    <?php if (isset($data['success'])): ?>
    <div class="mp-alert mp-alert-success" role="alert">
        <?= htmlspecialchars($data['success'], ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php endif; ?>

    <div class="mp-form-layout">

        <!-- ── Main form ── -->
        <main class="mp-form-main">
            <form
                method="post"
                action="zabbix.php?action=<?= $action ?>"
                id="mp-main-form"
                novalidate
            >
                <input type="hidden" name="action" value="<?= $action ?>">
                <?php if ($isEdit): ?>
                <input type="hidden" name="maintenanceid" value="<?= htmlspecialchars($m['maintenanceid'], ENT_QUOTES) ?>">
                <?php endif; ?>
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($data['csrf_token'], ENT_QUOTES) ?>">

                <!-- Card: General ─────────────────────────────────────── -->
                <section class="mp-card mp-form-card" aria-label="<?= _('General') ?>">
                    <div class="mp-card-header"><h2><?= _('General') ?></h2></div>
                    <div class="mp-card-body">

                        <?php if (!$isEdit): ?>
                        <div class="mp-field mp-field-row">
                            <label class="mp-toggle-label" for="mp-auto-name">
                                <input type="checkbox" id="mp-auto-name" name="auto_name" value="1" checked>
                                <span class="mp-toggle-track" aria-hidden="true"></span>
                                <?= _('Generate name automatically') ?>
                            </label>
                            <span class="mp-field-hint mp-hint-inline" id="mp-auto-name-hint"><?= _('Auto-name active — toggle off to edit manually') ?></span>
                        </div>
                        <?php endif; ?>

                        <div class="mp-field" id="mp-name-field">
                            <label class="mp-label" for="mp-name">
                                <?= _('Name') ?> <span class="mp-required" aria-hidden="true">*</span>
                            </label>
                            <input
                                type="text"
                                id="mp-name"
                                name="name"
                                value="<?= htmlspecialchars($m['name'], ENT_QUOTES, 'UTF-8') ?>"
                                class="mp-input"
                                maxlength="128"
                                placeholder="<?= _('Nome da manutenção — o sufixo "criado por" será adicionado automaticamente') ?>"
                                <?= (!$isEdit) ? 'readonly' : '' ?>
                            >
                        </div>

                        <div class="mp-field">
                            <label class="mp-label" for="mp-description"><?= _('Description') ?></label>
                            <textarea id="mp-description" name="description" class="mp-textarea" rows="3"
                                placeholder="<?= _('Optional notes...') ?>"
                            ><?= htmlspecialchars($m['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>

                        <div class="mp-field">
                            <label class="mp-label"><?= _('Maintenance type') ?></label>
                            <div class="mp-radio-group mp-radio-inline">
                                <label class="mp-radio-label">
                                    <input type="radio" name="maintenance_type" value="0" <?= ((int)$m['maintenance_type'] === 0) ? 'checked' : '' ?>>
                                    <?= _('With data collection') ?>
                                </label>
                                <label class="mp-radio-label">
                                    <input type="radio" name="maintenance_type" value="1" <?= ((int)$m['maintenance_type'] === 1) ? 'checked' : '' ?>>
                                    <?= _('No data collection') ?>
                                </label>
                            </div>
                        </div>

                    </div>
                </section>

                <!-- Card: Schedule ────────────────────────────────────── -->
                <section class="mp-card mp-form-card" aria-label="<?= _('Schedule') ?>">
                    <div class="mp-card-header"><h2><?= _('Schedule') ?></h2></div>
                    <div class="mp-card-body">

                        <div class="mp-fields-inline">
                            <div class="mp-field">
                                <label class="mp-label" for="mp-active-since">
                                    <?= _('Active since') ?> <span class="mp-required" aria-hidden="true">*</span>
                                </label>
                                <input
                                    type="datetime-local"
                                    id="mp-active-since"
                                    name="active_since"
                                    value="<?= htmlspecialchars($m['active_since'], ENT_QUOTES) ?>"
                                    class="mp-input mp-input-datetime"
                                    required
                                >
                            </div>
                            <div class="mp-field">
                                <label class="mp-label" for="mp-active-till">
                                    <?= _('Active till') ?> <span class="mp-required" aria-hidden="true">*</span>
                                </label>
                                <input
                                    type="datetime-local"
                                    id="mp-active-till"
                                    name="active_till"
                                    value="<?= htmlspecialchars($m['active_till'], ENT_QUOTES) ?>"
                                    class="mp-input mp-input-datetime"
                                    required
                                >
                            </div>
                        </div>

                        <div class="mp-field">
                            <label class="mp-label" for="mp-period"><?= _('Duration') ?></label>
                            <div class="mp-duration-picker">
                                <input
                                    type="number"
                                    id="mp-period"
                                    name="period"
                                    value="<?= (int) ($m['period'] ?? 3600) ?>"
                                    class="mp-input mp-input-sm"
                                    min="300"
                                    step="300"
                                    aria-describedby="mp-duration-label"
                                >
                                <div class="mp-duration-presets" role="group" aria-label="<?= _('Quick duration presets') ?>">
                                    <button type="button" class="mp-preset" data-seconds="1800">30m</button>
                                    <button type="button" class="mp-preset" data-seconds="3600">1h</button>
                                    <button type="button" class="mp-preset" data-seconds="7200">2h</button>
                                    <button type="button" class="mp-preset" data-seconds="14400">4h</button>
                                    <button type="button" class="mp-preset" data-seconds="28800">8h</button>
                                    <button type="button" class="mp-preset" data-seconds="86400">24h</button>
                                </div>
                                <span id="mp-duration-label" class="mp-duration-human" aria-live="polite"></span>
                            </div>
                        </div>

                        <div id="mp-conflict-warning" class="mp-alert mp-alert-warning" hidden role="alert">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                            <span id="mp-conflict-text"></span>
                        </div>

                    </div>
                </section>

                <!-- ── Host selection: side-by-side grid ─────────────── -->
                <div class="mp-host-grid">

                    <!-- Card: Host Selection (Primary) ──────────────── -->
                    <section class="mp-card mp-form-card" id="mp-manual-card">
                        <div class="mp-card-header">
                            <h2>
                                <?= _('Host Selection') ?>
                                <span class="mp-badge mp-badge-recommended"><?= _('Primary') ?></span>
                            </h2>
                            <p class="mp-card-subtitle"><?= _('Search and select hosts for this maintenance. After selecting, tags from those hosts become available in the next step.') ?></p>
                        </div>
                        <div class="mp-card-body">

                            <div class="mp-field">
                                <label class="mp-label"><?= _('Hosts') ?></label>
                                <div class="mp-multiselect-wrapper" id="mp-hosts-ms">
                                    <div class="mp-multiselect-input-wrap">
                                    <input type="text" class="mp-input mp-multiselect-search" placeholder="<?= _('Search hosts…') ?>" autocomplete="off">
                                    <div class="mp-multiselect-dropdown" hidden></div>
                                    </div>
                                    <div class="mp-multiselect-chips" id="mp-host-chips">
                                        <?php foreach (($m['hosts'] ?? []) as $host): ?>
                                        <span class="mp-chip" data-id="<?= htmlspecialchars($host['hostid'], ENT_QUOTES) ?>">
                                            <?= htmlspecialchars($host['name'], ENT_QUOTES, 'UTF-8') ?>
                                            <button type="button" class="mp-chip-remove" aria-label="<?= _('Remove') ?>">×</button>
                                            <input type="hidden" name="hostids[]" value="<?= htmlspecialchars($host['hostid'], ENT_QUOTES) ?>">
                                        </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </section>

                    <!-- Card: Tags (scoped to selected hosts) ────── -->
                    <section class="mp-card mp-form-card mp-card-tags" aria-label="<?= _('Tag-based host filter') ?>">
                        <div class="mp-card-header">
                            <h2>
                                <?= _('Tags') ?>
                                <span class="mp-optional-label">(<?= _('optional') ?>)</span>
                            </h2>
                            <p class="mp-card-subtitle"><?= _('Search and select tags from the chosen hosts’ items. Focus the field to see all available tags.') ?></p>
                        </div>
                        <div class="mp-card-body">

                            <div class="mp-field">
                                <label class="mp-label"><?= _('Tag match logic') ?></label>
                                <div class="mp-radio-group mp-radio-inline">
                                    <label class="mp-radio-label">
                                        <input type="radio" name="tags_evaltype" value="0" id="mp-eval-and"
                                            <?= ((int)$m['tags_evaltype'] !== 2) ? 'checked' : '' ?>>
                                        <?= _('AND — host must have all selected tags') ?>
                                    </label>
                                    <label class="mp-radio-label">
                                        <input type="radio" name="tags_evaltype" value="2" id="mp-eval-or"
                                            <?= ((int)$m['tags_evaltype'] === 2) ? 'checked' : '' ?>>
                                        <?= _('OR — host must have at least one tag') ?>
                                    </label>
                                </div>
                            </div>

                            <div class="mp-tag-builder" id="mp-tag-builder">
                                <div class="mp-tag-search-row">
                                    <div class="mp-tag-autocomplete-wrapper">
                                        <input
                                            type="text"
                                            id="mp-tag-search"
                                            class="mp-input mp-tag-search-input"
                                            placeholder="<?= _('Search tags… e.g. Environment=Production') ?>"
                                            autocomplete="off"
                                            aria-autocomplete="list"
                                            aria-haspopup="listbox"
                                            aria-controls="mp-tag-suggestions"
                                        >
                                        <div
                                            id="mp-tag-suggestions"
                                            class="mp-autocomplete-dropdown"
                                            role="listbox"
                                            aria-label="<?= _('Tag suggestions') ?>"
                                            hidden
                                        ></div>
                                    </div>
                                    <button type="button" id="mp-add-tag-manual" class="mp-btn mp-btn-secondary mp-btn-sm">
                                        <?= _('Add') ?>
                                    </button>
                                </div>

                                <div
                                    id="mp-active-tags"
                                    class="mp-active-tags-area"
                                    role="list"
                                    aria-label="<?= _('Active tag filters') ?>"
                                >
                                    <?php foreach (($m['tags'] ?? []) as $i => $tag): ?>
                                    <div
                                        class="mp-tag-chip"
                                        data-name="<?= htmlspecialchars($tag['name'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-value="<?= htmlspecialchars($tag['value'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-operator="<?= (int)($tag['operator'] ?? 0) ?>"
                                        role="listitem"
                                    >
                                        <span class="mp-tag-chip-label">
                                            <strong><?= htmlspecialchars($tag['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                            <?php if (!empty($tag['value'])): ?>
                                            <span class="mp-tag-op">=</span>
                                            <span class="mp-tag-value"><?= htmlspecialchars($tag['value'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php endif; ?>
                                        </span>
                                        <button type="button" class="mp-tag-chip-remove" aria-label="<?= _('Remove tag') ?>">×</button>
                                        <input type="hidden" name="filter_tags[<?= $i ?>][name]" value="<?= htmlspecialchars($tag['name'], ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="filter_tags[<?= $i ?>][value]" value="<?= htmlspecialchars($tag['value'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="filter_tags[<?= $i ?>][operator]" value="<?= (int)($tag['operator'] ?? 0) ?>">
                                    </div>
                                    <?php endforeach; ?>
                                    <p id="mp-tags-placeholder" class="mp-tags-placeholder <?= !empty($m['tags']) ? 'hidden' : '' ?>">
                                        <?= _('No tags yet. Select hosts first, then focus the search field to see available tags from their items.') ?>
                                    </p>
                                </div>
                            </div>

                        </div>
                    </section>

                </div><!-- .mp-host-grid -->

                <!-- Form actions ─────────────────────────────────────── -->
                <div class="mp-form-actions">
                    <button type="submit" class="mp-btn mp-btn-primary mp-btn-lg"><?= $submitLabel ?></button>
                    <a href="zabbix.php?action=maintenance.plus.list" class="mp-btn mp-btn-secondary mp-btn-lg"><?= _('Cancel') ?></a>
                    <?php if (!$isEdit): ?>
                    <button type="button" id="mp-save-template" class="mp-btn mp-btn-secondary mp-btn-lg">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        <?= _('Save as Template') ?>
                    </button>
                    <?php endif; ?>
                </div>

            </form>
        </main>

        <!-- ── Sidebar ── -->
        <aside class="mp-form-sidebar">

            <?php if (!$isEdit): ?>
            <div class="mp-card mp-sidebar-card" id="mp-templates-card">
                <div class="mp-card-header">
                    <h3><?= _('Templates') ?></h3>
                    <button id="mp-refresh-templates" class="mp-btn-icon-only" title="<?= _('Refresh') ?>" aria-label="<?= _('Refresh templates') ?>">↻</button>
                </div>
                <div class="mp-card-body" id="mp-templates-list-body">
                    <div class="mp-loading-spinner mp-loading-sm"></div>
                </div>
            </div>
            <?php endif; ?>

            <div class="mp-card mp-sidebar-card" id="mp-preview-card" aria-live="polite">
                <div class="mp-card-header">
                    <h3><?= _('Affected Hosts Preview') ?></h3>
                    <span id="mp-preview-count" class="mp-preview-count-badge" aria-label="<?= _('Matched host count') ?>">0</span>
                </div>
                <div class="mp-card-body" id="mp-preview-body">
                    <div class="mp-empty-state">
                        <div class="mp-empty-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <rect x="2" y="3" width="20" height="14" rx="2"/>
                                <line x1="8" y1="21" x2="16" y2="21"/>
                                <line x1="12" y1="17" x2="12" y2="21"/>
                            </svg>
                        </div>
                        <h3><?= _('Affected Hosts Preview') ?></h3>
                        <p><?= _('Add tags above to preview which hosts will be affected by this maintenance.') ?></p>
                    </div>
                </div>
            </div>

        </aside>

    </div><!-- .mp-form-layout -->
</div>

<!-- Save Template Modal ──────────────────────────────────────────── -->
<div id="mp-template-modal" class="mp-modal" role="dialog" aria-modal="true" aria-labelledby="mp-tpl-title" hidden>
    <div class="mp-modal-backdrop"></div>
    <div class="mp-modal-box mp-modal-sm">
        <div class="mp-modal-header">
            <h3 id="mp-tpl-title"><?= _('Save as Template') ?></h3>
            <button class="mp-modal-close" aria-label="<?= _('Close') ?>">&times;</button>
        </div>
        <div class="mp-modal-body">
            <div class="mp-field">
                <label class="mp-label" for="mp-tpl-label"><?= _('Template name') ?> <span class="mp-required">*</span></label>
                <input type="text" id="mp-tpl-label" class="mp-input" placeholder="<?= _('e.g. Patch Tuesday') ?>" maxlength="64">
            </div>
            <div class="mp-field">
                <label class="mp-label" for="mp-tpl-desc"><?= _('Description') ?></label>
                <input type="text" id="mp-tpl-desc" class="mp-input" placeholder="<?= _('Optional') ?>">
            </div>
        </div>
        <div class="mp-modal-footer">
            <button id="mp-tpl-save-confirm" class="mp-btn mp-btn-primary"><?= _('Save') ?></button>
            <button class="mp-modal-close mp-btn mp-btn-secondary"><?= _('Cancel') ?></button>
        </div>
    </div>
</div>

<?php
// Pass PHP data to JS via inline script (Zabbix-standard pattern)
$jsData = json_encode([
    'userName'   => $m['user_name'] ?? '',
    'mode'       => $data['mode'],
    'existingTags' => $m['tags'] ?? [],
], JSON_HEX_TAG | JSON_HEX_AMP);
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    window.MP_FORM_DATA = <?= $jsData ?>;
});
</script>
