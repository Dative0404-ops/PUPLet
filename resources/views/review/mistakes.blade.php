{{-- Render inside the shared layout --}}
@extends('layouts.app')

{{-- Page title --}}
@section('title', 'Review mistakes')

{{-- Content section --}}
@section('content')
    {{-- Header with breadcrumb and a count of the missed items --}}
    <section class="page-header">
        <div class="container">
            {{-- Breadcrumb: Results > Mistakes --}}
            <p class="breadcrumb">
                {{-- Link back to the attempt's results --}}
                <a href="{{ route('review.results', $attempt) }}">Results</a>
                {{-- Separator --}}
                <span>&rsaquo;</span>
                {{-- Current page (plain text) --}}
                <span>Mistakes</span>
            </p>
            <h1>Review your mistakes</h1>
            {{-- Summary of how many items were missed in this attempt --}}
            <p>
                {{ $wrong->count() }}
                {{ Str::plural('item', $wrong->count()) }} from "{{ $attempt->quiz->title }}" that you missed.
                Read the explanation, then try them again.
            </p>
        </div>
    </section>

    {{-- Main section listing only the missed questions --}}
    <section class="section">
        <div class="container container-narrow">
            {{-- Retry card shown only when there is at least one mistake --}}
            @if ($wrong->isNotEmpty())
                <div class="card">
                    <h3>Drill these items again</h3>
                    <p class="muted">Retry only the questions you got wrong in this attempt.</p>
                    {{-- POST form that retries only the missed items --}}
                    <form method="POST" action="{{ route('review.retry', $attempt) }}">
                        @csrf
                        {{-- scope=mistakes tells the controller to include only the wrong answers --}}
                        <input type="hidden" name="scope" value="mistakes">
                        <button type="submit" class="btn btn-primary">Retry the missed items</button>
                    </form>
                </div>
            @endif

            {{-- Loop over $wrong (the incorrect answers), rendering a review card for each --}}
            @forelse ($wrong as $answer)
                @include('review.partials.question-review', [
                    'question' => $answer->question,
                    'answer' => $answer,
                    'position' => $answer->position,
                    'bookmarked' => $bookmarked,
                ])
            @empty
                {{-- Shown when the attempt had no wrong answers --}}
                <div class="notice">
                    <p>You did not miss any item in this attempt. There is nothing to revisit here.</p>
                </div>
            @endforelse

            {{-- Link back to the results page --}}
            <p style="margin-top: 20px;">
                <a class="btn btn-secondary" href="{{ route('review.results', $attempt) }}">Back to the results</a>
            </p>
        </div>
    </section>
@endsection
