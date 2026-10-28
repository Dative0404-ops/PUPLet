<?php

namespace App\Http\Controllers\Admin;

// Base class with shared controller helpers.
use App\Http\Controllers\Controller;
// A LET coverage area such as General Education.
use App\Models\Subject;
// A topic inside a subject.
use App\Models\Topic;
// Returned when an action redirects the browser.
use Illuminate\Http\RedirectResponse;
// The current request: form input and signed-in user.
use Illuminate\Http\Request;
// Eloquent's array-like result wrapper.
use Illuminate\Support\Collection;
// Builds validation rules such as "in" and "unique".
use Illuminate\Validation\Rule;
// Returned when an action renders a Blade template.
use Illuminate\View\View;

/**
 * Manage subjects and topics in the LET reviewer catalog.
 */
class CatalogController extends Controller
{
    /**
     * Show the whole LET coverage.
     */
    public function index(): View
    {
        // Get every subject with its topics, ordering, and question counts.
        $subjects = Subject::query()
            // Also fetch each subject's topics in the same query, ordered by position then name.
            ->with(['topics' => fn ($query) => $query->withCount('questions')
                ->orderBy('sort_order')
                ->orderBy('name'),
            ])
            ->withCount('topics')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            // Group the subjects by area.
            ->groupBy('area');

        // Build one section per known area.
        $areas = [];

        foreach (Subject::AREA_LABELS as $area => $label) {
            $areas[] = [
                'area' => $area,
                'label' => $label,
                // This area's subjects, or an empty list when there are none.
                'subjects' => $subjects->get($area, new Collection),
            ];
        }

        return view('admin.catalog', [
            'areas' => $areas,
            // The label map, so forms can offer the area options.
            'areaLabels' => Subject::AREA_LABELS,
        ]);
    }

    /**
     * Add a subject to one of the LET areas.
     */
    public function storeSubject(Request $request): RedirectResponse
    {
        $data = $this->validateSubject($request);
        // No position given? Put the new subject at the end of its area.
        $data['sort_order'] ??= $this->nextSubjectOrder($data['area']);

        Subject::create($data);

        return back()->with('status', 'The subject has been added.');
    }

    /**
     * Rename a subject, move it to another area, or reorder it.
     */
    public function updateSubject(Request $request, Subject $subject): RedirectResponse
    {
        // Save the changes, but drop a blank order so the position stays the same.
        $subject->update($this->withoutBlankOrder($this->validateSubject($request, $subject)));

        return back()->with('status', 'The subject has been updated.');
    }

    /**
     * Delete a subject, once it holds no topics.
     */
    public function destroySubject(Subject $subject): RedirectResponse
    {
        // Block the delete while the subject still has topics.
        if ($subject->topics()->exists()) {
            return back()->withErrors([
                // Key the catalog view reads for this error.
                'catalog' => 'Delete or move the topics under "'.$subject->name.'" before removing the subject.',
            ]);
        }

        $subject->delete();

        return back()->with('status', 'The subject has been deleted.');
    }

    /**
     * Add a topic under a subject.
     */
    public function storeTopic(Request $request): RedirectResponse
    {
        $data = $this->validateTopic($request);
        // No position given? Put the new topic at the end of its subject.
        $data['sort_order'] ??= $this->nextTopicOrder($data['subject_id']);

        Topic::create($data);

        return back()->with('status', 'The topic has been added.');
    }

    /**
     * Rename a topic, move it to another subject, or reorder it.
     */
    public function updateTopic(Request $request, Topic $topic): RedirectResponse
    {
        // Save the changes, but drop a blank order so the position stays the same.
        $topic->update($this->withoutBlankOrder($this->validateTopic($request, $topic)));

        return back()->with('status', 'The topic has been updated.');
    }

    /**
     * Delete a topic, once it holds no questions.
     */
    public function destroyTopic(Topic $topic): RedirectResponse
    {
        // Block the delete while questions remain under this topic.
        $questions = $topic->questions()->count();

        if ($questions > 0) {
            return back()->withErrors([
                // Key the catalog view reads for this error.
                'catalog' => 'Move or delete the '.$questions.' question(s) under "'.$topic->name.'" first.',
            ]);
        }

        $topic->delete();

        return back()->with('status', 'The topic has been deleted.');
    }

    /**
     * Validate a subject, keeping names unique inside their area.
     *
     * @return array<string, mixed>
     */
    private function validateSubject(Request $request, ?Subject $subject = null): array
    {
        return $request->validate([
            // Area must be one of the known LET areas.
            'area' => ['required', Rule::in(array_keys(Subject::AREA_LABELS))],
            'name' => [
                // Required text, up to 120 characters, unique within the area.
                'required',
                'string',
                'max:120',
                Rule::unique('subjects', 'name')
                    ->where(fn ($query) => $query->where('area', $request->input('area')))
                    // Skip this row so the subject can keep its own name when edited.
                    ->ignore($subject?->id),
            ],
            // Optional position within the area.
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
    }

    /**
     * Validate a topic, keeping names unique inside their subject.
     *
     * @return array<string, mixed>
     */
    private function validateTopic(Request $request, ?Topic $topic = null): array
    {
        return $request->validate([
            // The parent subject must exist in the subjects table.
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'name' => [
                // Required text, up to 120 characters, unique within the subject.
                'required',
                'string',
                'max:120',
                Rule::unique('topics', 'name')
                    ->where(fn ($query) => $query->where('subject_id', $request->input('subject_id')))
                    // Skip this row so the topic can keep its own name when edited.
                    ->ignore($topic?->id),
            ],
            // Optional position within the subject.
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
    }

    /**
     * Drop a blank sort order so an existing position is left alone.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withoutBlankOrder(array $data): array
    {
        // A blank (null) sort order means "leave the position unchanged".
        if (($data['sort_order'] ?? null) === null) {
            // Remove the key so update() does not overwrite the stored position.
            unset($data['sort_order']);
        }

        return $data;
    }

    /**
     * The position a new subject takes at the end of its area.
     */
    private function nextSubjectOrder(string $area): int
    {
        // Highest existing position in the area, plus one.
        return (int) Subject::where('area', $area)->max('sort_order') + 1;
    }

    /**
     * The position a new topic takes at the end of its subject.
     */
    private function nextTopicOrder(int $subjectId): int
    {
        // Highest existing position in the subject, plus one.
        return (int) Topic::where('subject_id', $subjectId)->max('sort_order') + 1;
    }
}
