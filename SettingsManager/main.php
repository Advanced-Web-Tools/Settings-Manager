<?php

use Dashboard\api\events\NavigationResponseEvent;
use Dashboard\api\events\WidgetRegistrationListener;
use Dashboard\api\events\WidgetRegistryEvent;
use Dashboard\api\navigation\NavigationItem;
use Dashboard\api\widget\DashboardWidget;
use Dashboard\api\widget\WidgetDefinition;
use object\ObjectFactory;
use runtime\api\RuntimeLinkerAPI;
use SettingsManager\widgets\RecentSettingsChangesWidget;

final class SettingsManager extends RuntimeLinkerAPI
{
    public function main(): void
    {
        $this->createLink("SettingsManagerControllerAPI", "/runtime/SettingsManagerControllerAPI.php");
        $this->createLink("SettingsManagerRouter", "/runtime/SettingsManagerRouter.php");

        $nav = new NavigationItem(
            "Settings",
            "/dashboard/settings",
            "/awt_packages/SettingsManager/data/icon/icon.webp"
        );

        $nav->setPermissionLevel(1);
        $nav->addChild(new NavigationItem('Logs', '/dashboard/settings/logs', permissionLevel: 1));
        $nav->addChild(new NavigationItem('Help', '/dashboard/settings/help', permissionLevel: 1));

        $this->eventDispatcher->addListener(NavigationResponseEvent::NAME, new NavigationResponseEvent($nav));

        $definition = new WidgetDefinition(
            key: 'SettingsManager/recent-changes',
            packageName: 'SettingsManager',
            label: 'Recent settings changes',
            description: 'The latest five setting changes, showing the account and date.',
            factory: (new ObjectFactory())->setClassName(RecentSettingsChangesWidget::class)->setType(DashboardWidget::class),
            defaultVisible: true,
            defaultPosition: 30,
            size: DashboardWidget::SIZE_MEDIUM,
            permissionLevel: 1,
        );
        $this->eventDispatcher->addListener(WidgetRegistryEvent::NAME, new WidgetRegistrationListener($definition));

    }
}
