<?php

use router\Router;
use runtime\api\RuntimeControllerAPI;
use runtime\api\RuntimeRouterAPI;

final class SettingsManagerRouter extends RuntimeRouterAPI
{
    private RuntimeControllerAPI $SettingsManagerControllerAPI;
    private Router $router;

    public function setup(): void
    {
        $this->SettingsManagerControllerAPI = $this->getPassable("SettingsManager", "SettingsManagerControllerAPI");
        $this->router = new Router("/dashboard/settings", "index", $this->SettingsManagerControllerAPI->getController("SettingsManagerController"));
    }

    public function main(): void
    {
        $save = new Router('/dashboard/settings/save', 'save', clone $this->SettingsManagerControllerAPI->getController('SettingsManagerController'));
        $this->addRouter($save->setMethod('POST'));
        $logs = new Router('/dashboard/settings/logs', 'logs', clone $this->SettingsManagerControllerAPI->getController('SettingsManagerController'));
        $this->addRouter($logs->setMethod('GET'));
        $help = new Router('/dashboard/settings/help', 'help', clone $this->SettingsManagerControllerAPI->getController('SettingsManagerController'));
        $this->addRouter($help->setMethod('GET'));
        $this->addRouter($this->router);
    }
}
