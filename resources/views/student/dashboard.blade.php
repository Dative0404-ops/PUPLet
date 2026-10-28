{{-- Student dashboard: progress stats, resume card and recommendations. --}}
@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="dashboard-header">
        <div>
            <h1>Welcome back, {{ $user->name }}!</h1>
            <p class="muted" style="font-size: 1rem; margin-top: 4px;">
                {{ $user->exam_type === 'beed' ? 'Elementary (BEED)' : 'Secondary (BSED)' }} - {{ $user->specialization }}
            </p>
        </div>
    </div>

    {{-- Summary stat cards --}}
    <div class="stat-grid">
        <div class="stat-card green">
            <div class="stat-top">
                <div class="stat-icon">📊</div>
                <span class="label">Overall Score</span>
            </div>
            <div class="stat-info">
                <span class="value">{{ $overall['average_score'] ?? 0 }}%</span>
                <small style="color: #2f855a; font-weight: 700;">Overall progress</small>
            </div>
        </div>
        <div class="stat-card blue">
            <div class="stat-top">
                <div class="stat-icon">📝</div>
                <span class="label">Completed quizzes</span>
            </div>
            <div class="stat-info">
                <span class="value">{{ $overall['quizzes_completed'] }}</span>
            </div>
        </div>
        <div class="stat-card purple">
            <div class="stat-top">
                <div class="stat-icon">🎯</div>
                <span class="label">Daily Target</span>
            </div>
            <div class="stat-info">
                <span class="value">{{ $goals['items_today'] }} / {{ $goals['daily_target_items'] }}</span>
                <small class="muted">items answered today</small>
            </div>
        </div>
        <div class="stat-card orange">
            <div class="stat-top">
                <div class="stat-icon">🔥</div>
                <span class="label">Study Streak</span>
            </div>
            <div class="stat-info">
                <span class="value">2 days</span>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 32px;">
        <div>
            {{-- Continue where you left off --}}
            <div class="chart-card" style="border-left: 4px solid var(--maroon); margin-bottom: 32px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                    <div>
                        <span class="eyebrow" style="color: var(--maroon); font-weight: 700; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Continue reviewing</span>
                        @if ($resume['attempt'])
                            <h3 style="margin-top: 4px;">Unfinished test</h3>
                            <p class="muted" style="margin-bottom: 0;">Practice set: {{ $resume['attempt']->quiz->topic->name }}</p>
                        @else
                            <h3 style="margin-top: 4px;">Ready to practice?</h3>
                            <p class="muted" style="margin-bottom: 0;">Pick up where you left off or start a new quiz.</p>
                        @endif
                    </div>
                    @if ($resume['attempt'])
                        <a href="{{ route('review.attempt', $resume['attempt']) }}" class="btn btn-primary btn-sm">Resume Test</a>
                    @else
                        <a href="{{ route('review.practice') }}" class="btn btn-primary btn-sm">Start Quiz</a>
                    @endif
                </div>
                @if ($resume['attempt'])
                    <div style="font-size: 0.9rem; font-weight: 600;">
                        {{ $resume['attempt']->answered_items }} of {{ $resume['attempt']->total_items }} items answered
                    </div>
                @endif
            </div>

            {{-- Performance per LET area and subject --}}
            <div class="chart-card" style="margin-bottom: 32px;">
                <h3 style="margin-bottom: 24px;">Subject performance</h3>
                @foreach ($performance as $area)
                    <div style="margin-bottom: 24px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span style="font-weight: 700;">{{ $area['label'] }}</span>
                            <span class="muted" style="font-size: 0.85rem;">{{ $area['percentage'] ?? 0 }}%</span>
                        </div>
                        <div style="height: 8px; background: #f0f0f0; border-radius: 4px; overflow: hidden; margin-bottom: 12px;">
                            <div style="height: 100%; width: {{ $area['percentage'] ?? 0 }}%; background: var(--maroon);"></div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            @foreach ($area['subjects'] as $subject)
                                <div style="font-size: 0.85rem; display: flex; justify-content: space-between; padding: 4px 0;">
                                    <span class="muted">{{ $subject['name'] }}</span>
                                    <span style="font-weight: 600;">{{ $subject['percentage'] }}%</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Topics to practise next --}}
            <div class="chart-card">
                <h3 style="margin-bottom: 24px;">Recommended topics</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    @foreach ($recommended['weak'] as $topic)
                        <div style="padding: 16px; border: 1px solid var(--line); border-radius: 8px;">
                            <div class="muted" style="font-size: 0.75rem; text-transform: uppercase; margin-bottom: 4px;">{{ $topic['area'] }}</div>
                            <div style="font-weight: 700; margin-bottom: 8px;">{{ $topic['topic'] }}</div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span class="pill is-poor">{{ $topic['percentage'] }}% accuracy</span>
                                <a href="{{ route('review.practice', ['topic' => $topic['topic_id']]) }}" style="font-size: 0.85rem; font-weight: 700;">Practice →</a>
                            </div>
                        </div>
                    @endforeach
                    @foreach ($recommended['untouched'] as $topic)
                        <div style="padding: 16px; border: 1px solid var(--line); border-radius: 8px;">
                            <div class="muted" style="font-size: 0.75rem; text-transform: uppercase; margin-bottom: 4px;">{{ $topic['area'] }}</div>
                            <div style="font-weight: 700; margin-bottom: 8px;">{{ $topic['topic'] }}</div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span class="pill">Not started</span>
                                <a href="{{ route('review.practice', ['topic' => $topic['topic_id']]) }}" style="font-size: 0.85rem; font-weight: 700;">Start →</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div>
            {{-- Upcoming study goals --}}
            <div class="chart-card">
                <h3 style="margin-bottom: 20px;">Upcoming study goals</h3>
                @forelse ($goals['sessions'] as $session)
                    <div style="margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid var(--line);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
                            <div style="font-weight: 700;">{{ $session->title }}</div>
                            <div class="pill is-good" style="font-size: 0.7rem;">
                                @php($date = \Carbon\Carbon::parse($session->target_date)->toDateString())
                                {{ $date == now()->toDateString() ? 'Today' : ($date == now()->addDay()->toDateString() ? 'Tomorrow' : $date) }}
                            </div>
                        </div>
                        <div class="muted" style="font-size: 0.85rem;">🕒 {{ $session->target_time }}</div>
                    </div>
                @empty
                    <p class="muted" style="text-align: center; padding: 20px 0;">No upcoming goals.</p>
                @endforelse
                <a href="{{ route('account.planner') }}" class="btn btn-secondary btn-block btn-sm" style="margin-top: 8px;">Open Planner</a>
            </div>

            {{-- Subject shortcuts --}}
            <div style="margin-top: 32px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h3 style="margin: 0;">Your Subjects</h3>
                    <a href="{{ route('review.index') }}" style="font-weight: 600; font-size: 0.85rem;">View All</a>
                </div>
                <div style="display: grid; gap: 16px;">
                    <a href="{{ route('review.subject', ['subject' => 'prof-ed']) }}" class="subject-card" style="padding: 20px; text-align: left; display: flex; align-items: center; gap: 16px;">
                        <div class="subject-icon" style="margin: 0; width: 48px; height: 48px; font-size: 24px;">🎓</div>
                        <div>
                            <div style="font-weight: 700;">Prof Ed</div>
                            <small class="muted">Professional Education</small>
                        </div>
                    </a>
                    <a href="{{ route('review.subject', ['subject' => 'gen-ed']) }}" class="subject-card" style="padding: 20px; text-align: left; display: flex; align-items: center; gap: 16px;">
                        <div class="subject-icon" style="margin: 0; width: 48px; height: 48px; font-size: 24px;">📝</div>
                        <div>
                            <div style="font-weight: 700;">Gen Ed</div>
                            <small class="muted">General Education</small>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
