{{-- Public page: why the reviewer exists, who it is for, and how it works. --}}
@extends('layouts.app')

@section('title', 'About the system')
@section('meta_description', 'Why the LET Reviewer was built, who it is for, and how the review process works.')

@section('content')
    {{-- Page heading --}}
    <section class="page-header">
        <div class="container">
            <span class="eyebrow">About</span>
            <h1>About the LET Reviewer system</h1>
            <p>
                LET Reviewer is a web-based review platform built to help aspiring professional
                teachers prepare for the Licensure Examination for Teachers in a structured,
                measurable way.
            </p>
        </div>
    </section>

    {{-- Two columns: the purpose and the target users --}}
    <section class="section">
        <div class="container">
            <div class="grid grid-2">
                <div class="card">
                    <h2>Purpose of the reviewer</h2>
                    <p class="muted">
                        Many LET takers rely on printed reviewers and scattered online materials that
                        are hard to organize and impossible to measure. This system consolidates the
                        review materials into a single platform where every question is tagged by LET
                        area, subject, and topic.
                    </p>
                    <p class="muted">
                        The goal is simple: make it easy to review consistently, see real progress,
                        and spend the remaining review time on the subjects that actually need work.
                    </p>
                </div>

                <div class="card">
                    <h2>Target users</h2>
                    <ul class="muted">
                        <li>Education graduates preparing for their first LET attempt</li>
                        <li>Retakers who want to review specific weak areas</li>
                        <li>BEED and BSED students doing advance review before graduation</li>
                        <li>Review centers and instructors looking for an organized drill bank</li>
                        <li>Licensed teachers reviewing for a second specialization</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="section section-alt">
        <div class="container">
            <h2>How it works</h2>
            <p class="section-intro">From creating an account to reading your progress report.</p>

            <ol class="steps">
                <li>
                    <strong>Register the exam type</strong>
                    <span>Tell the system whether you are taking the Elementary or Secondary LET and select your specialization.</span>
                </li>
                <li>
                    <strong>Study the review materials</strong>
                    <span>Work through notes and key concepts for General Education, Professional Education, and your specialization.</span>
                </li>
                <li>
                    <strong>Answer practice sets</strong>
                    <span>Take untimed drills first, then timed sets that follow the LET's time limits and item weighting.</span>
                </li>
                <li>
                    <strong>Review the rationalization</strong>
                    <span>Read the explanation for every item so you understand why an answer is correct, not just what it is.</span>
                </li>
                <li>
                    <strong>Check the progress report</strong>
                    <span>Your dashboard shows scores per area and subject, plus the topics where you keep losing points.</span>
                </li>
                <li>
                    <strong>Send feedback</strong>
                    <span>Report an item that seems wrong or suggest a topic through the contact page so the content can be improved.</span>
                </li>
            </ol>
        </div>
    </section>

    {{-- Two columns: scope and limits, and who built it --}}
    <section class="section">
        <div class="container">
            <div class="grid grid-2">
                <div class="card">
                    <h2>Scope and limitations</h2>
                    <ul class="muted">
                        <li>The reviewer is a study aid, not an official PRC reviewer.</li>
                        <li>Scores from practice sets are for self-monitoring and do not predict board ratings.</li>
                        <li>Content is reviewed by the project team but may still contain errors; reports are welcome.</li>
                        <li>An internet connection and a modern browser are required.</li>
                    </ul>
                </div>

                <div class="card">
                    <h2>Who built it</h2>
                    <p class="muted">
                        {{-- Reads the support email from the system settings --}}
                        @php($supportEmail = \App\Support\SystemSettings::supportEmail())
                        LET Reviewer is a capstone project developed by a student team. Questions,
                        comments, and corrections can be sent through the contact page or by email at
                        <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>.
                    </p>
                    <a href="{{ route('contact') }}" class="btn btn-secondary">Send feedback</a>
                </div>
            </div>
        </div>
    </section>
@endsection
