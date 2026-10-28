<?php

namespace App\Http\Controllers\Admin;

// Base class with shared controller helpers.
use App\Http\Controllers\Controller;
// Used to list a user's filed reports.
use App\Models\QuestionReport;
// Used for the per-user activity figures.
use App\Models\QuizAttempt;
// The account model being browsed and managed.
use App\Models\User;
// Returned when an action redirects the browser.
use Illuminate\Http\RedirectResponse;
// The current request: form input, query string, and signed-in user.
use Illuminate\Http\Request;
// Builds the "in" rule for the role value.
use Illuminate\Validation\Rule;
// Returned when an action renders a Blade template.
use Illuminate\View\View;

/**
 * Manage accounts: who is on the platform, what they may do, and whether they
 * can still sign in.
 */
class UserController extends Controller
{
    /**
     * How many accounts are listed per page.
     */
    private const PER_PAGE = 20;

    /**
     * Browse and filter the accounts.
     */
    public function index(Request $request): View
    {
        // Read the current filters from the query string.
        $search = trim($request->string('search')->toString());
        $role = $request->string('role')->toString();
        $state = $request->string('state')->toString();

        // Get the accounts matching the chosen filters.
        $users = User::query()
            // Search the term against the name or the email.
            ->when($search !== '', fn ($query) => $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->when(isset(User::ROLE_LABELS[$role]), fn ($query) => $query->where('role', $role))
            ->when($state === 'suspended', fn ($query) => $query->whereNotNull('suspended_at'))
            ->when($state === 'active', fn ($query) => $query->whereNull('suspended_at'))
            // Count each account's quiz attempts, avoiding one query per row.
            ->withCount('quizAttempts')
            // Newest first, 20 per page, keeping the filters in the page links.
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search,
            'role' => $role,
            'state' => $state,
            'roles' => User::ROLE_LABELS,
            // Headline counters shown above the list.
            'summary' => [
                'total' => User::count(),
                // Accounts with the student role.
                'students' => User::students()->count(),
                // Accounts with the administrator role.
                'admins' => User::admins()->count(),
                // Accounts whose access is currently withdrawn.
                'suspended' => User::whereNotNull('suspended_at')->count(),
            ],
        ]);
    }

    /**
     * Show one account and what it has done.
     */
    public function show(User $user): View
    {
        // Total items answered and total correct, across completed attempts.
        $items = (int) QuizAttempt::where('user_id', $user->id)->completed()->sum('total_items');
        $correct = (int) QuizAttempt::where('user_id', $user->id)->completed()->sum('score');

        return view('admin.users.show', [
            // The account, plus counts of its bookmarks and lessons.
            'account' => $user->loadCount(['questionBookmarks', 'lessonProgresses']),
            // Headline figures for this account.
            'summary' => [
                // Completed attempts, and attempts still open.
                'attempts' => QuizAttempt::where('user_id', $user->id)->completed()->count(),
                'open_attempts' => QuizAttempt::where('user_id', $user->id)->inProgress()->count(),
                'items' => $items,
                // Overall accuracy, or null when nothing was answered.
                'average' => $items > 0 ? (int) round($correct / $items * 100) : null,
                'goals' => $user->studyGoals()->count(),
                'reports' => $user->questionReports()->count(),
            ],
            // The eight most recent attempts, newest first, with the quiz chain loaded.
            'attempts' => QuizAttempt::query()
                ->with('quiz.topic.subject')
                ->where('user_id', $user->id)
                ->latest('started_at')
                ->limit(8)
                ->get(),
            // The eight goals with the latest target dates.
            'goals' => $user->studyGoals()
                ->orderByDesc('target_date')
                ->limit(8)
                ->get(),
            // The eight most recent reports this user filed.
            'reports' => QuestionReport::query()
                ->with('question')
                ->where('user_id', $user->id)
                ->latest()
                ->limit(8)
                ->get(),
            'roles' => User::ROLE_LABELS,
        ]);
    }

    /**
     * Promote a student to administrator, or move an administrator back.
     */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        // An admin cannot change their own role.
        abort_if($user->id === $request->user()->id, 403, 'You cannot change your own role.');

        $data = $request->validate([
            // Role must be one of the known roles.
            'role' => ['required', Rule::in(array_keys(User::ROLE_LABELS))],
        ]);

        $user->update(['role' => $data['role']]);

        return back()->with('status', $user->name.' is now '.$user->roleLabel().'.');
    }

    /**
     * Withdraw an account's access.
     */
    public function suspend(Request $request, User $user): RedirectResponse
    {
        // An admin cannot suspend their own account.
        abort_if($user->id === $request->user()->id, 403, 'You cannot suspend your own account.');

        // Stamp the suspension time. forceFill skips mass-assignment guarding.
        $user->forceFill(['suspended_at' => now()])->save();

        return back()->with('status', $user->name.' can no longer sign in.');
    }

    /**
     * Give a suspended account its access back.
     */
    public function reactivate(Request $request, User $user): RedirectResponse
    {
        // An admin cannot reactivate their own account.
        abort_if($user->id === $request->user()->id, 403, 'You cannot reactivate your own account.');

        // Clear the suspension time so the account can sign in again.
        $user->forceFill(['suspended_at' => null])->save();

        return back()->with('status', $user->name.' can sign in again.');
    }
}
