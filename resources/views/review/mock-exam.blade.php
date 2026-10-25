{{-- Render inside the shared layout --}}
@extends('layouts.app')

{{-- Page title --}}
@section('title', 'Mock examination')

{{-- Content section --}}
@section('content')
    {{-- Header describing the mock examination --}}
    <section class="page-header">
        <div class="container">
            {{-- Breadcrumb: Reviewer > Mock examination --}}
            <p class="breadcrumb">
                <a href="{{ route('review.index') }}">Reviewer</a>
                {{-- Separator --}}
                <span>&rsaquo;</span>
                {{-- Current page (plain text) --}}
                <span>Mock examination</span>
            </p>
            <h1>Mock examination</h1>
            {{-- Explains the PRC-style weighting, total items and time limit --}}
            <p>
                A full-length test weighted the way the PRC builds the LET: {{ $totalItems }} items
                across every area of your track, with a {{ $timeLimit }}-minute time limit.
            </p>
        </div>
    </section>

    {{-- Main section: the coverage table and the start panel --}}
    <section class="section">
        <div class="container">
            {{-- Two-column grid --}}
            <div class="grid grid-2">
                {{-- Card showing the PRC-weighted blueprint per area --}}
                <div class="card">
                    <h3>Coverage</h3>
                    {{-- Wrapper enabling horizontal scroll on small screens --}}
                    <div class="table-wrap">
                        {{-- Table of required versus available items per area --}}
                        <table>
                            {{-- Column headers --}}
                            <thead>
                                <tr>
                                    <th>Area</th>
                                    <th>Items</th>
                                    <th>In the bank</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- One row per area in the weighted blueprint --}}
                                @foreach ($blueprint as $area)
                                    <tr>
                                        {{-- Area label (e.g. General Education) --}}
                                        <td>{{ $area['label'] }}</td>
                                        {{-- Items required by the blueprint --}}
                                        <td>{{ $area['needed'] }}</td>
                                        {{-- Items currently available in the bank --}}
                                        <td>{{ $area['available'] }}</td>
                                        <td>
                                            {{-- Status pill: good tone when ready, poor when short of items --}}
                                            <span class="pill {{ $area['ready'] ? 'is-good' : 'is-poor' }}">
                                                {{ $area['ready'] ? 'Ready' : 'Needs '.(max(0, $area['needed'] - $area['available'])).' more' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Card with exam facts and the start button --}}
                <div class="card">
                    <h3>Start the examination</h3>

                    {{-- Bullet list of exam facts --}}
                    <ul class="plain-list">
                        <li>{{ $totalItems }} multiple-choice items covering every LET area.</li>
                        <li>{{ $timeLimit }} minutes, timed with a live countdown.</li>
                        <li>Your answers are submitted automatically when time runs out.</li>
                        <li>You will get a full score breakdown and explanations afterwards.</li>
                    </ul>

                    {{-- Notice shown when the student already has a mock exam in progress --}}
                    @if ($inProgress)
                        <div class="notice">
                            <p>
                                You have a mock examination in progress.
                                {{-- Show the rounded-down minutes left when the limit is known --}}
                                @if ($inProgress->remainingSeconds() !== null)
                                    About {{ intdiv($inProgress->remainingSeconds(), 60) }} minutes remain.
                                @endif
                            </p>
                        </div>
                    @endif

                    {{-- Notice shown when the bank cannot fill the full mock exam --}}
                    @if (! $ready)
                        <div class="notice">
                            <p>
                                The question bank does not hold enough items yet for a full mock examination.
                                Author the missing items listed on the left, then come back.
                            </p>
                        </div>
                    @endif

                    {{-- POST form that starts or resumes the mock examination --}}
                    <form method="POST" action="{{ route('review.mock.start') }}">
                        @csrf
                        {{-- Button label toggles between Resume and Begin --}}
                        <button type="submit" class="btn btn-primary">
                            {{ $inProgress ? 'Resume the examination' : 'Begin the examination' }}
                        </button>
                    </form>

                    <p class="muted small">
                        The examination is generated from the question bank each time you begin.
                    </p>
                </div>
            </div>

            {{-- Card listing previous mock examination attempts --}}
            <div class="card" style="margin-top: 20px;">
                <h3>Past mock examinations</h3>

                {{-- Loop over past attempts, with an empty fallback --}}
                @forelse ($pastAttempts as $attempt)
                    <div class="list-row">
                        <div>
                            {{-- Raw score out of total items --}}
                            <strong>{{ $attempt->score }} / {{ $attempt->total_items }} correct</strong>
                            {{-- Accuracy percentage and relative finish time --}}
                            <small class="muted">
                                {{ $attempt->accuracy() }}% &middot; finished {{ $attempt->completed_at?->diffForHumans() }}
                            </small>
                        </div>
                        {{-- Link to the attempt's results page --}}
                        <a class="btn btn-secondary btn-sm" href="{{ route('review.results', $attempt) }}">View results</a>
                    </div>
                @empty
                    <p class="muted">You have not taken a mock examination yet.</p>
                @endforelse
            </div>
        </div>
    </section>
@endsection
