{{-- Admin page: details of a single user account. --}}
@extends('layouts.app')

@section('title', $account->name)

@section('content')
    {{-- True when the admin is looking at their own account. --}}
    @php($isSelf = $account->id === auth()->id())

    {{-- Page heading --}}
    <section class="dashboard-hero admin-hero">
        <div class="container">
            <span class="eyebrow">Admin panel</span>
            <h1>{{ $account->name }}</h1>
            <p class="track-line">
                {{ $account->email }}
                &middot; {{ $account->roleLabel() }}
                {{-- Only accounts that have a track show one. --}}
                @if ($account->exam_type)
                    &middot; {{ $account->trackLabel() }}
                @endif
            </p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            {{-- Tab bar shared by every admin page. --}}
            @include('admin.partials.nav')

            <p>
                <a class="btn btn-secondary btn-sm" href="{{ route('admin.users.index') }}">Back to all users</a>
            </p>

            {{-- Account statistics --}}
            <div class="grid grid-4 stat-grid">
                <div class="card stat-card">
                    <span class="stat-value">{{ $summary['attempts'] }}</span>
                    <span class="stat-label">Submitted attempts</span>
                </div>
                <div class="card stat-card">
                    <span class="stat-value">{{ $summary['items'] }}</span>
                    <span class="stat-label">Items answered</span>
                </div>
                <div class="card stat-card">
                    <span class="stat-value">
                        {{-- The average is null until the account has a scored attempt. --}}
                        @if ($summary['average'] !== null)
                            {{ $summary['average'] }}%
                        @else
                            {{-- Em dash when there is no average. --}}
                            &mdash;
                        @endif
                    </span>
                    <span class="stat-label">Average score</span>
                </div>
                <div class="card stat-card">
                    <span class="stat-value">{{ $summary['open_attempts'] }}</span>
                    <span class="stat-label">Open attempts</span>
                </div>
            </div>

            {{-- Access controls and a study-activity summary --}}
            <div class="grid grid-2">
                <div class="card">
                    <h3>Access</h3>

                    {{-- An admin cannot change their own role or deactivate themselves. --}}
                    @if ($isSelf)
                        <div class="notice">
                            <p>This is your own account, so its role and access cannot be changed here.</p>
                        </div>
                    @else
                        {{-- Role-change form --}}
                        <form method="POST" action="{{ route('admin.users.role', $account) }}" class="inline-form">
                            @csrf
                            @method('PUT')

                            <div class="field">
                                <label for="role">Role</label>
                                <select id="role" name="role" required>
                                    @foreach ($roles as $value => $label)
                                        <option value="{{ $value }}" @selected($account->role === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="field inline-form-actions">
                                <button type="submit" class="btn btn-primary btn-sm">Save role</button>
                            </div>
                        </form>

                        <hr>

                        {{-- Suspended accounts can be reactivated; active ones can be suspended. --}}
                        @if ($account->isSuspended())
                            <p>
                                <span class="pill is-poor">Suspended</span>
                                <small class="muted">since {{ $account->suspended_at->format('M j, Y g:i A') }}</small>
                            </p>
                            <p class="muted small">This account cannot sign in until it is reactivated.</p>
                            <form method="POST" action="{{ route('admin.users.reactivate', $account) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm">Reactivate this account</button>
                            </form>
                        @else
                            <p><span class="pill is-good">Active</span></p>
                            <p class="muted small">Suspending an account stops it from signing in, without deleting it.</p>
                            <form method="POST" action="{{ route('admin.users.suspend', $account) }}">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-sm">Suspend this account</button>
                            </form>
                        @endif
                    @endif
                </div>

                {{-- Study activity summary --}}
                <div class="card">
                    <h3>Activity at a glance</h3>
                    <ul class="fact-list">
                        <li>
                            <span>Lessons started</span>
                            <strong>{{ $account->lesson_progresses_count }}</strong>
                        </li>
                        <li>
                            <span>Saved questions</span>
                            <strong>{{ $account->question_bookmarks_count }}</strong>
                        </li>
                        <li>
                            <span>Study goals</span>
                            <strong>{{ $summary['goals'] }}</strong>
                        </li>
                        <li>
                            <span>Questions reported</span>
                            <strong>{{ $summary['reports'] }}</strong>
                        </li>
                        <li>
                            <span>Joined</span>
                            <strong>{{ $account->created_at->format('M j, Y') }}</strong>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Recent quiz attempts for this account --}}
            <div class="card">
                <h3>Recent attempts</h3>

                {{-- The wrapper lets the table scroll on small screens. --}}
                <div class="table-wrap">
                    <table>
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
                            @forelse ($attempts as $attempt)
                                <tr>
                                    <td>
                                        {{-- Quiz title, or a placeholder if the quiz was deleted. --}}
                                        <strong>{{ $attempt->quiz?->title ?? 'Deleted quiz' }}</strong>
                                        @if ($attempt->quiz?->topic)
                                            <small class="muted">{{ $attempt->quiz->topic->name }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $attempt->total_items }}</td>
                                    <td>
                                        {{-- An unfinished attempt shows how many items were answered so far. --}}
                                        @if ($attempt->isInProgress())
                                            <span class="muted">{{ $attempt->answered_items }} answered</span>
                                        @else
                                            {{ $attempt->score }} / {{ $attempt->total_items }}
                                        @endif
                                    </td>
                                    <td>{{ $attempt->started_at?->format('M j, Y g:i A') ?? '—' }}</td>
                                    <td>
                                        @if ($attempt->isInProgress())
                                            <span class="pill">Open</span>
                                        @else
                                            <span class="pill is-good">Submitted</span>
                                        @endif
                                    </td>
                                </tr>
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

            {{-- Study goals and filed reports --}}
            <div class="grid grid-2">
                <div class="card">
                    <h3>Study goals</h3>
                    <ul class="fact-list">
                        @forelse ($goals as $goal)
                            <li>
                                <span>
                                    {{ $goal->title }}
                                    <small class="muted">
                                        {{ $goal->target_date?->format('M j, Y') }}
                                        {{-- The time label is only added when the goal has one. --}}
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
                        @empty
                            <li><span class="muted">No goals set.</span></li>
                        @endforelse
                    </ul>
                </div>

                <div class="card">
                    <h3>Reports filed</h3>
                    <ul class="fact-list">
                        @forelse ($reports as $report)
                            <li>
                                <span>
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
                        @empty
                            <li><span class="muted">No reports filed.</span></li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </section>
@endsection
