{{--
    A read-only question with its answer key and explanation.

    Expects: $question, $answer (nullable QuizAttemptAnswer), $position (nullable int), $bookmarked (array of ids).
--}}
<article class="question-card {{ $answer ? ($answer->isCorrect() ? 'is-correct' : 'is-wrong') : '' }}">
    <header class="question-head">
        <span class="question-number">
            {{ $position ? 'Question '.$position : ($question->topic?->name ?? 'Saved question') }}
        </span>
        @if ($answer)
            <span class="pill {{ $answer->isCorrect() ? 'is-good' : 'is-poor' }}">
                {{ $answer->isCorrect() ? 'Correct' : ($answer->isAnswered() ? 'Incorrect' : 'Skipped') }}
            </span>
        @endif
    </header>

    <p class="question-prompt">{{ $question->prompt }}</p>

    <div class="options">
        @foreach (array_values($question->options ?? []) as $index => $text)
            @php
                $classes = ['option'];

                if ($index === $question->correct_option) {
                    $classes[] = 'is-correct';
                }

                if ($answer && $index === $answer->selected_option && ! $answer->isCorrect()) {
                    $classes[] = 'is-wrong';
                }
            @endphp
            <div class="{{ implode(' ', $classes) }}">
                <span class="option-label">{{ $question->optionLabel($index) }}</span>
                <span class="option-text">{{ $text }}</span>
            </div>
        @endforeach
    </div>

    <div class="answer-feedback">
        @if ($answer)
            <p class="muted">Your answer: <strong>{{ $answer->selectedAnswerText() ?? 'No answer' }}</strong></p>
        @endif

        <p class="muted">Correct answer: <strong>{{ $question->correctAnswerText() }}</strong></p>

        @if ($question->explanation)
            <div class="explanation"><strong>Why:</strong> {{ $question->explanation }}</div>
        @else
            <p class="muted small">No explanation has been written for this item yet.</p>
        @endif

        @if ($question->topic?->subject)
            <p class="muted small">
                {{ $question->topic->subject->areaLabel() }} &middot;
                {{ $question->topic->subject->name }} &middot;
                {{ $question->topic->name }}
            </p>
        @endif

        @include('review.partials.bookmark-button', ['question' => $question, 'bookmarked' => $bookmarked])
    </div>

    @include('review.partials.report-question', ['question' => $question])
    @include('review.partials.report-form', ['question' => $question])
</article>
