<?php

// Standalone integration check using the framework and sibling Dashboard/URPC packages.
// php tests/settings-change-log.php [path/to/awt_src/classes] [path/to/Packages]
namespace admin {
    // Simulate an authenticated account without starting the application/session runtime.
    class Admin {
        public function __construct(public int $id = 1, public string $username = 'alice', public ?int $permission_level = 1, public bool $authenticated = true) {}
        public function getKey(): ?int { return $this->id; }
        public function checkAuthentication(): bool { return $this->authenticated; }
        public function checkPermission(int $level): bool { return $this->permission_level !== null && $this->permission_level <= $level; }
    }
}

namespace {
    use admin\Admin;
    use database\creator\ColumnCreator as C;
    use database\creator\TableWizard;
    use database\creator\table\{TableRegistry, TableSchemaProvider};
    use database\DatabaseManager;
    use database\interface\ICache;
    use database\provider\DatabaseProvider;
    use database\query\QueryBuilder;
    use SettingsManager\migrations\CreateSettingsChangeLog;
    use SettingsManager\service\SettingsChangeLog;
    use SettingsManager\widgets\RecentSettingsChangesWidget;
    use setting\model\SettingModel;

    $framework = $argv[1] ?? dirname(__DIR__, 3) . '/Advanced-Web-Tools-Framework/awt_src/classes';
    $packages = $argv[2] ?? dirname(__DIR__, 2);
    $temporary = sys_get_temp_dir() . '/settings-log-' . bin2hex(random_bytes(6));
    mkdir($temporary);
    define('CONFIG', $temporary);
    file_put_contents($temporary . '/awt_db.php', "<?php const DB_TYPE = 'sqlite'; const DB_NAME = ':memory:';");
    register_shutdown_function(static function () use ($temporary): void {
        unlink($temporary . '/awt_db.php');
        rmdir($temporary);
    });
    spl_autoload_register(static function (string $class) use ($framework, $packages): void {
        $relative = str_replace('\\', '/', $class) . '.php';
        $package = explode('\\', $class)[0];
        $candidates = [$framework . '/' . $relative, $packages . '/' . $package . '/' . $relative];
        foreach ($candidates as $path) if (is_file($path)) { require_once $path; return; }
    });
    function check(bool $condition, string $message): void {
        if (!$condition) throw new \RuntimeException($message);
    }
    function fails(callable $callback): void {
        try { $callback(); } catch (\Throwable) { return; }
        throw new \RuntimeException('Expected operation to fail.');
    }
    $cache = new class implements ICache {
        public function get(string $table, string $query): array|false { return false; }
        public function set(string $table, string $query, array $result, array $conditions): void {}
        public function invalidate(string $table, array $cudConditions): void {}
        public function invalidateTable(string $table): void {}
    };
    $provider = new DatabaseProvider();
    $database = new DatabaseManager($provider, new QueryBuilder(), $cache);
    $schema = new TableSchemaProvider($provider);
    $registry = new TableRegistry($database);
    $wizard = static fn() => new TableWizard(7, $schema, $registry);
    $wizard()->headless()->addColumn(C::INT('id')->primary()->autoIncrement())
        ->addColumn(C::VARCHAR('name', '255'))->addColumn(C::INT('creator'))->createTable('awt_table');
    $wizard()->headless()->addColumn(C::INT('id')->primary()->autoIncrement())
        ->addColumn(C::INT('table_id'))->addColumn(C::TEXT('column_name'))->addColumn(C::TEXT('column_type'))
        ->createTable('awt_table_structure');
    $provider->execute('CREATE TABLE awt_admin (id INTEGER PRIMARY KEY)')->closeCursor();
    $provider->execute('INSERT INTO awt_admin (id) VALUES (1), (2)')->closeCursor();
    $provider->execute('CREATE TABLE awt_setting (id INTEGER PRIMARY KEY, value TEXT, required_permission_level INTEGER)')->closeCursor();
    $provider->execute("INSERT INTO awt_setting VALUES (1, 'Original', 1), (2, '10', 1), (3, 'private', 0)")->closeCursor();
    CreateSettingsChangeLog::up(7, $wizard());
    CreateSettingsChangeLog::up(7, $wizard());
    check($registry->columnExists('settingsmanager_loger', 'account_id'), 'Migration did not register account column.');
    check(count($database->table('awt_table')->select()->where(['name' => 'settingsmanager_loger'])->get()) === 1, 'Migration is not idempotent.');

    function setting(int $id, string $name, int $permission = 1): SettingModel {
        $model = (new \ReflectionClass(SettingModel::class))->newInstanceWithoutConstructor();
        $model->id = $id;
        $model->name = $name;
        $model->category = 'General';
        $model->required_permission_level = $permission;
        return $model;
    }
    $name = setting(1, '<Website & name>');
    $timeout = setting(2, 'Timeout');
    $restricted = setting(3, 'Restricted', 0);
    $alice = new Admin();
    $bob = new Admin(2, 'bob', 0);
    $log = new SettingsChangeLog($provider, $database);
    function countLogs(DatabaseProvider $provider): int {
        $statement = $provider->execute('SELECT COUNT(*) FROM settingsmanager_loger');
        $count = (int)$statement->fetchColumn();
        $statement->closeCursor();
        return $count;
    }
    $log->save([[$name, 'Original']], $alice);
    check(countLogs($provider) === 0, 'Unchanged setting was logged.');
    $log->save([[$name, 'Updated'], [$timeout, '20']], $alice);
    check(countLogs($provider) === 2, 'Batch did not create one entry per change.');
    $log->save([[$name, 'updated']], $bob);
    check(countLogs($provider) === 3, 'Case-only change was not logged.');
    check($log->latest(1)[0]['account_name'] === 'bob', 'Account attribution or newest-first ordering is wrong.');
    $log->save([[$name, 'updated']], $bob);
    check(countLogs($provider) === 3, 'Repeat save was logged.');
    fails(fn() => $log->save([[$restricted, 'unauthorized']], $alice));
    fails(fn() => $log->save([[$name, 'unauthenticated']], new Admin(authenticated: false)));
    check(countLogs($provider) === 3, 'Unauthorized save added history.');

    $provider->execute("CREATE TRIGGER fail_log BEFORE INSERT ON settingsmanager_loger WHEN NEW.setting_id = 2 BEGIN SELECT RAISE(ABORT, 'test failure'); END")->closeCursor();
    fails(fn() => $log->save([[$name, 'rollback'], [$timeout, '30']], $alice));
    check(countLogs($provider) === 3, 'Failed batch left partial history.');
    $rows = $database->table('awt_setting')->select()->where(['id' => 1])->get();
    check($rows[0]['value'] === 'updated', 'Failed log insertion did not roll back setting changes.');
    $rows = $database->table('awt_setting')->select()->where(['id' => 2])->get();
    check($rows[0]['value'] === '20', 'Failed batch left a partial setting update.');
    $provider->execute('DROP TRIGGER fail_log')->closeCursor();
    for ($i = 0; $i < 7; ++$i) $log->save([[$name, 'Value ' . $i]], $i % 2 ? $bob : $alice);
    $log->save([[$restricted, 'changed']], $bob);
    $recent = $log->latest(1);
    check(count($recent) === 5 && !in_array('Restricted', array_column($recent, 'setting_name'), true), 'Widget limit or permission filtering is wrong.');
    check($log->latest(0)[0]['setting_name'] === 'Restricted', 'Owner cannot see restricted changes.');
    $provider->execute('UPDATE awt_setting SET required_permission_level = 0 WHERE id = 1')->closeCursor();
    check(count($log->latest(1)) === 1, 'History ignores current setting permissions.');
    $provider->execute('UPDATE awt_setting SET required_permission_level = 1 WHERE id = 1')->closeCursor();

    $widget = (new RecentSettingsChangesWidget($log))->widget();
    $document = new \Dashboard\api\widget\WidgetDocument();
    $document->addBlock('main', $widget);
    $eventDispatcher = new \event\EventDispatcher();
    $renderer = new \URPC\service\renderer\URPC();
    $renderer->setDocument($document);
    $html = $renderer->render();
    $dom = new \DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new \DOMXPath($dom);
    check($xpath->query('//li')->length === 5, 'Widget does not display five changes.');
    check($xpath->query('//time[@datetime]')->length === 5, 'Widget is missing machine-readable dates.');
    check(str_contains($html, '&lt;Website &amp; name&gt;'), 'Widget failed to escape setting names.');
    check(str_contains($html, 'alice changed') && str_contains($html, 'bob changed'), 'Widget is missing account attribution.');

    $provider->execute('DELETE FROM awt_admin WHERE id = 2')->closeCursor();
    check(countLogs($provider) === 11, 'Account deletion removed history.');
    $provider->execute('DELETE FROM awt_setting WHERE id = 1')->closeCursor();
    check(countLogs($provider) === 11, 'Setting deletion removed history.');
    check(in_array('bob', array_column($log->latest(1), 'account_name'), true), 'Deleted account lost its name snapshot.');
    for ($i = 0; $i < 31; ++$i) {
        $log->save([[$timeout, (string)(100 + $i)]], new Admin(1, 'page-user-' . $i));
    }
    $firstPage = $log->history(1);
    $secondPage = $log->history(1, 2);
    check(count($firstPage) === 26, 'History is missing its next-page preview.');
    check(count($secondPage) === 16, 'History page offset is wrong.');
    check($firstPage[0]['account_name'] === 'page-user-30' && $secondPage[0]['account_name'] === 'page-user-5', 'History pagination overlaps or loses entries.');
    check($log->history(1, 3) === [], 'History has entries beyond its final page.');
    check(!in_array('Restricted', array_column($firstPage, 'setting_name'), true), 'History exposes restricted settings.');
    fails(fn() => $log->history(1, 0));

    define('HOSTNAME', '/');
    define('WEB_NAME', 'Test dashboard');
    class LogsBlade extends \render\TemplateEngine\BladeOne {
        public function getTemplateFile($templateName = ''): string {
            global $packages;
            if ($templateName === 'Dashboard.views.templates.main') return $packages . '/Dashboard/Dashboard/views/templates/main.awt.php';
            return parent::getTemplateFile($templateName);
        }
    }
    $blade = new LogsBlade(dirname(__DIR__) . '/SettingsManager/views', $temporary);
    $blade->setIsCompiled(false);
    $blade->setFileExtension('.awt.php');
    $blade->addAssetDict(0, 'awt_packages/Dashboard/views/assets');
    $bundle = ['name' => 'Logs', 'navigation' => '', 'logEntries' => array_slice($firstPage, 0, 25), 'logPage' => 1, 'hasMoreLogs' => true];
    $logsHtml = $blade->run('logs', $bundle);
    @$dom->loadHTML($logsHtml);
    $xpath = new \DOMXPath($dom);
    check($xpath->query('//tbody/tr')->length === 25, 'Logs page does not render 25 entries.');
    check($xpath->query('//a[@rel="next"][@href="/dashboard/settings/logs?page=2"]')->length === 1, 'Logs page next link is incorrect.');
    check($xpath->query('//a[@rel="prev"]')->length === 0, 'First logs page has a previous link.');
    $bundle['logEntries'] = $secondPage;
    $bundle['logPage'] = 2;
    $bundle['hasMoreLogs'] = false;
    $logsHtml = $blade->run('logs', $bundle);
    @$dom->loadHTML($logsHtml);
    $xpath = new \DOMXPath($dom);
    check($xpath->query('//a[@rel="prev"][@href="/dashboard/settings/logs?page=1"]')->length === 1, 'Logs page previous link is incorrect.');
    check($xpath->query('//a[@rel="next"]')->length === 0, 'Final logs page has a next link.');
    check(str_contains($logsHtml, '&lt;Website &amp; name&gt;'), 'Logs page does not escape setting names.');
    $bundle['logEntries'] = [];
    $bundle['logPage'] = 1;
    check(str_contains($blade->run('logs', $bundle), 'No settings changes recorded yet.'), 'Logs page is missing its empty state.');
    $provider->execute('DELETE FROM settingsmanager_loger')->closeCursor();
    $widget = (new RecentSettingsChangesWidget($log))->widget();
    $document->fromArray(['main' => [$widget]]);
    check(str_contains($renderer->render(), 'No settings changes recorded yet.'), 'Widget is missing its empty state.');
    echo "PASS: migration, actual changes, accounts, permissions, atomic rollback, widget, retained history, and paginated Logs page.\n";
}
