{{-- Render inside the shared layout --}}
@extends('layouts.app')

{{-- Page title is the lesson title --}}
@section('title', $lesson->title)

{{-- Content section --}}
@section('content')
    {{-- Header with breadcrumb and a small progress indicator --}}
    <section class="page-header">
        <div class="container">
            {{-- Breadcrumb: Reviewer > Subject > Topic --}}
            <p class="breadcrumb">
                {{-- Home link --}}
                <a href="{{ route('review.index') }}">Reviewer</a>
                {{-- Separator --}}
                <span>&rsaquo;</span>
                {{-- Parent subject link --}}
                <a href="{{ route('review.subject', $lesson->topic->subject) }}">{{ $lesson->topic->subject->name }}</a>
                {{-- Separator --}}
                <span>&rsaquo;</span>
                {{-- Parent topic link --}}
                <a href="{{ route('review.topic', $lesson->topic) }}">{{ $lesson->topic->name }}</a>
            </p>
            {{-- Lesson title heading --}}
            <h1>{{ $lesson->title }}</h1>
            {{-- Sub-line: material type, estimated reading time and LET area --}}
            <p>
                Reviewer material &middot; about {{ $lesson->estimated_minutes }} minutes
                &middot; {{ $lesson->topic->subject->areaLabel() }}
            </p>

            {{-- Progress bar container (width capped via inline style) --}}
            <div class="progress" style="max-width: 320px;">
                {{-- Filled portion; is-good tone once completed, width set inline from progress percent --}}
                <div class="progress-bar {{ $progress->isCompleted() ? 'is-good' : '' }}"
                     style="width: {{ $progress->progress }}%"></div>
            </div>
            {{-- Text summary of the review state: completed message or the percentage --}}
            <p class="muted small">
                {{ $progress->isCompleted() ? 'You have marked this topic as reviewed.' : $progress->progress.'% reviewed' }}
            </p>
        </div>
    </section>

    {{-- Main reading section, narrowed for readability --}}
    <section class="section">
        <div class="container container-narrow">
            {{-- Notice shown when the lesson has no content at all --}}
            @if (! $lesson->hasContent())
                <div class="notice">
                    <p>
                        No reviewer material has been written for this topic yet. Summaries, key terms,
                        worked examples, and study notes will appear here once they are added.
                    </p>
                </div>
            @endif

            {{-- Summary block, rendered only when the summary field is filled --}}
            @if (filled($lesson->summary))
                <div class="lesson-section">
                    <h2>Summary</h2>
                    <p>{{ $lesson->summary }}</p>
                </div>
            @endif

            {{-- Key terms block, rendered only when terms exist --}}
            @if (filled($lesson->key_terms))
                <div class="lesson-section">
                    <h2>Key terms and definitions</h2>
                    {{-- Definition list mapping each term to its meaning --}}
                    <dl class="key-terms">
                        {{-- Iterate over key_terms; each entry may be a plain string or an array --}}
                        @foreach ($lesson->key_terms as $key => $term)
                            {{-- PHP block normalising each entry into a $name and $meaning pair --}}
                            @php
                                $name = is_array($term) ? ($term['term'] ?? $key) : $key;
                                $meaning = is_array($term) ? ($term['definition'] ?? '') : $term;
                            @endphp
                            {{-- Term name --}}
                            <dt>{{ $name }}</dt>
                            {{-- Term definition --}}
                            <dd>{{ $meaning }}</dd>
                        @endforeach
                    </dl>
                </div>
            @endif

            {{-- Worked examples block, rendered only when examples exist --}}
            @if (filled($lesson->examples))
                <div class="lesson-section">
                    <h2>Worked examples</h2>
                    {{-- Ordered list of examples --}}
                    <ol class="examples">
                        {{-- Iterate over example entries (each a string or an array) --}}
                        @foreach ($lesson->examples as $example)
                            {{-- Rich example object with title/body --}}
                            @if (is_array($example))
                                <li>
                                    {{-- Example title, defaulting to "Example" --}}
                                    <strong>{{ $example['title'] ?? 'Example' }}</strong>
                                    {{-- Example body, falling back to a legacy 'solution' key or empty --}}
                                    <p>{{ $example['body'] ?? ($example['solution'] ?? '') }}</p>
                                </li>
                            {{-- Plain-text example --}}
                            @else
                                <li>{{ $example }}</li>
                            @endif
                        @endforeach
                    </ol>
                </div>
            @endif

            {{-- Study notes block, rendered only when notes exist --}}
            @if (filled($lesson->study_notes))
                <div class="lesson-section">
                    <h2>Study notes</h2>
                    <p class="study-notes">{{ $lesson->study_notes }}</p>
                </div>
            @endif

            {{-- Action row: mark-reviewed button, quiz link and back link --}}
            <div class="lesson-actions">
                {{-- If already reviewed, show a static pill instead of the button --}}
                @if ($progress->isCompleted())
                    <span class="pill is-good">Reviewed</span>
                {{-- Otherwise offer the form that marks the lesson complete --}}
                @else
                    <form method="POST" action="{{ route('review.lesson.complete', $lesson) }}">
                        {{-- CSRF token for the POST --}}
                        @csrf
                        <button type="submit" class="btn btn-primary">Mark as reviewed</button>
                    </form>
                @endif

                {{-- Link straight to the practice quiz when one exists --}}
                @if ($quiz)
                    <a class="btn btn-secondary" href="{{ route('review.quiz', $quiz) }}">
                        Go to the practice quiz
                    </a>
                @endif

                {{-- Link back to the topic page --}}
                <a class="btn btn-secondary" href="{{ route('review.topic', $lesson->topic) }}">
                    Back to the topic
                </a>
            </div>
        </div>
    </section>
@endsection
