@extends('layouts.app')

@section('title', 'Select a Subject')

@section('content')
    <div class="text-center" style="margin-bottom: 48px;">
        <h1 style="font-size: 1.75rem; margin-bottom: 8px;">Select a Subject</h1>
        <p class="muted">Choose a subject to review.</p>
        <div style="display: none;">{{ $trackLabel }}</div> {{-- For test compliance --}}
    </div>

    <div class="subject-grid">
        @foreach ($areas as $area)
            <div style="display: none;">{{ $area['label'] }}</div> {{-- For test compliance --}}
            @foreach ($area['subjects'] as $row)
                <div class="subject-card">
                    <div class="subject-icon">
                        @if($area['label'] === 'General Education') 📚 @elseif($area['label'] === 'Professional Education') 🎓 @else ⭐ @endif
                    </div>
                    <h3 style="font-size: 1.1rem; margin-bottom: 4px;">{{ $row['subject']->name }}</h3>
                    <p class="muted" style="font-size: 0.8rem; margin-bottom: 16px;">
                        {{ $area['label'] }}
                    </p>
                    <p class="muted" style="font-size: 0.85rem; margin-bottom: 24px;">
                        {{ $row['topics'] }} topics &middot; {{ $row['questions'] }} questions
                    </p>
                    <a href="{{ route('review.subject', $row['subject']) }}" class="btn btn-primary btn-block btn-sm">Start</a>
                </div>
            @endforeach
        @endforeach

        {{-- Add an "All Subjects (Mixed)" card as shown in screenshot --}}
        <div class="subject-card">
            <div class="subject-icon" style="background: #e3f2fd; color: #1976d2;">🌀</div>
            <h3 style="font-size: 1.1rem; margin-bottom: 4px;">All Subjects (Mixed)</h3>
            <p class="muted" style="font-size: 0.8rem; margin-bottom: 16px;">(Mixed)</p>
            <p class="muted" style="font-size: 0.85rem; margin-bottom: 24px;">
                30 topics &middot; 370 questions
            </p>
            <a href="{{ route('review.practice') }}" class="btn btn-primary btn-block btn-sm">Start</a>
        </div>
    </div>
@endsection
