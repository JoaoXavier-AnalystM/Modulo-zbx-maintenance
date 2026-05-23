# Projeto: Módulo Zabbix 7.4

## Stack
- Zabbix 7.4
- PHP 8.3+ com strict types obrigatório
- Frontend: componentes nativos Zabbix UI + assets próprios

## Skills ativas — carregar sempre que relevante

| Tarefa | Skill | Caminho |
|--------|-------|---------|
| PHP, classes, DTOs, services, testes | php-pro | `.claude/skills/php-pro/SKILL.md` |
| UI, componentes, CSS, JS, layout | frontend-design | `.claude/skills/frontend-design/SKILL.md` |
| Automação e browser (browser-use) | browser-use | `.claude/skills/browser-use/SKILL.md` |
| Automação e browser (agent-browser) | agent-browser | `.claude/skills/agent-browser/SKILL.md` |

> Antes de implementar qualquer coisa, carregar a skill correspondente e seguir suas diretrizes.

## Estrutura do projeto

```
.
├── .claude/skills/          ← skills do Claude Code (php-pro, frontend-design, browser-use, agent-browser)
├── actions/                 ← controllers Zabbix: uma classe por action
├── assets/css/              ← estilos do módulo
├── assets/js/               ← scripts do módulo
├── bugs/                    ← registro de bugs e pendências
├── includes/                ← helpers, traits, DTOs, value objects
├── views/                   ← templates de renderização Zabbix
├── manifest.json
└── Module.php
```

## Regras PHP — skill php-pro aplicada ao Zabbix

Seguir todas as regras do php-pro, adaptadas ao contexto Zabbix:

- `declare(strict_types=1)` em todo arquivo
- Typed properties, readonly onde imutável, enums para status/constantes
- DTOs readonly em `includes/` para dados entre action e view
- Services em `includes/` — lógica de negócio nunca em actions nem views
- Nunca `mixed`, nunca `var_dump` em produção
- PHPDoc em lógica complexa
- PHPStan level 9 antes de considerar pronto
- Injeção de dependência via construtor — sem global state

### Padrão de action Zabbix (equivale ao controller do php-pro)

```php
<?php declare(strict_types=1);

final class MinhaAction extends CController {

    protected function checkInput(): bool {
        $fields = [
            'hostid' => 'required|db hosts.hostid',
        ];
        $ret = $this->validateInput($fields);
        if (!$ret) {
            $this->setResponse(new CControllerResponseFatal());
        }
        return $ret;
    }

    protected function checkPermissions(): bool {
        return CWebUser::checkAccess(CRoleHelper::UI_MONITORING_HOSTS);
    }

    protected function doAction(): void {
        // lógica via service em includes/
        $service = new HostService();
        $data = ['hosts' => $service->getMonitored()];
        $this->setResponse(new CControllerResponseData($data));
    }
}
```

### Padrão de DTO (includes/)

```php
<?php declare(strict_types=1);

final readonly class HostDTO {
    public function __construct(
        public int    $hostid,
        public string $name,
        public int    $status,
    ) {}

    public static function fromArray(array $data): self {
        return new self(
            hostid: (int) $data['hostid'],
            name:   $data['name'],
            status: (int) $data['status'],
        );
    }
}
```

### API interna Zabbix — referência rápida

```php
// Hosts
API::Host()->get(['output' => ['hostid', 'name'], 'filter' => ['status' => HOST_STATUS_MONITORED]]);

// Triggers
API::Trigger()->get(['output' => ['triggerid', 'description', 'priority'], 'hostids' => [$hostid]]);

// Query direta
$result = DBselect('SELECT h.hostid, h.name FROM hosts h WHERE h.status='.HOST_STATUS_MONITORED);
while ($row = DBfetch($result)) { /* ... */ }

// Permissão
CWebUser::checkAccess(CRoleHelper::UI_MONITORING_HOSTS);
```

## Regras Frontend — skill frontend-design aplicada ao Zabbix

Seguir todas as diretrizes do frontend-design, dentro das restrições do Zabbix:

- Usar componentes nativos Zabbix (CForm, CButtonMenu, CTableInfo, CCol, CRow) como base
- CSS customizado em `assets/css/` — variáveis CSS para consistência, nunca valores hardcoded
- JS em `assets/js/` — registrar via `$this->addJavaScriptFile()` no Module.php
- Tipografia e cor coerentes com a UI do Zabbix, mas com identidade própria onde possível
- Evitar HTML puro nas views — preferir componentes Zabbix + CSS próprio por cima
- Animações e micro-interações em CSS sempre que possível; JS só quando necessário

## O que NÃO fazer

- Não usar `$_GET` / `$_POST` direto — sempre via `getRequest()` ou validador
- Não colocar lógica de negócio em views ou nas actions diretamente — vai em `includes/`
- Não hardcodar IDs de hosts, grupos ou triggers
- Não usar tipos genéricos (`mixed`, `array` sem shape) — tipar sempre
- Não fazer `echo` direto em views Zabbix
- Não commitar sem rodar `php -l` em todos os arquivos alterados

## Fluxo de entrega (baseado no php-pro)

Para cada feature, entregar nesta ordem:
1. DTO / value object em `includes/`
2. Service/helper em `includes/`
3. Action em `actions/`
4. View em `views/`
5. Assets em `assets/` se necessário
6. Registrar no `Module.php` se necessário
