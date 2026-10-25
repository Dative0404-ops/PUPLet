{{--
    The form element that receives a question report. Rendered next to
    `review.partials.report-question`, and outside any outer form on the
    timed attempt page.

    Expects: $question.
--}}
<form method="POST"
      action="{{ route('review.question.report', $question) }}"
      id="report-form-{{ $question->id }}"
      class="report-form-target">
    @csrf
</form>
