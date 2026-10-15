<?php

use installer\interfaces\package\actions\IPostInstall;
use SettingsManager\migrations\CreateSettingsChangeLog;

class SettingsManagerInstall implements IPostInstall
{
    public function postInstall(int $packageId, string $packageName): bool
    {
        CreateSettingsChangeLog::up($packageId);
        return true;
    }
}
