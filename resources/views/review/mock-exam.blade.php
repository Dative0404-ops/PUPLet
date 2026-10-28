{{-- Mock examination page: coverage blueprint and the start button. --}}
@extends('layouts.app')

@section('title', 'Mock examination')

@section('content')
    {{-- Page header --}}
    <section class="page-header">
        <div class="container">
            <p class="breadcrumb">
                <a href="{{ route('review.index') }}">Reviewer</a>
                <span>&rsaquo;</span>
                <span>Mock examination</span>
            </p>
            <h1>Mock examination</h1>
            <p>
                A full-length test weighted the way the PRC builds the LET: {{ $totalItems }} items
                across every area of your track, with a {{ $timeLimit }}-minute time limit.
            </p>
        </div>
    </section>

    {{-- Coverage table and start panel --}}
    <section class="section">
        <div class="container">
            <div class="grid grid-2">
                {{-- Coverage blueprint per LET area --}}
                <div class="card">
                    <h3>Coverage</h3>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Area</th>
                                    <th>Items</th>
                                    <th>In the bank</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($blueprint as $area)
                                    <tr>
                                        <td>{{ $area['label'] }}</td>
                                        <td>{{ $area['needed'] }}</td>
                                        <td>{{ $area['available'] }}</td>
                                        <td>
                                            {{-- Green when the bank has enough, red when short --}}
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

                {{-- Exam facts and start button --}}
                <div class="card">
                    <h3>Start the examination</h3>

                    <ul class="plain-list">
                        <li>{{ $totalItems }} multiple-choice items covering every LET area.</li>
                        <li>{{ $timeLimit }} minutes, timed with a live countdown.</li>
                        <li>Your answers are submitted automatically when time runs out.</li>
                        <li>You will get a full score breakdown and explanations afterwards.</li>
                    </ul>

                    {{-- Shown when a mock exam is already in progress --}}
                    @if ($inProgress)
                        <div class="notice">
                            <p>
                                You have a mock examination in progress.
                                @if ($inProgress->remainingSeconds() !== null)
                                    About {{ intdiv($inProgress->remainingSeconds(), 60) }} minutes remain.
                                @endif
                            </p>
                        </div>
                    @endif

                    {{-- Shown when the bank has too few items --}}
                    @if (! $ready)
                        <div class="notice">
                            <p>
                                The question bank does not hold enough items yet for a full mock examination.
                                Author the missing items listed on the left, then come back.
                            </p>
                        </div>
                    @endif

                    {{-- Starts or resumes the examination --}}
                    <form method="POST" action="{{ route('review.mock.start') }}">
                        {{-- @csrf is required on every POST form. --}}
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            {{ $inProgress ? 'Resume the examination' : 'Begin the examination' }}
                        </button>
                    </form>

                    <p class="muted small">
                        The examination is generated from the question bank each time you begin.
                    </p>
                </div>
            </div>

            {{-- Past attempts --}}
            <div class="card" style="margin-top: 20px;">
                <h3>Past mock examinations</h3>

                @forelse ($pastAttempts as $attempt)
                    <div class="list-row">
                        <div>
                            <strong>{{ $attempt->score }} / {{ $attempt->total_items }} correct</strong>
                            <small class="muted">
                                {{ $attempt->accuracy() }}% &middot; finished {{ $attempt->completed_at?->diffForHumans() }}
                            </small>
                        </div>
                        <a class="btn btn-secondary btn-sm" href="{{ route('review.results', $attempt) }}">View results</a>
                    </div>
                @empty
                    <p class="muted">You have not taken a mock examination yet.</p>
                @endforelse
            </div>
        </div>
    </section>
@endsection
