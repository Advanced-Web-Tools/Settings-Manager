@extends('Dashboard.views.templates.main')
@section('content')
    <link rel="stylesheet" href="/awt_packages/SettingsManager/views/assets/css/settings.css">
    <div class="settings-page">
        <header class="navbar border-bottom">
            <div class="container">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 py-2">
                    <div class="navbar-brand">Settings help</div>
                    <a class="btn btn-outline-secondary" href="/dashboard/settings">Back to settings</a>
                </div>
            </div>
        </header>
        <div class="py-5">
            <div class="container">
                <h1>Help with your settings</h1>
                <p class="lead mb-4">Choose a topic below for guidance on editing settings, saving changes, and reviewing activity.</p>

                <div class="card-stack content-panel settings-panel">
                    <section class="card settings-help-section" aria-labelledby="settings-help-start">
                        <h2 id="settings-help-start" class="h4">Getting started</h2>
                        <details open>
                            <summary>Where do I find a setting?</summary>
                            <div class="settings-help-answer">
                                <p>Open Settings and choose a category in the tabs below the header. The form shows the values currently saved for that category.</p>
                                <p>Installed packages provide the available settings and categories. If none are available, the page shows an empty state.</p>
                                <a href="/dashboard/settings">Open settings</a>
                            </div>
                        </details>
                        <details>
                            <summary>How do the different fields work?</summary>
                            <div class="settings-help-answer">
                                <p>Use text fields for text, number fields for numeric values, and switches to enable or disable an option. A switch that is turned off is saved as disabled.</p>
                                <p>Each field is filled from its saved value. Review your changes before saving the category.</p>
                            </div>
                        </details>
                    </section>

                    <section class="card settings-help-section" aria-labelledby="settings-help-save">
                        <h2 id="settings-help-save" class="h4">Saving changes</h2>
                        <details>
                            <summary>When does the Apply button appear?</summary>
                            <div class="settings-help-answer">
                                <p>Apply appears in the header when an editable field differs from its initial value. Restoring all fields to their initial values hides the button again.</p>
                                <p>Apply and Save settings submit the same form. Both save the current category. A confirmation appears when the save succeeds.</p>
                            </div>
                        </details>
                        <details>
                            <summary>Can I undo edits before saving?</summary>
                            <div class="settings-help-answer">
                                <p>Select Reset changes to restore the values the form had when it loaded. This only resets the form; it does not undo a previously saved change.</p>
                                <p>Save your edits before switching categories or leaving the page. Unsaved edits are not carried to another page.</p>
                            </div>
                        </details>
                        <details>
                            <summary>What if a value is invalid?</summary>
                            <div class="settings-help-answer">
                                <p>Check the message shown by the form, correct the value, and save again. Number fields must contain valid numbers.</p>
                                <p>The category is validated before any changes are saved. If saving its change history fails, the settings update is rolled back too.</p>
                            </div>
                        </details>
                    </section>

                    <section class="card settings-help-section" aria-labelledby="settings-help-history">
                        <h2 id="settings-help-history" class="h4">Change history</h2>
                        <details>
                            <summary>How do I see who changed a setting?</summary>
                            <div class="settings-help-answer">
                                <p>Open Logs under Settings in the sidebar. Each entry shows the account, setting name, category, and date in UTC. Entries appear newest first, with 25 per page.</p>
                                <p>Only settings whose values changed create entries. Saving an unchanged value does not add a log entry. The history records names and dates without copying setting values.</p>
                                <a href="/dashboard/settings/logs">View logs</a>
                            </div>
                        </details>
                        <details>
                            <summary>What does the dashboard widget show?</summary>
                            <div class="settings-help-answer">
                                <p>Recent settings changes shows the latest five changes you have permission to see, across accounts. Select View all logs to open the full history.</p>
                                <p>You can show, hide, or reorder the widget using the dashboard widget controls.</p>
                                <a href="/dashboard">Open dashboard</a>
                            </div>
                        </details>
                    </section>

                    <section class="card settings-help-section" aria-labelledby="settings-help-access">
                        <h2 id="settings-help-access" class="h4">Access and troubleshooting</h2>
                        <details>
                            <summary>Why can I not edit a setting or see a log entry?</summary>
                            <div class="settings-help-answer">
                                <p>Your account's permission level controls which settings you can change. Restricted fields are disabled and display a permission message.</p>
                                <p>Logs and the dashboard widget also respect setting permissions. Ask an administrator to review your access if you need to change a restricted setting.</p>
                            </div>
                        </details>
                        <details>
                            <summary>Why is the history empty?</summary>
                            <div class="settings-help-answer">
                                <p>History starts when change logging is installed. Earlier changes are not added retroactively. An empty history can also mean there are no recorded changes you have permission to see.</p>
                                <p>If saving or opening Logs fails after an upgrade, ask an administrator to verify that the SettingsManager package update completed successfully.</p>
                            </div>
                        </details>
                    </section>
                </div>
            </div>
        </div>
    </div>
@endsection
