{{-- Admin page: list of all user accounts. --}}
@extends('layouts.app')

@section('title', 'User management')

@section('content')
    {{-- Page heading --}}
    <section class="dashboard-hero admin-hero">
        <div class="container">
            <span class="eyebrow">Admin panel</span>
            <h1>User management</h1>
            <p class="track-line">See who is on the platform and manage their access.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            {{-- Tab bar shared by every admin page. --}}
            @include('admin.partials.nav')

            {{-- Account counts --}}
            <div class="grid grid-4 stat-grid">
                <div class="card stat-card">
                    <span class="stat-value">{{ $summary['total'] }}</span>
                    <span class="stat-label">Accounts</span>
                </div>
                <div class="card stat-card">
                    <span class="stat-value">{{ $summary['students'] }}</span>
                    <span class="stat-label">Students</span>
                </div>
                <div class="card stat-card">
                    <span class="stat-value">{{ $summary['admins'] }}</span>
                    <span class="stat-label">Administrators</span>
                </div>
                {{-- is-poor turns the number red as soon as an account is suspended. --}}
                <div class="card stat-card">
                    <span class="stat-value {{ $summary['suspended'] > 0 ? 'is-poor' : '' }}">{{ $summary['suspended'] }}</span>
                    <span class="stat-label">Suspended</span>
                </div>
            </div>

            {{-- Search and filter bar --}}
            <div class="admin-toolbar">
                {{-- GET keeps the filters in the URL, so a filtered list can be bookmarked. --}}
                <form method="GET" action="{{ route('admin.users.index') }}" class="admin-filters">
                    <div class="field">
                        <label for="search">Search</label>
                        <input type="search" id="search" name="search" value="{{ $search }}" placeholder="Name or email">
                    </div>

                    <div class="field">
                        <label for="role">Role</label>
                        <select id="role" name="role">
                            {{-- An empty value means no role filter. --}}
                            <option value="">Any role</option>
                            @foreach ($roles as $value => $label)
                                <option value="{{ $value }}" @selected($role === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="state">Access</label>
                        <select id="state" name="state">
                            {{-- An empty value means no access filter. --}}
                            <option value="">Any</option>
                            <option value="active" @selected($state === 'active')>Active</option>
                            <option value="suspended" @selected($state === 'suspended')>Suspended</option>
                        </select>
                    </div>

                    <div class="field admin-filter-actions">
                        <button type="submit" class="btn btn-primary btn-sm">Apply filters</button>
                        <a class="btn btn-secondary btn-sm" href="{{ route('admin.users.index') }}">Reset</a>
                    </div>
                </form>
            </div>

            {{-- Accounts table --}}
            <div class="table-wrap">
                <table>
                    {{-- The last heading is blank because the column holds the View button. --}}
                    <thead>
                        <tr>
                            <th>Account</th>
                            <th>Role</th>
                            <th>LET track</th>
                            <th>Access</th>
                            <th>Attempts</th>
                            <th>Joined</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $account)
                            <tr>
                                <td>
                                    <strong>{{ $account->name }}</strong>
                                    <small class="muted">{{ $account->email }}</small>
                                </td>
                                <td>
                                    {{-- is-good (green) is only used for administrators. --}}
                                    <span class="pill {{ $account->isAdmin() ? 'is-good' : '' }}">{{ $account->roleLabel() }}</span>
                                </td>
                                <td>
                                    {{-- Only students have an exam type; admins show a dash. --}}
                                    @if ($account->exam_type)
                                        {{ $account->trackLabel() }}
                                    @else
                                        <span class="muted">&mdash;</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($account->isSuspended())
                                        <span class="pill is-poor">Suspended</span>
                                        <small class="muted">{{ $account->suspended_at->format('M j, Y') }}</small>
                                    @else
                                        <span class="pill is-good">Active</span>
                                    @endif
                                </td>
                                <td>{{ $account->quiz_attempts_count }}</td>
                                <td>{{ $account->created_at->format('M j, Y') }}</td>
                                <td>
                                    <a class="btn btn-secondary btn-sm" href="{{ route('admin.users.show', $account) }}">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                {{-- colspan spans all seven columns. --}}
                                <td colspan="7"><span class="muted">No accounts match these filters.</span></td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination controls --}}
            @if ($users->hasPages())
                <nav class="pagination" aria-label="User pages">
                    @if ($users->onFirstPage())
                        <span class="page-link is-disabled">Previous</span>
                    @else
                        <a class="page-link" href="{{ $users->previousPageUrl() }}">Previous</a>
                    @endif

                    <span class="page-status">Page {{ $users->currentPage() }} of {{ $users->lastPage() }}</span>

                    @if ($users->hasMorePages())
                        <a class="page-link" href="{{ $users->nextPageUrl() }}">Next</a>
                    @else
                        <span class="page-link is-disabled">Next</span>
                    @endif
                </nav>
            @endif
        </div>
    </section>
@endsection
