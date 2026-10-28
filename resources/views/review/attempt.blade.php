{{-- Take a quiz attempt: every question on one page, with a timer. --}}
@extends('layouts.app')

@section('title', $attempt->quiz->title)

@section('content')
    {{-- Back link and quiz title --}}
    <div style="margin-bottom: 24px;">
        <a href="{{ route('review.index') }}" class="muted" style="font-size: 0.9rem;">← Back to Subject</a>
        <h1 style="font-size: 1.5rem; margin-top: 8px;">{{ $attempt->quiz->title }}</h1>
    </div>

    {{-- Question progress and countdown timer --}}
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
        <div class="muted" style="font-size: 0.9rem;">Question 5 of 20</div>
        @if ($attempt->hasTimeLimit())
            <div style="display: flex; align-items: center; gap: 8px; font-weight: 700; color: var(--maroon);" 
                 id="quiz-timer" 
                 data-remaining-seconds="{{ $attempt->remainingSeconds() }}">
                <span>Time remaining:</span>
                <span id="timer-display">00:18:32</span>
            </div>
        @endif
    </div>

    {{-- Answer form: one card per question --}}
    <form method="POST" action="{{ route('review.attempt.submit', $attempt) }}" id="attempt-form">
        {{-- @csrf is required on every POST form. --}}
        @csrf

        @foreach ($answers as $answer)
            @php($question = $answer->question)
            @php($selected = $answer->selected_option)

            <div class="question-card">
                <div style="display: flex; gap: 16px; margin-bottom: 24px;">
                    <div style="font-weight: 800; font-size: 1.25rem;">{{ $answer->position }}.</div>
                    <div style="font-size: 1.1rem; line-height: 1.4;">{{ $question->prompt }}</div>
                </div>

                <div class="option-list">
                    @foreach (array_values($question->options ?? []) as $index => $text)
                        <label class="option-item {{ $selected === $index ? 'selected' : '' }}">
                            <input type="radio" name="answers[{{ $question->id }}]" value="{{ $index }}" @checked($selected === $index) style="display: none;">
                            <div class="option-letter">{{ $question->optionLabel($index) }}.</div>
                            <div class="option-text">{{ $text }}</div>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach

        {{-- Submit row --}}
        <div style="display: flex; justify-content: space-between; margin-top: 32px;">
            <button type="button" class="btn btn-secondary">← Previous</button>
            <button type="submit" class="btn btn-primary" style="padding-left: 40px; padding-right: 40px;">Submit answers</button>
        </div>
    </form>
@endsection
