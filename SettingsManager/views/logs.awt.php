@extends('Dashboard.views.templates.main')
@section('content')
    <div class="settings-page">
        <header class="navbar border-bottom">
            <div class="container">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 py-2">
                    <div class="navbar-brand">Logs</div>
                    <div class="d-inline-flex align-items-center gap-1">
                        <a class="btn btn-ghost" href="/dashboard/settings/help"><i class="fa-solid fa-circle-question fa-fw" aria-hidden="true"></i> Help</a>
                        <a class="btn btn-outline-secondary" href="/dashboard/settings">Back to settings</a>
                    </div>
                </div>
            </div>
        </header>
        <div class="py-5">
            <div class="container">
                <h1>Settings change history</h1>
                <p class="lead mb-4">See who changed your settings and when. Dates are shown in UTC.</p>

                @if(count($logEntries) === 0)
                    <div class="card empty-state">
                        <h2 class="h3">{{ $logPage === 1 ? 'No settings changes recorded yet.' : 'No changes on this page.' }}</h2>
                        <p>Changes saved from Settings will appear here.</p>
                    </div>
                @else
                    <div class="card">
                        <div class="table-responsive">
                            <table class="table">
                                <caption class="visually-hidden">Settings changes, newest first</caption>
                                <thead>
                                    <tr>
                                        <th scope="col">Account</th>
                                        <th scope="col">Setting changed</th>
                                        <th scope="col">Category</th>
                                        <th scope="col">Date (UTC)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($logEntries as $entry)
                                        @php
                                            $date = new \DateTimeImmutable($entry['created_at'], new \DateTimeZone('UTC'));
                                        @endphp
                                        <tr>
                                            <td>{{ $entry['account_name'] }}</td>
                                            <td>{{ $entry['setting_name'] }}</td>
                                            <td>{{ $entry['category'] }}</td>
                                            <td><time datetime="{{ $date->format(DATE_ATOM) }}">{{ $date->format('j M Y, H:i') }}</time></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if($logPage > 1 || $hasMoreLogs)
                    <nav class="form-actions justify-content-between" aria-label="Log pages">
                        <div class="d-flex flex-wrap gap-1">
                            @if($logPage > 1)
                                <a class="btn btn-outline-secondary" href="/dashboard/settings/logs?page={{ $logPage - 1 }}" rel="prev">Previous</a>
                            @endif
                            @if($hasMoreLogs)
                                <a class="btn btn-outline-secondary" href="/dashboard/settings/logs?page={{ $logPage + 1 }}" rel="next">Next</a>
                            @endif
                        </div>
                        <span class="text-small text-muted">Page {{ $logPage }}</span>
                    </nav>
                @endif
            </div>
        </div>
    </div>
@endsection
