{{-- Topic page: reviewer material and the practice quiz for one topic. --}}
@extends('layouts.app')

@section('title', $topic->name)

@section('content')
    {{-- Page header --}}
    <section class="page-header">
        <div class="container">
            <p class="breadcrumb">
                <a href="{{ route('review.index') }}">Reviewer</a>
                <span>&rsaquo;</span>
                <a href="{{ route('review.subject', $topic->subject) }}">{{ $topic->subject->name }}</a>
                <span>&rsaquo;</span>
                <span>{{ $topic->name }}</span>
            </p>
            <h1>{{ $topic->name }}</h1>
            <p>{{ $topic->subject->areaLabel() }} &middot; {{ $topic->subject->name }}</p>
        </div>
    </section>

    {{-- Main content section --}}
    <section class="section">
        <div class="container">
            {{-- Summary counts --}}
            <div class="grid grid-3 stat-grid">
                <div class="card stat-card">
                    <span class="stat-value">{{ $questions }}</span>
                    <span class="stat-label">{{ Str::plural('Question', $questions) }} available</span>
                </div>
                <div class="card stat-card">
                    <span class="stat-value">{{ $bookmarks }}</span>
                    <span class="stat-label">{{ Str::plural('Bookmark', $bookmarks) }} in this topic</span>
                </div>
                <div class="card stat-card">
                    {{-- progress is null until there is a record, so fall back to 0 --}}
                    <span class="stat-value">{{ $progress?->progress ?? 0 }}%</span>
                    <span class="stat-label">Material reviewed</span>
                </div>
            </div>

            {{-- Reviewer material and the practice quiz --}}
            <div class="grid grid-2">
                <div class="card">
                    <h3>Reviewer material</h3>
                    {{-- Shown only when a lesson has been written for this topic --}}
                    @if ($lesson)
                        <p class="muted">
                            Summaries, key terms, worked examples, and study notes for this topic.
                        </p>
                        <p class="muted small">
                            About {{ $lesson->estimated_minutes }} minutes &middot;
                            {{ $progress?->isCompleted() ? 'Marked as reviewed' : 'Not yet marked as reviewed' }}
                        </p>
                        <a class="btn btn-secondary" href="{{ route('review.lesson', $lesson) }}">Read the material</a>
                    @else
                        <p class="muted">
                            No reviewer material has been written for this topic yet. Please check back soon.
                        </p>
                    @endif
                </div>

                <div class="card">
                    <h3>Practice quiz</h3>
                    {{-- Shown only when a quiz has been set up --}}
                    @if ($quiz)
                        <p class="muted">
                            Answer {{ $quiz->item_count }} multiple-choice items
                            {{-- Times are mentioned only when a time limit is set --}}
                            @if ($quiz->time_limit_minutes)
                                within {{ $quiz->time_limit_minutes }} minutes
                            @else
                                at your own pace
                            @endif
                            and see the correct answer and explanation for each item.
                        </p>
                        {{-- Starting is allowed only when the topic has questions --}}
                        @if ($questions > 0)
                            <form method="POST" action="{{ route('review.quiz.start', $quiz) }}">
                                {{-- @csrf is required on every POST form. --}}
                                @csrf
                                <button type="submit" class="btn btn-primary">Start practice quiz</button>
                            </form>
                        @else
                            <p class="muted small">
                                This practice quiz has no questions yet. Please check back soon.
                            </p>
                            <a class="btn btn-secondary" href="{{ route('review.quiz', $quiz) }}">View quiz details</a>
                        @endif
                    @else
                        <p class="muted">
                            No practice quiz has been set up for this topic yet. Please check back soon.
                        </p>
                    @endif
                </div>
            </div>

            {{-- Links to the mock exam and the bookmarks page --}}
            <p class="muted" style="margin-top: 20px;">
                Ready for the full test?
                <a href="{{ route('review.mock') }}">Take the mock examination</a> or
                <a href="{{ route('review.bookmarks') }}">review your bookmarks</a>.
            </p>
        </div>
    </section>
@endsection
