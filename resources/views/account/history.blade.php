@extends('layouts.app')

@section('title', 'Quiz history')

@section('content')
    @php
        $tone = function (?int $percent) {
            if ($percent === null) {
                return '';
            }

            return $percent >= 75 ? 'is-good' : ($percent >= 60 ? 'is-fair' : 'is-poor');
        };
    @endphp

    <section class="dashboard-hero">
        <div class="container">
            <span class="eyebrow">Student account</span>
            <h1>Quiz history</h1>
            <p class="track-line">Every practice quiz and mock examination you have taken, newest first.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            @include('account.partials.nav')

            <div class="grid grid-4 stat-grid">
                <div class="card stat-card">
                    <span class="stat-value">{{ $summary['attempts'] }}</span>
                    <span class="stat-label">Submitted attempts</span>
                </div>
                <div class="card stat-card">
                    <span class="stat-value">{{ $summary['items'] }}</span>
                    <span class="stat-label">Items answered</span>
                </div>
                <div class="card stat-card">
                    <span class="stat-value">
                        @if ($summary['average'] !== null)
                            {{ $summary['average'] }}%
                        @else
                            &mdash;
                        @endif
                    </span>
                    <span class="stat-label">Average score</span>
                </div>
                <div class="card stat-card">
                    <span class="stat-value {{ $tone($summary['best']) }}">
                        @if ($summary['best'] !== null)
                            {{ $summary['best'] }}%
                        @else
                            &mdash;
                        @endif
                    </span>
                    <span class="stat-label">Best score</span>
                </div>
            </div>

            @if ($summary['open'] > 0)
                <div class="alert alert-info" role="status">
                    You have {{ $summary['open'] }} unfinished {{ Str::plural('attempt', $summary['open']) }}.
                    Open {{ $summary['open'] === 1 ? 'it' : 'them' }} from the list below to carry on.
                </div>
            @endif

            <div class="filter-bar">
                @foreach ($filters as $key => $label)
                    <a href="{{ route('account.history', $key === 'all' ? [] : ['type' => $key]) }}"
                       class="filter-link {{ $type === $key ? 'is-active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Quiz</th>
                            <th>Area</th>
                            <th>Type</th>
                            <th>Score</th>
                            <th>Percentage</th>
                            <th>Date</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($attempts as $attempt)
                            @php
                                $percent = $attempt->total_items > 0 ? (int) round($attempt->accuracy()) : null;
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $attempt->quiz->title }}</strong>
                                    @if ($attempt->quiz->topic)
                                        <small class="muted">{{ $attempt->quiz->topic->name }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if ($attempt->quiz->topic)
                                        {{ $attempt->quiz->topic->subject->areaLabel() }}
                                    @else
                                        <span class="muted">Mixed</span>
                                    @endif
                                </td>
                                <td>{{ $attempt->quiz->typeLabel() }}</td>
                                <td>
                                    @if ($attempt->isInProgress())
                                        <span class="muted">{{ $attempt->answered_items }} / {{ $attempt->total_items }} answered</span>
                                    @else
                                        {{ $attempt->score }} / {{ $attempt->total_items }}
                                    @endif
                                </td>
                                <td>
                                    @if ($attempt->isInProgress())
                                        <span class="pill">In progress</span>
                                    @else
                                        <span class="pill {{ $tone($percent) }}">{{ $percent }}%</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($attempt->completed_at)
                                        <span title="{{ $attempt->completed_at->format('M j, Y g:i A') }}">
                                            {{ $attempt->completed_at->format('M j, Y') }}
                                        </span>
                                    @else
                                        <span title="{{ $attempt->started_at->format('M j, Y g:i A') }}">
                                            {{ $attempt->started_at->diffForHumans() }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if ($attempt->isInProgress())
                                        <a class="btn btn-primary btn-sm" href="{{ route('review.attempt', $attempt) }}">Resume</a>
                                    @else
                                        <a class="btn btn-secondary btn-sm" href="{{ route('review.results', $attempt) }}">Results</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <span class="muted">
                                        No attempts to show yet.
                                        <a href="{{ route('review.index') }}">Start a practice quiz</a> to build your history.
                                    </span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($attempts->hasPages())
                <nav class="pagination" aria-label="Quiz history pages">
                    @if ($attempts->onFirstPage())
                        <span class="page-link is-disabled">Previous</span>
                    @else
                        <a class="page-link" href="{{ $attempts->previousPageUrl() }}">Previous</a>
                    @endif

                    <span class="page-status">Page {{ $attempts->currentPage() }} of {{ $attempts->lastPage() }}</span>

                    @if ($attempts->hasMorePages())
                        <a class="page-link" href="{{ $attempts->nextPageUrl() }}">Next</a>
                    @else
                        <span class="page-link is-disabled">Next</span>
                    @endif
                </nav>
            @endif
        </div>
    </section>
@endsection
