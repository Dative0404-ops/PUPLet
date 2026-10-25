{{-- Shared admin sub-navigation partial, included at the top of every /admin page. --}}
{{-- aria-label gives assistive technology a name for this navigation landmark. --}}
<nav class="admin-nav" aria-label="Admin sections">
    {{-- route() builds the named route's URL; routeIs() is true only on the dashboard route. --}}
    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
    {{-- 'admin.questions.*' matches index/create/edit/archive/etc. so the tab stays highlighted. --}}
    <a href="{{ route('admin.questions.index') }}" class="{{ request()->routeIs('admin.questions.*') ? 'active' : '' }}">Manage Questions</a>
    {{-- Catalog page (Manage Subjects and Topics); wildcard covers its sub-routes too. --}}
    <a href="{{ route('admin.catalog') }}" class="{{ request()->routeIs('admin.catalog*') ? 'active' : '' }}">Manage Subjects and Topics</a>
    {{-- Users list/show; wildcard keeps the tab active on a single user's page. --}}
    <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">Users</a>
    {{-- Question reports queue; wildcard covers resolve/dismiss routes if they share the prefix. --}}
    <a href="{{ route('admin.reports.index') }}" class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">Reports</a>
    {{-- Content verification queue (single route, so an exact match is enough). --}}
    <a href="{{ route('admin.verification') }}" class="{{ request()->routeIs('admin.verification') ? 'active' : '' }}">Content Verification</a>
    {{-- DSS settings page; wildcard tolerates sub-routes under that name. --}}
    <a href="{{ route('admin.dss-settings') }}" class="{{ request()->routeIs('admin.dss-settings*') ? 'active' : '' }}">DSS Settings</a>
    {{-- System settings page; wildcard tolerates sub-routes under that name. --}}
    <a href="{{ route('admin.system-settings') }}" class="{{ request()->routeIs('admin.system-settings*') ? 'active' : '' }}">System Settings</a>
</nav>
