<?php

use Dashboard\api\page\DashboardControllerBase;
use admin\Admin;
use redirect\Redirect;
use router\security\Csrf;
use setting\model\SettingModel;
use setting\Settings;
use SettingsManager\service\SettingsChangeLog;
use view\View;

class SettingsManagerController extends DashboardControllerBase
{
    public array $settings;
    public ?string $active_tab;
    public ?string $error;
    public bool $saved;
    public string $token;
    public array $logEntries;
    public int $logPage;
    public bool $hasMoreLogs;

    public function __construct()
    {
        $this->setPermissionLevel(1);
        $this->checkAuth();
    }

    /**
     * @throws DOMException
     */
    public function index(array|string $params): View
    {
        return $this->dashboard($_GET['tab'] ?? null);
    }

    public function help(array|string $params): View
    {
        $this->viewName = 'help';
        return $this->view([
            'navigation' => $this->buildNavigation(),
            'name' => 'Settings help',
        ]);
    }

    public function logs(array|string $params): View
    {
        $this->viewName = 'logs';
        $account = new Admin();
        $page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 1000000],
        ]) ?: 1;
        $entries = (new SettingsChangeLog())->history((int)$account->permission_level, $page);

        return $this->view([
            'navigation' => $this->buildNavigation(),
            'name' => 'Logs',
            'logEntries' => array_slice($entries, 0, 25),
            'logPage' => $page,
            'hasMoreLogs' => count($entries) > 25,
        ]);
    }

    public function save(array|string $params): View|Redirect
    {
        // The router requires POST and validates the CSRF token.
        $category = $_POST['tab'] ?? null;
        $values = $_POST['settings'] ?? [];
        $models = (new Settings())->fetchSettings()->getSettings();
        $admin = new Admin();
        $updates = [];

        try {
            if (!is_string($category) || !is_array($values)) {
                throw new InvalidArgumentException('Choose a settings category and submit its values.');
            }

            $categoryExists = false;
            foreach ($models as $model) {
                if (!$model instanceof SettingModel || $model->category !== $category) {
                    continue;
                }
                $categoryExists = true;
                if (!$admin->checkPermission($model->required_permission_level)) {
                    if (array_key_exists($model->id, $values)) {
                        throw new InvalidArgumentException('You do not have permission to change ' . $model->getName() . '.');
                    }
                    continue;
                }

                $value = $values[$model->id] ?? null;
                if (!is_string($value)) {
                    throw new InvalidArgumentException('Provide a value for ' . $model->getName() . '.');
                }
                if ($model->value_type === 'boolean' && !in_array($value, ['true', 'false'], true)) {
                    throw new InvalidArgumentException($model->getName() . ' must be enabled or disabled.');
                }
                if ($model->value_type === 'number' && (!is_numeric($value) || !is_finite((float)$value))) {
                    throw new InvalidArgumentException($model->getName() . ' must be a valid number.');
                }
                $updates[$model->id] = [$model, $value];
            }

            if (!$categoryExists || array_diff_key($values, $updates)) {
                throw new InvalidArgumentException('The submitted settings do not belong to this category.');
            }
        } catch (InvalidArgumentException $exception) {
            http_response_code(422);
            return $this->dashboard($category, $exception->getMessage(), is_array($values) ? $values : []);
        }

        // Validate the entire category before persisting any changes.
        (new SettingsChangeLog())->save($updates, $admin);

        return (new Redirect())->redirect('/dashboard/settings?tab=' . rawurlencode($category) . '&saved=1');
    }

    private function dashboard(mixed $category, ?string $error = null, array $submitted = []): View
    {
        $this->viewName = 'index';
        $admin = new Admin();
        $params = [
            'navigation' => $this->buildNavigation(),
            'name' => 'Settings',
            'settings' => [],
            'error' => $error,
            'saved' => $error === null && ($_GET['saved'] ?? null) === '1',
            'token' => (new Csrf())->token(),
        ];

        foreach ((new Settings())->fetchSettings()->getSettings() as $settingModel) {
            if (!($settingModel instanceof SettingModel)) {
                continue;
            }
            $editable = $admin->checkPermission($settingModel->required_permission_level);
            $value = $editable && isset($submitted[$settingModel->id]) && is_string($submitted[$settingModel->id])
                ? $submitted[$settingModel->id] : $settingModel->getValue();
            $params['settings'][$settingModel->category][$settingModel->getName()] = [
                "id" => $settingModel->id,
                "type" => $settingModel->value_type,
                "value" => $settingModel->value_type === 'boolean'
                    ? filter_var($value, FILTER_VALIDATE_BOOLEAN) : $value,
                "editable" => $editable,
            ];
        }

        $params['active_tab'] = is_string($category) && array_key_exists($category, $params['settings'])
            ? $category : array_key_first($params['settings']);

        return $this->view($params);
    }

    public function view(array $data = []): View
    {
        // Compile Blade before parsing its directives as HTML, as ThemeEngine does.
        $this->bundle = $data;
        $this->createDocument();
        return $this;
    }
}
