{{-- Mistakes page: review the items you got wrong in an attempt. --}}
@extends('layouts.app')

@section('title', 'Review mistakes')

@section('content')
    {{-- Page header with the count of missed items --}}
    <section class="page-header">
        <div class="container">
            <p class="breadcrumb">
                <a href="{{ route('review.results', $attempt) }}">Results</a>
                <span>&rsaquo;</span>
                <span>Mistakes</span>
            </p>
            <h1>Review your mistakes</h1>
            <p>
                {{ $wrong->count() }}
                {{ Str::plural('item', $wrong->count()) }} from "{{ $attempt->quiz->title }}" that you missed.
                Read the explanation, then try them again.
            </p>
        </div>
    </section>

    {{-- Missed questions --}}
    <section class="section">
        <div class="container container-narrow">
            {{-- Retry card, only when something was missed --}}
            @if ($wrong->isNotEmpty())
                <div class="card">
                    <h3>Drill these items again</h3>
                    <p class="muted">Retry only the questions you got wrong in this attempt.</p>
                    {{-- Retries only the missed items --}}
                    <form method="POST" action="{{ route('review.retry', $attempt) }}">
                        {{-- @csrf is required on every POST form. --}}
                        @csrf
                        {{-- scope=mistakes tells the server to use only the wrong answers --}}
                        <input type="hidden" name="scope" value="mistakes">
                        <button type="submit" class="btn btn-primary">Retry the missed items</button>
                    </form>
                </div>
            @endif

            {{-- One review card per wrong answer --}}
            @forelse ($wrong as $answer)
                @include('review.partials.question-review', [
                    'question' => $answer->question,
                    'answer' => $answer,
                    'position' => $answer->position,
                    'bookmarked' => $bookmarked,
                ])
            @empty
                {{-- Empty state --}}
                <div class="notice">
                    <p>You did not miss any item in this attempt. There is nothing to revisit here.</p>
                </div>
            @endforelse

            <p style="margin-top: 20px;">
                <a class="btn btn-secondary" href="{{ route('review.results', $attempt) }}">Back to the results</a>
            </p>
        </div>
    </section>
@endsection
