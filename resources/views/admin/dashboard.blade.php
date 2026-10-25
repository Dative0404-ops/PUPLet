{{-- Blade directive that renders this view inside the layouts/app.blade.php master layout. --}}
@extends('layouts.app')

{{-- Fills the layout's @yield('title') slot with this page's browser/window title. --}}
@section('title', 'Admin dashboard')

{{-- Opens the main content block that the layout outputs through @yield('content'). --}}
@section('content')
    <div style="margin-bottom: 32px;">
        <h1 style="margin: 0;">Platform overview</h1>
        <p class="muted">Monitor system activity and manage content.</p>
    </div>

    <div class="stat-grid">
        <div class="stat-card green">
            <div class="stat-top">
                <div class="stat-icon">👥</div>
                <span class="label">Total Accounts</span>
            </div>
            <div class="stat-info">
                <span class="value">{{ $overview['users']['total'] }}</span>
                <small style="color: #2f855a; font-weight: 700;">{{ $overview['users']['new_this_week'] }} new this week</small>
            </div>
        </div>
        <div class="stat-card blue">
            <div class="stat-top">
                <div class="stat-icon">📂</div>
                <span class="label">Total Questions</span>
            </div>
            <div class="stat-info">
                <span class="value">{{ $overview['questions']['total'] }}</span>
                <small style="color: #2b6cb0; font-weight: 700;">{{ $overview['questions']['published'] }} published</small>
            </div>
        </div>
        <div class="stat-card purple">
            <div class="stat-top">
                <div class="stat-icon">📝</div>
                <span class="label">Quiz Attempts</span>
            </div>
            <div class="stat-info">
                <span class="value">{{ $overview['activity']['attempts'] }}</span>
                <small style="color: #6b46c1; font-weight: 700;">{{ $overview['activity']['items'] }} items answered</small>
            </div>
        </div>
        <div class="stat-card orange">
            <div class="stat-top">
                <div class="stat-icon">⚠️</div>
                <span class="label">Open Reports</span>
            </div>
            <div class="stat-info">
                <span class="value {{ $overview['reports']['open'] > 0 ? 'is-poor' : '' }}">{{ $overview['reports']['open'] }}</span>
                <small style="color: #c05621; font-weight: 700;">Needs attention</small>
            </div>
        </div>
    </div>

    {{-- Two-column grid holding the Accounts and Question bank summary cards. --}}
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
        {{-- Card summarising accounts by type. --}}
        <div class="card" style="background: #fff; padding: 24px; border-radius: 12px; border: 1px solid var(--line);">
                    <h3>Accounts</h3>
                    {{-- Label/value list used across the dashboard. --}}
                    <ul class="fact-list">
                        <li>
                            {{-- Left-hand row label. --}}
                            <span>Students</span>
                            {{-- Right-hand value: number of student accounts. --}}
                            <strong>{{ $overview['users']['students'] }}</strong>
                        </li>
                        <li>
                            {{-- Left-hand row label. --}}
                            <span>Administrators</span>
                            {{-- Right-hand value: number of admin accounts. --}}
                            <strong>{{ $overview['users']['admins'] }}</strong>
                        </li>
                        <li>
                            {{-- Left-hand row label. --}}
                            <span>Suspended</span>
                            {{-- Right-hand value: accounts currently suspended. --}}
                            <strong>{{ $overview['users']['suspended'] }}</strong>
                        </li>
                        <li>
                            {{-- Left-hand row label. --}}
                            <span>Joined this week</span>
                            {{-- Right-hand value: accounts created in the last seven days. --}}
                            <strong>{{ $overview['users']['new_this_week'] }}</strong>
                        </li>
                    </ul>
                    <p>
                        {{-- Link to the user management page, generated from the named route. --}}
                        <a class="btn btn-secondary btn-sm" href="{{ route('admin.users.index') }}">Manage users</a>
                    </p>
                </div>

                {{-- Card summarising the question bank by state. --}}
                <div class="card">
                    <h3>Question bank</h3>
                    {{-- Label/value list of question-bank counts. --}}
                    <ul class="fact-list">
                        <li>
                            {{-- Left-hand row label. --}}
                            <span>Published</span>
                            {{-- Right-hand value: questions with the published status. --}}
                            <strong>{{ $overview['questions']['published'] }}</strong>
                        </li>
                        <li>
                            {{-- Left-hand row label. --}}
                            <span>Drafts</span>
                            {{-- Right-hand value: draft questions. --}}
                            <strong>{{ $overview['questions']['draft'] }}</strong>
                        </li>
                        <li>
                            {{-- Left-hand row label. --}}
                            <span>Archived</span>
                            {{-- Right-hand value: archived questions. --}}
                            <strong>{{ $overview['questions']['archived'] }}</strong>
                        </li>
                        <li>
                            {{-- Left-hand row label. --}}
                            <span>Verified</span>
                            {{-- Right-hand value: questions signed off by an admin. --}}
                            <strong>{{ $overview['questions']['verified'] }}</strong>
                        </li>
                        <li>
                            {{-- Left-hand row label. --}}
                            <span>Published but not verified</span>
                            {{-- Right-hand value: published items still awaiting verification. --}}
                            <strong>{{ $overview['questions']['unverified'] }}</strong>
                        </li>
                        <li>
                            {{-- Left-hand row label. --}}
                            <span>Published without an explanation</span>
                            {{-- Right-hand value: published items missing an explanation. --}}
                            <strong>{{ $overview['questions']['unexplained'] }}</strong>
                        </li>
                    </ul>
                    <p>
                        {{-- Link to the content verification queue. --}}
                        <a class="btn btn-secondary btn-sm" href="{{ route('admin.verification') }}">Open the verification queue</a>
                    </p>
                </div>
            </div>

            {{-- Two-column grid holding the activity and reports summary cards. --}}
            <div class="grid grid-2">
                {{-- Card summarising platform activity. --}}
                <div class="card">
                    <h3>Platform activity</h3>
                    {{-- Label/value list of activity metrics. --}}
                    <ul class="fact-list">
                        <li>
                            {{-- Left-hand row label. --}}
                            <span>Items answered</span>
                            {{-- Right-hand value: total questions answered across all attempts. --}}
                            <strong>{{ $overview['activity']['items'] }}</strong>
                        </li>
                        <li>
                            {{-- Left-hand row label. --}}
                            <span>Average score</span>
                            <strong>
                                {{-- @if only prints the average when one exists (otherwise it is null). --}}
                                @if ($overview['activity']['average'] !== null)
                                    {{-- Prints the average score as a percentage. --}}
                                    {{ $overview['activity']['average'] }}%
                                @else
                                    {{-- Em dash placeholder when there is no scored attempt yet. --}}
                                    &mdash;
                                @endif
                            </strong>
                        </li>
                        <li>
                            {{-- Left-hand row label. --}}
                            <span>Attempts still open</span>
                            {{-- Right-hand value: attempts that have not been submitted. --}}
                            <strong>{{ $overview['activity']['open_attempts'] }}</strong>
                        </li>
                        <li>
                            {{-- Row label; the window length is printed straight from the data. --}}
                            <span>Active students ({{ $overview['activity']['window_days'] }} days)</span>
                            {{-- Right-hand value: students active within the window. --}}
                            <strong>{{ $overview['activity']['active_students'] }}</strong>
                        </li>
                        <li>
                            {{-- Row label with the same window length printed in brackets. --}}
                            <span>Attempts ({{ $overview['activity']['window_days'] }} days)</span>
                            {{-- Right-hand value: attempts started within the window. --}}
                            <strong>{{ $overview['activity']['attempts_in_window'] }}</strong>
                        </li>
                    </ul>
                </div>

                {{-- Card summarising student reports by state. --}}
                <div class="card">
                    <h3>Reports</h3>
                    {{-- Label/value list of report counts. --}}
                    <ul class="fact-list">
                        <li>
                            {{-- Left-hand row label. --}}
                            <span>Open</span>
                            {{-- Right-hand value: reports not yet resolved or dismissed. --}}
                            <strong>{{ $overview['reports']['open'] }}</strong>
                        </li>
                        <li>
                            {{-- Left-hand row label. --}}
                            <span>Resolved</span>
                            {{-- Right-hand value: reports closed by fixing the item. --}}
                            <strong>{{ $overview['reports']['resolved'] }}</strong>
                        </li>
                        <li>
                            {{-- Left-hand row label. --}}
                            <span>Dismissed</span>
                            {{-- Right-hand value: reports closed without a change. --}}
                            <strong>{{ $overview['reports']['dismissed'] }}</strong>
                        </li>
                    </ul>
                    <p>
                        {{-- Link straight to the reports queue. --}}
                        <a class="btn btn-secondary btn-sm" href="{{ route('admin.reports.index') }}">Review reported questions</a>
                    </p>
                </div>
            </div>

            {{-- Full-width card showing question-bank coverage per LET area. --}}
            <div class="card">
                <h3>Coverage</h3>
                {{-- muted class renders this summary text in a lighter colour. --}}
                <p class="muted">
                    {{-- Prints the subject total and topic total from the catalog summary. --}}
                    {{ $overview['catalog']['subjects'] }} subjects and {{ $overview['catalog']['topics'] }} topics across the three LET areas.
                </p>

                {{-- Wrapper that lets wide tables scroll horizontally on small screens. --}}
                <div class="table-wrap">
                    <table>
                        {{-- Header row of the coverage table. --}}
                        <thead>
                            <tr>
                                <th>Area</th>
                                <th>Subjects</th>
                                <th>Topics</th>
                                <th>Published questions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- @foreach loops over each LET area supplied by the controller. --}}
                            @foreach ($overview['areas'] as $area)
                                <tr>
                                    {{-- Area name, printed bold. --}}
                                    <td><strong>{{ $area['label'] }}</strong></td>
                                    {{-- Number of subjects in this area. --}}
                                    <td>{{ $area['subjects'] }}</td>
                                    {{-- Number of topics in this area. --}}
                                    <td>{{ $area['topics'] }}</td>
                                    {{-- Number of published questions in this area. --}}
                                    <td>{{ $area['published'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p>
                    {{-- Link to the catalogue page where areas, subjects and topics are organised. --}}
                    <a class="btn btn-secondary btn-sm" href="{{ route('admin.catalog') }}">Organise the coverage</a>
                </p>
            </div>

            {{-- Two-column grid holding the newest accounts and latest activity cards. --}}
            <div class="grid grid-2">
                {{-- Card listing the most recently created accounts. --}}
                <div class="card">
                    <h3>Newest accounts</h3>
                    {{-- Label/value list. --}}
                    <ul class="fact-list">
                        {{-- @forelse behaves like @foreach but runs @empty when the list is empty. --}}
                        @forelse ($overview['recent_users'] as $account)
                            <li>
                                <span>
                                    {{-- Link to the admin view of this account; route() fills in the user id. --}}
                                    <a href="{{ route('admin.users.show', $account) }}">{{ $account->name }}</a>
                                    {{-- Email shown small and muted underneath the name. --}}
                                    <small class="muted">{{ $account->email }}</small>
                                </span>
                                {{-- Role label (Student or Administrator) on the right. --}}
                                <strong>{{ $account->roleLabel() }}</strong>
                            </li>
                        {{-- @empty renders when there are no recent accounts. --}}
                        @empty
                            <li><span class="muted">No accounts yet.</span></li>
                        @endforelse
                    </ul>
                </div>

                {{-- Card listing the most recent quiz attempts. --}}
                <div class="card">
                    <h3>Latest activity</h3>
                    {{-- Label/value list. --}}
                    <ul class="fact-list">
                        {{-- Loop over recent attempts; @empty covers the no-activity case. --}}
                        @forelse ($overview['recent_attempts'] as $attempt)
                            <li>
                                <span>
                                    {{-- null-safe operator: falls back to a placeholder if the user was deleted. --}}
                                    <strong>{{ $attempt->user?->name ?? 'Deleted account' }}</strong>
                                    {{-- Quiz title, or a placeholder if the quiz no longer exists. --}}
                                    <small class="muted">{{ $attempt->quiz?->title ?? 'Deleted quiz' }}</small>
                                </span>
                                <strong>
                                    {{-- isInProgress() distinguishes an unfinished attempt from a submitted one. --}}
                                    @if ($attempt->isInProgress())
                                        {{-- Unfinished attempts show an "Open" pill instead of a score. --}}
                                        <span class="pill">Open</span>
                                    @else
                                        {{-- Submitted attempts show the score out of the total items. --}}
                                        {{ $attempt->score }} / {{ $attempt->total_items }}
                                    @endif
                                </strong>
                            </li>
                        {{-- Fallback row when there are no quiz attempts yet. --}}
                        @empty
                            <li><span class="muted">No quiz activity yet.</span></li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
