<?php

namespace SettingsManager\service;

use admin\Admin;
use database\DatabaseManager;
use database\interface\ITransactionalProvider;
use database\provider\DatabaseProvider;
use PDO;
use setting\model\SettingModel;

final class SettingsChangeLog
{
    private readonly DatabaseManager $database;

    public function __construct(
        private readonly ITransactionalProvider $provider = new DatabaseProvider(),
        ?DatabaseManager $database = null,
    ) {
        $this->database = $database ?? new DatabaseManager($provider);
    }

    /** @param array<int, array{SettingModel, string}> $updates */
    public function save(array $updates, Admin $account): void
    {
        $accountId = $account->getKey();
        if (!$account->checkAuthentication() || !$accountId) {
            throw new \LogicException('An authenticated account is required to change settings.');
        }

        $this->database->transaction(function (DatabaseManager $database) use ($updates, $account, $accountId): void {
            $createdAt = gmdate('Y-m-d H:i:s');
            foreach ($updates as [$setting, $value]) {
                if (!$account->checkPermission($setting->required_permission_level)) {
                    throw new \LogicException('You do not have permission to change this setting.');
                }
                $current = $database->table('awt_setting')->select(['value'])->where(['id' => $setting->id])->get();
                if (!$current) {
                    throw new \RuntimeException('This setting no longer exists.');
                }
                if ((string)$current[0]['value'] === $value) {
                    continue;
                }
                // Only changes that actually update a stored value get a log entry.
                $changed = $database->table('awt_setting')
                    ->where(['id' => $setting->id])
                    ->update(['value' => $value]);
                if (!$changed) {
                    continue;
                }

                $id = $database->table('settingsmanager_loger')->insert([
                    'account_id' => $accountId,
                    'account_name' => $account->username ?? 'Account #' . $accountId,
                    'setting_id' => $setting->id,
                    'setting_name' => $setting->getName(),
                    'category' => $setting->category,
                    'required_permission_level' => $setting->required_permission_level,
                    'created_at' => $createdAt,
                ])->executeInsert();
                if (!$id) {
                    throw new \RuntimeException('Could not record the settings change.');
                }
            }
        });
    }

    /** @return list<array<string, mixed>> */
    public function latest(int $permissionLevel): array
    {
        return $this->entries($permissionLevel, 5, 0);
    }

    /** Return 25 entries plus one to detect whether another page exists.
     * @return list<array<string, mixed>>
     */
    public function history(int $permissionLevel, int $page = 1): array
    {
        if ($page < 1 || $page > 1000000) {
            throw new \InvalidArgumentException('Invalid log page.');
        }
        return $this->entries($permissionLevel, 26, ($page - 1) * 25);
    }

    /** @return list<array<string, mixed>> */
    private function entries(int $permissionLevel, int $limit, int $offset): array
    {
        // Query directly so the widget always reflects the latest committed changes.
        $statement = $this->provider->execute(
            'SELECT log.account_name, log.setting_name, log.category, log.created_at
             FROM settingsmanager_loger AS log
             LEFT JOIN awt_setting AS setting ON setting.id = log.setting_id
             WHERE log.required_permission_level >= :level
               AND (setting.id IS NULL OR setting.required_permission_level >= :current_level)
             ORDER BY log.created_at DESC, log.id DESC
             LIMIT :limit OFFSET :offset',
            [':level' => $permissionLevel, ':current_level' => $permissionLevel, ':limit' => $limit, ':offset' => $offset],
        );
        try {
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            $statement->closeCursor();
        }
    }
}
