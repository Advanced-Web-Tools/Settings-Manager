<?php

use installer\interfaces\package\actions\IPostUpdate;
use SettingsManager\migrations\CreateSettingsChangeLog;

final class SettingsManagerUpdate implements IPostUpdate
{
    public function postUpdate(int $packageId, string $packageName): void
    {
        CreateSettingsChangeLog::up($packageId);
    }
}
