<?php

namespace SettingsManager\migrations;

use database\creator\ColumnCreator as C;
use database\creator\TableWizard;

final class CreateSettingsChangeLog
{
    public static function up(int $packageId, ?TableWizard $wizard = null): void
    {
        $wizard ??= TableWizard::create($packageId);
        if ($wizard->tableExists('settingsmanager_loger')) {
            return;
        }

        $created = $wizard
            ->addColumn(C::INT('id')->primary()->autoIncrement())
            ->addColumn(C::INT('account_id')->nullable()->index()->foreignKey('awt_admin', 'id', onDelete: 'SET NULL'))
            ->addColumn(C::VARCHAR('account_name', '255'))
            ->addColumn(C::INT('setting_id')->nullable()->index()->foreignKey('awt_setting', 'id', onDelete: 'SET NULL'))
            ->addColumn(C::VARCHAR('setting_name', '255'))
            ->addColumn(C::VARCHAR('category', '255'))
            ->addColumn(C::INT('required_permission_level'))
            ->addColumn(C::DATETIME('created_at')->index())
            ->createTable('settingsmanager_loger');

        if (!$created) {
            throw new \RuntimeException('Could not create the settings change log table.');
        }
    }
}
