# Maintenance Plus — Zabbix 7.4 Module

Modern maintenance management with tag-based host selection, templates, calendar view, and CSV export. Built for NOC/SRE operators.

## Features

- **Tag-based host selection** — dynamic host matching via tag filters (AND/OR) with live preview
- **Auto-name with creator suffix** — maintenance names get "criado por USER" appended automatically
- **Templates** — save and reuse tag/period configurations per user
- **Calendar view** — interactive mini-calendar with maintenance overlays
- **Dashboard** — active, upcoming, expired counts plus host summary
- **CSV export** — one-click export with full maintenance details
- **Creator, duration, tags in list** — quick operator overview
- **Audit log** — create/update/delete actions tracked per user
- **Dark mode** — auto-detects Zabbix theme, no configuration

## Requirements

| Component | Version |
|-----------|---------|
| Zabbix    | 7.4+    |
| PHP       | 8.1+    |

## Installation

```bash
# 1. Copy module (folder must match manifest "id")
cp -r zbx-manutencao-v1 /usr/share/zabbix/ui/modules/maintenance_plus

# 2. Remove BOM if copying from Windows (fatal error otherwise)
find /usr/share/zabbix/ui/modules/maintenance_plus -name "*.php" -exec sed -i '1s/^\xEF\xBB\xBF//' {} \;

# 3. Set permissions
chown -R www-data:www-data /usr/share/zabbix/ui/modules/maintenance_plus
chmod -R 755 /usr/share/zabbix/ui/modules/maintenance_plus

# 4. Restart PHP-FPM
systemctl restart php8.3-fpm
```

Then in Zabbix: **Administration → General → Modules → Scan directory → Enable**.

Access via **Monitoring → Maintenance Plus** or `zabbix.php?action=maintenance.plus.dashboard`.

## Manifest v2.0 Notes

- `manifest_version` must be integer `2`, not float `2.0`
- Page actions require `"layout": "layout.htmlpage"`
- API/JSON actions use `"layout": "layout.json"`
- CSS/JS declared in `"assets"` block, not via `Module.php`

## Directory Structure

```
maintenance_plus/
├── manifest.json
├── Module.php
├── actions/           # 12 controllers
├── views/             # 3 templates (dashboard, list, form)
├── includes/          # 3 services (API, templates, audit)
├── assets/
│   ├── css/           # maintenance-plus.css
│   └── js/            # maintenance-plus-app.js, calendar-view.js
└── locales/           # en_US
```

## Permissions

Any authenticated Zabbix user (User, Admin, Super Admin) has full access to all module actions.

## API Endpoints (AJAX)

| Action                              | Layout      | Description                |
|-------------------------------------|-------------|----------------------------|
| `maintenance.plus.api.tags`         | layout.json | Tag autocomplete           |
| `maintenance.plus.api.hosts`        | layout.json | Host search                |
| `maintenance.plus.api.preview`      | layout.json | Live host preview by tags  |
| `maintenance.plus.templates.list`   | layout.json | List user templates        |
| `maintenance.plus.templates.save`   | layout.json | Save/update template       |
| `maintenance.plus.templates.delete` | layout.json | Delete template            |
| `maintenance.plus.delete`           | layout.json | Delete maintenance(s)      |
| `maintenance.plus.export`           | layout.json | Export CSV                 |

## Security

- CSRF tokens on all POST forms
- Output HTML-escaped (`htmlspecialchars` / ENT_QUOTES)
- No direct SQL — all data via Zabbix API
- JS DOM construction uses escaped values

## License

MIT
