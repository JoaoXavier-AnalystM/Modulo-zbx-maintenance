# Maintenance Plus — Módulo Zabbix 7.4

Gerenciamento moderno de manutenções com seleção de hosts por tags, templates, calendário e exportação CSV. Foco em operadores NOC/SRE.

## Funcionalidades

- **Seleção de hosts por tags** — filtro dinâmico de hosts via tags (AND/OR) com preview ao vivo
- **Auto-nome com sufixo do criador** — nomes de manutenção recebem "criado por USUÁRIO" automaticamente
- **Templates** — salve e reutilize configurações de tags e período
- **Calendário** — mini-calendário interativo com marcação das manutenções
- **Dashboard** — contagem de ativas, futuras, expiradas e total de hosts afetados
- **Exportação CSV** — exportação completa com um clique
- **Criador, duração e tags na lista** — visão rápida para o operador
- **Registro de auditoria** — ações de criar/editar/excluir rastreadas por usuário
- **Modo escuro** — detecta automaticamente o tema do Zabbix

## Requisitos

| Componente | Versão |
|------------|--------|
| Zabbix     | 7.4+   |
| PHP        | 8.1+   |

## Instalação

```bash
# 1. Copiar módulo (pasta deve ter o mesmo nome do "id" no manifest)
cp -r zbx-manutencao-v1 /usr/share/zabbix/ui/modules/maintenance_plus

# 2. Permissões
chown -R www-data:www-data /usr/share/zabbix/ui/modules/maintenance_plus
chmod -R 755 /usr/share/zabbix/ui/modules/maintenance_plus

# 3. Reiniciar PHP-FPM
systemctl restart php8.3-fpm
```

No Zabbix: **Administration → General → Modules → Scan directory → Enable**.

Acesso em **Monitoring → Maintenance Plus** ou `zabbix.php?action=maintenance.plus.dashboard`.

## Estrutura

```
maintenance_plus/
├── manifest.json
├── Module.php
├── actions/           # 12 controladores
├── views/             # 3 templates (dashboard, lista, formulário)
├── includes/          # 3 serviços (API, templates, auditoria)
├── assets/
│   ├── css/           # maintenance-plus.css
│   └── js/            # maintenance-plus-app.js, calendar-view.js
└── locales/           # en_US
```

## Permissões

Qualquer usuário autenticado (User, Admin, Super Admin) tem acesso total a todas as ações do módulo.

## Endpoints API (AJAX interno)

| Ação                                | Descrição                       |
|-------------------------------------|---------------------------------|
| `maintenance.plus.api.tags`         | Sugestões de tags (autocomplete)|
| `maintenance.plus.api.hosts`        | Busca de hosts                  |
| `maintenance.plus.api.preview`      | Preview de hosts por tags       |
| `maintenance.plus.templates.list`   | Listar templates do usuário     |
| `maintenance.plus.templates.save`   | Salvar/atualizar template       |
| `maintenance.plus.templates.delete` | Excluir template                |
| `maintenance.plus.delete`           | Excluir manutenção(ões)         |
| `maintenance.plus.export`           | Exportar CSV                    |

## Segurança

- Tokens CSRF em todos os formulários POST
- Output escapa HTML (`htmlspecialchars` / ENT_QUOTES)
- Sem SQL direto — todos os dados via API do Zabbix
- DOM construído com valores escapados no JS
