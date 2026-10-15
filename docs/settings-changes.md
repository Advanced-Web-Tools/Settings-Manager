# Settings change history

Installing or updating SettingsManager runs `CreateSettingsChangeLog::up()` to create
`settingsmanager_loger`. The migration can run repeatedly without recreating the table.
Update existing installations through the package installer before using the new save handler.

Each changed setting saves one entry with the account ID, username, setting ID/name,
category, permission level, and UTC timestamp. Unchanged values produce no entry.
The settings update and its history are committed together; a failed history write rolls
back the whole category save. Setting values are not copied into the history.

Account and setting names are retained as snapshots. Deleting an account or setting
sets its history reference to null and preserves the entries.

The **Recent settings changes** dashboard widget is registered as
`SettingsManager/recent-changes`, visible by default for accounts that can open Settings.
It shows the latest five permitted changes across accounts, newest first, with a username,
setting name, and date in UTC. Dashboard users can hide or reorder it through the widget controls.

The **Logs** child under **Settings** opens `/dashboard/settings/logs`. It lists the permitted
history in pages of 25 entries with account, setting, category, and UTC date. The widget's
**View all logs** link opens the same page.

Run the SQLite integration checks from the repository root:

```sh
php tests/settings-change-log.php
```

The test expects the framework, Dashboard, and URPC in their usual sibling folders.
For another checkout layout, supply the framework class directory and Packages directory:

```sh
php tests/settings-change-log.php /path/to/awt_src/classes /path/to/Packages
```

The check uses an isolated in-memory database and simulated authenticated accounts.
