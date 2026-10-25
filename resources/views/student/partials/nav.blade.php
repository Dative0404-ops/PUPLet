<nav class="sidebar-nav">
    @if (!request()->is('admin*'))
        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <span>🏠</span> Dashboard
        </a>
        <a href="{{ route('review.index') }}" class="{{ request()->routeIs('review.index', 'review.subject') ? 'active' : '' }}">
            <span>📚</span> My Subjects
        </a>
        <a href="{{ route('review.practice') }}" class="{{ request()->routeIs('review.practice', 'review.quiz') ? 'active' : '' }}">
            <span>📝</span> Practice Quiz
        </a>
        <a href="{{ route('account.analytics') }}" class="{{ request()->routeIs('account.analytics') ? 'active' : '' }}">
            <span>📊</span> My Progress
        </a>
        <a href="{{ route('review.recommendations') }}" class="{{ request()->routeIs('review.recommendations') ? 'active' : '' }}">
            <span>💡</span> DSS Recommendations
        </a>
        <a href="{{ route('account.planner') }}" class="{{ request()->routeIs('account.planner') ? 'active' : '' }}">
            <span>📅</span> Study Planner
        </a>
        <a href="{{ route('review.bookmarks') }}" class="{{ request()->routeIs('review.bookmarks') ? 'active' : '' }}">
            <span>🔖</span> Bookmarks
        </a>
        <a href="{{ route('account.profile') }}" class="{{ request()->routeIs('account.profile') ? 'active' : '' }}">
            <span>👤</span> Profile and Settings
        </a>
        @if (auth()->user()->isAdmin())
            <a href="{{ route('admin.dashboard') }}" class="admin-link">
                <span>🛡️</span> Admin Panel
            </a>
        @endif
    @else
        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <span>🏠</span> Dashboard
        </a>
        <a href="{{ route('admin.questions.index') }}" class="{{ request()->routeIs('admin.questions.*') ? 'active' : '' }}">
            <span>📂</span> Manage Questions
        </a>
        <a href="{{ route('admin.catalog') }}" class="{{ request()->routeIs('admin.catalog*') ? 'active' : '' }}">
            <span>📚</span> Manage Subjects and Topics
        </a>
        <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
            <span>👥</span> Users
        </a>
        <a href="{{ route('admin.reports.index') }}" class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
            <span>📝</span> Reports
        </a>
        <a href="{{ route('admin.verification') }}" class="{{ request()->routeIs('admin.verification') ? 'active' : '' }}">
            <span>✅</span> Content Verification
        </a>
        <a href="{{ route('admin.dss-settings') }}" class="{{ request()->routeIs('admin.dss-settings*') ? 'active' : '' }}">
            <span>💡</span> DSS Settings
        </a>
        <a href="{{ route('admin.system-settings') }}" class="{{ request()->routeIs('admin.system-settings*') ? 'active' : '' }}">
            <span>⚙️</span> System Settings
        </a>
    @endif
</nav>
