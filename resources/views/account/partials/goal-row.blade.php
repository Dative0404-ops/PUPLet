{{-- One study goal row: details, complete/remove buttons, and an edit form. --}}
@php
    $isDone = $goal->completed_at !== null;
@endphp

<div class="goal-row {{ $isDone ? 'is-done' : '' }}">
    {{-- Goal details --}}
    <div class="goal-main">
        <strong>{{ $goal->title }}</strong>
        <span class="goal-meta">
            <span class="pill {{ $goal->isDailyTarget() ? 'is-fair' : '' }}">
                {{ $goal->isDailyTarget() ? 'Daily target' : 'Review session' }}
            </span>
            &middot;
            @if ($goal->target_date->isToday())
                Today
            @elseif ($goal->target_date->isTomorrow())
                Tomorrow
            @else
                {{ $goal->target_date->format('D, M j') }}
            @endif
            @if ($goal->timeLabel())
                &middot; {{ date('g:i A', strtotime($goal->target_time)) }}
            @endif
            @if ($goal->isDailyTarget() && $goal->target_items)
                &middot; {{ $goal->target_items }} items
            @endif
            @if ($isDone)
                &middot; <span class="muted">finished {{ $goal->completed_at->diffForHumans() }}</span>
            @endif
        </span>
    </div>

    {{-- Mark done / remove buttons --}}
    <div class="goal-actions">
        <form method="POST" action="{{ route('account.planner.complete', $goal) }}">
            @csrf
            <button type="submit" class="btn btn-secondary btn-sm">
                {{ $isDone ? 'Reopen' : 'Mark done' }}
            </button>
        </form>

        <form method="POST" action="{{ route('account.planner.destroy', $goal) }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-secondary btn-sm">Remove</button>
        </form>
    </div>

    {{-- Collapsible edit and reschedule form --}}
    <details class="goal-edit">
        <summary>Edit or reschedule</summary>

        <form method="POST" action="{{ route('account.planner.update', $goal) }}">
            @csrf
            @method('PUT')

            <div class="field">
                <label for="goal-{{ $goal->id }}-title">Title</label>
                <input id="goal-{{ $goal->id }}-title" type="text" name="title" value="{{ $goal->title }}" required>
            </div>

            <div class="field">
                <label for="goal-{{ $goal->id }}-type">Goal type</label>
                <select id="goal-{{ $goal->id }}-type" name="type" required>
                    <option value="daily_target" @selected($goal->isDailyTarget())>Daily item target</option>
                    <option value="review_session" @selected(! $goal->isDailyTarget())>Review session</option>
                </select>
            </div>

            <div class="field">
                <label for="goal-{{ $goal->id }}-date">Date</label>
                <input id="goal-{{ $goal->id }}-date" type="date" name="target_date"
                    value="{{ $goal->target_date->toDateString() }}" required>
            </div>

            <div class="field">
                <label for="goal-{{ $goal->id }}-time">Start time <small class="muted">(review sessions)</small></label>
                <input id="goal-{{ $goal->id }}-time" type="time" name="target_time" value="{{ $goal->timeLabel() }}">
            </div>

            <div class="field">
                <label for="goal-{{ $goal->id }}-items">Items to answer <small class="muted">(daily targets)</small></label>
                <input id="goal-{{ $goal->id }}-items" type="number" name="target_items" min="1" max="1000"
                    value="{{ $goal->target_items }}">
            </div>

            <button type="submit" class="btn btn-primary btn-sm">Save changes</button>
        </form>
    </details>
</div>
