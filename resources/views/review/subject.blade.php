{{-- Subject page: lists every topic in one LET subject. --}}
@extends('layouts.app')

@section('title', $subject->name)

@section('content')
    {{-- Page header --}}
    <section class="page-header">
        <div class="container">
            <p class="breadcrumb">
                <a href="{{ route('review.index') }}">Reviewer</a>
                <span>&rsaquo;</span>
                <span>{{ $subject->areaLabel() }}</span>
            </p>
            <h1>{{ $subject->name }}</h1>
            <p>
                {{ $reviewed }} of {{ $topics->count() }}
                {{ Str::plural('topic', $topics->count()) }} marked as reviewed.
            </p>
        </div>
    </section>

    {{-- Topic list --}}
    <section class="section">
        <div class="container">
            @forelse ($topics as $row)
                <article class="card topic-card">
                    <div>
                        <h3>{{ $row['topic']->name }}</h3>
                        <p class="muted small">
                            {{ $row['questions'] }} {{ Str::plural('question', $row['questions']) }}
                            {{-- Item count and time limit only show when a quiz exists --}}
                            @if ($row['quiz'])
                                &middot; {{ $row['quiz']->item_count }} items per set
                                @if ($row['quiz']->time_limit_minutes)
                                    &middot; {{ $row['quiz']->time_limit_minutes }} min
                                @endif
                            @endif
                        </p>
                        {{-- Green pill once the topic is marked reviewed --}}
                        <span class="pill {{ $row['completed'] ? 'is-good' : '' }}">
                            {{ $row['completed'] ? 'Reviewed' : ($row['started'] ? 'In progress' : 'Not started') }}
                        </span>
                    </div>

                    <div class="topic-card-actions">
                        {{-- Shown only when a lesson has been written for this topic --}}
                        @if ($row['lesson'])
                            <a class="btn btn-secondary btn-sm" href="{{ route('review.lesson', $row['lesson']) }}">
                                Reviewer material
                            </a>
                        @endif
                        <a class="btn btn-primary btn-sm" href="{{ route('review.topic', $row['topic']) }}">
                            Open topic
                        </a>
                    </div>
                </article>
            {{-- Shown when the subject has no topics --}}
            @empty
                <p class="muted">No topics have been added to this subject yet.</p>
            @endforelse
        </div>
    </section>
@endsection
