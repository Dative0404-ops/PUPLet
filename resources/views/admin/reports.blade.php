{{-- Admin page: question reports filed by students. --}}
@extends('layouts.app')

@section('title', 'Question reports')

@section('content')
    {{-- Page heading --}}
    <section class="dashboard-hero admin-hero">
        <div class="container">
            <span class="eyebrow">Admin panel</span>
            <h1>Question reports</h1>
            <p class="track-line">Problems students have flagged, so a wrong answer or a confusing item can be put right.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            {{-- Tab bar shared by every admin page. --}}
            @include('admin.partials.nav')

            {{-- Report counts --}}
            <div class="grid grid-3 stat-grid">
                {{-- is-poor turns this red when there is work to do. --}}
                <div class="card stat-card">
                    <span class="stat-value {{ $summary['open'] > 0 ? 'is-poor' : '' }}">{{ $summary['open'] }}</span>
                    <span class="stat-label">Open</span>
                </div>
                <div class="card stat-card">
                    <span class="stat-value">{{ $summary['resolved'] }}</span>
                    <span class="stat-label">Resolved</span>
                </div>
                <div class="card stat-card">
                    <span class="stat-value">{{ $summary['dismissed'] }}</span>
                    <span class="stat-label">Dismissed</span>
                </div>
            </div>

            {{-- Filter links by report status --}}
            <div class="filter-bar">
                {{-- The "All" link sends no status, so is-active applies when no filter is set. --}}
                <a href="{{ route('admin.reports.index') }}"
                   class="filter-link {{ $status === '' ? 'is-active' : '' }}">All</a>
                @foreach ($filters as $key => $label)
                    {{-- The link for the currently selected status gets is-active. --}}
                    <a href="{{ route('admin.reports.index', ['status' => $key]) }}"
                       class="filter-link {{ $status === $key ? 'is-active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>

            {{-- One card per report --}}
            @forelse ($reports as $report)
                {{-- Shorthand so the blocks below can use $question directly. --}}
                @php($question = $report->question)

                <article class="card report-card">
                    <header class="report-head">
                        <div>
                            {{-- Red while the report is open, green once it is closed. --}}
                            <span class="pill {{ $report->isOpen() ? 'is-poor' : 'is-good' }}">{{ $report->statusLabel() }}</span>
                            <strong>{{ $report->reasonLabel() }}</strong>
                        </div>
                        <small class="muted">
                            Reported {{ $report->created_at->diffForHumans() }}
                            @if ($report->reporter)
                                by {{ $report->reporter->name }}
                            @else
                                by a removed account
                            @endif
                        </small>
                    </header>

                    {{-- The details text is optional, so this only shows when present. --}}
                    @if ($report->details)
                        <p class="muted small">"{{ $report->details }}"</p>
                    @endif

                    {{-- The reported question as the student saw it --}}
                    <div class="report-question">
                        @if ($question)
                            <p><strong>{{ $question->prompt }}</strong></p>

                            <ul class="fact-list">
                                {{-- array_values re-indexes the options so the letter matches the position. --}}
                                @foreach (array_values($question->options ?? []) as $index => $text)
                                    <li>
                                        <span>
                                            {{ $question->optionLabel($index) }}. {{ $text }}
                                            @if ($index === $question->correct_option)
                                                <strong class="muted">(marked correct)</strong>
                                            @endif
                                        </span>
                                    </li>
                                @endforeach
                            </ul>

                            <p class="muted small">
                                @if ($question->explanation)
                                    <strong>Explanation:</strong> {{ $question->explanation }}
                                @else
                                    No explanation has been written for this item.
                                @endif
                            </p>

                            <p class="muted small">
                                {{-- Area, subject and topic the question is filed under. --}}
                                @if ($question->topic)
                                    {{ $question->topic->subject->areaLabel() }} &middot;
                                    {{ $question->topic->subject->name }} &middot;
                                    {{ $question->topic->name }}
                                @else
                                    Uncategorised
                                @endif
                                &middot; <span class="pill">{{ $question->statusLabel() }}</span>
                                @if ($question->isVerified())
                                    <span class="pill is-good">Verified</span>
                                @endif
                            </p>
                        @else
                            <p class="muted">The question this was about has since been deleted.</p>
                        @endif
                    </div>

                    {{-- Open reports show action buttons; closed reports show the resolution. --}}
                    @if ($report->isOpen())
                        <div class="report-actions">
                            {{-- The link only appears while the question still exists. --}}
                            @if ($question)
                                <a class="btn btn-secondary btn-sm" href="{{ route('admin.questions.edit', $question) }}">Open the question</a>
                            @endif

                            {{-- Marks the report resolved because the item was fixed. --}}
                            <form method="POST" action="{{ route('admin.reports.resolve', $report) }}" class="report-form">
                                @csrf
                                <div class="field">
                                    <label for="resolve-note-{{ $report->id }}">What did you do? (optional)</label>
                                    <input type="text" id="resolve-note-{{ $report->id }}" name="resolution_note" maxlength="500"
                                           placeholder="For example: corrected the answer key">
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm">Mark as resolved</button>
                            </form>

                            {{-- Closes the report with no change needed. --}}
                            <form method="POST" action="{{ route('admin.reports.dismiss', $report) }}" class="report-form">
                                @csrf
                                <div class="field">
                                    <label for="dismiss-note-{{ $report->id }}">Why is no change needed? (optional)</label>
                                    <input type="text" id="dismiss-note-{{ $report->id }}" name="resolution_note" maxlength="500"
                                           placeholder="For example: the answer key is correct">
                                </div>
                                <button type="submit" class="btn btn-secondary btn-sm">Dismiss</button>
                            </form>
                        </div>
                    @else
                        {{-- Who closed the report, when, and any note they left. --}}
                        <div class="report-resolution">
                            <p class="muted small">
                                Closed by {{ $report->resolver?->name ?? 'an administrator' }}
                                @if ($report->resolved_at)
                                    on {{ $report->resolved_at->format('M j, Y g:i A') }}
                                @endif
                            </p>
                            @if ($report->resolution_note)
                                <p class="muted small">Note: {{ $report->resolution_note }}</p>
                            @endif
                        </div>
                    @endif
                </article>
            @empty
                <div class="notice">
                    <p>No reports in this view. Nothing has been flagged by students here.</p>
                </div>
            @endforelse

            {{-- Pagination controls --}}
            @if ($reports->hasPages())
                <nav class="pagination" aria-label="Report pages">
                    @if ($reports->onFirstPage())
                        <span class="page-link is-disabled">Previous</span>
                    @else
                        <a class="page-link" href="{{ $reports->previousPageUrl() }}">Previous</a>
                    @endif

                    <span class="page-status">Page {{ $reports->currentPage() }} of {{ $reports->lastPage() }}</span>

                    @if ($reports->hasMorePages())
                        <a class="page-link" href="{{ $reports->nextPageUrl() }}">Next</a>
                    @else
                        <span class="page-link is-disabled">Next</span>
                    @endif
                </nav>
            @endif
        </div>
    </section>
@endsection
