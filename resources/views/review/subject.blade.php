{{-- Render this view inside the shared master layout --}}
@extends('layouts.app')

{{-- Page title taken from the subject's name --}}
@section('title', $subject->name)

{{-- Start of the content injected into the layout --}}
@section('content')
    {{-- Header band with breadcrumb, subject name and a progress summary --}}
    <section class="page-header">
        <div class="container">
            {{-- Breadcrumb trail back to the reviewer home --}}
            <p class="breadcrumb">
                {{-- Link to the reviewer catalogue --}}
                <a href="{{ route('review.index') }}">Reviewer</a>
                {{-- Separator chevron --}}
                <span>&rsaquo;</span>
                {{-- LET area label for this subject (e.g. "General Education") --}}
                <span>{{ $subject->areaLabel() }}</span>
            </p>
            {{-- Subject name as the page heading --}}
            <h1>{{ $subject->name }}</h1>
            {{-- Progress summary: reviewed topics out of the total --}}
            <p>
                {{ $reviewed }} of {{ $topics->count() }}
                {{ Str::plural('topic', $topics->count()) }} marked as reviewed.
            </p>
        </div>
    </section>

    {{-- Section listing every topic in the subject --}}
    <section class="section">
        <div class="container">
            {{-- Loop over $topics; each row is an array of the topic plus its counts and state flags --}}
            @forelse ($topics as $row)
                {{-- Card for one topic --}}
                <article class="card topic-card">
                    <div>
                        {{-- Topic name --}}
                        <h3>{{ $row['topic']->name }}</h3>
                        {{-- Meta line: question count plus quiz length/time limit when a quiz exists --}}
                        <p class="muted small">
                            {{ $row['questions'] }} {{ Str::plural('question', $row['questions']) }}
                            {{-- Only mention items/time when the topic has a quiz --}}
                            @if ($row['quiz'])
                                &middot; {{ $row['quiz']->item_count }} items per set
                                {{-- Only show minutes when a time limit is set --}}
                                @if ($row['quiz']->time_limit_minutes)
                                    &middot; {{ $row['quiz']->time_limit_minutes }} min
                                @endif
                            @endif
                        </p>
                        {{-- Status pill; the is-good tone class is added only once the topic is completed --}}
                        <span class="pill {{ $row['completed'] ? 'is-good' : '' }}">
                            {{ $row['completed'] ? 'Reviewed' : ($row['started'] ? 'In progress' : 'Not started') }}
                        </span>
                    </div>

                    {{-- Action buttons under the topic card --}}
                    <div class="topic-card-actions">
                        {{-- Reviewer-material link shown only when a lesson exists for the topic --}}
                        @if ($row['lesson'])
                            <a class="btn btn-secondary btn-sm" href="{{ route('review.lesson', $row['lesson']) }}">
                                Reviewer material
                            </a>
                        @endif
                        {{-- Always available: open the topic detail page --}}
                        <a class="btn btn-primary btn-sm" href="{{ route('review.topic', $row['topic']) }}">
                            Open topic
                        </a>
                    </div>
                </article>
            {{-- Branch rendered when the subject has no topics --}}
            @empty
                <p class="muted">No topics have been added to this subject yet.</p>
            @endforelse
        </div>
    </section>
@endsection
