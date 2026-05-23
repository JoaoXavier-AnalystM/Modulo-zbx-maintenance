# Maintenance Plus — Registro de Alterações

## 2026-05-21 — Refatoração de Tema e Integração Visual

### Análise Comparativa

Capturas de referência analisadas em `bugs/`:
- `reader.png` — menu lateral e página principal integrada ao Zabbix
- `telainicial.png` — dashboard com cards, calendário e listas
- `createmaintenance.png` — formulário de criação
- `listmaintenance.png` — listagem de manutenções

### Disparidades Encontradas

| Problema | Detalhe |
|----------|---------|
| Isolamento de tema | CSS definia tokens `:root` próprios que substituíam o fundo, fonte e cores do Zabbix |
| Fundo da página | `.mp-page` aplicava `background: #f4f5f7` sobrepondo o chrome nativo do Zabbix |
| Fonte customizada | `font-family` com Segoe UI substituía a stack de fontes do Zabbix |
| Cor primária divergente | Azul `#2563eb` (Tailwind) em vez do azul Zabbix 7.4 (`#1c6ab1`) |
| Dark mode frágil | Seletor `body.dark-theme` sem fallback para `[data-theme="dark"]` (padrão Zabbix 7.4) |

### Alterações Realizadas

**Arquivo:** `assets/css/maintenance-plus.css`

| Token/Tag | Antes | Depois |
|-----------|-------|--------|
| `.mp-page` | `background: var(--mp-bg); padding: 24px; min-height: 100vh; font-family: var(--mp-font); box-sizing: border-box;` | Apenas `color: var(--mp-text);` |
| `--mp-primary` | `#2563eb` | `#1c6ab1` (Zabbix 7.4 blue) |
| `--mp-primary-hover` | `#1d4ed8` | `#165a9e` |
| `--mp-primary-light` | `#dbeafe` | `#e8f0f8` |
| `--mp-border-focus` | `#4a90d9` | `#1c6ab1` |
| `--mp-text-link` | `#2563eb` | `#1c6ab1` |
| `--mp-bg` | `#f4f5f7` | **Removido** |
| `--mp-font` | Segoe UI stack | **Removido** |
| Dark mode | `body.dark-theme, [data-theme="dark"]` | `[data-theme="dark"]` principal, `body.dark-theme` fallback |

### Verificações

- **Module.php** — menu "Maintenance Plus" em Monitoring conforme `reader.png`. Sem alterações necessárias
- **Views** (dashboard, list, form) — usam apenas classes CSS, sem estilos inline. Confirmadas OK
- **Controllers** (12), **Services** (3), **JS** (2), **Manifest** — sem alterações necessárias

### Resultado

Módulo agora herda o chrome nativo do Zabbix 7.4:
- Fundo da página → herdado do Zabbix
- Fonte → herdada do Zabbix
- Dark mode → detectado via `[data-theme="dark"]` (padrão 7.4)
- Cores de destaque → alinhadas ao azul Zabbix (`#1c6ab1`)
- Componentes internos (cards, tabelas, tags, preview, calendário) mantêm identidade visual funcional integrada ao tema

 Amanhã é só abrir o Claude Code no diretório e dizer "Continuar evolução do módulo Maintenance Plus". O sistema carrega tudo automaticamente.

## 2026-05-21 — Bug Fixes: CSS, Navigation, JS

### CSS — Missing class rules added

| Classe | Onde usada | Adicionado |
|--------|-----------|------------|
| `.mp-alert-success` | `form.php:49` | Estilo verde com `--mp-success-light` |
| `.mp-field-row` | `form.php:77` | `flex-direction: row` para toggle + label lado a lado |
| `.mp-field-readonly` | JS `initAutoName()` | Input com fundo muted e itálico quando readonly |
| `.mp-sidebar-card` | `form.php` sidebar | Margem zero, padding reduzido |
| `.mp-template-error` | JS `loadTemplates()` catch | Cor danger, texto centrado |
| `.mp-preview-error` | JS `HostPreview._renderError()` | Cor danger, padding, centrado |
| `.mp-preview-host-icon` | JS preview render | Dimensões e alinhamento flex |
| `.mp-card-open` / `.mp-card-collapsible` | JS `initCollapsibles()` | `display: none` quando `[hidden]`, rotação do ícone |

### Navigation — Breadcrumbs and back buttons

- **List page**: adicionado breadcrumb `Maintenance Plus / Maintenances`
- **Dashboard**: adicionado breadcrumb `Maintenance Plus`
- **Form page**: adicionado botão "Back to list" no header + breadcrumb com `/` (substitui `›`)
- Separador de breadcrumb padronizado para `/` em todas as páginas

### JS — Bug fixes

- **Duration presets**: ao clicar em preset (30m, 1h, 2h...), campo `active_till` agora é recalculado automaticamente (`active_since + period`)
- **Duration input**: ao digitar valor manual no campo period, `active_till` também atualiza
- **Active since change**: ao alterar `active_since`, `active_till` recalcula
- **Collapse icon**: removida troca de texto `›`/`‹` via JS; agora usa classe CSS `mp-card-open` com `transform: rotate(90deg)` no ícone
- **Preview host icon**: emoji substituído por SVG de servidor estilizado

## 2026-05-21 — Live UX Analysis + Design/JS/PHP Improvements

### Live Analysis (browser-use)

Module acessado via `https://zabbix-hl.joaoxavier.app.br/zabbix.php?action=maintenance.plus.*`
- Dashboard: stats cards (5), calendar (May 2026), active/upcoming lists
- List: 3 maintenances, search, filter tabs, bulk delete, export
- Create form: auto-name, schedule with presets, tag builder, preview sidebar

### Bug Fix

**Auto-name "criado por" sem username**: JS `init()` lia `document.body.dataset.userName` via meta tag, mas form view nunca renderizava `<meta name="mp-user">`. Adicionado fallback: `window.MP_FORM_DATA.userName`.

### CSS — Design Refinements (frontend-design skill)

| Componente | Mudanca |
|-----------|---------|
| Stats cards | `border-left` 3px colorido + `translateY(-2px)` no hover |
| Calendario | Today com `border: 2px solid --mp-primary` |
| Tabela | `position: sticky` no thead |
| Form cards | Header com `border-bottom: 2px solid --mp-primary` |
| Breadcrumb | Links com `font-weight: 500` |
| Presets | Pill shape (`border-radius: 99px`), classe `.active-preset` |
| Empty states | Icone maior, melhor spacing |
| Form actions | `border-top` separador dos cards |

### JS — Active Preset Highlight

`initDurationPresets()` agora destaca o preset ativo com classe `.active-preset`.

### PHP — Code Quality (php-pro skill)

| Arquivo | Alteracao |
|---------|-----------|
| `MaintenancePlusEdit.php` | `== 2` para `(int)... === 2` (strict) |
| `MaintenancePlusCreate.php` | `== 2` para `=== 2` (strict) |
| `CMaintenancePlusService.php` | Metodo `normalizeFilterTags()` extraido da duplicacao |
| Ambos controllers | Usam service method, removem private duplicates |

## 2026-05-21 — Enterprise Design System Upgrade

### Design Tokens Refatorados

Sistema de espaçamento 4px base (`--mp-space-1: 4px` ate `--mp-space-10: 40px`).
Cores refinadas: soft grays (`#fcfcfd`, `#f5f6f8`) em vez de branco puro.
Dark mode: deep grays (`#1b1d28`, `#212433`) nunca preto puro.

Tipografia: `'Inter', 'Geist Sans', 'SF Pro Text', -apple-system...` com `tabular-nums` para valores numericos.

Nova paleta semantica: danger `#d93030`, success `#1a8d4a`, warning `#c08010` (mais profissionais, menos saturados).

### Skeleton Loading System

| Variant | Uso |
|---------|-----|
| `.mp-skeleton-text` | Linha de texto (12px altura) |
| `.mp-skeleton-heading` | Titulo (16px, 60% largura) |
| `.mp-skeleton-avatar` | Avatar circular (36px) |
| `.mp-skeleton-card` | Card placeholder (120px min) |
| `.mp-skeleton-row` | Linha com texto + avatar |

Integrado no JS: preview panel e templates panel mostram skeletons durante fetch.

### Empty States & Error Boundaries

**Empty states** padronizados em todas as views:
- Dashboard: active/upcoming empty states com icone SVG + titulo + descricao
- List: empty table state com icone de busca + mensagem contextual
- Form: preview empty state com icone de servidor + call-to-action

**Error boundaries**: componente `.mp-error-boundary` com borda dashed danger, titulo + descricao + botao retry.

### Micro-interacoes

- Stats cards: entrada staggered (`.mpFadeInUp` com 50ms delay entre cards)
- Table rows: entrada staggered (`.mpRowIn` com 40ms delay entre rows)
- Buttons: `translateY(-1px)` no hover primary, `scale(.92)` no click
- Cards: hover elevation (`box-shadow` transition)
- Presets: `scale(.95)` on click, active highlight pill
- Tag chips: hover com `box-shadow` e `border-color` primary

### SRE Visual Hierarchy

- Status badges: maior peso visual (bold + color + border-left accent nas rows)
- Metric values: `font-variant-numeric: tabular-nums` para alinhamento perfeito
- Datas/secundarias: fonte menor, cor muted
- Active maintenances: animacao pulse no border-left para atencao imediata