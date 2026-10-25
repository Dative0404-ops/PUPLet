@extends('layouts.app')

@section('title', 'Study planner')

@section('content')
    @php
        $tone = function (?int $percent) {
            if ($percent === null) {
                return '';
            }

            return $percent >= 75 ? 'is-good' : ($percent >= 60 ? 'is-fair' : 'is-poor');
        };

        $target = $todayTarget?->target_items ?? $preferredTarget;
        $progress = $target ? min(100, (int) round($itemsToday / $target * 100)) : null;
    @endphp

    <section class="dashboard-hero">
        <div class="container">
            <span class="eyebrow">Student account</span>
            <h1>Study planner</h1>
            <p class="track-line">Set your daily targets and organise the review sessions you plan to attend.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            @include('account.partials.nav')

            <div class="grid grid-2">
                <div class="card">
                    <h2>Today's target</h2>

                    @if ($target)
                        <div class="score-line">
                            <span class="score-value">{{ $itemsToday }} / {{ $target }}</span>
                            <span class="muted">items answered today</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar {{ $tone($progress) }}" style="width: {{ $progress }}%"></div>
                        </div>
                        <p class="muted">
                            @if ($progress >= 100)
                                Target reached. Well done!
                            @elseif ($todayTarget)
                                Answer {{ max(0, $target - $itemsToday) }} more items to hit today's goal.
                            @else
                                This is your preferred daily target from account settings.
                                Answer {{ max(0, $target - $itemsToday) }} more items today.
                            @endif
                        </p>
                    @else
                        <p class="muted">{{ $itemsToday }} items answered today. No daily target is set.</p>
                    @endif
                </div>

                <div class="card">
                    <h2>Add a study goal</h2>
                    <p class="muted">Add a daily item target or schedule a review session.</p>

                    <form method="POST" action="{{ route('account.planner.store') }}">
                        @csrf

                        <div class="field">
                            <label for="title">What will you do?</label>
                            <input id="title" type="text" name="title" value="{{ old('title') }}"
                                placeholder="e.g. Review GenEd Mathematics" required>
                        </div>

                        <div class="field">
                            <label for="type">Goal type</label>
                            <select id="type" name="type" required>
                                <option value="daily_target" @selected(old('type') === 'daily_target')>Daily item target</option>
                                <option value="review_session" @selected(old('type') === 'review_session')>Review session</option>
                            </select>
                        </div>

                        <div class="field">
                            <label for="target_date">Date</label>
                            <input id="target_date" type="date" name="target_date"
                                value="{{ old('target_date', now()->toDateString()) }}" required>
                        </div>

                        <div class="field">
                            <label for="target_time">Start time <small class="muted">(review sessions)</small></label>
                            <input id="target_time" type="time" name="target_time" value="{{ old('target_time') }}">
                        </div>

                        <div class="field">
                            <label for="target_items">Items to answer <small class="muted">(daily targets)</small></label>
                            <input id="target_items" type="number" name="target_items" min="1" max="1000"
                                value="{{ old('target_items') }}" placeholder="e.g. 50">
                        </div>

                        <button type="submit" class="btn btn-primary">Add goal</button>
                    </form>
                </div>
            </div>

            @if ($overdue->isNotEmpty())
                <div class="card goal-card is-overdue">
                    <h2>Overdue</h2>
                    <p class="muted">These goals were planned for a past date and are still open.</p>
                    @foreach ($overdue as $goal)
                        @include('account.partials.goal-row', ['goal' => $goal])
                    @endforeach
                </div>
            @endif

            <div class="card goal-card">
                <h2>Upcoming</h2>
                <p class="muted">Today and everything you have planned ahead.</p>

                @php $lastDate = null; @endphp

                @forelse ($upcoming as $goal)
                    @php $isNewDay = $lastDate !== ($day = $goal->target_date->toDateString()); $lastDate = $day; @endphp

                    @if ($isNewDay)
                        <h4 class="goal-day">
                            @if ($goal->target_date->isToday())
                                Today
                            @elseif ($goal->target_date->isTomorrow())
                                Tomorrow
                            @else
                                {{ $goal->target_date->format('l, M j') }}
                            @endif
                        </h4>
                    @endif

                    @include('account.partials.goal-row', ['goal' => $goal])
                @empty
                    <p class="muted">Nothing planned yet. Add a goal above to get started.</p>
                @endforelse
            </div>

            @if ($completed->isNotEmpty())
                <div class="card goal-card">
                    <h2>Recently completed</h2>
                    <p class="muted">The last {{ $completed->count() }} goals you finished.</p>
                    @foreach ($completed as $goal)
                        @include('account.partials.goal-row', ['goal' => $goal])
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
