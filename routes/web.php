<?php

// `use` imports let the routes below refer to a controller by its short name.
// Two controllers are both named CatalogController, so the Admin one is aliased below.
use App\Http\Controllers\Account\AnalyticsController;
// Shows the student's analytics charts, built from past attempts.
use App\Http\Controllers\Account\HistoryController;
// Lists the student's past quiz attempts and scores.
use App\Http\Controllers\Account\PlannerController;
// Creates, updates and deletes the student's study goals.
use App\Http\Controllers\Account\ProfileController;
// Shows and updates the student's profile.
use App\Http\Controllers\Account\SettingsController;
// Handles study preferences and password changes.
use App\Http\Controllers\Admin\CatalogController as AdminCatalogController;
// Admin add/edit/delete for subjects and topics (aliased to avoid the name clash).
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
// The admin landing page with site-wide totals.
use App\Http\Controllers\Admin\DssSettingsController as AdminDssSettingsController;
// Edits the DSS weights used by the recommender.
use App\Http\Controllers\Admin\QuestionController as AdminQuestionController;
// Create, edit, archive, restore, verify and delete questions.
use App\Http\Controllers\Admin\ReportController as AdminReportController;
// Reviews student-submitted question reports.
use App\Http\Controllers\Admin\SystemSettingsController as AdminSystemSettingsController;
// Edits the site-wide key/value settings.
use App\Http\Controllers\Admin\UserController as AdminUserController;
// Lists users and manages their accounts.
use App\Http\Controllers\Admin\VerificationController as AdminVerificationController;
// Lists questions that still need verification.
use App\Http\Controllers\Auth\AuthenticatedSessionController;
// Shows the login form, starts a session, and ends it on logout.
use App\Http\Controllers\Auth\NewPasswordController;
// Shows the new-password form and saves the new password.
use App\Http\Controllers\Auth\PasswordResetLinkController;
// Sends the password reset email.
use App\Http\Controllers\Auth\RegisteredUserController;
// Shows the registration form and saves a new student account.
use App\Http\Controllers\FeedbackController;
// Saves public contact-form messages.
use App\Http\Controllers\PageController;
// Renders the static public pages.
use App\Http\Controllers\Review\AttemptController;
// Serves a quiz attempt and shows results and mistakes.
use App\Http\Controllers\Review\BookmarkController;
// Lists saved questions and toggles a bookmark.
use App\Http\Controllers\Review\CatalogController;
// Lets signed-in students browse subjects and topics.
use App\Http\Controllers\Review\LessonController;
// Shows a review lesson and marks it completed.
use App\Http\Controllers\Review\MockExamController;
// Shows the mock exam page and starts a mock exam.
use App\Http\Controllers\Review\PracticeController;
// Chooses practice settings and starts a topic practice quiz.
use App\Http\Controllers\Review\QuestionReportController;
// Saves "report a faulty item" messages from students.
use App\Http\Controllers\Review\QuizController;
// Shows a quiz intro page and starts an attempt.
use App\Http\Controllers\Review\RecommendationController;
// Recommends what to study next (single-action controller).
use App\Http\Controllers\Student\StudentDashboardController;
// The student dashboard page (single-action controller).
// Registers every URL in the application.
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public pages
|--------------------------------------------------------------------------
*/

// Home, about, coverage and contact pages. These need no login.
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/let-coverage', [PageController::class, 'coverage'])->name('coverage');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
// Save a contact-form message.
Route::post('/contact', [FeedbackController::class, 'store'])
    // throttle:10,1 = at most 10 submissions per minute per client, to stop spam.
    ->middleware('throttle:10,1')
    ->name('contact.store');

/*
|--------------------------------------------------------------------------
| Guest authentication pages
|--------------------------------------------------------------------------
*/

// Guests only: signed-in users are redirected away, so a logged-in student
// cannot revisit the login or register pages.
Route::middleware('guest')->group(function () {
    // Register a new student account.
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        // At most 10 registrations per minute per client.
        ->middleware('throttle:10,1');

    // Sign in.
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        // 6 attempts per minute, to slow down password guessing.
        ->middleware('throttle:6,1');

    // Forgot password: show the form, then email the reset link.
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        // At most 6 reset emails per minute per client.
        ->middleware('throttle:6,1')
        ->name('password.email');

    // Reset password. The {token} comes from the emailed link and proves the request.
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

/*
|--------------------------------------------------------------------------
| Student pages
|--------------------------------------------------------------------------
*/

// Signed-in users only; guests are sent to the login page.
Route::middleware('auth')->group(function () {
    // The student dashboard. This controller has a single action, so it is
    // referenced by class name instead of [class, method].
    Route::get('/dashboard', StudentDashboardController::class)->name('dashboard');
    // Sign the user out.
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    /*
    |----------------------------------------------------------------------
    | Student account and history
    |----------------------------------------------------------------------
    */

    // prefix('account') adds /account to every URL here; name('account.') adds
    // "account." to every route name.
    Route::prefix('account')->name('account.')->group(function () {
        // Profile: view and update.
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

        // Past attempts and the analytics charts.
        Route::get('/history', [HistoryController::class, 'index'])->name('history');
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');

        // Study planner. {goal} is route model binding: Laravel loads the
        // matching StudyGoal for us.
        Route::get('/planner', [PlannerController::class, 'index'])->name('planner');
        Route::post('/planner', [PlannerController::class, 'store'])->name('planner.store');
        Route::put('/planner/{goal}', [PlannerController::class, 'update'])->name('planner.update');
        Route::post('/planner/{goal}/complete', [PlannerController::class, 'complete'])->name('planner.complete');
        Route::delete('/planner/{goal}', [PlannerController::class, 'destroy'])->name('planner.destroy');

        // Settings: study preferences and password.
        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
        Route::put('/settings/preferences', [SettingsController::class, 'updatePreferences'])->name('settings.preferences');
        Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
    });

    /*
    |----------------------------------------------------------------------
    | Reviewer and quiz pages
    |----------------------------------------------------------------------
    */

    // Browse subjects and topics.
    Route::get('/review', [CatalogController::class, 'index'])->name('review.index');
    Route::get('/review/subjects/{subject}', [CatalogController::class, 'subject'])->name('review.subject');
    Route::get('/review/topics/{topic}', [CatalogController::class, 'topic'])->name('review.topic');

    // Practice quizzes: show the setup form, then start the quiz.
    Route::get('/practice', [PracticeController::class, 'index'])->name('review.practice');
    Route::post('/practice', [PracticeController::class, 'store'])->name('review.practice.start');

    // Study recommendations (single-action controller).
    Route::get('/recommendations', RecommendationController::class)->name('review.recommendations');

    // Review lessons: read one, then mark it completed.
    Route::get('/review/lessons/{lesson}', [LessonController::class, 'show'])->name('review.lesson');
    Route::post('/review/lessons/{lesson}/complete', [LessonController::class, 'complete'])->name('review.lesson.complete');

    // Quiz intro page, then start an attempt.
    Route::get('/review/quizzes/{quiz}', [QuizController::class, 'show'])->name('review.quiz');
    Route::post('/review/quizzes/{quiz}/attempts', [QuizController::class, 'store'])->name('review.quiz.start');

    // Take an attempt, submit it, then view results, mistakes or retry.
    Route::get('/review/attempts/{attempt}', [AttemptController::class, 'show'])->name('review.attempt');
    Route::post('/review/attempts/{attempt}/submit', [AttemptController::class, 'submit'])->name('review.attempt.submit');
    Route::get('/review/attempts/{attempt}/results', [AttemptController::class, 'results'])->name('review.results');
    Route::get('/review/attempts/{attempt}/mistakes', [AttemptController::class, 'mistakes'])->name('review.mistakes');
    Route::post('/review/attempts/{attempt}/retry', [AttemptController::class, 'retry'])->name('review.retry');

    // Mock exam landing page, then start a mock exam.
    Route::get('/mock-exam', [MockExamController::class, 'show'])->name('review.mock');
    Route::post('/mock-exam', [MockExamController::class, 'store'])->name('review.mock.start');

    // Bookmarks and reporting a faulty item.
    Route::get('/bookmarks', [BookmarkController::class, 'index'])->name('review.bookmarks');
    Route::post('/review/questions/{question}/bookmark', [BookmarkController::class, 'toggle'])->name('review.bookmark.toggle');
    Route::post('/review/questions/{question}/report', [QuestionReportController::class, 'store'])->name('review.question.report');
});

/*
|--------------------------------------------------------------------------
| Admin panel
|--------------------------------------------------------------------------
*/

// prefix('admin') adds /admin to every URL; name('admin.') adds "admin." to every
// route name. The 'admin' middleware (registered in bootstrap/app.php) maps to
// app\Http\Middleware\EnsureUserIsAdmin, which returns a 404 for non-admin users.
Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
    // Admin dashboard with site-wide totals.
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    /*
    |----------------------------------------------------------------------
    | Question management
    |----------------------------------------------------------------------
    */

    // List, create, edit, archive, restore, verify and delete questions.
    Route::get('/questions', [AdminQuestionController::class, 'index'])->name('questions.index');
    Route::get('/questions/create', [AdminQuestionController::class, 'create'])->name('questions.create');
    Route::post('/questions', [AdminQuestionController::class, 'store'])->name('questions.store');
    Route::get('/questions/{question}/edit', [AdminQuestionController::class, 'edit'])->name('questions.edit');
    Route::put('/questions/{question}', [AdminQuestionController::class, 'update'])->name('questions.update');
    Route::post('/questions/{question}/archive', [AdminQuestionController::class, 'archive'])->name('questions.archive');
    Route::post('/questions/{question}/restore', [AdminQuestionController::class, 'restore'])->name('questions.restore');
    Route::post('/questions/{question}/verify', [AdminQuestionController::class, 'verify'])->name('questions.verify');
    Route::delete('/questions/{question}', [AdminQuestionController::class, 'destroy'])->name('questions.destroy');

    /*
    |----------------------------------------------------------------------
    | Subject and topic management
    |----------------------------------------------------------------------
    */

    // List subjects and topics.
    Route::get('/catalog', [AdminCatalogController::class, 'index'])->name('catalog');

    // Add, edit and delete subjects.
    Route::post('/catalog/subjects', [AdminCatalogController::class, 'storeSubject'])->name('catalog.subjects.store');
    Route::put('/catalog/subjects/{subject}', [AdminCatalogController::class, 'updateSubject'])->name('catalog.subjects.update');
    Route::delete('/catalog/subjects/{subject}', [AdminCatalogController::class, 'destroySubject'])->name('catalog.subjects.destroy');

    // Add, edit and delete topics.
    Route::post('/catalog/topics', [AdminCatalogController::class, 'storeTopic'])->name('catalog.topics.store');
    Route::put('/catalog/topics/{topic}', [AdminCatalogController::class, 'updateTopic'])->name('catalog.topics.update');
    Route::delete('/catalog/topics/{topic}', [AdminCatalogController::class, 'destroyTopic'])->name('catalog.topics.destroy');

    /*
    |----------------------------------------------------------------------
    | Content verification
    |----------------------------------------------------------------------
    */

    // Questions waiting to be verified.
    Route::get('/verification', [AdminVerificationController::class, 'index'])->name('verification');

    /*
    |----------------------------------------------------------------------
    | User management
    |----------------------------------------------------------------------
    */

    // List users and manage their accounts.
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::put('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.role');
    Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend'])->name('users.suspend');
    Route::post('/users/{user}/reactivate', [AdminUserController::class, 'reactivate'])->name('users.reactivate');

    /*
    |----------------------------------------------------------------------
    | Question reports
    |----------------------------------------------------------------------
    */

    // Review student reports: resolve or dismiss each one.
    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::post('/reports/{report}/resolve', [AdminReportController::class, 'resolve'])->name('reports.resolve');
    Route::post('/reports/{report}/dismiss', [AdminReportController::class, 'dismiss'])->name('reports.dismiss');

    /*
    |----------------------------------------------------------------------
    | DSS and system settings
    |----------------------------------------------------------------------
    */

    // DSS weighting used by the recommender.
    Route::get('/dss-settings', [AdminDssSettingsController::class, 'edit'])->name('dss-settings');
    Route::put('/dss-settings', [AdminDssSettingsController::class, 'update'])->name('dss-settings.update');

    // General site-wide settings.
    Route::get('/system-settings', [AdminSystemSettingsController::class, 'edit'])->name('system-settings');
    Route::put('/system-settings', [AdminSystemSettingsController::class, 'update'])->name('system-settings.update');
});
