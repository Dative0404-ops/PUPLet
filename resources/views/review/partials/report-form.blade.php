{{--
    The empty form that receives a question report.

    It is kept separate from the report controls so it can sit outside the
    attempt's own form. Forms must not be nested inside each other, so the
    controls point at this form using the form="" attribute.
--}}
<form method="POST"
      action="{{ route('review.question.report', $question) }}"
      id="report-form-{{ $question->id }}"
      class="report-form-target">
    @csrf
</form>
