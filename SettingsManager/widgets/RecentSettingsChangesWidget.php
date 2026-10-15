<?php

namespace SettingsManager\widgets;

use admin\Admin;
use Dashboard\api\widget\DashboardWidget;
use SettingsManager\service\SettingsChangeLog;
use URPC\api\URPCBlock;
use URPC\api\URPCBlockDescriptor;

final class RecentSettingsChangesWidget extends DashboardWidget
{
    public function __construct(private readonly SettingsChangeLog $log = new SettingsChangeLog())
    {
        parent::__construct();
    }

    public function widget(): DashboardWidget
    {
        $account = new Admin();
        if (!$account->checkAuthentication() || !$account->checkPermission(1) || $account->permission_level === null) {
            throw new \LogicException('You do not have permission to view settings changes.');
        }

        $descriptor = new URPCBlockDescriptor('section');
        $descriptor->setSourceKey('SettingsManager/widgets/recent-changes');
        $this->setBlockDescriptor($descriptor);
        $this->setBlockAttributes(['class' => 'card min-w-0']);

        $children = [
            $this->text('h2', 'Recent settings changes', ['class' => 'card-title']),
            $this->text('p', 'The latest five changes to your settings.', ['class' => 'card-subtitle text-small mb-3']),
        ];
        $changes = $this->log->latest($account->permission_level);
        if ($changes === []) {
            $children[] = $this->text('p', 'No settings changes recorded yet.', ['class' => 'text-small text-muted mb-0']);
        } else {
            $list = new URPCBlock(new URPCBlockDescriptor('ul'));
            $list->setBlockAttributes(['class' => 'list-group', 'aria-label' => 'Settings changes, newest first']);
            foreach ($changes as $change) {
                $date = new \DateTimeImmutable($change['created_at'], new \DateTimeZone('UTC'));
                $item = new URPCBlock(new URPCBlockDescriptor('li'));
                $item->setBlockAttributes(['class' => 'd-block']);
                $item->setBlockChildren([
                    $this->text('p', $change['account_name'] . ' changed ' . $change['setting_name'] . '.', ['class' => 'text-small mb-1']),
                    $this->text('time', $date->format('j M Y, H:i \U\T\C'), [
                        'class' => 'd-block text-small text-muted',
                        'datetime' => $date->format(DATE_ATOM),
                    ]),
                ]);
                $list->addBlockChild($item);
            }
            $children[] = $list;
        }
        $children[] = $this->text('a', 'Open settings', ['class' => 'btn btn-outline-secondary btn-sm mt-3', 'href' => '/dashboard/settings']);
        $children[] = $this->text('a', 'View all logs', ['class' => 'btn btn-ghost btn-sm mt-3', 'href' => '/dashboard/settings/logs']);
        $this->setBlockChildren($children);
        return $this;
    }

    private function text(string $tag, string $content, array $attributes): URPCBlock
    {
        $block = new URPCBlock(new URPCBlockDescriptor($tag));
        $block->setBlockAttributes($attributes);
        $block->setBlockContent($content);
        return $block;
    }
}
