{{-- Render inside the shared layout --}}
@extends('layouts.app')

{{-- Page title --}}
@section('title', 'Bookmarks')

{{-- Content section --}}
@section('content')
    {{-- Header with breadcrumb and the count of saved questions --}}
    <section class="page-header">
        <div class="container">
            {{-- Breadcrumb: Reviewer > Bookmarks --}}
            <p class="breadcrumb">
                <a href="{{ route('review.index') }}">Reviewer</a>
                {{-- Separator --}}
                <span>&rsaquo;</span>
                {{-- Current page (plain text) --}}
                <span>Bookmarks</span>
            </p>
            <h1>Saved questions</h1>
            {{-- Total number of saved questions --}}
            <p>
                {{ $bookmarks->count() }}
                {{ Str::plural('question', $bookmarks->count()) }} saved for later review.
            </p>
        </div>
    </section>

    {{-- Main section listing the saved questions --}}
    <section class="section">
        <div class="container container-narrow">
            {{-- Collect the saved question ids so the bookmark partial can mark them as saved --}}
            @php($savedIds = $bookmarks->pluck('question_id')->all())

            {{-- Loop over bookmarks, rendering a review card for each saved question --}}
            @forelse ($bookmarks as $bookmark)
                @include('review.partials.question-review', [
                    'question' => $bookmark->question,
                    'answer' => null,
                    'position' => null,
                    'bookmarked' => $savedIds,
                ])
            @empty
                {{-- Shown when the student has no bookmarks --}}
                <div class="notice">
                    <p>
                        You have not saved any questions yet. While taking a quiz, use the
                        "Save for later" button on any item you find difficult and it will appear here.
                    </p>
                </div>

                {{-- Call to action heading back to the catalogue --}}
                <p style="margin-top: 20px;">
                    <a class="btn btn-primary" href="{{ route('review.index') }}">Browse the reviewer</a>
                </p>
            @endforelse
        </div>
    </section>
@endsection
