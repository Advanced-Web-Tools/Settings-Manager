<?php

use controller\Controller;
use object\ObjectFactory;
use runtime\api\RuntimeControllerAPI;
use runtime\enums\ERuntimeFlags;

final class SettingsManagerControllerAPI extends RuntimeControllerAPI
{
    private ObjectFactory $factory;

    /**
     * @throws Exception
     */

    public function environmentSetup(): void
    {
        parent::environmentSetup();
        $this->setRuntimeFlag(ERuntimeFlags::EventDispatcher);
    }

    public function setup(): void
    {
        $props = [
            "controllerName" => "SettingsManagerController",
            "localAssetPath" => "awt_packages/SettingsManager/views/assets",
            "eventDispatcher" => $this->eventDispatcher,
        ];

        $methods = [
            "setRootPath",
            "setViewPath",
        ];

        $methodArgs = [
            "setRootPath" =>
                [
                    $this->runtimePath,
                ],
            "setViewPath" => [
                "/views/"
            ]
        ];

        $objectFactory = new ObjectFactory();
        $objectFactory->setType(Controller::class);
        $objectFactory->setClassPath($this->runtimePath . "/controllers/SettingsManagerController.php");

        $objectFactory->setProperties($props);
        $objectFactory->setMethodCalls($methods);
        $objectFactory->setMethodArgs($methodArgs);


        $this->factory = $objectFactory;
    }

    /**
     * @throws Exception
     */
    public function main(): void
    {
        $this->addController($this->factory, "SettingsManagerController");
    }
}
