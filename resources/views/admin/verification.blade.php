{{-- Render this view inside the shared layouts/app.blade.php master layout. --}}
@extends('layouts.app')

{{-- Sets the page/browser title via the layout's @yield('title'). --}}
@section('title', 'Content verification')

{{-- Opens the main content block injected into the layout. --}}
@section('content')
    {{-- Hero banner for the admin page. --}}
    <section class="dashboard-hero admin-hero">
        {{-- Centring container. --}}
        <div class="container">
            {{-- Small label above the heading. --}}
            <span class="eyebrow">Admin panel</span>
            {{-- Page heading. --}}
            <h1>Content verification</h1>
            {{-- Explanatory sub-line. --}}
            <p class="track-line">Track the source, the reviewer, and the sign-off status of every item.</p>
        </div>
    </section>

    {{-- Main body section. --}}
    <section class="section">
        {{-- Centring container. --}}
        <div class="container">
            {{-- Shared admin tab bar. --}}
            @include('admin.partials.nav')

            {{-- Four-column grid of verification statistics. --}}
            <div class="grid grid-4 stat-grid">
                {{-- Card: total questions in the bank. --}}
                <div class="card stat-card">
                    <span class="stat-value">{{ $summary['total'] }}</span>
                    <span class="stat-label">Questions</span>
                </div>
                {{-- Card: published questions. --}}
                <div class="card stat-card">
                    <span class="stat-value">{{ $summary['published'] }}</span>
                    <span class="stat-label">Published</span>
                </div>
                {{-- Card: verified questions; permanently green as this is the good state. --}}
                <div class="card stat-card">
                    <span class="stat-value is-good">{{ $summary['verified'] }}</span>
                    <span class="stat-label">Verified</span>
                </div>
                {{-- Card: published items with no explanation; red when any exist. --}}
                <div class="card stat-card">
                    <span class="stat-value {{ $summary['unexplained'] > 0 ? 'is-poor' : '' }}">
                        {{ $summary['unexplained'] }}
                    </span>
                    <span class="stat-label">Published without an explanation</span>
                </div>
            </div>

            {{-- Row of verification filter links. --}}
            <div class="filter-bar">
                {{-- $filters is a filter-key => label map for this queue (e.g. unverified/unsourced). --}}
                @foreach ($filters as $key => $label)
                    {{-- Links to this filter; is-active marks the currently selected one. --}}
                    <a href="{{ route('admin.verification', ['filter' => $key]) }}"
                       class="filter-link {{ $filter === $key ? 'is-active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>

            {{-- Summary line about unsourced questions, with correct singular/plural wording. --}}
            <p class="muted small">
                {{-- Count with pluralised noun, then the matching has/have verb. --}}
                {{ $summary['unsourced'] }} {{ Str::plural('question', $summary['unsourced']) }} in the bank
                {{ $summary['unsourced'] === 1 ? 'has' : 'have' }} no source recorded.
            </p>

            {{-- Scrollable wrapper around the verification table. --}}
            <div class="table-wrap">
                <table>
                    {{-- Column headings for the queue. --}}
                    <thead>
                        <tr>
                            <th>Question</th>
                            <th>Placement</th>
                            <th>Source</th>
                            <th>Reference</th>
                            <th>Reviewer</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- $questions is a paginator; @forelse loops it and @empty covers an empty queue. --}}
                        @forelse ($questions as $question)
                            <tr>
                                <td>
                                    {{-- Prompt truncated to 80 characters. --}}
                                    <strong>{{ Str::limit($question->prompt, 80) }}</strong>
                                    <small class="muted">
                                        {{-- Status pill coloured by publish state. --}}
                                        <span class="pill {{ $question->isPublished() ? 'is-good' : ($question->isArchived() ? 'is-poor' : '') }}">
                                            {{ $question->statusLabel() }}
                                        </span>
                                        {{-- Extra red flag when the item has no explanation text. --}}
                                        @if (empty($question->explanation))
                                            <span class="pill is-poor">No explanation</span>
                                        @endif
                                    </small>
                                </td>
                                <td>
                                    {{-- Topic and subject, or a fallback for uncategorised items. --}}
                                    @if ($question->topic)
                                        {{ $question->topic->name }}
                                        <small class="muted">{{ $question->topic->subject->name }}</small>
                                    @else
                                        <span class="muted">Uncategorised</span>
                                    @endif
                                </td>
                                <td>
                                    {{-- The recorded source, or a red "Missing" pill when absent. --}}
                                    @if ($question->source)
                                        {{ $question->source }}
                                    @else
                                        <span class="pill is-poor">Missing</span>
                                    @endif
                                </td>
                                <td>
                                    {{-- Reference truncated to 70 characters, or a dash when absent. --}}
                                    @if ($question->reference)
                                        {{ Str::limit($question->reference, 70) }}
                                    @else
                                        <span class="muted">&mdash;</span>
                                    @endif
                                </td>
                                <td>
                                    {{-- Verified items show the reviewer's name and the sign-off date. --}}
                                    @if ($question->isVerified())
                                        <span class="pill is-good">Verified</span>
                                        <small class="muted">
                                            {{-- Reviewer name, or "Unknown" if that account was removed. --}}
                                            {{ $question->reviewer?->name ?? 'Unknown' }}
                                            &middot; {{ $question->verified_at->format('M j, Y') }}
                                        </small>
                                    @else
                                        <span class="pill">Not verified</span>
                                    @endif
                                </td>
                                <td>
                                    {{-- Row action buttons. --}}
                                    <div class="row-actions">
                                        {{-- Link to the edit form where the item can be corrected. --}}
                                        <a class="btn btn-secondary btn-sm" href="{{ route('admin.questions.edit', $question) }}">Open</a>

                                        {{-- @unless hides the Verify button once the item is already verified. --}}
                                        @unless ($question->isVerified())
                                            <form method="POST" action="{{ route('admin.questions.verify', $question) }}">
                                                {{-- CSRF token. --}}
                                                @csrf
                                                <button type="submit" class="btn btn-primary btn-sm">Verify</button>
                                            </form>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        {{-- Fallback row when this filter has nothing to verify. --}}
                        @empty
                            <tr>
                                {{-- colspan spans all six columns. --}}
                                <td colspan="6">
                                    <span class="muted">Nothing in the queue for this filter. That is a good sign.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Hand-written pagination controls. --}}
            @if ($questions->hasPages())
                <nav class="pagination" aria-label="Verification pages">
                    {{-- Disabled span on the first page, otherwise a working Previous link. --}}
                    @if ($questions->onFirstPage())
                        <span class="page-link is-disabled">Previous</span>
                    @else
                        {{-- URL of the preceding page of questions. --}}
                        <a class="page-link" href="{{ $questions->previousPageUrl() }}">Previous</a>
                    @endif

                    {{-- Current page number and total page count. --}}
                    <span class="page-status">Page {{ $questions->currentPage() }} of {{ $questions->lastPage() }}</span>

                    {{-- Disabled span on the last page, otherwise a working Next link. --}}
                    @if ($questions->hasMorePages())
                        {{-- URL of the following page of questions. --}}
                        <a class="page-link" href="{{ $questions->nextPageUrl() }}">Next</a>
                    @else
                        <span class="page-link is-disabled">Next</span>
                    @endif
                </nav>
            @endif
        </div>
    </section>
@endsection
