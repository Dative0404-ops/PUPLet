<?php

// Groups this controller with the other admin-panel controllers.
namespace App\Http\Controllers\Admin;

// The base controller, providing shared controller behaviour.
use App\Http\Controllers\Controller;
// The QuestionReport model, used to show a user's reports.
use App\Models\QuestionReport;
// The QuizAttempt model, used for the per-user activity figures.
use App\Models\QuizAttempt;
// The User model being browsed and managed.
use App\Models\User;
// RedirectResponse is the return type for actions that redirect the browser.
use Illuminate\Http\RedirectResponse;
// Request is the incoming HTTP request (form input, query string, signed-in user).
use Illuminate\Http\Request;
// Rule builds the "in" rule for the role value.
use Illuminate\Validation\Rule;
// View is the return type of actions that render a Blade template.
use Illuminate\View\View;

/**
 * User management: who is on the platform, what they may do, and whether they
 * can still sign in.
 */
// Admins manage accounts (role and suspension) through this controller.
class UserController extends Controller
{
    /**
     * How many accounts are listed per page.
     */
    // Class-level constant: the page size used by the paginator in index().
    private const PER_PAGE = 20;

    /**
     * Browse and filter the accounts.
     */
    // Route action for GET /admin/users.
    public function index(Request $request): View
    {
        // Text search term, trimmed of surrounding whitespace.
        $search = trim($request->string('search')->toString());
        // Role filter, read as text.
        $role = $request->string('role')->toString();
        // Account state filter (active/suspended), read as text.
        $state = $request->string('state')->toString();

        // Start building the query for the list of accounts.
        $users = User::query()
            // When searching, match the term against name OR email (grouped).
            ->when($search !== '', fn ($query) => $query->where(function ($inner) use ($search) {
                // Match the search term against the account name...
                $inner->where('name', 'like', "%{$search}%")
                    // ...or against the account email.
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            // When a known role was chosen, filter to it.
            ->when(isset(User::ROLE_LABELS[$role]), fn ($query) => $query->where('role', $role))
            // State filter: only suspended accounts.
            ->when($state === 'suspended', fn ($query) => $query->whereNotNull('suspended_at'))
            // State filter: only active accounts.
            ->when($state === 'active', fn ($query) => $query->whereNull('suspended_at'))
            // Add an attempt count for each account (avoiding N+1 queries).
            ->withCount('quizAttempts')
            // Newest accounts first (highest id).
            ->latest('id')
            // Split into pages of PER_PAGE rows.
            ->paginate(self::PER_PAGE)
            // Keep filters in the pagination links.
            ->withQueryString();

        // Render the users index with the paginator and summary counters.
        return view('admin.users.index', [
            // The paginated accounts to loop over.
            'users' => $users,
            // Current search text, echoed back into the form.
            'search' => $search,
            // Currently selected role filter.
            'role' => $role,
            // Currently selected state filter.
            'state' => $state,
            // The available role options.
            'roles' => User::ROLE_LABELS,
            // Headline counters shown above the list.
            'summary' => [
                // Every account on the platform.
                'total' => User::count(),
                // Accounts holding the student role (local scope).
                'students' => User::students()->count(),
                // Accounts holding the administrator role (local scope).
                'admins' => User::admins()->count(),
                // Accounts whose access is currently withdrawn.
                'suspended' => User::whereNotNull('suspended_at')->count(),
            ],
        ]);
    }

    /**
     * Show one account and what it has done.
     */
    // Route action for GET /admin/users/{user}.
    public function show(User $user): View
    {
        // Total items answered across this user's completed attempts.
        $items = (int) QuizAttempt::where('user_id', $user->id)->completed()->sum('total_items');
        // Total correct answers across the same attempts.
        $correct = (int) QuizAttempt::where('user_id', $user->id)->completed()->sum('score');

        // Render the account detail view with activity data.
        return view('admin.users.show', [
            // The user plus bookmark and lesson counts, without loading the rows.
            'account' => $user->loadCount(['questionBookmarks', 'lessonProgresses']),
            // Headline figures for this account.
            'summary' => [
                // Number of completed attempts.
                'attempts' => QuizAttempt::where('user_id', $user->id)->completed()->count(),
                // Number of attempts still open.
                'open_attempts' => QuizAttempt::where('user_id', $user->id)->inProgress()->count(),
                // Total items answered.
                'items' => $items,
                // Overall accuracy percentage, or null when nothing was answered.
                'average' => $items > 0 ? (int) round($correct / $items * 100) : null,
                // Number of study goals this user has.
                'goals' => $user->studyGoals()->count(),
                // Number of question reports this user has filed.
                'reports' => $user->questionReports()->count(),
            ],
            // The eight most recent attempts, with their quiz chain loaded.
            'attempts' => QuizAttempt::query()
                // Eager-load quiz -> topic -> subject for display.
                ->with('quiz.topic.subject')
                // Restrict to this user's attempts.
                ->where('user_id', $user->id)
                // Newest first.
                ->latest('started_at')
                // Cap at eight rows.
                ->limit(8)
                // Run the query.
                ->get(),
            // The eight study goals with the latest target dates.
            'goals' => $user->studyGoals()
                // Newest target date first.
                ->orderByDesc('target_date')
                // Cap at eight rows.
                ->limit(8)
                // Run the query.
                ->get(),
            // The eight most recent reports this user filed.
            'reports' => QuestionReport::query()
                // Eager-load the reported question.
                ->with('question')
                // Restrict to this user's reports.
                ->where('user_id', $user->id)
                // Newest first.
                ->latest()
                // Cap at eight rows.
                ->limit(8)
                // Run the query.
                ->get(),
            // The available role options (for the role-change form).
            'roles' => User::ROLE_LABELS,
        ]);
    }

    /**
     * Promote a student to administrator, or move an administrator back.
     */
    // Route action for changing a user's role.
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        // An admin must not change their own role; abort 403 if they try.
        abort_if($user->id === $request->user()->id, 403, 'You cannot change your own role.');

        // Validate that the submitted role is a known one.
        $data = $request->validate([
            // Role must be one of the keys in the role label map.
            'role' => ['required', Rule::in(array_keys(User::ROLE_LABELS))],
        ]);

        // Persist the new role onto the account.
        $user->update(['role' => $data['role']]);

        // Return with a message naming the user and their new role.
        return back()->with('status', $user->name.' is now '.$user->roleLabel().'.');
    }

    /**
     * Withdraw an account's access.
     */
    // Route action for suspending an account.
    public function suspend(Request $request, User $user): RedirectResponse
    {
        // An admin must not suspend their own account; abort 403 if they try.
        abort_if($user->id === $request->user()->id, 403, 'You cannot suspend your own account.');

        // Stamp the suspension time; forceFill bypasses mass-assignment guarding.
        $user->forceFill(['suspended_at' => now()])->save();

        // Return with a message naming the suspended user.
        return back()->with('status', $user->name.' can no longer sign in.');
    }

    /**
     * Give a suspended account its access back.
     */
    // Route action for reactivating an account.
    public function reactivate(Request $request, User $user): RedirectResponse
    {
        // An admin must not reactivate their own account; abort 403 if they try.
        abort_if($user->id === $request->user()->id, 403, 'You cannot reactivate your own account.');

        // Clear the suspension time so the account can sign in again.
        $user->forceFill(['suspended_at' => null])->save();

        // Return with a message naming the reactivated user.
        return back()->with('status', $user->name.' can sign in again.');
    }
}
