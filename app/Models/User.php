<?php

// Lets Laravel find this class automatically.
namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
// The LET track helper, used for the track label and subject checks below.
use App\Support\LetTrack;
// The factory class used by tests and seeders.
use Database\Factories\UserFactory;
// The query builder, used as the type hint in the scopes below.
use Illuminate\Database\Eloquent\Builder;
// Gives the model User::factory() support.
use Illuminate\Database\Eloquent\Factories\HasFactory;
// HasMany: used by the relationship methods below.
use Illuminate\Database\Eloquent\Relations\HasMany;
// The framework base class that handles authentication.
use Illuminate\Foundation\Auth\User as Authenticatable;
// Lets the user receive notifications.
use Illuminate\Notifications\Notifiable;

// An account: a student or an administrator.
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * A student who reviews and takes quizzes.
     */
    public const ROLE_STUDENT = 'student';

    /**
     * A content manager who runs the admin panel.
     */
    public const ROLE_ADMIN = 'admin';

    /**
     * The roles an account can hold.
     *
     * @var array<string, string>
     */
    // Role constant => label shown in the UI.
    public const ROLE_LABELS = [
        self::ROLE_STUDENT => 'Student',
        self::ROLE_ADMIN => 'Administrator',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // Columns a form may set (registration and profile edits). password is hashed
    // by the cast below, and role is one of the ROLE_* constants above. For
    // practice_time_limit_minutes, null follows the quiz and 0 means untimed.
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'exam_type',
        'specialization',
        'daily_target_items',
        'practice_time_limit_minutes',
        'default_landing_page',
        'suspended_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    // These columns are left out when the model is turned into an array or JSON.
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    // hashed stores the password already hashed; the two integer casts make the
    // counts whole numbers, and the datetimes become Carbon dates.
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'daily_target_items' => 'integer',
            'practice_time_limit_minutes' => 'integer',
            'suspended_at' => 'datetime',
        ];
    }

    /**
     * Only the accounts that hold the student role.
     * Used as User::students().
     */
    public function scopeStudents(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_STUDENT);
    }

    /**
     * Only the accounts that hold the administrator role.
     * Used as User::admins().
     */
    public function scopeAdmins(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_ADMIN);
    }

    /**
     * Whether this account may open the admin panel.
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Whether access to this account has been withdrawn.
     */
    public function isSuspended(): bool
    {
        // Suspended once suspended_at is set.
        return $this->suspended_at !== null;
    }

    /**
     * The readable name of this account's role.
     */
    public function roleLabel(): string
    {
        // Use ROLE_LABELS when known, else capitalise the raw role.
        return self::ROLE_LABELS[$this->role] ?? ucfirst((string) $this->role);
    }

    /**
     * The quiz attempts made by this student.
     */
    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * The lessons this student has started or finished.
     */
    public function lessonProgresses(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    /**
     * The study goals set by this student.
     */
    public function studyGoals(): HasMany
    {
        return $this->hasMany(StudyGoal::class);
    }

    /**
     * The questions this student has saved for later review.
     */
    public function questionBookmarks(): HasMany
    {
        return $this->hasMany(QuestionBookmark::class);
    }

    /**
     * The problems this account has reported about individual questions.
     */
    public function questionReports(): HasMany
    {
        return $this->hasMany(QuestionReport::class);
    }

    /**
     * The questions this account has been recorded as verifying.
     */
    public function reviewedQuestions(): HasMany
    {
        // The foreign key column is reviewed_by, not the default user_id.
        return $this->hasMany(Question::class, 'reviewed_by');
    }

    /**
     * The student's LET track, for example "Secondary (BSED) - English".
     */
    public function trackLabel(): string
    {
        // Build the readable name from the exam type and specialization.
        return LetTrack::label($this->exam_type, $this->specialization);
    }

    /**
     * Whether a subject falls inside this student's LET track. Only the
     * specialization area is restricted, and only to the chosen major.
     */
    public function coversSubject(Subject $subject): bool
    {
        // General and professional education subjects are always in track.
        if ($subject->area !== 'specialization') {
            return true;
        }

        // Otherwise the exam type must involve a specialization test, and the
        // subject name must equal the student's chosen major.
        return LetTrack::hasSpecializationTest($this->exam_type)
            && $this->specialization === $subject->name;
    }
}
