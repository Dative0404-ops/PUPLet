{{-- Admin page: form to write or edit a single question. --}}
@extends('layouts.app')

{{-- The title depends on whether an existing question was passed in. --}}
@section('title', $question ? 'Edit question' : 'Write a question')

@section('content')
    {{-- Work out the six option rows and which one is currently the correct answer. --}}
    @php
        // Saved options; null on the create form, so this falls back to an empty array.
        $existing = array_values($question?->options ?? []);
        // The editor always shows six option rows, even when fewer are used.
        $slots = 6;
        // Holds the text shown in each option input.
        $choices = [];

        // Fill one slot per option row.
        for ($i = 0; $i < $slots; $i++) {
            // old('options.N') keeps what was typed after a failed save; otherwise use the saved text.
            $choices[$i] = old('options.'.$i, $existing[$i] ?? '');
        }

        // The correct option: the old input, the saved value, or 0 by default.
        $correct = (int) old('correct_option', $question?->correct_option ?? 0);
        // Turns an index like 0, 1, 2 into the letters A, B, C.
        $letters = fn (int $index): string => chr(65 + $index);
    @endphp

    {{-- Page heading --}}
    <section class="dashboard-hero admin-hero">
        <div class="container">
            <span class="eyebrow">Admin panel</span>
            <h1>{{ $question ? 'Edit question' : 'Write a question' }}</h1>
            <p class="track-line">
                The prompt, the answer choices, the correct answer, the explanation, and the reference all live on this one screen.
            </p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            {{-- Tab bar shared by every admin page. --}}
            @include('admin.partials.nav')

            {{-- Two-column layout: the form on the left, a sidebar on the right. --}}
            <div class="admin-form-layout">
                {{-- Main question form --}}
                <div class="form-card">
                    <form method="POST"
                          action="{{ $question ? route('admin.questions.update', $question) : route('admin.questions.store') }}">
                        @csrf
                        {{-- Only an existing question is updated, so only then is the PUT verb spoofed. --}}
                        @if ($question)
                            @method('PUT')
                        @endif

                        {{-- Topic picker --}}
                        <div class="field">
                            <label for="topic_id">Topic</label>
                            {{-- is-invalid shows the error styling when validation failed for this field. --}}
                            <select id="topic_id" name="topic_id" required class="{{ $errors->has('topic_id') ? 'is-invalid' : '' }}">
                                {{-- A blank starting option forces a deliberate choice. --}}
                                <option value="">Choose a topic</option>
                                {{-- One optgroup per subject that already has topics. --}}
                                @foreach ($subjects as $subject)
                                    @if ($subject->topics->isNotEmpty())
                                        <optgroup label="{{ $subject->areaLabel() }} &middot; {{ $subject->name }}">
                                            @foreach ($subject->topics as $topic)
                                                <option value="{{ $topic->id }}" @selected((int) old('topic_id', $question?->topic_id) === $topic->id)>
                                                    {{ $topic->name }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                @endforeach
                            </select>
                            <span class="field-help muted small">This places the question in the LET coverage.</span>
                        </div>

                        <div class="field">
                            <label for="prompt">Question prompt</label>
                            {{-- Filled with the old input, or the saved prompt; is-invalid marks a validation error. --}}
                            <textarea id="prompt" name="prompt" rows="4" required class="{{ $errors->has('prompt') ? 'is-invalid' : '' }}">{{ old('prompt', $question?->prompt) }}</textarea>
                        </div>

                        {{-- Answer choices; the fieldset groups the option rows. --}}
                        <fieldset class="options-editor">
                            <legend>Answer choices</legend>
                            <p class="muted small">
                                Fill in at least two choices and pick the correct one. Blank rows are dropped when the question is saved.
                            </p>

                            {{-- One row per prepared slot; $index numbers the option. --}}
                            @foreach ($choices as $index => $text)
                                <div class="option-row">
                                    <span class="option-letter">{{ $letters($index) }}</span>
                                    {{-- The options[N] name lets the group be read back as an array. --}}
                                    <input type="text"
                                           name="options[{{ $index }}]"
                                           value="{{ $text }}"
                                           maxlength="1000"
                                           placeholder="Choice {{ $letters($index) }}"
                                           aria-label="Choice {{ $letters($index) }}">
                                    {{-- Wrapping the radio in the label makes the whole row clickable. --}}
                                    <label class="option-correct">
                                        {{-- All radios share the name "correct_option". --}}
                                        <input type="radio"
                                               name="correct_option"
                                               value="{{ $index }}"
                                               @checked($correct === $index)>
                                        <span>Correct</span>
                                    </label>
                                </div>
                            @endforeach
                        </fieldset>

                        <div class="field">
                            <label for="explanation">Explanation</label>
                            <textarea id="explanation" name="explanation" rows="4">{{ old('explanation', $question?->explanation) }}</textarea>
                            <span class="field-help muted small">Shown to students after they submit their answers.</span>
                        </div>

                        <div class="field">
                            <label for="reference">Reference</label>
                            <textarea id="reference" name="reference" rows="2" maxlength="500">{{ old('reference', $question?->reference) }}</textarea>
                            <span class="field-help muted small">The rule, page, or authority the answer comes from.</span>
                        </div>

                        <div class="field">
                            <label for="source">Source</label>
                            <input type="text"
                                   id="source"
                                   name="source"
                                   value="{{ old('source', $question?->source) }}"
                                   maxlength="150"
                                   placeholder="For example: PRC LET reviewer, 2023 edition">
                        </div>

                        {{-- Status and display order, side by side. --}}
                        <div class="grid grid-2">
                            <div class="field">
                                <label for="status">Status</label>
                                <select id="status" name="status" required>
                                    {{-- A new item defaults to "published". --}}
                                    @foreach ($statuses as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', $question?->status ?? 'published') === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="field">
                                <label for="sort_order">Display order (optional)</label>
                                <input type="number" id="sort_order" name="sort_order" min="0" max="9999"
                                       value="{{ old('sort_order', $question?->sort_order ?? 0) }}">
                            </div>
                        </div>

                        {{-- Submit row --}}
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                {{ $question ? 'Save the question' : 'Add the question' }}
                            </button>
                            <a class="btn btn-secondary" href="{{ route('admin.questions.index') }}">Back to the list</a>
                        </div>
                    </form>
                </div>

                {{-- Sidebar shows edit tools for an existing item, or tips for a new one. --}}
                @if ($question)
                    <aside class="admin-form-side">
                        {{-- Verification state --}}
                        <div class="card">
                            <h3>Verification</h3>

                            @if ($question->isVerified())
                                <p>
                                    <span class="pill is-good">Verified</span>
                                </p>
                                {{-- Reviewer name, or a fallback if that account was removed. --}}
                                <p class="muted small">
                                    Checked by {{ $question->reviewer?->name ?? 'a former administrator' }}
                                    on {{ $question->verified_at->format('M j, Y g:i A') }}.
                                </p>
                            @else
                                <p>
                                    <span class="pill">Not verified</span>
                                </p>
                                <p class="muted small">
                                    This item has not been signed off. Editing the prompt, choices, answer,
                                    explanation, reference, or source clears the verification again.
                                </p>
                            @endif

                            {{-- The button only shows while the item is not verified. --}}
                            @unless ($question->isVerified())
                                <form method="POST" action="{{ route('admin.questions.verify', $question) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">Mark as verified</button>
                                </form>
                            @endunless
                        </div>

                        {{-- Open reports about this question --}}
                        <div class="card">
                            <h3>Reported problems</h3>

                            {{-- @php(...) runs one statement; here it keeps only the open reports. --}}
                            @php($openReports = $question->reports->where('status', \App\Models\QuestionReport::STATUS_OPEN))

                            @if ($openReports->isEmpty())
                                <p class="muted small">No open reports about this question.</p>
                            @else
                                <ul class="fact-list">
                                    @foreach ($openReports as $report)
                                        <li>
                                            <span>{{ $report->reasonLabel() }}</span>
                                            <strong>{{ $report->created_at->format('M j, Y') }}</strong>
                                        </li>
                                    @endforeach
                                </ul>
                                <p>
                                    <a class="btn btn-secondary btn-sm" href="{{ route('admin.reports.index', ['status' => 'open']) }}">
                                        Open the report queue
                                    </a>
                                </p>
                            @endif
                        </div>

                        {{-- Permanently delete the question --}}
                        <div class="card danger-zone">
                            <h3>Delete</h3>
                            <p class="muted small">
                                Archiving keeps the item on record. Deleting removes it for good.
                            </p>
                            <form method="POST" action="{{ route('admin.questions.destroy', $question) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Delete this question</button>
                            </form>
                        </div>
                    </aside>
                @else
                    {{-- A new item has no record yet, so the sidebar shows writing tips. --}}
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
