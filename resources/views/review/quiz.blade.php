{{-- Render inside the shared layout --}}
@extends('layouts.app')

{{-- Page title is the quiz title --}}
@section('title', $quiz->title)

{{-- Content section --}}
@section('content')
    {{-- Header with breadcrumb, title and quiz meta --}}
    <section class="page-header">
        <div class="container">
            {{-- Breadcrumb trail; subject/topic links appear only for a topic-linked quiz --}}
            <p class="breadcrumb">
                {{-- Home link --}}
                <a href="{{ route('review.index') }}">Reviewer</a>
                {{-- Only show subject/topic links when the quiz belongs to a topic (mock exams do not) --}}
                @if ($quiz->topic)
                    {{-- Separator --}}
                    <span>&rsaquo;</span>
                    {{-- Parent subject link --}}
                    <a href="{{ route('review.subject', $quiz->topic->subject) }}">{{ $quiz->topic->subject->name }}</a>
                    {{-- Separator --}}
                    <span>&rsaquo;</span>
                    {{-- Parent topic link --}}
                    <a href="{{ route('review.topic', $quiz->topic) }}">{{ $quiz->topic->name }}</a>
                @endif
            </p>
            {{-- Quiz title heading --}}
            <h1>{{ $quiz->title }}</h1>
            {{-- Meta line: quiz type, item count, and time limit if any --}}
            <p>{{ $quiz->typeLabel() }} &middot; {{ $quiz->item_count }} items
                @if ($timeLimit)
                    &middot; {{ $timeLimit }} minute time limit
                @else
                    &middot; no time limit
                @endif
            </p>
        </div>
    </section>

    {{-- Main section --}}
    <section class="section">
        <div class="container container-narrow">
            {{-- Card with instructions and the start/resume button --}}
            <div class="card">
                <h3>Before you begin</h3>
                {{-- Bullet list of rules the student should know before starting --}}
                <ul class="plain-list">
                    <li>Each item is multiple choice. Pick the best answer.</li>
                    <li>After you submit, you will see your score, the correct answers, and an explanation for each item.</li>
                    {{-- Timer-specific rule shown only when a time limit exists --}}
                    @if ($timeLimit)
                        <li>
                            The timer starts as soon as you begin. A live countdown stays on screen and your
                            answers are submitted automatically when time runs out.
                        </li>
                    {{-- Otherwise explain that the quiz is untimed --}}
                    @else
                        <li>You may take as long as you need.</li>
                    @endif
                    <li>You can save difficult questions to your bookmarks and revisit them later.</li>
                </ul>

                {{-- Notice offering to resume an unfinished attempt --}}
                @if ($inProgress)
                    <div class="notice">
                        <p>You have a set that is still open. Resume it to keep your saved answers.</p>
                    </div>
                @endif

                {{-- Notice shown when the question bank holds no items --}}
                @if ($available === 0)
                    <div class="notice">
                        <p>
                            This practice quiz has no questions yet. Please check back soon.
                        </p>
                    </div>
                @endif

                {{-- Action row: start/resume form and a back link --}}
                <div class="quiz-actions">
                    {{-- POST form that starts or resumes the attempt --}}
                    <form method="POST" action="{{ route('review.quiz.start', $quiz) }}">
                        {{-- CSRF token required by Laravel for the POST --}}
                        @csrf
                        {{-- Button label toggles between Resume and Start --}}
                        <button type="submit" class="btn btn-primary">
                            {{ $inProgress ? 'Resume the quiz' : 'Start the quiz' }}
                        </button>
                    </form>

                    {{-- Back link shown only when the quiz is attached to a topic --}}
                    @if ($quiz->topic)
                        <a class="btn btn-secondary" href="{{ route('review.topic', $quiz->topic) }}">Back to the topic</a>
                    @endif
                </div>

                {{-- Small note with the number of questions available for this topic --}}
                <p class="muted small">
                    {{ $available }} {{ Str::plural('question', $available) }} available in the bank for this topic.
                </p>
            </div>

            {{-- Card listing past attempts of this quiz --}}
            <div class="card" style="margin-top: 20px;">
                <h3>Past attempts</h3>

                {{-- Loop over past attempts, falling back to an empty message --}}
                @forelse ($pastAttempts as $attempt)
                    <div class="list-row">
                        <div>
                            {{-- Raw score out of total items --}}
                            <strong>{{ $attempt->score }} / {{ $attempt->total_items }} correct</strong>
                            {{-- Accuracy percentage and relative finish time --}}
                            <small class="muted">
                                {{ $attempt->accuracy() }}% &middot; finished {{ $attempt->completed_at?->diffForHumans() }}
                            </small>
                        </div>
                        {{-- Link to the attempt's results page --}}
                        <a class="btn btn-secondary btn-sm" href="{{ route('review.results', $attempt) }}">View results</a>
                    </div>
                @empty
                    <p class="muted">You have not finished this quiz yet.</p>
                @endforelse
            </div>
        </div>
    </section>
@endsection
