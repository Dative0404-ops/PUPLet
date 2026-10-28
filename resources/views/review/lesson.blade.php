{{-- Lesson page: reviewer material for one topic. --}}
@extends('layouts.app')

@section('title', $lesson->title)

@section('content')
    {{-- Page header --}}
    <section class="page-header">
        <div class="container">
            <p class="breadcrumb">
                <a href="{{ route('review.index') }}">Reviewer</a>
                <span>&rsaquo;</span>
                <a href="{{ route('review.subject', $lesson->topic->subject) }}">{{ $lesson->topic->subject->name }}</a>
                <span>&rsaquo;</span>
                <a href="{{ route('review.topic', $lesson->topic) }}">{{ $lesson->topic->name }}</a>
            </p>
            <h1>{{ $lesson->title }}</h1>
            <p>
                Reviewer material &middot; about {{ $lesson->estimated_minutes }} minutes
                &middot; {{ $lesson->topic->subject->areaLabel() }}
            </p>

            {{-- Topic review progress --}}
            <div class="progress" style="max-width: 320px;">
                <div class="progress-bar {{ $progress->isCompleted() ? 'is-good' : '' }}"
                     style="width: {{ $progress->progress }}%"></div>
            </div>
            <p class="muted small">
                {{ $progress->isCompleted() ? 'You have marked this topic as reviewed.' : $progress->progress.'% reviewed' }}
            </p>
        </div>
    </section>

    {{-- Lesson content --}}
    <section class="section">
        <div class="container container-narrow">
            {{-- Shown when the lesson has no content yet --}}
            @if (! $lesson->hasContent())
                <div class="notice">
                    <p>
                        No reviewer material has been written for this topic yet. Summaries, key terms,
                        worked examples, and study notes will appear here once they are added.
                    </p>
                </div>
            @endif

            {{-- Summary, only when filled in --}}
            @if (filled($lesson->summary))
                <div class="lesson-section">
                    <h2>Summary</h2>
                    <p>{{ $lesson->summary }}</p>
                </div>
            @endif

            {{-- Key terms, only when present --}}
            @if (filled($lesson->key_terms))
                <div class="lesson-section">
                    <h2>Key terms and definitions</h2>
                    <dl class="key-terms">
                        {{-- Each term may be a plain string or an array --}}
                        @foreach ($lesson->key_terms as $key => $term)
                            {{-- Normalise each entry into a name and a meaning --}}
                            @php
                                $name = is_array($term) ? ($term['term'] ?? $key) : $key;
                                $meaning = is_array($term) ? ($term['definition'] ?? '') : $term;
                            @endphp
                            <dt>{{ $name }}</dt>
                            <dd>{{ $meaning }}</dd>
                        @endforeach
                    </dl>
                </div>
            @endif

            {{-- Worked examples, only when present --}}
            @if (filled($lesson->examples))
                <div class="lesson-section">
                    <h2>Worked examples</h2>
                    <ol class="examples">
                        {{-- Each example may be a plain string or an array --}}
                        @foreach ($lesson->examples as $example)
                            {{-- Array example has a title and a body --}}
                            @if (is_array($example))
                                <li>
                                    <strong>{{ $example['title'] ?? 'Example' }}</strong>
                                    {{-- Body, or the older 'solution' key, or blank --}}
                                    <p>{{ $example['body'] ?? ($example['solution'] ?? '') }}</p>
                                </li>
                            @else
                                <li>{{ $example }}</li>
                            @endif
                        @endforeach
                    </ol>
                </div>
            @endif

            {{-- Study notes, only when present --}}
            @if (filled($lesson->study_notes))
                <div class="lesson-section">
                    <h2>Study notes</h2>
                    <p class="study-notes">{{ $lesson->study_notes }}</p>
                </div>
            @endif

            {{-- Action buttons --}}
            <div class="lesson-actions">
                {{-- Show a pill once reviewed, otherwise the button --}}
                @if ($progress->isCompleted())
                    <span class="pill is-good">Reviewed</span>
                @else
                    <form method="POST" action="{{ route('review.lesson.complete', $lesson) }}">
                        {{-- @csrf is required on every POST form. --}}
                        @csrf
                        <button type="submit" class="btn btn-primary">Mark as reviewed</button>
                    </form>
                @endif

                {{-- Quiz link, only when a quiz exists --}}
                @if ($quiz)
                    <a class="btn btn-secondary" href="{{ route('review.quiz', $quiz) }}">
                        Go to the practice quiz
                    </a>
                @endif

                <a class="btn btn-secondary" href="{{ route('review.topic', $lesson->topic) }}">
                    Back to the topic
                </a>
            </div>
        </div>
    </section>
@endsection
