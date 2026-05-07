/**
 * Maintenance Plus - Main Application
 * Vanilla JS, no external runtime dependencies.
 */

'use strict';

const MaintenancePlus = (() => {

    // ── Utilities ─────────────────────────────────────────────────────────────

    const $ = (sel, ctx = document) => ctx.querySelector(sel);
    const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];

    function debounce(fn, delay = 300) {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => fn(...args), delay);
        };
    }

    function csrfToken() {
        return document.querySelector('input[name="_csrf_token"]')?.value ?? '';
    }

    async function apiFetch(action, params = {}, method = 'GET') {
        const url = new URL('zabbix.php', location.origin + location.pathname.replace(/\/[^/]*$/, '/'));
        url.searchParams.set('action', action);

        const opts = { headers: { 'X-Requested-With': 'XMLHttpRequest' } };

        if (method === 'POST') {
            opts.method = 'POST';
            const body = new FormData();
            body.append('_csrf_token', csrfToken());
            Object.entries(params).forEach(([k, v]) => {
                if (Array.isArray(v)) {
                    v.forEach(item => {
                        if (typeof item === 'object') {
                            Object.entries(item).forEach(([ik, iv]) => body.append(`${k}[][${ik}]`, iv));
                        } else {
                            body.append(`${k}[]`, item);
                        }
                    });
                } else {
                    body.append(k, v);
                }
            });
            opts.body = body;
        } else {
            Object.entries(params).forEach(([k, v]) => {
                if (Array.isArray(v)) {
                    v.forEach(item => url.searchParams.append(k + '[]', item));
                } else {
                    url.searchParams.set(k, v);
                }
            });
        }

        const res = await fetch(url.toString(), opts);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
    }

    function showToast(message, type = 'success') {
        let container = $('#mp-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'mp-toast-container';
            container.setAttribute('aria-live', 'polite');
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `mp-toast mp-toast-${type}`;
        toast.textContent = message;
        container.appendChild(toast);

        requestAnimationFrame(() => toast.classList.add('mp-toast-visible'));
        setTimeout(() => {
            toast.classList.remove('mp-toast-visible');
            toast.addEventListener('transitionend', () => toast.remove(), { once: true });
        }, 4000);
    }

    function formatSeconds(s) {
        if (s < 3600) return `${Math.round(s / 60)}min`;
        if (s < 86400) return `${Math.round(s / 3600)}h`;
        return `${Math.round(s / 86400)}d`;
    }

    // ── Tag Selector ──────────────────────────────────────────────────────────

    class TagSelector {
        constructor(container) {
            this.container    = container;
            this.searchInput  = $('#mp-tag-search', container);
            this.dropdown     = $('#mp-tag-suggestions', container);
            this.activeArea   = $('#mp-active-tags', container);
            this.placeholder  = $('#mp-tags-placeholder', container);
            this.evalType     = 'and';
            this.tags         = this._readExistingTags();
            this._recentTags  = [];

            this._attachEvents();
            this._updatePlaceholder();
        }

        _readExistingTags() {
            return $$('.mp-tag-chip', this.activeArea).map(chip => ({
                name:     chip.dataset.name,
                value:    chip.dataset.value,
                operator: parseInt(chip.dataset.operator, 10) || 0,
            }));
        }

        _attachEvents() {
            const debouncedSearch = debounce(q => this._fetchSuggestions(q), 200);

            this.searchInput.addEventListener('input', e => {
                const q = e.target.value.trim();
                if (q.length >= 1) {
                    debouncedSearch(q);
                } else {
                    this._showRecentOrHide();
                }
            });

            this.searchInput.addEventListener('focus', () => {
                if (!this.searchInput.value.trim()) this._showRecentOrHide();
            });

            this.searchInput.addEventListener('keydown', e => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const highlighted = $('.mp-suggestion-item.highlighted', this.dropdown);
                    if (highlighted) {
                        highlighted.click();
                    } else {
                        this._addTagFromInput();
                    }
                }
                if (e.key === 'ArrowDown') { e.preventDefault(); this._moveFocus(1); }
                if (e.key === 'ArrowUp')   { e.preventDefault(); this._moveFocus(-1); }
                if (e.key === 'Escape')    this._hideSuggestions();
                if (e.key === 'Tab')       this._hideSuggestions();
            });

            document.addEventListener('click', e => {
                if (!this.container.contains(e.target)) this._hideSuggestions();
            });

            $('#mp-add-tag-manual')?.addEventListener('click', () => this._addTagFromInput());

            $$('input[name="tags_evaltype"]').forEach(radio => {
                radio.addEventListener('change', () => {
                    this.evalType = radio.value === '2' ? 'or' : 'and';
                    HostPreview.refresh(this.tags, this.evalType);
                });
            });
        }

        _showRecentOrHide() {
            if (this._recentTags.length > 0) {
                this._renderSuggestions(this._recentTags, 'Recently used');
            } else {
                this._hideSuggestions();
            }
        }

        async _fetchSuggestions(q) {
            try {
                const data = await apiFetch('maintenance.plus.api.tags', { search: q, limit: 20 });
                const tags = data.tags || [];
                this._renderSuggestions(tags, tags.length + ' tag(s) found');
            } catch {
                this._hideSuggestions();
            }
        }

        _renderSuggestions(tags, heading = '') {
            if (!tags.length) {
                this.dropdown.innerHTML = '<div class="mp-suggestion-empty">' + this._esc('No tags found') + '</div>';
                this.dropdown.hidden = false;
                return;
            }

            let html = '';
            if (heading) {
                html += `<div class="mp-suggestion-heading">${this._esc(heading)}</div>`;
            }

            tags.forEach(tag => {
                html += `<div class="mp-suggestion-item" role="option" data-name="${this._esc(tag.name)}" data-value="${this._esc(tag.value)}">
                    <strong class="mp-sug-name">${this._esc(tag.name)}</strong>${tag.value ? `<span class="mp-sug-sep">=</span><span class="mp-sug-value">${this._esc(tag.value)}</span>` : ''}
                </div>`;
            });

            this.dropdown.innerHTML = html;
            this.dropdown.hidden = false;

            // Attach click handlers
            $$('.mp-suggestion-item', this.dropdown).forEach(item => {
                item.addEventListener('mousedown', e => {
                    e.preventDefault();
                    this._addTag(item.dataset.name, item.dataset.value);
                    this._hideSuggestions();
                    this.searchInput.value = '';
                    this.searchInput.focus();
                });
            });
        }

        _hideSuggestions() {
            this.dropdown.hidden = true;
            this.dropdown.innerHTML = '';
        }

        _moveFocus(dir) {
            const items = $$('.mp-suggestion-item', this.dropdown);
            if (!items.length) return;
            const current = $('.mp-suggestion-item.highlighted', this.dropdown);
            let idx = items.indexOf(current) + dir;
            if (idx < 0) idx = items.length - 1;
            if (idx >= items.length) idx = 0;
            items.forEach(i => i.classList.remove('highlighted'));
            items[idx]?.classList.add('highlighted');
            items[idx]?.scrollIntoView({ block: 'nearest' });
        }

        _addTagFromInput() {
            const raw = this.searchInput.value.trim();
            if (!raw) return;

            const eqIdx = raw.indexOf('=');
            const name  = eqIdx > -1 ? raw.slice(0, eqIdx).trim() : raw;
            const value = eqIdx > -1 ? raw.slice(eqIdx + 1).trim() : '';

            if (name) {
                this._addTag(name, value);
                this.searchInput.value = '';
                this._hideSuggestions();
            }
        }

        _addTag(name, value = '', operator = 0) {
            if (this.tags.find(t => t.name === name && t.value === value)) return;

            this.tags.push({ name, value, operator });
            this._renderChip(name, value, operator);
            this._updatePlaceholder();
            HostPreview.refresh(this.tags, this.evalType);

            // Track recently used tags (keep last 10, most recent first)
            const key = name + '=' + value;
            this._recentTags = this._recentTags.filter(t => (t.name + '=' + t.value) !== key);
            this._recentTags.unshift({ name, value });
            if (this._recentTags.length > 10) this._recentTags.pop();
        }

        _renderChip(name, value, operator) {
            const chip = document.createElement('div');
            chip.className    = 'mp-tag-chip';
            chip.dataset.name = name;
            chip.dataset.value = value;
            chip.dataset.operator = operator;

            const labelParts = `<strong>${this._esc(name)}</strong>${value ? `<span class="mp-tag-op">=</span><span class="mp-tag-value">${this._esc(value)}</span>` : ''}`;
            const idx = this.tags.length - 1;
            chip.innerHTML = `
                <span class="mp-tag-chip-label">${labelParts}</span>
                <button type="button" class="mp-tag-chip-remove" aria-label="Remove tag">×</button>
                <input type="hidden" name="filter_tags[${idx}][name]" value="${this._esc(name)}">
                <input type="hidden" name="filter_tags[${idx}][value]" value="${this._esc(value)}">
                <input type="hidden" name="filter_tags[${idx}][operator]" value="${operator}">
            `;

            chip.querySelector('.mp-tag-chip-remove').addEventListener('click', () => {
                this.tags = this.tags.filter(t => !(t.name === name && t.value === value));
                chip.remove();
                this._reIndexHiddenInputs();
                this._updatePlaceholder();
                HostPreview.refresh(this.tags, this.evalType);
            });

            this.placeholder.before(chip);
        }

        _reIndexHiddenInputs() {
            $$('.mp-tag-chip', this.activeArea).forEach((chip, i) => {
                $$('input[type="hidden"]', chip).forEach(inp => {
                    inp.name = inp.name.replace(/\[\d+\]/, `[${i}]`);
                });
            });
        }

        _updatePlaceholder() {
            this.placeholder.classList.toggle('hidden', this.tags.length > 0);
        }

        _esc(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }
    }

    // ── Host Preview Panel ────────────────────────────────────────────────────

    const HostPreview = (() => {
        let _loading = false;
        let _pending = null;

        function refresh(tags, evalType) {
            if (_loading) { _pending = { tags, evalType }; return; }
            _fetch(tags, evalType);
        }

        async function _fetch(tags, evalType) {
            if (!tags.length) { _render({ host_count: 0, group_count: 0, hosts: [], groups: [] }); return; }

            _loading = true;
            _showLoading();

            try {
                const data = await apiFetch('maintenance.plus.api.preview', { tags, eval_type: evalType }, 'POST');
                _render(data);
            } catch {
                _renderError();
            } finally {
                _loading = false;
                if (_pending) { const p = _pending; _pending = null; _fetch(p.tags, p.evalType); }
            }
        }

        function _showLoading() {
            const body = $('#mp-preview-body');
            if (body) body.innerHTML = '<div class="mp-loading-spinner"></div>';
        }

        function _render(data) {
            const countBadge = $('#mp-preview-count');
            const body       = $('#mp-preview-body');
            if (!body) return;

            if (countBadge) {
                countBadge.textContent = data.host_count;
                countBadge.className   = `mp-preview-count-badge ${data.host_count > 0 ? 'has-hosts' : ''}`;
            }

            if (data.host_count === 0) {
                body.innerHTML = '<div class="mp-preview-empty"><p>No hosts match the selected tags.</p></div>';
                return;
            }

            const hostsHtml = data.hosts.map(h => `
                <div class="mp-preview-host">
                    <span class="mp-preview-host-icon">⬜</span>
                    <span class="mp-preview-host-name">${_esc(h.name)}</span>
                    <span class="mp-preview-host-groups">${h.groups.map(g => `<span class="mp-mini-badge">${_esc(g)}</span>`).join('')}</span>
                </div>
            `).join('');

            const truncNote = data.truncated
                ? `<div class="mp-preview-truncated">Showing first 50 of ${data.host_count} hosts</div>`
                : '';

            body.innerHTML = `
                <div class="mp-preview-summary">
                    <div class="mp-preview-stat">
                        <span class="mp-preview-stat-val">${data.host_count}</span>
                        <span class="mp-preview-stat-lbl">Hosts</span>
                    </div>
                    <div class="mp-preview-stat">
                        <span class="mp-preview-stat-val">${data.group_count}</span>
                        <span class="mp-preview-stat-lbl">Groups</span>
                    </div>
                </div>
                <div class="mp-preview-hosts-list">${hostsHtml}</div>
                ${truncNote}
            `;
        }

        function _renderError() {
            const body = $('#mp-preview-body');
            if (body) body.innerHTML = '<div class="mp-preview-error">Failed to load preview.</div>';
        }

        function _esc(str) {
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        return { refresh };
    })();

    // ── Auto Name ─────────────────────────────────────────────────────────────

    function initAutoName() {
        const toggle  = $('#mp-auto-name');
        const field   = $('#mp-name-field');
        const nameInp = $('#mp-name');
        if (!toggle || !nameInp) return;

        function getUserSuffix() {
            const user = document.body.dataset.userName || '';
            return user ? ` - criado por ${user}` : '';
        }

        function updateState() {
            const auto = toggle.checked;
            nameInp.readOnly = auto;
            field.classList.toggle('mp-field-readonly', auto);
            if (auto) refreshAutoName();
        }

        function refreshAutoName() {
            const user  = document.body.dataset.userName || '';
            const now   = new Date();
            const pad   = n => String(n).padStart(2, '0');
            const dt    = `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())} ${pad(now.getHours())}:${pad(now.getMinutes())}`;
            const suffix = user ? ` - criado por ${user}` : '';
            nameInp.value = `Manutenção programada - ${dt}${suffix}`;
        }

        // Auto-append suffix on blur when manual mode
        nameInp.addEventListener('blur', () => {
            if (toggle.checked) return;
            const suffix = getUserSuffix();
            if (!suffix) return;
            let val = nameInp.value.trim();
            if (val === '') return;
            if (val.endsWith(suffix)) return;
            nameInp.value = val + suffix;
        });

        toggle.addEventListener('change', updateState);
        updateState();

        // Refresh every minute to keep timestamp current
        setInterval(() => { if (toggle.checked) refreshAutoName(); }, 60_000);
    }

    // ── Duration Presets ──────────────────────────────────────────────────────

    function initDurationPresets() {
        const periodInp = $('#mp-period');
        const label     = $('#mp-duration-label');

        function updateLabel() {
            if (label && periodInp) label.textContent = formatSeconds(parseInt(periodInp.value, 10) || 3600);
        }

        $$('.mp-preset').forEach(btn => {
            btn.addEventListener('click', () => {
                if (periodInp) periodInp.value = btn.dataset.seconds;
                updateLabel();
            });
        });

        periodInp?.addEventListener('input', updateLabel);
        updateLabel();
    }

    // ── Collapsible cards ─────────────────────────────────────────────────────

    function initCollapsibles() {
        $$('.mp-card-toggle').forEach(toggle => {
            const targetId = toggle.dataset.target;
            const body     = targetId ? document.getElementById(targetId) : null;
            const icon     = $('.mp-collapse-icon', toggle);

            toggle.addEventListener('click', () => {
                const isHidden = body.hidden;
                body.hidden = !isHidden;
                if (icon) icon.textContent = isHidden ? '‹' : '›';
                toggle.closest('.mp-card-collapsible')?.classList.toggle('mp-card-open', isHidden);
            });
        });
    }

    // ── Templates Panel ───────────────────────────────────────────────────────

    async function loadTemplates() {
        const body = $('#mp-templates-list-body');
        if (!body) return;

        try {
            const data = await apiFetch('maintenance.plus.templates.list');
            const templates = data.templates || [];

            if (!templates.length) {
                body.innerHTML = '<p class="mp-no-templates">No saved templates.</p>';
                return;
            }

            body.innerHTML = templates.map(t => `
                <div class="mp-template-item" data-id="${t.id}">
                    <div class="mp-tpl-label">${_esc(t.label)}</div>
                    ${t.description ? `<div class="mp-tpl-desc">${_esc(t.description)}</div>` : ''}
                    <div class="mp-tpl-tags">${(t.tags || []).map(tag =>
                        `<span class="mp-mini-badge">${_esc(tag.name)}${tag.value ? '=' + _esc(tag.value) : ''}</span>`
                    ).join('')}</div>
                    <div class="mp-tpl-actions">
                        <button class="mp-btn mp-btn-xs mp-tpl-apply" data-tpl='${JSON.stringify(t)}'>Apply</button>
                        <button class="mp-btn mp-btn-xs mp-btn-ghost mp-tpl-delete" data-id="${t.id}">×</button>
                    </div>
                </div>
            `).join('');

            // Apply template
            $$('.mp-tpl-apply', body).forEach(btn => {
                btn.addEventListener('click', () => {
                    try {
                        const tpl = JSON.parse(btn.dataset.tpl);
                        _applyTemplate(tpl);
                    } catch {}
                });
            });

            // Delete template
            $$('.mp-tpl-delete', body).forEach(btn => {
                btn.addEventListener('click', async () => {
                    if (!confirm('Delete this template?')) return;
                    await apiFetch('maintenance.plus.templates.delete', { id: btn.dataset.id }, 'POST');
                    loadTemplates();
                });
            });
        } catch {
            body.innerHTML = '<p class="mp-template-error">Failed to load templates.</p>';
        }
    }

    function _applyTemplate(tpl) {
        // Clear existing tags
        $$('.mp-tag-chip', document).forEach(c => c.remove());

        // Apply template tags via TagSelector
        (tpl.tags || []).forEach(tag => {
            window._mpTagSelector?._addTag(tag.name, tag.value || '', tag.operator || 0);
        });

        // Set period
        const periodInp = $('#mp-period');
        if (periodInp && tpl.period) {
            periodInp.value = tpl.period;
            $('#mp-duration-label').textContent = formatSeconds(tpl.period);
        }

        showToast(`Template "${tpl.label}" applied.`);
    }

    function _esc(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // ── Save Template Modal ───────────────────────────────────────────────────

    function initSaveTemplate() {
        const btn   = $('#mp-save-template');
        const modal = $('#mp-template-modal');
        if (!btn || !modal) return;

        btn.addEventListener('click', () => { modal.hidden = false; });

        $$('.mp-modal-close', modal).forEach(b => {
            b.addEventListener('click', () => { modal.hidden = true; });
        });

        modal.querySelector('.mp-modal-backdrop')?.addEventListener('click', () => { modal.hidden = true; });

        $('#mp-tpl-save-confirm')?.addEventListener('click', async () => {
            const label = $('#mp-tpl-label')?.value.trim();
            if (!label) { showToast('Template name is required.', 'error'); return; }

            const tags = window._mpTagSelector?.tags ?? [];
            const period = parseInt($('#mp-period')?.value, 10) || 3600;

            try {
                await apiFetch('maintenance.plus.templates.save', {
                    label,
                    description: $('#mp-tpl-desc')?.value ?? '',
                    tags: JSON.stringify(tags),
                    period,
                }, 'POST');

                modal.hidden = true;
                showToast('Template saved!');
                loadTemplates();
            } catch {
                showToast('Failed to save template.', 'error');
            }
        });
    }

    // ── List Page: delete & bulk select ──────────────────────────────────────

    function initListPage() {
        const selectAll  = $('#mp-select-all');
        const bulkDelete = $('#mp-bulk-delete');

        if (!selectAll) return;

        function updateBulkBtn() {
            const checked = $$('.mp-row-check:checked');
            if (bulkDelete) {
                bulkDelete.disabled = checked.length === 0;
                bulkDelete.textContent = checked.length > 0
                    ? `Delete selected (${checked.length})`
                    : 'Delete selected';
            }
        }

        selectAll.addEventListener('change', () => {
            $$('.mp-row-check').forEach(c => { c.checked = selectAll.checked; });
            updateBulkBtn();
        });

        document.addEventListener('change', e => {
            if (e.target.matches('.mp-row-check')) updateBulkBtn();
        });

        // Single row delete
        document.addEventListener('click', e => {
            const btn = e.target.closest('.mp-action-delete');
            if (!btn) return;
            _confirmDelete([btn.dataset.id], btn.dataset.name);
        });

        // Bulk delete
        bulkDelete?.addEventListener('click', () => {
            const ids = $$('.mp-row-check:checked').map(c => c.value);
            _confirmDelete(ids, `${ids.length} maintenance(s)`);
        });
    }

    function _confirmDelete(ids, label) {
        const modal = $('#mp-delete-modal');
        const msg   = $('#mp-del-message');
        const conf  = $('#mp-del-confirm');
        if (!modal) return;

        if (msg) msg.textContent = `Delete "${label}"? This action cannot be undone.`;
        modal.hidden = false;

        const handler = async () => {
            conf.removeEventListener('click', handler);
            modal.hidden = true;

            try {
                const data = await apiFetch('maintenance.plus.delete', { maintenanceids: ids }, 'POST');
                if (data.success) {
                    showToast(data.success);
                    ids.forEach(id => document.querySelector(`tr[data-id="${id}"]`)?.remove());
                } else {
                    showToast(data.error || 'Delete failed.', 'error');
                }
            } catch {
                showToast('Request failed.', 'error');
            }
        };

        conf.addEventListener('click', handler);
        $$('.mp-modal-close', modal).forEach(b => b.addEventListener('click', () => {
            modal.hidden = true;
            conf.removeEventListener('click', handler);
        }, { once: true }));
    }

    // ── Form Validation ───────────────────────────────────────────────────────

    function initFormValidation() {
        const form   = $('#mp-main-form');
        const since  = $('#mp-active-since');
        const till   = $('#mp-active-till');
        if (!form) return;

        form.addEventListener('submit', e => {
            let valid = true;

            if (since && till) {
                const a = new Date(since.value);
                const b = new Date(till.value);
                if (a >= b) {
                    since.classList.add('mp-input-error');
                    till.classList.add('mp-input-error');
                    showToast('"Active since" must be before "Active till".', 'error');
                    valid = false;
                } else {
                    since.classList.remove('mp-input-error');
                    till.classList.remove('mp-input-error');
                }
            }

            const nameInp = $('#mp-name');
            const autoToggle = $('#mp-auto-name');
            if (nameInp && !autoToggle?.checked && !nameInp.value.trim()) {
                nameInp.classList.add('mp-input-error');
                showToast('Maintenance name is required.', 'error');
                valid = false;
            }

            if (!valid) e.preventDefault();
        });
    }

    // ── Refresh Templates button ──────────────────────────────────────────────

    function initRefreshTemplates() {
        $('#mp-refresh-templates')?.addEventListener('click', loadTemplates);
    }

    // ── Bootstrap ─────────────────────────────────────────────────────────────

    function init() {
        // Set user name on body for auto-name generation
        const userMeta = document.querySelector('meta[name="mp-user"]');
        if (userMeta) document.body.dataset.userName = userMeta.content;

        const tagBuilderEl = $('#mp-tag-builder');
        if (tagBuilderEl) {
            window._mpTagSelector = new TagSelector(tagBuilderEl);
        }

        initAutoName();
        initDurationPresets();
        initCollapsibles();
        initSaveTemplate();
        initFormValidation();
        initListPage();
        loadTemplates();
        initRefreshTemplates();
    }

    document.readyState === 'loading'
        ? document.addEventListener('DOMContentLoaded', init)
        : init();

    return { showToast, HostPreview };
})();
