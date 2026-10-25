{{-- Render this view inside the shared layouts/app.blade.php master layout. --}}
@extends('layouts.app')

{{-- Page title is the account holder's name. --}}
@section('title', $account->name)

{{-- Opens the main content block injected into the layout. --}}
@section('content')
    {{-- Inline @php runs one statement: true when the admin is viewing their own account. --}}
    @php($isSelf = $account->id === auth()->id())

    {{-- Hero banner for the admin page. --}}
    <section class="dashboard-hero admin-hero">
        {{-- Centring container. --}}
        <div class="container">
            {{-- Small label above the heading. --}}
            <span class="eyebrow">Admin panel</span>
            {{-- Heading is the account holder's name. --}}
            <h1>{{ $account->name }}</h1>
            {{-- Summary line: email, role, and optionally the LET track. --}}
            <p class="track-line">
                {{-- Email of the account. --}}
                {{ $account->email }}
                {{-- Separator, then the role label. --}}
                &middot; {{ $account->roleLabel() }}
                {{-- Only show the track for accounts that have one. --}}
                @if ($account->exam_type)
                    &middot; {{ $account->trackLabel() }}
                @endif
            </p>
        </div>
    </section>

    {{-- Main body section. --}}
    <section class="section">
        {{-- Centring container. --}}
        <div class="container">
            {{-- Shared admin tab bar. --}}
            @include('admin.partials.nav')

            <p>
                {{-- Link back to the full user list. --}}
                <a class="btn btn-secondary btn-sm" href="{{ route('admin.users.index') }}">Back to all users</a>
            </p>

            {{-- Four-column grid of this account's statistics. --}}
            <div class="grid grid-4 stat-grid">
                {{-- Card: number of submitted attempts. --}}
                <div class="card stat-card">
                    <span class="stat-value">{{ $summary['attempts'] }}</span>
                    <span class="stat-label">Submitted attempts</span>
                </div>
                {{-- Card: total items answered. --}}
                <div class="card stat-card">
                    <span class="stat-value">{{ $summary['items'] }}</span>
                    <span class="stat-label">Items answered</span>
                </div>
                {{-- Card: average score, or a dash when the account has no scored attempt. --}}
                <div class="card stat-card">
                    <span class="stat-value">
                        @if ($summary['average'] !== null)
                            {{-- Average score printed as a percentage. --}}
                            {{ $summary['average'] }}%
                        @else
                            {{-- Em dash when there is no average. --}}
                            &mdash;
                        @endif
                    </span>
                    <span class="stat-label">Average score</span>
                </div>
                {{-- Card: attempts that were started but not submitted. --}}
                <div class="card stat-card">
                    <span class="stat-value">{{ $summary['open_attempts'] }}</span>
                    <span class="stat-label">Open attempts</span>
                </div>
            </div>

            {{-- Two-column grid: access controls and activity summary. --}}
            <div class="grid grid-2">
                {{-- Card holding the role and suspension controls. --}}
                <div class="card">
                    <h3>Access</h3>

                    {{-- Admins cannot change their own role or deactivate themselves. --}}
                    @if ($isSelf)
                        {{-- notice box explaining why the controls are hidden. --}}
                        <div class="notice">
                            <p>This is your own account, so its role and access cannot be changed here.</p>
                        </div>
                    @else
                        {{-- Role-change form for another user's account. --}}
                        <form method="POST" action="{{ route('admin.users.role', $account) }}" class="inline-form">
                            {{-- CSRF token. --}}
                            @csrf
                            {{-- Spoofs the PUT verb for the role update. --}}
                            @method('PUT')

                            {{-- Field group for the role select. --}}
                            <div class="field">
                                <label for="role">Role</label>
                                <select id="role" name="role" required>
                                    {{-- $roles is a value=>label map (student/admin). --}}
                                    @foreach ($roles as $value => $label)
                                        {{-- Pre-selects the account's current role. --}}
                                        <option value="{{ $value }}" @selected($account->role === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Submit row for the role form. --}}
                            <div class="field inline-form-actions">
                                <button type="submit" class="btn btn-primary btn-sm">Save role</button>
                            </div>
                        </form>

                        {{-- Horizontal rule separating role management from account access. --}}
                        <hr>

                        {{-- Suspended accounts can be reactivated; active ones can be suspended. --}}
                        @if ($account->isSuspended())
                            <p>
                                <span class="pill is-poor">Suspended</span>
                                {{-- Date and time the suspension started. --}}
                                <small class="muted">since {{ $account->suspended_at->format('M j, Y g:i A') }}</small>
                            </p>
                            <p class="muted small">This account cannot sign in until it is reactivated.</p>
                            <form method="POST" action="{{ route('admin.users.reactivate', $account) }}">
                                {{-- CSRF token. --}}
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm">Reactivate this account</button>
                            </form>
                        @else
                            <p><span class="pill is-good">Active</span></p>
                            <p class="muted small">Suspending an account stops it from signing in, without deleting it.</p>
                            <form method="POST" action="{{ route('admin.users.suspend', $account) }}">
                                {{-- CSRF token. --}}
                                @csrf
                                {{-- Danger styling marks this as a restrictive action. --}}
                                <button type="submit" class="btn btn-danger btn-sm">Suspend this account</button>
                            </form>
                        @endif
                    @endif
                </div>

                {{-- Card summarising the account's study activity. --}}
                <div class="card">
                    <h3>Activity at a glance</h3>
                    {{-- Label/value list of counters. --}}
                    <ul class="fact-list">
                        <li>
                            <span>Lessons started</span>
                            {{-- Number of lesson progress records for this account. --}}
                            <strong>{{ $account->lesson_progresses_count }}</strong>
                        </li>
                        <li>
                            <span>Saved questions</span>
                            {{-- Number of bookmarked questions. --}}
                            <strong>{{ $account->question_bookmarks_count }}</strong>
                        </li>
                        <li>
                            <span>Study goals</span>
                            {{-- Number of study goals, from the controller summary. --}}
                            <strong>{{ $summary['goals'] }}</strong>
                        </li>
                        <li>
                            <span>Questions reported</span>
                            {{-- Number of reports this account has filed. --}}
                            <strong>{{ $summary['reports'] }}</strong>
                        </li>
                        <li>
                            <span>Joined</span>
                            {{-- Formatted registration date. --}}
                            <strong>{{ $account->created_at->format('M j, Y') }}</strong>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Card listing this account's recent quiz attempts. --}}
            <div class="card">
                <h3>Recent attempts</h3>

                {{-- Scrollable wrapper around the attempts table. --}}
                <div class="table-wrap">
                    <table>
                        {{-- Column headings for the attempts table. --}}
                        <thead>
                            <tr>
                                <th>Quiz</th>
                                <th>Items</th>
                                <th>Score</th>
                                <th>Started</th>
                                <th>State</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Loop the attempts; @empty covers an account with no quizzes taken. --}}
                            @forelse ($attempts as $attempt)
                                <tr>
                                    <td>
                                        {{-- Quiz title, or a placeholder if the quiz was deleted. --}}
                                        <strong>{{ $attempt->quiz?->title ?? 'Deleted quiz' }}</strong>
                                        {{-- Show the topic name when the quiz still has one. --}}
                                        @if ($attempt->quiz?->topic)
                                            <small class="muted">{{ $attempt->quiz->topic->name }}</small>
                                        @endif
                                    </td>
                                    {{-- Total number of items in the attempt. --}}
                                    <td>{{ $attempt->total_items }}</td>
                                    <td>
                                        {{-- In-progress attempts show how many items were answered so far. --}}
                                        @if ($attempt->isInProgress())
                                            <span class="muted">{{ $attempt->answered_items }} answered</span>
                                        @else
                                            {{-- Submitted attempts show score out of total items. --}}
                                            {{ $attempt->score }} / {{ $attempt->total_items }}
                                        @endif
                                    </td>
                                    {{-- Formatted start time, or a dash if it was never recorded. --}}
                                    <td>{{ $attempt->started_at?->format('M j, Y g:i A') ?? '—' }}</td>
                                    <td>
                                        {{-- Open pill for unfinished attempts, green Submitted pill otherwise. --}}
                                        @if ($attempt->isInProgress())
                                            <span class="pill">Open</span>
                                        @else
                                            <span class="pill is-good">Submitted</span>
                                        @endif
                                    </td>
                                </tr>
                            {{-- Fallback row when the account has no attempts. --}}
                            @empty
                                <tr>
                                    {{-- colspan spans all five columns. --}}
                                    <td colspan="5"><span class="muted">This account has not taken a quiz yet.</span></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Two-column grid: study goals and filed reports. --}}
            <div class="grid grid-2">
                {{-- Card listing the account's study goals. --}}
                <div class="card">
                    <h3>Study goals</h3>
                    {{-- Label/value list of goals. --}}
                    <ul class="fact-list">
                        {{-- Loop the goals; @empty covers an account with none. --}}
                        @forelse ($goals as $goal)
                            <li>
                                <span>
                                    {{-- Goal title. --}}
                                    {{ $goal->title }}
                                    <small class="muted">
                                        {{-- Target date, or nothing when it has no date. --}}
                                        {{ $goal->target_date?->format('M j, Y') }}
                                        {{-- print only when the goal has a time label. --}}
                                        @if ($goal->timeLabel())
                                            &middot; {{ $goal->timeLabel() }}
                                        @endif
                                    </small>
                                </span>
                                <strong>
                                    {{-- Green Done pill when completed, plain Open pill otherwise. --}}
                                    @if ($goal->completed_at)
                                        <span class="pill is-good">Done</span>
                                    @else
                                        <span class="pill">Open</span>
                                    @endif
                                </strong>
                            </li>
                        {{-- Fallback row when no goals are set. --}}
                        @empty
                            <li><span class="muted">No goals set.</span></li>
                        @endforelse
                    </ul>
                </div>

                {{-- Card listing the reports this account has filed. --}}
                <div class="card">
                    <h3>Reports filed</h3>
                    {{-- Label/value list of reports. --}}
                    <ul class="fact-list">
                        {{-- Loop the reports; @empty covers an account with none. --}}
                        @forelse ($reports as $report)
                            <li>
                                <span>
                                    {{-- Human-readable report reason. --}}
                                    {{ $report->reasonLabel() }}
                                    {{-- Truncated prompt of the reported question, or a placeholder if deleted. --}}
                                    <small class="muted">{{ Str::limit($report->question?->prompt ?? 'Deleted question', 60) }}</small>
                                </span>
                                <strong>
                                    {{-- Red pill while open, green once resolved or dismissed. --}}
                                    <span class="pill {{ $report->isOpen() ? 'is-poor' : 'is-good' }}">
                                        {{ $report->statusLabel() }}
                                    </span>
                                </strong>
                            </li>
                        {{-- Fallback row when no reports have been filed. --}}
                        @empty
                            <li><span class="muted">No reports filed.</span></li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </section>
@endsection
