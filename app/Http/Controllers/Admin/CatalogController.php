<?php

// Groups this controller with the other admin-panel controllers.
namespace App\Http\Controllers\Admin;

// The base controller, providing shared controller behaviour.
use App\Http\Controllers\Controller;
// The Subject model holding a LET coverage area (e.g. General Education).
use App\Models\Subject;
// The Topic model nested under a subject.
use App\Models\Topic;
// RedirectResponse is the return type for actions that redirect the browser.
use Illuminate\Http\RedirectResponse;
// Request is the incoming HTTP request (form input and signed-in user).
use Illuminate\Http\Request;
// Collection is Eloquent's array-like result wrapper, used for a fallback value.
use Illuminate\Support\Collection;
// Rule builds validation rules such as "in" and "unique".
use Illuminate\Validation\Rule;
// View is the return type of actions that render a Blade template.
use Illuminate\View\View;

/**
 * Subject and topic management: keeping the reviewer organised along the LET
 * coverage of General Education, Professional Education, and specialization.
 */
// Admins use this controller to manage subjects and topics in the reviewer catalog.
class CatalogController extends Controller
{
    /**
     * Browse the whole LET coverage.
     */
    // Route action for GET /admin/catalog.
    public function index(): View
    {
        // Load every subject with its topics and question counts.
        $subjects = Subject::query()
            // Eager-load topics, each carrying its own question count, ordered.
            ->with(['topics' => fn ($query) => $query->withCount('questions')
                // Order topics by their manual position first.
                ->orderBy('sort_order')
                // Then alphabetically by name as a tie-breaker.
                ->orderBy('name'),
            ])
            // Also count how many topics each subject has.
            ->withCount('topics')
            // Order subjects by their manual position.
            ->orderBy('sort_order')
            // Then alphabetically by name.
            ->orderBy('name')
            // Run the query.
            ->get()
            // Group the subjects into buckets keyed by their area.
            ->groupBy('area');

        // Prepare the list of area sections the view will render.
        $areas = [];

        // Walk the known area labels (area key => label) in a fixed order.
        foreach (Subject::AREA_LABELS as $area => $label) {
            // Add one section describing that area and its subjects.
            $areas[] = [
                // The stored area key used to match subjects.
                'area' => $area,
                // The human-readable heading for the area.
                'label' => $label,
                // The subject collection for this area, or an empty one when none.
                'subjects' => $subjects->get($area, new Collection),
            ];
        }

        // Render the catalog view with the grouped areas.
        return view('admin.catalog', [
            // The ordered list of area sections.
            'areas' => $areas,
            // The raw label map, so forms can offer the area options.
            'areaLabels' => Subject::AREA_LABELS,
        ]);
    }

    /**
     * Add a subject to one of the LET areas.
     */
    // Route action for POST /admin/catalog/subjects.
    public function storeSubject(Request $request): RedirectResponse
    {
        // Validate the incoming subject fields.
        $data = $this->validateSubject($request);
        // If no sort order was given, place the new subject at the end of its area.
        $data['sort_order'] ??= $this->nextSubjectOrder($data['area']);

        // Insert the new subject row.
        Subject::create($data);

        // Return to the previous page with a success message.
        return back()->with('status', 'The subject has been added.');
    }

    /**
     * Rename a subject, move it to another area, or reorder it.
     */
    // Route action for PUT/PATCH /admin/catalog/subjects/{subject}.
    public function updateSubject(Request $request, Subject $subject): RedirectResponse
    {
        // Validate, drop a blank sort order, then apply the changes to the subject.
        $subject->update($this->withoutBlankOrder($this->validateSubject($request, $subject)));

        // Return to the previous page with a success message.
        return back()->with('status', 'The subject has been updated.');
    }

    /**
     * Delete a subject, once it holds no topics.
     */
    // Route action for DELETE /admin/catalog/subjects/{subject}.
    public function destroySubject(Subject $subject): RedirectResponse
    {
        // Refuse to delete while the subject still has topics under it.
        if ($subject->topics()->exists()) {
            // Send the message back as a form error instead of deleting.
            return back()->withErrors([
                // Error key the catalog view looks for.
                'catalog' => 'Delete or move the topics under "'.$subject->name.'" before removing the subject.',
            ]);
        }

        // Delete the subject row.
        $subject->delete();

        // Return to the previous page with a success message.
        return back()->with('status', 'The subject has been deleted.');
    }

    /**
     * Add a topic under a subject.
     */
    // Route action for POST /admin/catalog/topics.
    public function storeTopic(Request $request): RedirectResponse
    {
        // Validate the incoming topic fields.
        $data = $this->validateTopic($request);
        // If no sort order was given, place the new topic at the end of its subject.
        $data['sort_order'] ??= $this->nextTopicOrder($data['subject_id']);

        // Insert the new topic row.
        Topic::create($data);

        // Return to the previous page with a success message.
        return back()->with('status', 'The topic has been added.');
    }

    /**
     * Rename a topic, move it to another subject, or reorder it.
     */
    // Route action for PUT/PATCH /admin/catalog/topics/{topic}.
    public function updateTopic(Request $request, Topic $topic): RedirectResponse
    {
        // Validate, drop a blank sort order, then apply the changes to the topic.
        $topic->update($this->withoutBlankOrder($this->validateTopic($request, $topic)));

        // Return to the previous page with a success message.
        return back()->with('status', 'The topic has been updated.');
    }

    /**
     * Delete a topic, once it holds no questions.
     */
    // Route action for DELETE /admin/catalog/topics/{topic}.
    public function destroyTopic(Topic $topic): RedirectResponse
    {
        // Count the questions that currently sit under this topic.
        $questions = $topic->questions()->count();

        // Refuse to delete while questions remain.
        if ($questions > 0) {
            // Report how many questions must be moved or deleted first.
            return back()->withErrors([
                // Error key the catalog view looks for.
                'catalog' => 'Move or delete the '.$questions.' question(s) under "'.$topic->name.'" first.',
            ]);
        }

        // Delete the topic row.
        $topic->delete();

        // Return to the previous page with a success message.
        return back()->with('status', 'The topic has been deleted.');
    }

    /**
     * Validate a subject, keeping names unique inside their area.
     *
     * @return array<string, mixed>
     */
    // Shared validation for adding and updating a subject.
    private function validateSubject(Request $request, ?Subject $subject = null): array
    {
        // Validate the request; $subject is passed when editing an existing row.
        return $request->validate([
            // Area must be one of the known LET areas.
            'area' => ['required', Rule::in(array_keys(Subject::AREA_LABELS))],
            // Name must be present, text, and unique within the same area.
            'name' => [
                // Must be present.
                'required',
                // Must be a string.
                'string',
                // At most 120 characters.
                'max:120',
                // Unique within the target area (and ignore this subject's own row).
                Rule::unique('subjects', 'name')
                    // Restrict the uniqueness check to the submitted area.
                    ->where(fn ($query) => $query->where('area', $request->input('area')))
                    // Exclude the row being edited so it can keep its own name.
                    ->ignore($subject?->id),
            ],
            // Optional manual position within the area.
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
    }

    /**
     * Validate a topic, keeping names unique inside their subject.
     *
     * @return array<string, mixed>
     */
    // Shared validation for adding and updating a topic.
    private function validateTopic(Request $request, ?Topic $topic = null): array
    {
        // Validate the request; $topic is passed when editing an existing row.
        return $request->validate([
            // The parent subject must exist in the subjects table.
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            // Name must be present, text, and unique within the same subject.
            'name' => [
                // Must be present.
                'required',
                // Must be a string.
                'string',
                // At most 120 characters.
                'max:120',
                // Unique within the target subject (and ignore this topic's own row).
                Rule::unique('topics', 'name')
                    // Restrict the uniqueness check to the submitted subject.
                    ->where(fn ($query) => $query->where('subject_id', $request->input('subject_id')))
                    // Exclude the row being edited so it can keep its own name.
                    ->ignore($topic?->id),
            ],
            // Optional manual position within the subject.
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
    }

    /**
     * Drop a blank sort order so an existing position is left alone.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    // Removes the sort_order key when it came through empty.
    private function withoutBlankOrder(array $data): array
    {
        // Treat a null (blank) sort order as "leave the position unchanged".
        if (($data['sort_order'] ?? null) === null) {
            // Remove the key so update() does not overwrite the stored position.
            unset($data['sort_order']);
        }

        // Return the (possibly trimmed) data array.
        return $data;
    }

    /**
     * The position a new subject takes at the end of its area.
     */
    // Computes the next free sort_order for a subject in a given area.
    private function nextSubjectOrder(string $area): int
    {
        // Highest existing order in the area, plus one (0 + 1 when none exist).
        return (int) Subject::where('area', $area)->max('sort_order') + 1;
    }

    /**
     * The position a new topic takes at the end of its subject.
     */
    // Computes the next free sort_order for a topic within a subject.
    private function nextTopicOrder(int $subjectId): int
    {
        // Highest existing order in the subject, plus one (0 + 1 when none exist).
        return (int) Topic::where('subject_id', $subjectId)->max('sort_order') + 1;
    }
}
