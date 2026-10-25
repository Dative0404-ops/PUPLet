{{-- Render inside the shared layout --}}
@extends('layouts.app')

{{-- Page title is the topic name --}}
@section('title', $topic->name)

{{-- Content section --}}
@section('content')
    {{-- Header with breadcrumb trail: Reviewer > Subject > Topic --}}
    <section class="page-header">
        <div class="container">
            {{-- Breadcrumb navigation --}}
            <p class="breadcrumb">
                {{-- Home link --}}
                <a href="{{ route('review.index') }}">Reviewer</a>
                {{-- Separator --}}
                <span>&rsaquo;</span>
                {{-- Link back to the parent subject --}}
                <a href="{{ route('review.subject', $topic->subject) }}">{{ $topic->subject->name }}</a>
                {{-- Separator --}}
                <span>&rsaquo;</span>
                {{-- Current topic (plain text, since it is the current page) --}}
                <span>{{ $topic->name }}</span>
            </p>
            {{-- Topic heading --}}
            <h1>{{ $topic->name }}</h1>
            {{-- Sub-line showing the LET area label and subject name --}}
            <p>{{ $topic->subject->areaLabel() }} &middot; {{ $topic->subject->name }}</p>
        </div>
    </section>

    {{-- Main content section --}}
    <section class="section">
        <div class="container">
            {{-- Three statistic cards: questions, bookmarks and review progress --}}
            <div class="grid grid-3 stat-grid">
                {{-- Card: total questions available in this topic --}}
                <div class="card stat-card">
                    <span class="stat-value">{{ $questions }}</span>
                    <span class="stat-label">{{ Str::plural('Question', $questions) }} available</span>
                </div>
                {{-- Card: questions the student has bookmarked in this topic --}}
                <div class="card stat-card">
                    <span class="stat-value">{{ $bookmarks }}</span>
                    <span class="stat-label">{{ Str::plural('Bookmark', $bookmarks) }} in this topic</span>
                </div>
                {{-- Card: percentage of the lesson material reviewed; null-safe operator defaults to 0 --}}
                <div class="card stat-card">
                    <span class="stat-value">{{ $progress?->progress ?? 0 }}%</span>
                    <span class="stat-label">Material reviewed</span>
                </div>
            </div>

            {{-- Two-column layout: reviewer material on the left, practice quiz on the right --}}
            <div class="grid grid-2">
                {{-- Card for the lesson / reviewer material --}}
                <div class="card">
                    <h3>Reviewer material</h3>
                    {{-- Only offer the material when a lesson has been written for this topic --}}
                    @if ($lesson)
                        <p class="muted">
                            Summaries, key terms, worked examples, and study notes for this topic.
                        </p>
                        {{-- Estimated reading time and whether the student already marked it reviewed --}}
                        <p class="muted small">
                            About {{ $lesson->estimated_minutes }} minutes &middot;
                            {{ $progress?->isCompleted() ? 'Marked as reviewed' : 'Not yet marked as reviewed' }}
                        </p>
                        {{-- Link to open the lesson page --}}
                        <a class="btn btn-secondary" href="{{ route('review.lesson', $lesson) }}">Read the material</a>
                    {{-- No lesson exists yet --}}
                    @else
                        <p class="muted">
                            No reviewer material has been written for this topic yet. Please check back soon.
                        </p>
                    @endif
                </div>

                {{-- Card for the practice quiz --}}
                <div class="card">
                    <h3>Practice quiz</h3>
                    {{-- Describe the quiz only when one is configured --}}
                    @if ($quiz)
                        <p class="muted">
                            Answer {{ $quiz->item_count }} multiple-choice items
                            {{-- Show the time limit when set, otherwise say it is untimed --}}
                            @if ($quiz->time_limit_minutes)
                                within {{ $quiz->time_limit_minutes }} minutes
                            @else
                                at your own pace
                            @endif
                            and see the correct answer and explanation for each item.
                        </p>
                        {{-- Only allow starting when the topic actually has questions --}}
                        @if ($questions > 0)
                            {{-- POST form that starts a new quiz attempt --}}
                            <form method="POST" action="{{ route('review.quiz.start', $quiz) }}">
                                {{-- CSRF token required by Laravel for POST requests --}}
                                @csrf
                                <button type="submit" class="btn btn-primary">Start practice quiz</button>
                            </form>
                        {{-- Quiz exists but has no questions yet --}}
                        @else
                            <p class="muted small">
                                This practice quiz has no questions yet. Please check back soon.
                            </p>
                            {{-- Let the student still view the quiz's detail page --}}
                            <a class="btn btn-secondary" href="{{ route('review.quiz', $quiz) }}">View quiz details</a>
                        @endif
                    {{-- No quiz configured for this topic --}}
                    @else
                        <p class="muted">
                            No practice quiz has been set up for this topic yet. Please check back soon.
                        </p>
                    @endif
                </div>
            </div>

            {{-- Footer links pointing to the mock exam or the bookmarks page --}}
            <p class="muted" style="margin-top: 20px;">
                Ready for the full test?
                <a href="{{ route('review.mock') }}">Take the mock examination</a> or
                <a href="{{ route('review.bookmarks') }}">review your bookmarks</a>.
            </p>
        </div>
    </section>
@endsection
