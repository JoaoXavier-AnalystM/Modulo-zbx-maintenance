<?php

declare(strict_types=1);

namespace Modules\MaintenancePlus\Includes;

use CProfile;
use CWebUser;

/**
 * Stores user maintenance templates in Zabbix profile table.
 * No schema changes required — uses the built-in profiles mechanism.
 */
class CTemplateService {

    private const PROFILE_KEY  = 'web.maintenance_plus.templates';
    private const MAX_TEMPLATES = 50;

    public function getAll(): array {
        return $this->load();
    }

    public function getById(string $id): ?array {
        foreach ($this->load() as $tpl) {
            if (($tpl['id'] ?? '') === $id) {
                return $tpl;
            }
        }

        return null;
    }

    /**
     * Creates or updates a template. Returns the saved template with its ID.
     *
     * @throws \OverflowException  when MAX_TEMPLATES is reached on create
     * @throws \InvalidArgumentException when updating a non-existent ID
     */
    public function save(array $template): array {
        $templates = $this->load();

        if (empty($template['id'])) {
            if (count($templates) >= self::MAX_TEMPLATES) {
                throw new \OverflowException(
                    sprintf('Maximum of %d templates reached.', self::MAX_TEMPLATES)
                );
            }

            $template['id']         = bin2hex(random_bytes(8));
            $template['created_at'] = time();
            $template['created_by'] = CWebUser::$data['alias'] ?? '';
            $templates[]            = $template;
        } else {
            $found = false;
            foreach ($templates as &$tpl) {
                if ($tpl['id'] === $template['id']) {
                    $template['updated_at'] = time();
                    $tpl     = $template;
                    $found   = true;
                    break;
                }
            }
            unset($tpl);

            if (!$found) {
                throw new \InvalidArgumentException('Template not found: ' . $template['id']);
            }
        }

        $this->persist($templates);

        return $template;
    }

    public function delete(string $id): bool {
        $templates = $this->load();
        $filtered  = array_values(array_filter($templates, static fn($t) => $t['id'] !== $id));

        if (count($filtered) === count($templates)) {
            return false;
        }

        $this->persist($filtered);

        return true;
    }

    private function load(): array {
        $raw = CProfile::get(self::PROFILE_KEY);

        if ($raw === null || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function persist(array $templates): void {
        CProfile::update(
            self::PROFILE_KEY,
            json_encode($templates, JSON_UNESCAPED_UNICODE),
            PROFILE_TYPE_STR
        );
    }
}
