{{-- Render this view inside the shared layouts/app.blade.php master layout. --}}
@extends('layouts.app')

{{-- Sets the page/browser title via the layout's @yield('title'). --}}
@section('title', 'Question reports')

{{-- Opens the main content block injected into the layout. --}}
@section('content')
    {{-- Hero banner for the admin page. --}}
    <section class="dashboard-hero admin-hero">
        {{-- Centring container. --}}
        <div class="container">
            {{-- Small label above the heading. --}}
            <span class="eyebrow">Admin panel</span>
            {{-- Page heading. --}}
            <h1>Question reports</h1>
            {{-- Explanatory sub-line. --}}
            <p class="track-line">Problems students have flagged, so a wrong answer or a confusing item can be put right.</p>
        </div>
    </section>

    {{-- Main body section. --}}
    <section class="section">
        {{-- Centring container. --}}
        <div class="container">
            {{-- Shared admin tab bar. --}}
            @include('admin.partials.nav')

            {{-- Three-column grid of report statistics. --}}
            <div class="grid grid-3 stat-grid">
                {{-- Card: open reports; is-poor colours it red when there is work to do. --}}
                <div class="card stat-card">
                    <span class="stat-value {{ $summary['open'] > 0 ? 'is-poor' : '' }}">{{ $summary['open'] }}</span>
                    <span class="stat-label">Open</span>
                </div>
                {{-- Card: reports closed by fixing the item. --}}
                <div class="card stat-card">
                    <span class="stat-value">{{ $summary['resolved'] }}</span>
                    <span class="stat-label">Resolved</span>
                </div>
                {{-- Card: reports closed without a change. --}}
                <div class="card stat-card">
                    <span class="stat-value">{{ $summary['dismissed'] }}</span>
                    <span class="stat-label">Dismissed</span>
                </div>
            </div>

            {{-- Row of status filter links. --}}
            <div class="filter-bar">
                {{-- "All" link with no status parameter; is-active when no status filter is applied. --}}
                <a href="{{ route('admin.reports.index') }}"
                   class="filter-link {{ $status === '' ? 'is-active' : '' }}">All</a>
                {{-- $filters is a status-key => label map (open/resolved/dismissed). --}}
                @foreach ($filters as $key => $label)
                    {{-- Links to this status; is-active reflects the currently selected filter. --}}
                    <a href="{{ route('admin.reports.index', ['status' => $key]) }}"
                       class="filter-link {{ $status === $key ? 'is-active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>

            {{-- Loop the paginated reports; @empty shows a notice when there are none. --}}
            @forelse ($reports as $report)
                {{-- Inline @php caches the related question for this report in a shorter variable. --}}
                @php($question = $report->question)

                {{-- One card per report. --}}
                <article class="card report-card">
                    {{-- Card header: status pill, reason, and who reported and when. --}}
                    <header class="report-head">
                        <div>
                            {{-- Status pill: red while open, green once closed. --}}
                            <span class="pill {{ $report->isOpen() ? 'is-poor' : 'is-good' }}">{{ $report->statusLabel() }}</span>
                            {{-- Human-readable reason for the report. --}}
                            <strong>{{ $report->reasonLabel() }}</strong>
                        </div>
                        <small class="muted">
                            {{-- Relative time such as "3 days ago" for when the report was filed. --}}
                            Reported {{ $report->created_at->diffForHumans() }}
                            {{-- Reporter's name, or a fallback if their account was deleted. --}}
                            @if ($report->reporter)
                                by {{ $report->reporter->name }}
                            @else
                                by a removed account
                            @endif
                        </small>
                    </header>

                    {{-- Free-text details are optional, so only show the paragraph when present. --}}
                    @if ($report->details)
                        <p class="muted small">"{{ $report->details }}"</p>
                    @endif

                    {{-- Block showing the reported question as the student saw it. --}}
                    <div class="report-question">
                        {{-- The question may since have been deleted. --}}
                        @if ($question)
                            {{-- The full question prompt. --}}
                            <p><strong>{{ $question->prompt }}</strong></p>

                            {{-- List every option with a letter prefix. --}}
                            <ul class="fact-list">
                                {{-- array_values re-indexes options so the letter matches the position. --}}
                                @foreach (array_values($question->options ?? []) as $index => $text)
                                    <li>
                                        <span>
                                            {{-- Option letter from the model, then the option text. --}}
                                            {{ $question->optionLabel($index) }}. {{ $text }}
                                            {{-- Mark the option stored as the correct answer. --}}
                                            @if ($index === $question->correct_option)
                                                <strong class="muted">(marked correct)</strong>
                                            @endif
                                        </span>
                                    </li>
                                @endforeach
                            </ul>

                            <p class="muted small">
                                {{-- Show the explanation, or say that none exists. --}}
                                @if ($question->explanation)
                                    <strong>Explanation:</strong> {{ $question->explanation }}
                                @else
                                    No explanation has been written for this item.
                                @endif
                            </p>

                            <p class="muted small">
                                {{-- Breadcrumb of the question's placement: area, subject, topic. --}}
                                @if ($question->topic)
                                    {{ $question->topic->subject->areaLabel() }} &middot;
                                    {{ $question->topic->subject->name }} &middot;
                                    {{ $question->topic->name }}
                                @else
                                    Uncategorised
                                @endif
                                {{-- Then the status pill and, if applicable, the verified pill. --}}
                                &middot; <span class="pill">{{ $question->statusLabel() }}</span>
                                @if ($question->isVerified())
                                    <span class="pill is-good">Verified</span>
                                @endif
                            </p>
                        @else
                            {{-- Message shown when the referenced question was deleted. --}}
                            <p class="muted">The question this was about has since been deleted.</p>
                        @endif
                    </div>

                    {{-- Open reports offer actions; closed ones show how they were closed. --}}
                    @if ($report->isOpen())
                        <div class="report-actions">
                            {{-- Only offer a jump to the question if it still exists. --}}
                            @if ($question)
                                <a class="btn btn-secondary btn-sm" href="{{ route('admin.questions.edit', $question) }}">Open the question</a>
                            @endif

                            {{-- Form to resolve the report (the item was fixed). --}}
                            <form method="POST" action="{{ route('admin.reports.resolve', $report) }}" class="report-form">
                                {{-- CSRF token. --}}
                                @csrf
                                <div class="field">
                                    <label for="resolve-note-{{ $report->id }}">What did you do? (optional)</label>
                                    {{-- Optional note recorded with the resolution. --}}
                                    <input type="text" id="resolve-note-{{ $report->id }}" name="resolution_note" maxlength="500"
                                           placeholder="For example: corrected the answer key">
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm">Mark as resolved</button>
                            </form>

                            {{-- Form to dismiss the report (no change needed). --}}
                            <form method="POST" action="{{ route('admin.reports.dismiss', $report) }}" class="report-form">
                                {{-- CSRF token. --}}
                                @csrf
                                <div class="field">
                                    <label for="dismiss-note-{{ $report->id }}">Why is no change needed? (optional)</label>
                                    {{-- Optional note explaining why the report was dismissed. --}}
                                    <input type="text" id="dismiss-note-{{ $report->id }}" name="resolution_note" maxlength="500"
                                           placeholder="For example: the answer key is correct">
                                </div>
                                <button type="submit" class="btn btn-secondary btn-sm">Dismiss</button>
                            </form>
                        </div>
                    @else
                        {{-- Summary of who closed the report, when, and their note. --}}
                        <div class="report-resolution">
                            <p class="muted small">
                                {{-- Resolver's name, or a generic fallback. --}}
                                Closed by {{ $report->resolver?->name ?? 'an administrator' }}
                                {{-- Include the closing date when one was recorded. --}}
                                @if ($report->resolved_at)
                                    on {{ $report->resolved_at->format('M j, Y g:i A') }}
                                @endif
                            </p>
                            {{-- Print the resolution note only when one was left. --}}
                            @if ($report->resolution_note)
                                <p class="muted small">Note: {{ $report->resolution_note }}</p>
                            @endif
                        </div>
                    @endif
                </article>
            {{-- Fallback shown when this filter matches no reports. --}}
            @empty
                <div class="notice">
                    <p>No reports in this view. Nothing has been flagged by students here.</p>
                </div>
            @endforelse

            {{-- Hand-written pagination controls. --}}
            @if ($reports->hasPages())
                <nav class="pagination" aria-label="Report pages">
                    {{-- Disabled span on the first page, otherwise a working Previous link. --}}
                    @if ($reports->onFirstPage())
                        <span class="page-link is-disabled">Previous</span>
                    @else
                        {{-- URL of the preceding page of reports. --}}
                        <a class="page-link" href="{{ $reports->previousPageUrl() }}">Previous</a>
                    @endif

                    {{-- Current page number and total page count. --}}
                    <span class="page-status">Page {{ $reports->currentPage() }} of {{ $reports->lastPage() }}</span>

                    {{-- Disabled span on the last page, otherwise a working Next link. --}}
                    @if ($reports->hasMorePages())
                        {{-- URL of the following page of reports. --}}
                        <a class="page-link" href="{{ $reports->nextPageUrl() }}">Next</a>
                    @else
                        <span class="page-link is-disabled">Next</span>
                    @endif
                </nav>
            @endif
        </div>
    </section>
@endsection
