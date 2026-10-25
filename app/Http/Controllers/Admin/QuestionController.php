<?php

// Groups this controller with the other admin-panel controllers.
namespace App\Http\Controllers\Admin;

// The base controller, providing shared controller behaviour.
use App\Http\Controllers\Controller;
// The Question model being managed here.
use App\Models\Question;
// The Subject model, used to build the categorisation dropdowns.
use App\Models\Subject;
// RedirectResponse is the return type for actions that redirect the browser.
use Illuminate\Http\RedirectResponse;
// Request is the incoming HTTP request (form input, query string, signed-in user).
use Illuminate\Http\Request;
// Collection is Eloquent's array-like result wrapper, used as a return type.
use Illuminate\Support\Collection;
// Rule builds validation rules such as "in" for allowed values.
use Illuminate\Validation\Rule;
// ValidationException lets us raise our own validation errors manually.
use Illuminate\Validation\ValidationException;
// View is the return type of actions that render a Blade template.
use Illuminate\View\View;

/**
 * Question management: writing, categorising, archiving, and verifying items.
 */
// Admins manage the question bank through this controller.
class QuestionController extends Controller
{
    /**
     * How many questions are listed per page.
     */
    // Class-level constant: the page size used by the paginator in index().
    private const PER_PAGE = 20;

    /**
     * The filterable states of the question bank.
     *
     * @var array<string, string>
     */
    // Maps the verification filter keys to the labels shown in the UI.
    private const VERIFICATION_FILTERS = [
        // Filter for questions that have been verified.
        'verified' => 'Verified',
        // Filter for questions that have not been verified.
        'unverified' => 'Not verified',
    ];

    /**
     * Browse and filter the question bank.
     */
    // Route action for GET /admin/questions.
    public function index(Request $request): View
    {
        // Text search term, trimmed of surrounding whitespace.
        $search = trim($request->string('search')->toString());
        // Subject id filter, cast to int (0 means "no subject chosen").
        $subject = (int) $request->integer('subject');
        // Topic id filter, cast to int (0 means "no topic chosen").
        $topic = (int) $request->integer('topic');
        // Status filter (published/archived/draft), read as text.
        $status = $request->string('status')->toString();
        // Verification filter (verified/unverified), read as text.
        $verification = $request->string('verification')->toString();

        // Start building the query for the list of questions.
        $questions = Question::query()
            // Eager-load each question's topic and subject, plus its reviewer.
            ->with(['topic.subject', 'reviewer'])
            // Add an "open reports" count for each question (scope filters to open ones).
            ->withCount(['reports' => fn ($query) => $query->open()])
            // When a search term is present, match it against the prompt text.
            ->when($search !== '', fn ($query) => $query->where('prompt', 'like', "%{$search}%"))
            // When a topic was chosen, filter to that topic directly.
            ->when($topic > 0, fn ($query) => $query->where('topic_id', $topic))
            // When only a subject was chosen, filter by the subject via the topic.
            ->when($topic === 0 && $subject > 0, fn ($query) => $query->whereHas(
                // Relation to traverse: each question belongs to a topic.
                'topic',
                // Keep questions whose topic belongs to the chosen subject.
                fn ($inner) => $inner->where('subject_id', $subject),
            ))
            // When a known status was chosen, filter to it.
            ->when(isset(Question::STATUS_LABELS[$status]), fn ($query) => $query->where('status', $status))
            // Verification filter: only verified questions.
            ->when($verification === 'verified', fn ($query) => $query->whereNotNull('verified_at'))
            // Verification filter: only not-yet-verified questions.
            ->when($verification === 'unverified', fn ($query) => $query->whereNull('verified_at'))
            // Newest questions first (highest id).
            ->latest('id')
            // Split into pages of PER_PAGE rows.
            ->paginate(self::PER_PAGE)
            // Keep filters in the pagination links.
            ->withQueryString();

        // Render the questions index with the paginator and filter options.
        return view('admin.questions.index', [
            // The paginated questions to loop over.
            'questions' => $questions,
            // Current search text, echoed back into the form.
            'search' => $search,
            // Currently selected subject id.
            'subjectId' => $subject,
            // Currently selected topic id.
            'topicId' => $topic,
            // Currently selected status.
            'status' => $status,
            // Currently selected verification filter.
            'verification' => $verification,
            // The subject/topic tree for the filter dropdowns.
            'subjects' => $this->subjects(),
            // All possible statuses for the status dropdown.
            'statuses' => Question::STATUS_LABELS,
            // The verification filter options.
            'verifications' => self::VERIFICATION_FILTERS,
        ]);
    }

    /**
     * The form for a brand new question.
     */
    // Route action for GET /admin/questions/create.
    public function create(): View
    {
        // Render the shared form with no existing question (creating a new one).
        return view('admin.questions.form', $this->formData(null));
    }

    /**
     * Write a new question into the bank.
     */
    // Route action for POST /admin/questions.
    public function store(Request $request): RedirectResponse
    {
        // Validate and normalise the payload, then insert a new question row.
        Question::create($this->payload($request));

        // Redirect to the questions list with a success message.
        return redirect()
            // Named route for the question list.
            ->route('admin.questions.index')
            // Flash message for the next request.
            ->with('status', 'The question has been added.');
    }

    /**
     * The form for editing an existing question.
     */
    // Route action for GET /admin/questions/{question}/edit.
    public function edit(Question $question): View
    {
        // Eager-load the relations the form needs before rendering.
        $question->load('topic.subject', 'reviewer', 'reports');

        // Render the shared form pre-filled with this question.
        return view('admin.questions.form', $this->formData($question));
    }

    /**
     * Save changes to a question. Editing the content clears its verification,
     * because the item that was signed off is no longer the item shown.
     */
    // Route action for PUT/PATCH /admin/questions/{question}.
    public function update(Request $request, Question $question): RedirectResponse
    {
        // Fill (but do not yet save) the model with the validated payload.
        $question->fill($this->payload($request));

        // Detect whether any content field actually changed on the model.
        $contentChanged = $question->isDirty([
            // The question text itself.
            'prompt', 'options', 'correct_option', 'explanation', 'reference', 'source',
        ]);

        // If the content changed, revoke the previous verification.
        if ($contentChanged) {
            // forceFill bypasses mass-assignment guarding (these are not fillable).
            $question->forceFill(['reviewed_by' => null, 'verified_at' => null]);
        }

        // Persist the model to the database.
        $question->save();

        // Redirect back to the edit form with a message.
        return redirect()
            // Named route for editing this question.
            ->route('admin.questions.edit', $question)
            // Different message depending on whether verification was cleared.
            ->with('status', $contentChanged
                ? 'The question has been updated. It needs to be verified again.'
                : 'The question has been updated.');
    }

    /**
     * Withdraw a question from the reviewer without deleting it.
     */
    // Route action for archiving a question.
    public function archive(Question $question): RedirectResponse
    {
        // Mark the question as archived so it is hidden from students.
        $question->update(['status' => Question::STATUS_ARCHIVED]);

        // Return to the previous page with a success message.
        return back()->with('status', 'The question has been archived.');
    }

    /**
     * Put an archived or drafted question back into circulation.
     */
    // Route action for restoring a question to published.
    public function restore(Question $question): RedirectResponse
    {
        // Set the question back to the published status.
        $question->update(['status' => Question::STATUS_PUBLISHED]);

        // Return to the previous page with a success message.
        return back()->with('status', 'The question is published again.');
    }

    /**
     * Record that an administrator has checked this question.
     */
    // Route action for marking a question as verified.
    public function verify(Request $request, Question $question): RedirectResponse
    {
        // Record the current admin as reviewer and stamp the verification time.
        $question->forceFill([
            // The id of the administrator performing the verification.
            'reviewed_by' => $request->user()->id,
            // The moment the verification happened.
            'verified_at' => now(),
        ])->save();

        // Return to the previous page with a success message.
        return back()->with('status', 'The question has been marked as verified.');
    }

    /**
     * Remove a question for good.
     */
    // Route action for DELETE /admin/questions/{question}.
    public function destroy(Question $question): RedirectResponse
    {
        // Delete the question row from the database.
        $question->delete();

        // Redirect to the question list with a confirmation message.
        return redirect()
            // Named route for the question list.
            ->route('admin.questions.index')
            // Flash message for the next request.
            ->with('status', 'The question has been deleted.');
    }

    /**
     * The subjects with their topics, for the categorisation controls.
     *
     * @return Collection<int, Subject>
     */
    // Loads the subject/topic tree used by the form and filter dropdowns.
    private function subjects(): Collection
    {
        // Query subjects, eager-loading their ordered topics.
        return Subject::query()
            // Eager-load topics, each ordered for display.
            ->with(['topics' => fn ($query) => $query->orderBy('sort_order')->orderBy('name')])
            // Order the subjects themselves by manual position...
            ->orderBy('sort_order')
            // ...then alphabetically as a tie-breaker.
            ->orderBy('name')
            // Run the query and return the collection.
            ->get();
    }

    /**
     * Everything the question form needs.
     *
     * @return array<string, mixed>
     */
    // Builds the shared data passed to the create/edit form.
    private function formData(?Question $question): array
    {
        // Return the view data array.
        return [
            // The question being edited, or null when creating a new one.
            'question' => $question,
            // The subject/topic options for the categorisation controls.
            'subjects' => $this->subjects(),
            // The status options for the status dropdown.
            'statuses' => Question::STATUS_LABELS,
        ];
    }

    /**
     * Validate the submitted question and normalise its answer choices.
     *
     * @return array<string, mixed>
     */
    // Validates a question submission and cleans up the answer-option list.
    private function payload(Request $request): array
    {
        // Validate the raw submission; only the listed keys are returned.
        $data = $request->validate([
            // The topic this question belongs to must exist.
            'topic_id' => ['required', 'integer', 'exists:topics,id'],
            // The question text is required and at most 2000 characters.
            'prompt' => ['required', 'string', 'max:2000'],
            // Options must be an array with between 2 and 6 entries.
            'options' => ['required', 'array', 'min:2', 'max:6'],
            // Each individual option may be blank (blank rows are dropped below).
            'options.*' => ['nullable', 'string', 'max:1000'],
            // Index of the correct option; must be a whole number at least 0.
            'correct_option' => ['required', 'integer', 'min:0'],
            // Optional explanation text.
            'explanation' => ['nullable', 'string', 'max:2000'],
            // Optional reference (e.g. page or citation).
            'reference' => ['nullable', 'string', 'max:500'],
            // Optional source label.
            'source' => ['nullable', 'string', 'max:150'],
            // Status must be one of the known question statuses.
            'status' => ['required', Rule::in(array_keys(Question::STATUS_LABELS))],
            // Optional manual position.
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        // The index the admin marked as correct, in the submitted option list.
        $chosen = (int) $data['correct_option'];
        // Will hold the cleaned list of non-empty options.
        $options = [];
        // Will hold the index of the correct option within the cleaned list.
        $correctIndex = null;

        // Walk the submitted options, reindexing them from zero.
        foreach (array_values($data['options']) as $index => $text) {
            // Trim the option text and force it to be a string.
            $text = trim((string) $text);

            // Skip option rows the admin left blank.
            if ($text === '') {
                // Move on to the next option.
                continue;
            }

            // If this submitted row is the chosen correct answer...
            if ($index === $chosen) {
                // ...remember where it landed in the cleaned list.
                $correctIndex = count($options);
            }

            // Append the cleaned option text to the list.
            $options[] = $text;
        }

        // A question needs at least two real choices to be usable.
        if (count($options) < 2) {
            // Abort with a validation error on the options field.
            throw ValidationException::withMessages([
                // Message shown against the options input.
                'options' => 'Write at least two answer choices.',
            ]);
        }

        // The chosen correct option must still exist after blank rows are dropped.
        if ($correctIndex === null) {
            // Abort with a validation error on the correct_option field.
            throw ValidationException::withMessages([
                // Message shown against the correct-answer picker.
                'correct_option' => 'Choose which answer choice is the correct one.',
            ]);
        }

        // Return the normalised data ready for create()/fill().
        return [
            // The owning topic id, cast to int.
            'topic_id' => (int) $data['topic_id'],
            // The trimmed question text.
            'prompt' => trim($data['prompt']),
            // The cleaned list of answer choices.
            'options' => $options,
            // The index of the correct choice within the cleaned list.
            'correct_option' => $correctIndex,
            // Explanation text, or null when omitted.
            'explanation' => $data['explanation'] ?? null,
            // Reference text, or null when omitted.
            'reference' => $data['reference'] ?? null,
            // Source label, or null when omitted.
            'source' => $data['source'] ?? null,
            // The chosen status.
            'status' => $data['status'],
            // The manual position, defaulting to 0.
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }
}
