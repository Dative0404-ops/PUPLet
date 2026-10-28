{{--
    Lets a student flag a question that looks wrong or hard to understand.

    The controls point at the separate form in review.partials.report-form
    using form="", so this block can sit inside another form (the timed
    attempt) without nesting forms.

    Expects: $question.
--}}
@php($reasons = \App\Models\QuestionReport::REASON_LABELS)
@php($formId = 'report-form-'.$question->id)

<details class="report-control">
    <summary>Report this question</summary>

    <div class="report-form">
        {{-- The reason is required; the extra note is optional --}}
        <div class="field">
            <label for="report-reason-{{ $question->id }}">What is the problem?</label>
            <select id="report-reason-{{ $question->id }}"
                    name="reason"
                    form="{{ $formId }}"
                    required>
                @foreach ($reasons as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label for="report-details-{{ $question->id }}">Anything else? (optional)</label>
            <textarea id="report-details-{{ $question->id }}"
                      name="details"
                      form="{{ $formId }}"
                      rows="3"
                      maxlength="500"></textarea>
        </div>

        <button type="submit" form="{{ $formId }}" class="btn btn-secondary btn-sm">Send report</button>
    </div>
</details>
