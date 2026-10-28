{{-- Bookmarks page: saved questions for later review. --}}
@extends('layouts.app')

@section('title', 'Bookmarks')

@section('content')
    {{-- Page header with the saved-question count --}}
    <section class="page-header">
        <div class="container">
            <p class="breadcrumb">
                <a href="{{ route('review.index') }}">Reviewer</a>
                <span>&rsaquo;</span>
                <span>Bookmarks</span>
            </p>
            <h1>Saved questions</h1>
            <p>
                {{ $bookmarks->count() }}
                {{ Str::plural('question', $bookmarks->count()) }} saved for later review.
            </p>
        </div>
    </section>

    {{-- Saved question list --}}
    <section class="section">
        <div class="container container-narrow">
            {{-- Pass the saved ids so the bookmark button shows as saved --}}
            @php($savedIds = $bookmarks->pluck('question_id')->all())

            {{-- One review card per saved question --}}
            @forelse ($bookmarks as $bookmark)
                @include('review.partials.question-review', [
                    'question' => $bookmark->question,
                    'answer' => null,
                    'position' => null,
                    'bookmarked' => $savedIds,
                ])
            @empty
                {{-- Empty state --}}
                <div class="notice">
                    <p>
                        You have not saved any questions yet. While taking a quiz, use the
                        "Save for later" button on any item you find difficult and it will appear here.
                    </p>
                </div>

                <p style="margin-top: 20px;">
                    <a class="btn btn-primary" href="{{ route('review.index') }}">Browse the reviewer</a>
                </p>
            @endforelse
        </div>
    </section>
@endsection
