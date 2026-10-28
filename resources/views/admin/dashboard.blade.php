{{-- Admin page: dashboard overview of the whole platform. --}}
@extends('layouts.app')

@section('title', 'Admin dashboard')

@section('content')
    {{-- Page heading --}}
    <div style="margin-bottom: 32px;">
        <h1 style="margin: 0;">Platform overview</h1>
        <p class="muted">Monitor system activity and manage content.</p>
    </div>

    {{-- Key platform numbers --}}
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

    {{-- Accounts and question-bank summary cards --}}
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
        <div class="card" style="background: #fff; padding: 24px; border-radius: 12px; border: 1px solid var(--line);">
                    <h3>Accounts</h3>
                    <ul class="fact-list">
                        <li>
                            <span>Students</span>
                            <strong>{{ $overview['users']['students'] }}</strong>
                        </li>
                        <li>
                            <span>Administrators</span>
                            <strong>{{ $overview['users']['admins'] }}</strong>
                        </li>
                        <li>
                            <span>Suspended</span>
                            <strong>{{ $overview['users']['suspended'] }}</strong>
                        </li>
                        <li>
                            <span>Joined this week</span>
                            <strong>{{ $overview['users']['new_this_week'] }}</strong>
                        </li>
                    </ul>
                    <p>
                        <a class="btn btn-secondary btn-sm" href="{{ route('admin.users.index') }}">Manage users</a>
                    </p>
                </div>

                <div class="card">
                    <h3>Question bank</h3>
                    <ul class="fact-list">
                        <li>
                            <span>Published</span>
                            <strong>{{ $overview['questions']['published'] }}</strong>
                        </li>
                        <li>
                            <span>Drafts</span>
                            <strong>{{ $overview['questions']['draft'] }}</strong>
                        </li>
                        <li>
                            <span>Archived</span>
                            <strong>{{ $overview['questions']['archived'] }}</strong>
                        </li>
                        <li>
                            <span>Verified</span>
                            <strong>{{ $overview['questions']['verified'] }}</strong>
                        </li>
                        <li>
                            <span>Published but not verified</span>
                            <strong>{{ $overview['questions']['unverified'] }}</strong>
                        </li>
                        <li>
                            <span>Published without an explanation</span>
                            <strong>{{ $overview['questions']['unexplained'] }}</strong>
                        </li>
                    </ul>
                    <p>
                        <a class="btn btn-secondary btn-sm" href="{{ route('admin.verification') }}">Open the verification queue</a>
                    </p>
                </div>
            </div>

            {{-- Activity and reports summary cards --}}
            <div class="grid grid-2">
                <div class="card">
                    <h3>Platform activity</h3>
                    <ul class="fact-list">
                        <li>
                            <span>Items answered</span>
                            <strong>{{ $overview['activity']['items'] }}</strong>
                        </li>
                        <li>
                            <span>Average score</span>
                            <strong>
                                {{-- An average only exists once an attempt has been scored; otherwise it is null. --}}
                                @if ($overview['activity']['average'] !== null)
                                    {{ $overview['activity']['average'] }}%
                                @else
                                    {{-- Em dash when there is no scored attempt yet. --}}
                                    &mdash;
                                @endif
                            </strong>
                        </li>
                        <li>
                            <span>Attempts still open</span>
                            <strong>{{ $overview['activity']['open_attempts'] }}</strong>
                        </li>
                        <li>
                            <span>Active students ({{ $overview['activity']['window_days'] }} days)</span>
                            <strong>{{ $overview['activity']['active_students'] }}</strong>
                        </li>
                        <li>
                            <span>Attempts ({{ $overview['activity']['window_days'] }} days)</span>
                            <strong>{{ $overview['activity']['attempts_in_window'] }}</strong>
                        </li>
                    </ul>
                </div>

                <div class="card">
                    <h3>Reports</h3>
                    <ul class="fact-list">
                        <li>
                            <span>Open</span>
                            <strong>{{ $overview['reports']['open'] }}</strong>
                        </li>
                        <li>
                            <span>Resolved</span>
                            <strong>{{ $overview['reports']['resolved'] }}</strong>
                        </li>
                        <li>
                            <span>Dismissed</span>
                            <strong>{{ $overview['reports']['dismissed'] }}</strong>
                        </li>
                    </ul>
                    <p>
                        <a class="btn btn-secondary btn-sm" href="{{ route('admin.reports.index') }}">Review reported questions</a>
                    </p>
                </div>
            </div>

            {{-- Question-bank coverage per LET area --}}
            <div class="card">
                <h3>Coverage</h3>
                <p class="muted">
                    {{ $overview['catalog']['subjects'] }} subjects and {{ $overview['catalog']['topics'] }} topics across the three LET areas.
                </p>

                {{-- Wrapper so the wide table can scroll on small screens. --}}
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Area</th>
                                <th>Subjects</th>
                                <th>Topics</th>
                                <th>Published questions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($overview['areas'] as $area)
                                <tr>
                                    <td><strong>{{ $area['label'] }}</strong></td>
                                    <td>{{ $area['subjects'] }}</td>
                                    <td>{{ $area['topics'] }}</td>
                                    <td>{{ $area['published'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p>
                    <a class="btn btn-secondary btn-sm" href="{{ route('admin.catalog') }}">Organise the coverage</a>
                </p>
            </div>

            {{-- Newest accounts and latest activity --}}
            <div class="grid grid-2">
                <div class="card">
                    <h3>Newest accounts</h3>
                    <ul class="fact-list">
                        {{-- @forelse loops the list; @empty shows the fallback when the list is empty. --}}
                        @forelse ($overview['recent_users'] as $account)
                            <li>
                                <span>
                                    <a href="{{ route('admin.users.show', $account) }}">{{ $account->name }}</a>
                                    <small class="muted">{{ $account->email }}</small>
                                </span>
                                <strong>{{ $account->roleLabel() }}</strong>
                            </li>
                        @empty
                            <li><span class="muted">No accounts yet.</span></li>
                        @endforelse
                    </ul>
                </div>

                <div class="card">
                    <h3>Latest activity</h3>
                    <ul class="fact-list">
                        @forelse ($overview['recent_attempts'] as $attempt)
                            <li>
                                <span>
                                    {{-- ?-> falls back to a placeholder if the user was deleted. --}}
                                    <strong>{{ $attempt->user?->name ?? 'Deleted account' }}</strong>
                                    {{-- Quiz title, or a placeholder if the quiz no longer exists. --}}
                                    <small class="muted">{{ $attempt->quiz?->title ?? 'Deleted quiz' }}</small>
                                </span>
                                <strong>
                                    @if ($attempt->isInProgress())
                                        <span class="pill">Open</span>
                                    @else
                                        {{ $attempt->score }} / {{ $attempt->total_items }}
                                    @endif
                                </strong>
                            </li>
                        @empty
                            <li><span class="muted">No quiz activity yet.</span></li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
