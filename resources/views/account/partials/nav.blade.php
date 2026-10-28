{{-- Account sub-navigation, included at the top of every account page. --}}
<nav class="account-nav" aria-label="Account sections">
    {{-- The tab for the page you are on gets class "active". --}}
    <a href="{{ route('account.profile') }}" class="{{ request()->routeIs('account.profile') ? 'active' : '' }}">Profile</a>
    <a href="{{ route('account.history') }}" class="{{ request()->routeIs('account.history') ? 'active' : '' }}">Results</a>
    <a href="{{ route('account.analytics') }}" class="{{ request()->routeIs('account.analytics') ? 'active' : '' }}">Progress</a>
    <a href="{{ route('account.planner') }}" class="{{ request()->routeIs('account.planner') ? 'active' : '' }}">Planner</a>
    <a href="{{ route('account.settings') }}" class="{{ request()->routeIs('account.settings') ? 'active' : '' }}">Settings</a>
</nav>
