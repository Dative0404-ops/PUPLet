<?php

// `use` imports let each route below refer to a controller class by its short name.
// Two controllers are both named CatalogController, so the Admin one is aliased below.
// AnalyticsController: renders the student's analytics page (charts from past attempts).
use App\Http\Controllers\Account\AnalyticsController;
// HistoryController: lists the student's previous quiz attempts with their scores.
use App\Http\Controllers\Account\HistoryController;
// PlannerController: study planner actions (create, update, complete, delete StudyGoals).
use App\Http\Controllers\Account\PlannerController;
// ProfileController: shows and updates the student's profile details.
use App\Http\Controllers\Account\ProfileController;
// SettingsController: handles study preferences and password changes.
use App\Http\Controllers\Account\SettingsController;
// AdminCatalogController: admin CRUD for subjects and topics (aliased to avoid a name clash).
use App\Http\Controllers\Admin\CatalogController as AdminCatalogController;
// AdminDashboardController: admin landing page with site-wide statistics.
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
// AdminDssSettingsController: edits the DSS weighting used by the recommender.
use App\Http\Controllers\Admin\DssSettingsController as AdminDssSettingsController;
// AdminQuestionController: create, edit, archive, restore, verify and delete questions.
use App\Http\Controllers\Admin\QuestionController as AdminQuestionController;
// AdminReportController: reviews student-submitted question reports.
use App\Http\Controllers\Admin\ReportController as AdminReportController;
// AdminSystemSettingsController: edits general site-wide key/value settings.
use App\Http\Controllers\Admin\SystemSettingsController as AdminSystemSettingsController;
// AdminUserController: list, view, change role, suspend and reactivate accounts.
use App\Http\Controllers\Admin\UserController as AdminUserController;
// AdminVerificationController: queues questions that still need verification.
use App\Http\Controllers\Admin\VerificationController as AdminVerificationController;
// AuthenticatedSessionController: shows login form, starts a session, destroys it on logout.
use App\Http\Controllers\Auth\AuthenticatedSessionController;
// NewPasswordController: shows the new-password form and saves the new password.
use App\Http\Controllers\Auth\NewPasswordController;
// PasswordResetLinkController: sends the password reset link email.
use App\Http\Controllers\Auth\PasswordResetLinkController;
// RegisteredUserController: shows the registration form and stores a new student account.
use App\Http\Controllers\Auth\RegisteredUserController;
// FeedbackController: stores public contact-form messages in the feedbacks table.
use App\Http\Controllers\FeedbackController;
// PageController: renders the static public pages (home, about, coverage, contact).
use App\Http\Controllers\PageController;
// AttemptController: serves a quiz attempt, records answers, shows results and mistakes.
use App\Http\Controllers\Review\AttemptController;
// BookmarkController: lists bookmarked questions and toggles a bookmark on or off.
use App\Http\Controllers\Review\BookmarkController;
// CatalogController: reviewer browsing of subjects and topics for signed-in students.
use App\Http\Controllers\Review\CatalogController;
// LessonController: shows a review lesson and marks it as completed.
use App\Http\Controllers\Review\LessonController;
// MockExamController: shows the mock exam page and starts a new mock exam attempt.
use App\Http\Controllers\Review\MockExamController;
// PracticeController: chooses practice settings and starts a topic practice quiz.
use App\Http\Controllers\Review\PracticeController;
// QuestionReportController: stores "report a faulty item" submissions from students.
use App\Http\Controllers\Review\QuestionReportController;
// QuizController: shows a quiz intro page and starts a new attempt for it.
use App\Http\Controllers\Review\QuizController;
// RecommendationController: recommends what to study next (invokable controller).
use App\Http\Controllers\Review\RecommendationController;
// StudentDashboardController: the student dashboard page (invokable controller).
use App\Http\Controllers\Student\StudentDashboardController;
// Route facade: the entry point for registering every URL in the application.
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public pages
|--------------------------------------------------------------------------
*/

// GET / -> PageController@home (route name: home). Public, no middleware.
Route::get('/', [PageController::class, 'home'])->name('home');
// GET /about -> PageController@about (route name: about). Public static page.
Route::get('/about', [PageController::class, 'about'])->name('about');
// GET /let-coverage -> PageController@coverage (route name: coverage). Public LET coverage page.
Route::get('/let-coverage', [PageController::class, 'coverage'])->name('coverage');
// GET /contact -> PageController@contact (route name: contact). Public contact form page.
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
// POST /contact -> FeedbackController@store (route name: contact.store). Saves the form message.
Route::post('/contact', [FeedbackController::class, 'store'])
    // throttle:10,1 = at most 10 submissions per minute per client, to stop spam.
    ->middleware('throttle:10,1')
    ->name('contact.store');

/*
|--------------------------------------------------------------------------
| Guest authentication pages
|--------------------------------------------------------------------------
*/

// 'guest' middleware: only visitors WITHOUT a session can use these pages; signed-in
// users are redirected away, so a logged-in student cannot revisit login or register.
Route::middleware('guest')->group(function () {
    // GET /register -> RegisteredUserController@create (route name: register). Show form.
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    // POST /register -> RegisteredUserController@store. Creates the student account.
    Route::post('/register', [RegisteredUserController::class, 'store'])
        // Rate limit: at most 10 registration posts per minute per client.
        ->middleware('throttle:10,1');

    // GET /login -> AuthenticatedSessionController@create (route name: login). Show form.
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    // POST /login -> AuthenticatedSessionController@store. Checks credentials, starts session.
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        // Rate limit: 6 attempts per minute, slowing down password guessing.
        ->middleware('throttle:6,1');

    // GET /forgot-password -> PasswordResetLinkController@create (name: password.request).
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    // POST /forgot-password -> PasswordResetLinkController@store (name: password.email).
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        // Rate limit: 6 reset emails per minute per client.
        ->middleware('throttle:6,1')
        ->name('password.email');

    // GET /reset-password/{token} -> NewPasswordController@create (name: password.reset).
    // {token} is the signed value emailed to the user; it proves the reset request.
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    // POST /reset-password -> NewPasswordController@store (name: password.store). Saves it.
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

/*
|--------------------------------------------------------------------------
| Student pages
|--------------------------------------------------------------------------
*/

// 'auth' middleware: only signed-in users reach these routes; guests go to the login page.
Route::middleware('auth')->group(function () {
    // GET /dashboard -> StudentDashboardController (invokable) (route name: dashboard).
    // A single-action controller is referenced by class name, not an [class, method] array.
    Route::get('/dashboard', StudentDashboardController::class)->name('dashboard');
    // POST /logout -> AuthenticatedSessionController@destroy (route name: logout).
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    /*
    |----------------------------------------------------------------------
    | Student account and history
    |----------------------------------------------------------------------
    */

    // prefix('account') prepends /account to every URL in this group.
    // name('account.') prepends "account." to every route name in this group.
    Route::prefix('account')->name('account.')->group(function () {
        // GET /account/profile -> ProfileController@edit (route name: account.profile).
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
        // PUT /account/profile -> ProfileController@update (name: account.profile.update).
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

        // GET /account/history -> HistoryController@index (name: account.history).
        Route::get('/history', [HistoryController::class, 'index'])->name('history');
        // GET /account/analytics -> AnalyticsController@index (name: account.analytics).
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');

        // GET /account/planner -> PlannerController@index (name: account.planner).
        Route::get('/planner', [PlannerController::class, 'index'])->name('planner');
        // POST /account/planner -> PlannerController@store (name: account.planner.store).
        Route::post('/planner', [PlannerController::class, 'store'])->name('planner.store');
        // PUT /account/planner/{goal} -> PlannerController@update (name: account.planner.update).
        // {goal} is route model binding: Laravel loads the matching StudyGoal automatically.
        Route::put('/planner/{goal}', [PlannerController::class, 'update'])->name('planner.update');
        // POST /account/planner/{goal}/complete -> PlannerController@complete (name: ...complete).
        Route::post('/planner/{goal}/complete', [PlannerController::class, 'complete'])->name('planner.complete');
        // DELETE /account/planner/{goal} -> PlannerController@destroy (name: account.planner.destroy).
        Route::delete('/planner/{goal}', [PlannerController::class, 'destroy'])->name('planner.destroy');

        // GET /account/settings -> SettingsController@edit (name: account.settings).
        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
        // PUT /account/settings/preferences -> SettingsController@updatePreferences (name: ...preferences).
        Route::put('/settings/preferences', [SettingsController::class, 'updatePreferences'])->name('settings.preferences');
        // PUT /account/settings/password -> SettingsController@updatePassword (name: ...password).
        Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
    });

    /*
    |----------------------------------------------------------------------
    | Reviewer and quiz pages
    |----------------------------------------------------------------------
    */

    // GET /review -> CatalogController@index (name: review.index). Lists all subjects.
    Route::get('/review', [CatalogController::class, 'index'])->name('review.index');
    // GET /review/subjects/{subject} -> CatalogController@subject (name: review.subject).
    Route::get('/review/subjects/{subject}', [CatalogController::class, 'subject'])->name('review.subject');
    // GET /review/topics/{topic} -> CatalogController@topic (name: review.topic).
    Route::get('/review/topics/{topic}', [CatalogController::class, 'topic'])->name('review.topic');

    // GET /practice -> PracticeController@index (name: review.practice). Practice setup form.
    Route::get('/practice', [PracticeController::class, 'index'])->name('review.practice');
    // POST /practice -> PracticeController@store (name: review.practice.start). Starts it.
    Route::post('/practice', [PracticeController::class, 'store'])->name('review.practice.start');

    // GET /recommendations -> RecommendationController (invokable) (name: review.recommendations).
    Route::get('/recommendations', RecommendationController::class)->name('review.recommendations');

    // GET /review/lessons/{lesson} -> LessonController@show (name: review.lesson).
    Route::get('/review/lessons/{lesson}', [LessonController::class, 'show'])->name('review.lesson');
    // POST /review/lessons/{lesson}/complete -> LessonController@complete (name: ...complete).
    Route::post('/review/lessons/{lesson}/complete', [LessonController::class, 'complete'])->name('review.lesson.complete');

    // GET /review/quizzes/{quiz} -> QuizController@show (name: review.quiz). Quiz intro page.
    Route::get('/review/quizzes/{quiz}', [QuizController::class, 'show'])->name('review.quiz');
    // POST /review/quizzes/{quiz}/attempts -> QuizController@store (name: review.quiz.start).
    Route::post('/review/quizzes/{quiz}/attempts', [QuizController::class, 'store'])->name('review.quiz.start');

    // GET /review/attempts/{attempt} -> AttemptController@show (name: review.attempt). Take it.
    Route::get('/review/attempts/{attempt}', [AttemptController::class, 'show'])->name('review.attempt');
    // POST /review/attempts/{attempt}/submit -> AttemptController@submit (name: ...submit).
    Route::post('/review/attempts/{attempt}/submit', [AttemptController::class, 'submit'])->name('review.attempt.submit');
    // GET /review/attempts/{attempt}/results -> AttemptController@results (name: review.results).
    Route::get('/review/attempts/{attempt}/results', [AttemptController::class, 'results'])->name('review.results');
    // GET /review/attempts/{attempt}/mistakes -> AttemptController@mistakes (name: review.mistakes).
    Route::get('/review/attempts/{attempt}/mistakes', [AttemptController::class, 'mistakes'])->name('review.mistakes');
    // POST /review/attempts/{attempt}/retry -> AttemptController@retry (name: review.retry).
    Route::post('/review/attempts/{attempt}/retry', [AttemptController::class, 'retry'])->name('review.retry');

    // GET /mock-exam -> MockExamController@show (name: review.mock). Mock exam landing page.
    Route::get('/mock-exam', [MockExamController::class, 'show'])->name('review.mock');
    // POST /mock-exam -> MockExamController@store (name: review.mock.start). Starts the mock exam.
    Route::post('/mock-exam', [MockExamController::class, 'store'])->name('review.mock.start');

    // GET /bookmarks -> BookmarkController@index (name: review.bookmarks). Saved questions.
    Route::get('/bookmarks', [BookmarkController::class, 'index'])->name('review.bookmarks');
    // POST /review/questions/{question}/bookmark -> BookmarkController@toggle (name: ...toggle).
    Route::post('/review/questions/{question}/bookmark', [BookmarkController::class, 'toggle'])->name('review.bookmark.toggle');
    // POST /review/questions/{question}/report -> QuestionReportController@store (name: ...report).
    Route::post('/review/questions/{question}/report', [QuestionReportController::class, 'store'])->name('review.question.report');
});

/*
|--------------------------------------------------------------------------
| Admin panel
|--------------------------------------------------------------------------
*/

// prefix('admin') = every URL starts with /admin.
// name('admin.') = every route name starts with "admin.".
// middleware('admin') = the "admin" alias registered in bootstrap/app.php; it maps to
// app\Http\Middleware\EnsureUserIsAdmin, which returns a 404 for non-admin users.
Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
    // GET /admin -> AdminDashboardController@index (route name: admin.dashboard).
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    /*
    |----------------------------------------------------------------------
    | Question management
    |----------------------------------------------------------------------
    */

    // GET /admin/questions -> index (name: admin.questions.index). Lists questions.
    Route::get('/questions', [AdminQuestionController::class, 'index'])->name('questions.index');
    // GET /admin/questions/create -> create (name: admin.questions.create). New question form.
    Route::get('/questions/create', [AdminQuestionController::class, 'create'])->name('questions.create');
    // POST /admin/questions -> store (name: admin.questions.store). Saves a new question.
    Route::post('/questions', [AdminQuestionController::class, 'store'])->name('questions.store');
    // GET /admin/questions/{question}/edit -> edit (name: admin.questions.edit). Edit form.
    Route::get('/questions/{question}/edit', [AdminQuestionController::class, 'edit'])->name('questions.edit');
    // PUT /admin/questions/{question} -> update (name: admin.questions.update). Saves edits.
    Route::put('/questions/{question}', [AdminQuestionController::class, 'update'])->name('questions.update');
    // POST /admin/questions/{question}/archive -> archive (name: admin.questions.archive).
    Route::post('/questions/{question}/archive', [AdminQuestionController::class, 'archive'])->name('questions.archive');
    // POST /admin/questions/{question}/restore -> restore (name: admin.questions.restore).
    Route::post('/questions/{question}/restore', [AdminQuestionController::class, 'restore'])->name('questions.restore');
    // POST /admin/questions/{question}/verify -> verify (name: admin.questions.verify).
    Route::post('/questions/{question}/verify', [AdminQuestionController::class, 'verify'])->name('questions.verify');
    // DELETE /admin/questions/{question} -> destroy (name: admin.questions.destroy).
    Route::delete('/questions/{question}', [AdminQuestionController::class, 'destroy'])->name('questions.destroy');

    /*
    |----------------------------------------------------------------------
    | Subject and topic management
    |----------------------------------------------------------------------
    */

    // GET /admin/catalog -> AdminCatalogController@index (name: admin.catalog).
    Route::get('/catalog', [AdminCatalogController::class, 'index'])->name('catalog');

    // POST /admin/catalog/subjects -> storeSubject (name: admin.catalog.subjects.store).
    Route::post('/catalog/subjects', [AdminCatalogController::class, 'storeSubject'])->name('catalog.subjects.store');
    // PUT /admin/catalog/subjects/{subject} -> updateSubject (name: ...subjects.update).
    Route::put('/catalog/subjects/{subject}', [AdminCatalogController::class, 'updateSubject'])->name('catalog.subjects.update');
    // DELETE /admin/catalog/subjects/{subject} -> destroySubject (name: ...subjects.destroy).
    Route::delete('/catalog/subjects/{subject}', [AdminCatalogController::class, 'destroySubject'])->name('catalog.subjects.destroy');

    // POST /admin/catalog/topics -> storeTopic (name: admin.catalog.topics.store).
    Route::post('/catalog/topics', [AdminCatalogController::class, 'storeTopic'])->name('catalog.topics.store');
    // PUT /admin/catalog/topics/{topic} -> updateTopic (name: admin.catalog.topics.update).
    Route::put('/catalog/topics/{topic}', [AdminCatalogController::class, 'updateTopic'])->name('catalog.topics.update');
    // DELETE /admin/catalog/topics/{topic} -> destroyTopic (name: admin.catalog.topics.destroy).
    Route::delete('/catalog/topics/{topic}', [AdminCatalogController::class, 'destroyTopic'])->name('catalog.topics.destroy');

    /*
    |----------------------------------------------------------------------
    | Content verification
    |----------------------------------------------------------------------
    */

    // GET /admin/verification -> AdminVerificationController@index (name: admin.verification).
    Route::get('/verification', [AdminVerificationController::class, 'index'])->name('verification');

    /*
    |----------------------------------------------------------------------
    | User management
    |----------------------------------------------------------------------
    */

    // GET /admin/users -> AdminUserController@index (name: admin.users.index). User list.
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    // GET /admin/users/{user} -> show (name: admin.users.show). One user's detail page.
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    // PUT /admin/users/{user}/role -> updateRole (name: admin.users.role). Change the role.
    Route::put('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.role');
    // POST /admin/users/{user}/suspend -> suspend (name: admin.users.suspend). Sets suspended_at.
    Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend'])->name('users.suspend');
    // POST /admin/users/{user}/reactivate -> reactivate (name: admin.users.reactivate).
    Route::post('/users/{user}/reactivate', [AdminUserController::class, 'reactivate'])->name('users.reactivate');

    /*
    |----------------------------------------------------------------------
    | Question reports
    |----------------------------------------------------------------------
    */

    // GET /admin/reports -> AdminReportController@index (name: admin.reports.index). Report list.
    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
    // POST /admin/reports/{report}/resolve -> resolve (name: admin.reports.resolve).
    Route::post('/reports/{report}/resolve', [AdminReportController::class, 'resolve'])->name('reports.resolve');
    // POST /admin/reports/{report}/dismiss -> dismiss (name: admin.reports.dismiss).
    Route::post('/reports/{report}/dismiss', [AdminReportController::class, 'dismiss'])->name('reports.dismiss');

    /*
    |----------------------------------------------------------------------
    | DSS and system settings
    |----------------------------------------------------------------------
    */

    // GET /admin/dss-settings -> AdminDssSettingsController@edit (name: admin.dss-settings).
    Route::get('/dss-settings', [AdminDssSettingsController::class, 'edit'])->name('dss-settings');
    // PUT /admin/dss-settings -> update (name: admin.dss-settings.update). Saves DSS weights.
    Route::put('/dss-settings', [AdminDssSettingsController::class, 'update'])->name('dss-settings.update');

    // GET /admin/system-settings -> AdminSystemSettingsController@edit (name: admin.system-settings).
    Route::get('/system-settings', [AdminSystemSettingsController::class, 'edit'])->name('system-settings');
    // PUT /admin/system-settings -> update (name: admin.system-settings.update). Saves settings.
    Route::put('/system-settings', [AdminSystemSettingsController::class, 'update'])->name('system-settings.update');
});
