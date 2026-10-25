{{-- Render this view inside the shared layouts/app.blade.php master layout. --}}
@extends('layouts.app')

{{-- Sets the page/browser title via the layout's @yield('title'). --}}
@section('title', 'User management')

{{-- Opens the main content block injected into the layout. --}}
@section('content')
    {{-- Hero banner for the admin page. --}}
    <section class="dashboard-hero admin-hero">
        {{-- Centring container. --}}
        <div class="container">
            {{-- Small label above the heading. --}}
            <span class="eyebrow">Admin panel</span>
            {{-- Page heading. --}}
            <h1>User management</h1>
            {{-- Explanatory sub-line. --}}
            <p class="track-line">See who is on the platform and manage their access.</p>
        </div>
    </section>

    {{-- Main body section. --}}
    <section class="section">
        {{-- Centring container. --}}
        <div class="container">
            {{-- Shared admin tab bar. --}}
            @include('admin.partials.nav')

            {{-- Four-column grid of account statistics. --}}
            <div class="grid grid-4 stat-grid">
                {{-- Card: total accounts, from the controller's $summary array. --}}
                <div class="card stat-card">
                    <span class="stat-value">{{ $summary['total'] }}</span>
                    <span class="stat-label">Accounts</span>
                </div>
                {{-- Card: number of student accounts. --}}
                <div class="card stat-card">
                    <span class="stat-value">{{ $summary['students'] }}</span>
                    <span class="stat-label">Students</span>
                </div>
                {{-- Card: number of administrator accounts. --}}
                <div class="card stat-card">
                    <span class="stat-value">{{ $summary['admins'] }}</span>
                    <span class="stat-label">Administrators</span>
                </div>
                {{-- Card: suspended accounts; is-poor colours it red when non-zero. --}}
                <div class="card stat-card">
                    <span class="stat-value {{ $summary['suspended'] > 0 ? 'is-poor' : '' }}">{{ $summary['suspended'] }}</span>
                    <span class="stat-label">Suspended</span>
                </div>
            </div>

            {{-- Toolbar holding the user filter form. --}}
            <div class="admin-toolbar">
                {{-- GET keeps the filters in the query string so the view is bookmarkable. --}}
                <form method="GET" action="{{ route('admin.users.index') }}" class="admin-filters">
                    {{-- Field group for the name/email search. --}}
                    <div class="field">
                        <label for="search">Search</label>
                        {{-- value comes from the controller's $search to preserve the current term. --}}
                        <input type="search" id="search" name="search" value="{{ $search }}" placeholder="Name or email">
                    </div>

                    {{-- Field group for the role filter. --}}
                    <div class="field">
                        <label for="role">Role</label>
                        <select id="role" name="role">
                            {{-- Empty value means no role filter. --}}
                            <option value="">Any role</option>
                            {{-- $roles is a value=>label map (student/admin). --}}
                            @foreach ($roles as $value => $label)
                                {{-- Pre-selects the role currently being filtered. --}}
                                <option value="{{ $value }}" @selected($role === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Field group for the access (active/suspended) filter. --}}
                    <div class="field">
                        <label for="state">Access</label>
                        <select id="state" name="state">
                            {{-- Empty value means no access filter. --}}
                            <option value="">Any</option>
                            {{-- Static Active option; @selected keeps it chosen when filtering by it. --}}
                            <option value="active" @selected($state === 'active')>Active</option>
                            {{-- Static Suspended option; @selected keeps it chosen when filtering by it. --}}
                            <option value="suspended" @selected($state === 'suspended')>Suspended</option>
                        </select>
                    </div>

                    {{-- Filter action buttons. --}}
                    <div class="field admin-filter-actions">
                        <button type="submit" class="btn btn-primary btn-sm">Apply filters</button>
                        {{-- Reset link returns to the unfiltered list. --}}
                        <a class="btn btn-secondary btn-sm" href="{{ route('admin.users.index') }}">Reset</a>
                    </div>
                </form>
            </div>

            {{-- Scrollable wrapper around the accounts table. --}}
            <div class="table-wrap">
                <table>
                    {{-- Column headings; the last one is blank because it holds the View button. --}}
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
                        {{-- $users is a paginator; @forelse loops it and @empty covers no results. --}}
                        @forelse ($users as $account)
                            <tr>
                                <td>
                                    {{-- Account name, bold. --}}
                                    <strong>{{ $account->name }}</strong>
                                    {{-- Email shown small and muted below the name. --}}
                                    <small class="muted">{{ $account->email }}</small>
                                </td>
                                <td>
                                    {{-- Role pill; is-good (green) only for administrators. --}}
                                    <span class="pill {{ $account->isAdmin() ? 'is-good' : '' }}">{{ $account->roleLabel() }}</span>
                                </td>
                                <td>
                                    {{-- Students have an exam type; admins may not, hence the dash fallback. --}}
                                    @if ($account->exam_type)
                                        {{-- Human-readable LET track label. --}}
                                        {{ $account->trackLabel() }}
                                    @else
                                        <span class="muted">&mdash;</span>
                                    @endif
                                </td>
                                <td>
                                    {{-- Red pill with the suspension date, or a green Active pill. --}}
                                    @if ($account->isSuspended())
                                        <span class="pill is-poor">Suspended</span>
                                        {{-- Date the account was suspended. --}}
                                        <small class="muted">{{ $account->suspended_at->format('M j, Y') }}</small>
                                    @else
                                        <span class="pill is-good">Active</span>
                                    @endif
                                </td>
                                {{-- Count of quiz attempts, eager-loaded on the account. --}}
                                <td>{{ $account->quiz_attempts_count }}</td>
                                {{-- Formatted registration date. --}}
                                <td>{{ $account->created_at->format('M j, Y') }}</td>
                                <td>
                                    {{-- Link to the detail page for this account. --}}
                                    <a class="btn btn-secondary btn-sm" href="{{ route('admin.users.show', $account) }}">View</a>
                                </td>
                            </tr>
                        {{-- Fallback row when the filters match no accounts. --}}
                        @empty
                            <tr>
                                {{-- colspan spans all seven columns. --}}
                                <td colspan="7"><span class="muted">No accounts match these filters.</span></td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Hand-written pagination controls. --}}
            @if ($users->hasPages())
                <nav class="pagination" aria-label="User pages">
                    {{-- Disabled span on the first page, otherwise a working Previous link. --}}
                    @if ($users->onFirstPage())
                        <span class="page-link is-disabled">Previous</span>
                    @else
                        {{-- URL of the preceding page of users. --}}
                        <a class="page-link" href="{{ $users->previousPageUrl() }}">Previous</a>
                    @endif

                    {{-- Current page number and total page count. --}}
                    <span class="page-status">Page {{ $users->currentPage() }} of {{ $users->lastPage() }}</span>

                    {{-- Disabled span on the last page, otherwise a working Next link. --}}
                    @if ($users->hasMorePages())
                        {{-- URL of the following page of users. --}}
                        <a class="page-link" href="{{ $users->nextPageUrl() }}">Next</a>
                    @else
                        <span class="page-link is-disabled">Next</span>
                    @endif
                </nav>
            @endif
        </div>
    </section>
@endsection
