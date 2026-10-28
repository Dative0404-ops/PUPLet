{{--
    Bookmark button for one question: saves it or removes it.

    When $ajax is set a plain button is used, so JavaScript can send the
    request in the background. Otherwise a normal form does the same job
    when JavaScript is off.
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
