{{-- Render this view inside the shared layouts/app.blade.php master layout. --}}
@extends('layouts.app')

{{-- Title differs for edit vs create; ternary picks based on whether $question was passed. --}}
@section('title', $question ? 'Edit question' : 'Write a question')

{{-- Opens the main content block injected into the layout. --}}
@section('content')
    {{-- @php opens a raw PHP block used to prepare values before the HTML is rendered. --}}
    @php
        // Reads the saved options; $question is null on the create form, so it falls back to [].
        $existing = array_values($question?->options ?? []);
        // The editor always shows six option slots, even when fewer are used.
        $slots = 6;
        // This array will hold the text shown in each option input.
        $choices = [];

        // Loop over the six slot positions.
        for ($i = 0; $i < $slots; $i++) {
            // old('options.N') keeps the submitted value after a failed validation; else the saved text.
            $choices[$i] = old('options.'.$i, $existing[$i] ?? '');
        }

        // Which option is correct: the old input, the saved record, or 0 by default.
        $correct = (int) old('correct_option', $question?->correct_option ?? 0);
        // Small closure turning index 0,1,2... into the letters A,B,C... with chr().
        $letters = fn (int $index): string => chr(65 + $index);
    @endphp

    {{-- Hero banner for the admin page. --}}
    <section class="dashboard-hero admin-hero">
        {{-- Centring container. --}}
        <div class="container">
            {{-- Small label above the heading. --}}
            <span class="eyebrow">Admin panel</span>
            {{-- Heading switches between edit and create wording. --}}
            <h1>{{ $question ? 'Edit question' : 'Write a question' }}</h1>
            {{-- Explanatory sub-line. --}}
            <p class="track-line">
                The prompt, the answer choices, the correct answer, the explanation, and the reference all live on this one screen.
            </p>
        </div>
    </section>

    {{-- Main body section. --}}
    <section class="section">
        {{-- Centring container. --}}
        <div class="container">
            {{-- Shared admin tab bar. --}}
            @include('admin.partials.nav')

            {{-- Two-column layout: the form on the left, a sidebar on the right. --}}
            <div class="admin-form-layout">
                {{-- Left column holding the main question form. --}}
                <div class="form-card">
                    <form method="POST"
                          action="{{ $question ? route('admin.questions.update', $question) : route('admin.questions.store') }}">
                        {{-- CSRF token required on every state-changing form. --}}
                        @csrf
                        {{-- Only an existing question needs the spoofed PUT verb (updates the record). --}}
                        @if ($question)
                            @method('PUT')
                        @endif

                        {{-- Field group for choosing the question's topic. --}}
                        <div class="field">
                            <label for="topic_id">Topic</label>
                            {{-- is-invalid class is added when validation failed for topic_id. --}}
                            <select id="topic_id" name="topic_id" required class="{{ $errors->has('topic_id') ? 'is-invalid' : '' }}">
                                {{-- Empty option forces a deliberate choice; required blocks blank submissions. --}}
                                <option value="">Choose a topic</option>
                                {{-- Loop every subject and its topics to build the grouped options. --}}
                                @foreach ($subjects as $subject)
                                    {{-- Skip subjects that have no topics yet. --}}
                                    @if ($subject->topics->isNotEmpty())
                                        {{-- optgroup labels the group with the LET area and subject name. --}}
                                        <optgroup label="{{ $subject->areaLabel() }} &middot; {{ $subject->name }}">
                                            {{-- One option per topic in this subject. --}}
                                            @foreach ($subject->topics as $topic)
                                                {{-- Pre-selects the old input, else the saved topic of the question. --}}
                                                <option value="{{ $topic->id }}" @selected((int) old('topic_id', $question?->topic_id) === $topic->id)>
                                                    {{ $topic->name }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                @endforeach
                            </select>
                            {{-- Helper text explaining what the field does. --}}
                            <span class="field-help muted small">This places the question in the LET coverage.</span>
                        </div>

                        {{-- Field group for the question prompt. --}}
                        <div class="field">
                            <label for="prompt">Question prompt</label>
                            {{-- Textarea keeps the old input, else the saved prompt; is-invalid on validation failure. --}}
                            <textarea id="prompt" name="prompt" rows="4" required class="{{ $errors->has('prompt') ? 'is-invalid' : '' }}">{{ old('prompt', $question?->prompt) }}</textarea>
                        </div>

                        {{-- fieldset groups the option inputs; legend titles the group. --}}
                        <fieldset class="options-editor">
                            <legend>Answer choices</legend>
                            {{-- Helper text describing how to fill the option rows. --}}
                            <p class="muted small">
                                Fill in at least two choices and pick the correct one. Blank rows are dropped when the question is saved.
                            </p>

                            {{-- Loop the six prepared slots; $index is the option number, $text its value. --}}
                            @foreach ($choices as $index => $text)
                                <div class="option-row">
                                    {{-- Prints the slot letter (A, B, C...) using the $letters closure. --}}
                                    <span class="option-letter">{{ $letters($index) }}</span>
                                    {{-- Text input for this choice; name uses the options[N] array syntax. --}}
                                    <input type="text"
                                           name="options[{{ $index }}]"
                                           value="{{ $text }}"
                                           maxlength="1000"
                                           placeholder="Choice {{ $letters($index) }}"
                                           aria-label="Choice {{ $letters($index) }}">
                                    {{-- Label wrapping the radio so clicking the text also selects it. --}}
                                    <label class="option-correct">
                                        {{-- Radio group "correct_option"; the chosen value is the option index. --}}
                                        <input type="radio"
                                               name="correct_option"
                                               value="{{ $index }}"
                                               @checked($correct === $index)>
                                        <span>Correct</span>
                                    </label>
                                </div>
                            @endforeach
                        </fieldset>

                        {{-- Field group for the explanation text. --}}
                        <div class="field">
                            <label for="explanation">Explanation</label>
                            {{-- Textarea pre-filled with the old input or the saved explanation. --}}
                            <textarea id="explanation" name="explanation" rows="4">{{ old('explanation', $question?->explanation) }}</textarea>
                            {{-- Helper text describing where the explanation is shown. --}}
                            <span class="field-help muted small">Shown to students after they submit their answers.</span>
                        </div>

                        {{-- Field group for the reference text. --}}
                        <div class="field">
                            <label for="reference">Reference</label>
                            {{-- Textarea pre-filled with the old input or the saved reference. --}}
                            <textarea id="reference" name="reference" rows="2" maxlength="500">{{ old('reference', $question?->reference) }}</textarea>
                            {{-- Helper text describing what the reference should contain. --}}
                            <span class="field-help muted small">The rule, page, or authority the answer comes from.</span>
                        </div>

                        {{-- Field group for the source text. --}}
                        <div class="field">
                            <label for="source">Source</label>
                            {{-- Text input pre-filled with the old input or the saved source. --}}
                            <input type="text"
                                   id="source"
                                   name="source"
                                   value="{{ old('source', $question?->source) }}"
                                   maxlength="150"
                                   placeholder="For example: PRC LET reviewer, 2023 edition">
                        </div>

                        {{-- Two-column grid for status and display order. --}}
                        <div class="grid grid-2">
                            {{-- Field group for the publish status. --}}
                            <div class="field">
                                <label for="status">Status</label>
                                <select id="status" name="status" required>
                                    {{-- $statuses is a value=>label map (published/archived). --}}
                                    @foreach ($statuses as $value => $label)
                                        {{-- Pre-selects old input, the saved status, or "published" for a new item. --}}
                                        <option value="{{ $value }}" @selected(old('status', $question?->status ?? 'published') === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Field group for the optional display order. --}}
                            <div class="field">
                                <label for="sort_order">Display order (optional)</label>
                                {{-- Number input defaulting to the old value or 0. --}}
                                <input type="number" id="sort_order" name="sort_order" min="0" max="9999"
                                       value="{{ old('sort_order', $question?->sort_order ?? 0) }}">
                            </div>
                        </div>

                        {{-- Submit row at the bottom of the form. --}}
                        <div class="form-actions">
                            {{-- Button label switches between save (edit) and add (create). --}}
                            <button type="submit" class="btn btn-primary">
                                {{ $question ? 'Save the question' : 'Add the question' }}
                            </button>
                            {{-- Cancel link back to the questions list. --}}
                            <a class="btn btn-secondary" href="{{ route('admin.questions.index') }}">Back to the list</a>
                        </div>
                    </form>
                </div>

                {{-- Sidebar varies by mode: edit tools for an existing item, tips for a new one. --}}
                @if ($question)
                    <aside class="admin-form-side">
                        {{-- Card showing the verification state of this question. --}}
                        <div class="card">
                            <h3>Verification</h3>

                            {{-- Verified items show who checked them and when. --}}
                            @if ($question->isVerified())
                                <p>
                                    <span class="pill is-good">Verified</span>
                                </p>
                                {{-- Reviewer name (or a fallback) and the formatted verification timestamp. --}}
                                <p class="muted small">
                                    Checked by {{ $question->reviewer?->name ?? 'a former administrator' }}
                                    on {{ $question->verified_at->format('M j, Y g:i A') }}.
                                </p>
                            @else
                                <p>
                                    <span class="pill">Not verified</span>
                                </p>
                                {{-- Explains that editing any content field clears the verification flag. --}}
                                <p class="muted small">
                                    This item has not been signed off. Editing the prompt, choices, answer,
                                    explanation, reference, or source clears the verification again.
                                </p>
                            @endif

                            {{-- @unless shows the verify button only while the item is not verified. --}}
                            @unless ($question->isVerified())
                                <form method="POST" action="{{ route('admin.questions.verify', $question) }}">
                                    {{-- CSRF token. --}}
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">Mark as verified</button>
                                </form>
                            @endunless
                        </div>

                        {{-- Card listing any open reports about this question. --}}
                        <div class="card">
                            <h3>Reported problems</h3>

                            {{-- Inline @php(...) runs one statement; here it filters the item's reports to open ones. --}}
                            @php($openReports = $question->reports->where('status', \App\Models\QuestionReport::STATUS_OPEN))

                            {{-- When nothing is open, show a reassuring message instead of a list. --}}
                            @if ($openReports->isEmpty())
                                <p class="muted small">No open reports about this question.</p>
                            @else
                                <ul class="fact-list">
                                    {{-- One row per open report: its reason and the date it was filed. --}}
                                    @foreach ($openReports as $report)
                                        <li>
                                            {{-- Human-readable report reason. --}}
                                            <span>{{ $report->reasonLabel() }}</span>
                                            {{-- Date the report was submitted. --}}
                                            <strong>{{ $report->created_at->format('M j, Y') }}</strong>
                                        </li>
                                    @endforeach
                                </ul>
                                <p>
                                    {{-- Link to the report queue pre-filtered to open reports. --}}
                                    <a class="btn btn-secondary btn-sm" href="{{ route('admin.reports.index', ['status' => 'open']) }}">
                                        Open the report queue
                                    </a>
                                </p>
                            @endif
                        </div>

                        {{-- Danger zone card for permanently deleting the question. --}}
                        <div class="card danger-zone">
                            <h3>Delete</h3>
                            {{-- Warns that archiving is reversible while deleting is not. --}}
                            <p class="muted small">
                                Archiving keeps the item on record. Deleting removes it for good.
                            </p>
                            <form method="POST" action="{{ route('admin.questions.destroy', $question) }}">
                                {{-- CSRF token. --}}
                                @csrf
                                {{-- Spoofs the DELETE verb. --}}
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Delete this question</button>
                            </form>
                        </div>
                    </aside>
                @else
                    {{-- Create mode has no record yet, so the sidebar shows writing tips instead. --}}
                    <aside class="admin-form-side">
                        <div class="card">
                            <h3>Writing tips</h3>
                            <ul class="fact-list">
                                <li><span>Keep the prompt to one clear idea.</span></li>
                                <li><span>Write four plausible choices where you can.</span></li>
                                <li><span>Explain why the correct answer is right.</span></li>
                                <li><span>Record the source so the item can be checked later.</span></li>
                            </ul>
                        </div>
                    </aside>
                @endif
            </div>
        </div>
    </section>
@endsection
