{{-- Admin page: verification queue for the question bank. --}}
@extends('layouts.app')

@section('title', 'Content verification')

@section('content')
    {{-- Page heading --}}
    <section class="dashboard-hero admin-hero">
        <div class="container">
            <span class="eyebrow">Admin panel</span>
            <h1>Content verification</h1>
            <p class="track-line">Track the source, the reviewer, and the sign-off status of every item.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            {{-- Tab bar shared by every admin page. --}}
            @include('admin.partials.nav')

            {{-- Verification counts --}}
            <div class="grid grid-4 stat-grid">
                <div class="card stat-card">
                    <span class="stat-value">{{ $summary['total'] }}</span>
                    <span class="stat-label">Questions</span>
                </div>
                <div class="card stat-card">
                    <span class="stat-value">{{ $summary['published'] }}</span>
                    <span class="stat-label">Published</span>
                </div>
                <div class="card stat-card">
                    <span class="stat-value is-good">{{ $summary['verified'] }}</span>
                    <span class="stat-label">Verified</span>
                </div>
                {{-- is-poor turns the number red as soon as any item is missing an explanation. --}}
                <div class="card stat-card">
                    <span class="stat-value {{ $summary['unexplained'] > 0 ? 'is-poor' : '' }}">
                        {{ $summary['unexplained'] }}
                    </span>
                    <span class="stat-label">Published without an explanation</span>
                </div>
            </div>

            {{-- Filter links for the queue --}}
            <div class="filter-bar">
                @foreach ($filters as $key => $label)
                    {{-- The link for the selected filter gets is-active. --}}
                    <a href="{{ route('admin.verification', ['filter' => $key]) }}"
                       class="filter-link {{ $filter === $key ? 'is-active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>

            {{-- Singular/plural wording follows the count. --}}
            <p class="muted small">
                {{ $summary['unsourced'] }} {{ Str::plural('question', $summary['unsourced']) }} in the bank
                {{ $summary['unsourced'] === 1 ? 'has' : 'have' }} no source recorded.
            </p>

            {{-- Verification table; the wrapper lets it scroll on small screens. --}}
            <div class="table-wrap">
                <table>
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
                        @forelse ($questions as $question)
                            <tr>
                                <td>
                                    <strong>{{ Str::limit($question->prompt, 80) }}</strong>
                                    <small class="muted">
                                        {{-- The pill colour follows the publish state. --}}
                                        <span class="pill {{ $question->isPublished() ? 'is-good' : ($question->isArchived() ? 'is-poor' : '') }}">
                                            {{ $question->statusLabel() }}
                                        </span>
                                        @if (empty($question->explanation))
                                            <span class="pill is-poor">No explanation</span>
                                        @endif
                                    </small>
                                </td>
                                <td>
                                    @if ($question->topic)
                                        {{ $question->topic->name }}
                                        <small class="muted">{{ $question->topic->subject->name }}</small>
                                    @else
                                        <span class="muted">Uncategorised</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($question->source)
                                        {{ $question->source }}
                                    @else
                                        <span class="pill is-poor">Missing</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($question->reference)
                                        {{ Str::limit($question->reference, 70) }}
                                    @else
                                        <span class="muted">&mdash;</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($question->isVerified())
                                        <span class="pill is-good">Verified</span>
                                        <small class="muted">
                                            {{ $question->reviewer?->name ?? 'Unknown' }}
                                            &middot; {{ $question->verified_at->format('M j, Y') }}
                                        </small>
                                    @else
                                        <span class="pill">Not verified</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <a class="btn btn-secondary btn-sm" href="{{ route('admin.questions.edit', $question) }}">Open</a>

                                        {{-- The Verify button is hidden once the item is already verified. --}}
                                        @unless ($question->isVerified())
                                            <form method="POST" action="{{ route('admin.questions.verify', $question) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-primary btn-sm">Verify</button>
                                            </form>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                {{-- colspan spans all six columns below. --}}
                                <td colspan="6">
                                    <span class="muted">Nothing in the queue for this filter. That is a good sign.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination controls --}}
            @if ($questions->hasPages())
                <nav class="pagination" aria-label="Verification pages">
                    @if ($questions->onFirstPage())
                        <span class="page-link is-disabled">Previous</span>
                    @else
                        <a class="page-link" href="{{ $questions->previousPageUrl() }}">Previous</a>
                    @endif

                    <span class="page-status">Page {{ $questions->currentPage() }} of {{ $questions->lastPage() }}</span>

                    @if ($questions->hasMorePages())
                        <a class="page-link" href="{{ $questions->nextPageUrl() }}">Next</a>
                    @else
                        <span class="page-link is-disabled">Next</span>
                    @endif
                </nav>
            @endif
        </div>
    </section>
@endsection
