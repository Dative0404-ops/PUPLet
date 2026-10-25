<?php

// Declares the namespace so Laravel can autoload this class from app/Models.
namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
// Imports the LetTrack helper used to describe and test the student's LET track.
use App\Support\LetTrack;
// Imports the factory class used by tests/seeders with HasFactory.
use Database\Factories\UserFactory;
// Imports the query Builder type used by the scope methods.
use Illuminate\Database\Eloquent\Builder;
// Provides the HasFactory trait giving User::factory() model factories.
use Illuminate\Database\Eloquent\Factories\HasFactory;
// Imports the HasMany relation type used by the relationship methods.
use Illuminate\Database\Eloquent\Relations\HasMany;
// Imports the framework Authenticatable base class handling auth concerns.
use Illuminate\Foundation\Auth\User as Authenticatable;
// Provides the Notifiable trait for sending notifications to the user.
use Illuminate\Notifications\Notifiable;

// Eloquent auth model representing an account (student or administrator).
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    // Applies the factory support and notification helpers to this model.
    use HasFactory, Notifiable;

    /**
     * A student who reviews and takes quizzes.
     */
    // Role constant for ordinary student accounts.
    public const ROLE_STUDENT = 'student';

    /**
     * A content manager who runs the admin panel.
     */
    // Role constant for administrator accounts.
    public const ROLE_ADMIN = 'admin';

    /**
     * The roles an account can hold.
     *
     * @var array<string, string>
     */
    // Maps each role constant to its human-readable label.
    public const ROLE_LABELS = [
        // Student role label.
        self::ROLE_STUDENT => 'Student',
        // Administrator role label.
        self::ROLE_ADMIN => 'Administrator',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // Mass assignment whitelist for user columns (registration and profile edits).
    protected $fillable = [
        // The account holder's full name.
        'name',
        // The login email address.
        'email',
        // The hashed password (hashed automatically by the cast below).
        'password',
        // One of the ROLE_* constants controlling admin access.
        'role',
        // The LET track: elementary (BEED) or secondary (BSED).
        'exam_type',
        // The student's major, used to filter specialization subjects.
        'specialization',
        // Preferred number of items to answer per day.
        'daily_target_items',
        // Preferred practice time limit; null follows the quiz, 0 means untimed.
        'practice_time_limit_minutes',
        // The page the student wants to land on after signing in.
        'default_landing_page',
        // Timestamp set when the account is suspended; null when active.
        'suspended_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    // Hidden attributes are excluded when the model is converted to an array/JSON.
    protected $hidden = [
        // Never expose the password hash.
        'password',
        // Never expose the remember-me token.
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    // Declares casts applied automatically when these attributes are accessed.
    protected function casts(): array
    {
        return [
            // Converts the verification timestamp into a Carbon datetime instance.
            'email_verified_at' => 'datetime',
            // Hashes the password on assignment, so plain text is never stored.
            'password' => 'hashed',
            // Forces the daily target to an integer.
            'daily_target_items' => 'integer',
            // Forces the time limit to an integer (null stays null).
            'practice_time_limit_minutes' => 'integer',
            // Converts the suspension timestamp into a Carbon datetime instance.
            'suspended_at' => 'datetime',
        ];
    }

    /**
     * Only the accounts that hold the student role.
     */
    // Local scope; called as User::students() to filter by role.
    public function scopeStudents(Builder $query): Builder
    {
        // Adds a WHERE role = 'student' clause to the query.
        return $query->where('role', self::ROLE_STUDENT);
    }

    /**
     * Only the accounts that hold the administrator role.
     */
    // Local scope; called as User::admins() to filter by role.
    public function scopeAdmins(Builder $query): Builder
    {
        // Adds a WHERE role = 'admin' clause to the query.
        return $query->where('role', self::ROLE_ADMIN);
    }

    /**
     * Whether this account may open the admin panel.
     */
    // Authorisation helper used by admin routes and middleware.
    public function isAdmin(): bool
    {
        // True when the role column equals the admin constant.
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Whether access to this account has been withdrawn.
     */
    // Suspended accounts are blocked from signing in.
    public function isSuspended(): bool
    {
        // Suspended once a suspended_at timestamp has been set.
        return $this->suspended_at !== null;
    }

    /**
     * The readable name of this account's role.
     */
    // Returns the role label, falling back to a prettified value.
    public function roleLabel(): string
    {
        // Looks up ROLE_LABELS, defaulting to a capitalised raw role.
        return self::ROLE_LABELS[$this->role] ?? ucfirst((string) $this->role);
    }

    /**
     * The quiz attempts made by this student.
     */
    // HasMany relationship: one user makes many quiz attempts.
    public function quizAttempts(): HasMany
    {
        // Matches quiz_attempts.user_id to this user's id.
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * The lessons this student has started or finished.
     */
    // HasMany relationship: one user has many lesson progress rows.
    public function lessonProgresses(): HasMany
    {
        // Matches lesson_progresses.user_id to this user's id.
        return $this->hasMany(LessonProgress::class);
    }

    /**
     * The study goals set by this student.
     */
    // HasMany relationship: one user owns many study goals.
    public function studyGoals(): HasMany
    {
        // Matches study_goals.user_id to this user's id.
        return $this->hasMany(StudyGoal::class);
    }

    /**
     * The questions this student has saved for later review.
     */
    // HasMany relationship: one user has many bookmarks.
    public function questionBookmarks(): HasMany
    {
        // Matches question_bookmarks.user_id to this user's id.
        return $this->hasMany(QuestionBookmark::class);
    }

    /**
     * The problems this account has reported about individual questions.
     */
    // HasMany relationship: one user files many question reports.
    public function questionReports(): HasMany
    {
        // Matches question_reports.user_id to this user's id.
        return $this->hasMany(QuestionReport::class);
    }

    /**
     * The questions this account has been recorded as verifying.
     */
    // HasMany relationship to questions, using the custom reviewed_by foreign key.
    public function reviewedQuestions(): HasMany
    {
        // Second argument names reviewed_by as the foreign key column.
        return $this->hasMany(Question::class, 'reviewed_by');
    }

    /**
     * The student's LET track, for example "Secondary (BSED) - English".
     */
    // Delegates label building to the LetTrack helper for reuse elsewhere.
    public function trackLabel(): string
    {
        // Builds the readable track name from the exam type and specialization.
        return LetTrack::label($this->exam_type, $this->specialization);
    }

    /**
     * Whether a subject falls inside this student's LET track. Only the
     * specialization area is restricted, and only to the chosen major.
     */
    // Used to hide specialization subjects that do not match the student's major.
    public function coversSubject(Subject $subject): bool
    {
        // General and professional education subjects are always in track.
        if ($subject->area !== 'specialization') {
            return true;
        }

        // Otherwise the exam type must involve a specialization test...
        // ...and the subject name must equal the student's chosen major.
        return LetTrack::hasSpecializationTest($this->exam_type)
            && $this->specialization === $subject->name;
    }
}
