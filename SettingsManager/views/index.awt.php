@extends("Dashboard.views.templates.main")
@section("content")
    <link rel="stylesheet" href="/awt_packages/SettingsManager/views/assets/css/settings.css">
    <div id="settings-app" class="settings-page">
        <header class="navbar border-bottom">
            <div class="container">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 py-2">
                    <div class="navbar-brand">Settings</div>
                    <div class="d-inline-flex align-items-center gap-1">
                        <a class="btn btn-ghost" href="/dashboard/settings/help"><i class="fa-solid fa-circle-question fa-fw" aria-hidden="true"></i> Help</a>
                        @if($active_tab !== null)
                            <button class="btn btn-primary" id="apply" type="submit" form="settings-form" hidden>Apply</button>
                        @endif
                    </div>
                </div>
            </div>
            <div class="container">
                <nav class="nav-tabs" aria-label="Settings categories">
                    @foreach($settings as $category => $setting)
                        <a href="/dashboard/settings?tab={{ rawurlencode($category) }}" class="{{ $active_tab === $category ? 'active' : '' }}" @if($active_tab === $category) aria-current="page" @endif>{{ $category }}</a>
                    @endforeach
                </nav>
            </div>
        </header>
        <div class="py-5">
            <div class="container">
                @if($error)
                    <div class="alert alert-danger" role="alert">{{ $error }}</div>
                @elseif($saved)
                    <div class="alert alert-success" role="status">Settings saved.</div>
                @endif

                @if($active_tab === null)
                    <div class="card empty-state">
                        <h1 class="h3">No settings available.</h1>
                        <p>Settings will appear here when a package provides them.</p>
                    </div>
                @else
                    <h1>{{ $active_tab }} settings</h1>
                    <p class="lead mb-4">Update the settings below, then apply your changes.</p>

                    <form id="settings-form" class="card content-panel settings-panel" method="post" action="/dashboard/settings/save">
                        <input type="hidden" name="_token" value="{{ $token }}">
                        <input type="hidden" name="tab" value="{{ $active_tab }}">
                        <h2 class="h4 mb-1">{{ $active_tab }}</h2>
                        <span class="text-small text-muted">Changes apply to this category.</span>

                        @foreach($settings[$active_tab] as $key => $field)
                            <div class="form-field">
                                <label class="form-label" for="setting-{{ $field['id'] }}">{{ $key }}</label>
                                @if($field['type'] === 'boolean')
                                    @if($field['editable'])
                                        <input type="hidden" name="settings[{{ $field['id'] }}]" value="false">
                                    @endif
                                    <label class="form-switch">
                                        <input id="setting-{{ $field['id'] }}" type="checkbox" name="settings[{{ $field['id'] }}]" value="true" aria-label="{{ $key }}" @if($field['value']) checked @endif @if(!$field['editable']) disabled @endif>
                                        <span class="switch-track" aria-hidden="true"></span>
                                    </label>
                                @elseif($field['type'] === 'textarea')
                                    <textarea class="form-control" id="setting-{{ $field['id'] }}" name="settings[{{ $field['id'] }}]" @if(!$field['editable']) disabled @endif>{{ $field['value'] }}</textarea>
                                @else
                                    <input class="form-control" id="setting-{{ $field['id'] }}" type="{{ $field['type'] === 'number' ? 'number' : 'text' }}" name="settings[{{ $field['id'] }}]" value="{{ $field['value'] }}" @if($field['type'] === 'number') step="any" required @endif @if(!$field['editable']) disabled @endif>
                                @endif
                                @if(!$field['editable'])
                                    <small>You do not have permission to change this setting.</small>
                                @endif
                            </div>
                        @endforeach

                        <div class="form-actions">
                            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk fa-fw" aria-hidden="true"></i> Save settings</button>
                            <button class="btn btn-outline-secondary" type="reset">Reset changes</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
    <script src="/awt_packages/SettingsManager/views/assets/js/settings.js" defer></script>
@endsection
