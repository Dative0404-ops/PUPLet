{{-- Admin sub-navigation, included at the top of every /admin page. --}}
<nav class="admin-nav" aria-label="Admin sections">
    {{-- The tab for the page you are on gets class "active". The "*" wildcard also matches sub-pages. --}}
    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
    <a href="{{ route('admin.questions.index') }}" class="{{ request()->routeIs('admin.questions.*') ? 'active' : '' }}">Manage Questions</a>
    <a href="{{ route('admin.catalog') }}" class="{{ request()->routeIs('admin.catalog*') ? 'active' : '' }}">Manage Subjects and Topics</a>
    <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">Users</a>
    <a href="{{ route('admin.reports.index') }}" class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">Reports</a>
    <a href="{{ route('admin.verification') }}" class="{{ request()->routeIs('admin.verification') ? 'active' : '' }}">Content Verification</a>
    <a href="{{ route('admin.dss-settings') }}" class="{{ request()->routeIs('admin.dss-settings*') ? 'active' : '' }}">DSS Settings</a>
    <a href="{{ route('admin.system-settings') }}" class="{{ request()->routeIs('admin.system-settings*') ? 'active' : '' }}">System Settings</a>
</nav>
