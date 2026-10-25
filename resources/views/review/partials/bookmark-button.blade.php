{{--
    Saves or removes a question from the student's bookmarks.

    Expects: $question, $bookmarked (array of question ids), $ajax (optional bool).
--}}
@php($saved = in_array($question->id, $bookmarked, true))

@if (! empty($ajax))
    <button type="button"
            class="bookmark-btn {{ $saved ? 'is-saved' : '' }}"
            data-bookmark-url="{{ route('review.bookmark.toggle', $question) }}"
            aria-pressed="{{ $saved ? 'true' : 'false' }}">
        {{ $saved ? 'Saved' : 'Save for later' }}
    </button>
@else
    <form method="POST" action="{{ route('review.bookmark.toggle', $question) }}">
        @csrf
        <button type="submit" class="bookmark-btn {{ $saved ? 'is-saved' : '' }}">
            {{ $saved ? 'Saved' : 'Save for later' }}
        </button>
    </form>
@endif
