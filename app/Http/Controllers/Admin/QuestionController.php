<?php

namespace App\Http\Controllers\Admin;

// Base class with shared controller helpers.
use App\Http\Controllers\Controller;
// The question model this controller manages.
use App\Models\Question;
// Used to build the subject dropdowns.
use App\Models\Subject;
// Returned when an action redirects the browser.
use Illuminate\Http\RedirectResponse;
// The current request: form input, query string, and signed-in user.
use Illuminate\Http\Request;
// Eloquent's array-like result wrapper.
use Illuminate\Support\Collection;
// Builds validation rules such as "in".
use Illuminate\Validation\Rule;
// Lets the controller raise its own validation errors.
use Illuminate\Validation\ValidationException;
// Returned when an action renders a Blade template.
use Illuminate\View\View;

/**
 * Manage questions: writing, categorising, archiving, and verifying items.
 */
class QuestionController extends Controller
{
    /**
     * How many questions are listed per page.
     */
    private const PER_PAGE = 20;

    /**
     * The labels for the verification filter.
     *
     * @var array<string, string>
     */
    private const VERIFICATION_FILTERS = [
        // Questions already verified.
        'verified' => 'Verified',
        // Questions not yet verified.
        'unverified' => 'Not verified',
    ];

    /**
     * Browse and filter the question bank.
     */
    public function index(Request $request): View
    {
        // Read the filters; subject and topic use 0 to mean "not chosen".
        $search = trim($request->string('search')->toString());
        $subject = (int) $request->integer('subject');
        $topic = (int) $request->integer('topic');
        $status = $request->string('status')->toString();
        $verification = $request->string('verification')->toString();

        // Get the questions matching the chosen filters.
        $questions = Question::query()
            // Also fetch each question's topic, subject, and reviewer in the same query.
            ->with(['topic.subject', 'reviewer'])
            // Count each question's open reports.
            ->withCount(['reports' => fn ($query) => $query->open()])
            // Each filter applies only when it is set.
            ->when($search !== '', fn ($query) => $query->where('prompt', 'like', "%{$search}%"))
            ->when($topic > 0, fn ($query) => $query->where('topic_id', $topic))
            // With no topic chosen, match the subject through the question's topic.
            ->when($topic === 0 && $subject > 0, fn ($query) => $query->whereHas(
                'topic',
                fn ($inner) => $inner->where('subject_id', $subject),
            ))
            ->when(isset(Question::STATUS_LABELS[$status]), fn ($query) => $query->where('status', $status))
            ->when($verification === 'verified', fn ($query) => $query->whereNotNull('verified_at'))
            ->when($verification === 'unverified', fn ($query) => $query->whereNull('verified_at'))
            // Newest first, 20 per page, keeping the filters in the page links.
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.questions.index', [
            'questions' => $questions,
            'search' => $search,
            'subjectId' => $subject,
            'topicId' => $topic,
            'status' => $status,
            'verification' => $verification,
            // The subject/topic tree used to fill the filter dropdowns.
            'subjects' => $this->subjects(),
            'statuses' => Question::STATUS_LABELS,
            'verifications' => self::VERIFICATION_FILTERS,
        ]);
    }

    /**
     * Show the form for a new question.
     */
    public function create(): View
    {
        // Pass null so the shared form starts blank.
        return view('admin.questions.form', $this->formData(null));
    }

    /**
     * Add a new question to the bank.
     */
    public function store(Request $request): RedirectResponse
    {
        // Validate the input and insert the new question.
        Question::create($this->payload($request));

        return redirect()
            ->route('admin.questions.index')
            ->with('status', 'The question has been added.');
    }

    /**
     * Show the form for editing a question.
     */
    public function edit(Question $question): View
    {
        // Also fetch the topic, subject, reviewer, and reports the form shows.
        $question->load('topic.subject', 'reviewer', 'reports');

        return view('admin.questions.form', $this->formData($question));
    }

    /**
     * Save changes to a question. Editing the content clears its verification,
     * because the item that was signed off is no longer the item shown.
     */
    public function update(Request $request, Question $question): RedirectResponse
    {
        // Fill the model with the validated input, but do not save yet.
        $question->fill($this->payload($request));

        // Check whether any content field actually changed.
        $contentChanged = $question->isDirty([
            'prompt', 'options', 'correct_option', 'explanation', 'reference', 'source',
        ]);

        if ($contentChanged) {
            // forceFill skips mass-assignment guarding; these fields are not fillable.
            $question->forceFill(['reviewed_by' => null, 'verified_at' => null]);
        }

        $question->save();

        return redirect()
            ->route('admin.questions.edit', $question)
            // Show a different message when the verification was cleared.
            ->with('status', $contentChanged
                ? 'The question has been updated. It needs to be verified again.'
                : 'The question has been updated.');
    }

    /**
     * Withdraw a question from the reviewer without deleting it.
     */
    public function archive(Question $question): RedirectResponse
    {
        // An archived question is hidden from students but kept in the bank.
        $question->update(['status' => Question::STATUS_ARCHIVED]);

        return back()->with('status', 'The question has been archived.');
    }

    /**
     * Put an archived or drafted question back into circulation.
     */
    public function restore(Question $question): RedirectResponse
    {
        $question->update(['status' => Question::STATUS_PUBLISHED]);

        return back()->with('status', 'The question is published again.');
    }

    /**
     * Record that an administrator has checked this question.
     */
    public function verify(Request $request, Question $question): RedirectResponse
    {
        // Record the current admin as reviewer and the time of the check.
        $question->forceFill([
            'reviewed_by' => $request->user()->id,
            'verified_at' => now(),
        ])->save();

        return back()->with('status', 'The question has been marked as verified.');
    }

    /**
     * Remove a question for good.
     */
    public function destroy(Question $question): RedirectResponse
    {
        $question->delete();

        return redirect()
            ->route('admin.questions.index')
            ->with('status', 'The question has been deleted.');
    }

    /**
     * The subjects with their topics, for the categorisation controls.
     *
     * @return Collection<int, Subject>
     */
    private function subjects(): Collection
    {
        // Get every subject with its topics, ordered by position then name.
        return Subject::query()
            ->with(['topics' => fn ($query) => $query->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Everything the question form needs.
     *
     * @return array<string, mixed>
     */
    private function formData(?Question $question): array
    {
        return [
            // The question being edited, or null for a new one.
            'question' => $question,
            // The subject/topic options for the dropdowns.
            'subjects' => $this->subjects(),
            'statuses' => Question::STATUS_LABELS,
        ];
    }

    /**
     * Validate the submitted question and clean up its answer choices.
     *
     * @return array<string, mixed>
     */
    private function payload(Request $request): array
    {
        // Validate the input; only the keys below are returned.
        $data = $request->validate([
            // The topic must exist.
            'topic_id' => ['required', 'integer', 'exists:topics,id'],
            // Question text, up to 2000 characters.
            'prompt' => ['required', 'string', 'max:2000'],
            // Between 2 and 6 answer choices.
            'options' => ['required', 'array', 'min:2', 'max:6'],
            // A single choice may be blank; blank rows are dropped further down.
            'options.*' => ['nullable', 'string', 'max:1000'],
            // Index of the correct choice.
            'correct_option' => ['required', 'integer', 'min:0'],
            'explanation' => ['nullable', 'string', 'max:2000'],
            'reference' => ['nullable', 'string', 'max:500'],
            'source' => ['nullable', 'string', 'max:150'],
            // Must be a known question status.
            'status' => ['required', Rule::in(array_keys(Question::STATUS_LABELS))],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        // The chosen index, the cleaned choice list, and where the correct choice lands.
        $chosen = (int) $data['correct_option'];
        $options = [];
        $correctIndex = null;

        // Reindex the choices from zero and drop blank rows.
        foreach (array_values($data['options']) as $index => $text) {
            $text = trim((string) $text);

            if ($text === '') {
                continue;
            }

            // Remember where the chosen correct answer lands in the cleaned list.
            if ($index === $chosen) {
                $correctIndex = count($options);
            }

            $options[] = $text;
        }

        // A question needs at least two real choices to be usable.
        if (count($options) < 2) {
            throw ValidationException::withMessages([
                'options' => 'Write at least two answer choices.',
            ]);
        }

        // The chosen correct option must survive the blank rows being dropped.
        if ($correctIndex === null) {
            throw ValidationException::withMessages([
                'correct_option' => 'Choose which answer choice is the correct one.',
            ]);
        }

        // Return the cleaned data, ready for create() or fill().
        return [
            'topic_id' => (int) $data['topic_id'],
            // Trimmed question text.
            'prompt' => trim($data['prompt']),
            'options' => $options,
            // Position of the correct choice in the cleaned list.
            'correct_option' => $correctIndex,
            // Optional fields, null when omitted.
            'explanation' => $data['explanation'] ?? null,
            'reference' => $data['reference'] ?? null,
            'source' => $data['source'] ?? null,
            'status' => $data['status'],
            // Position, defaulting to 0.
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }
}
