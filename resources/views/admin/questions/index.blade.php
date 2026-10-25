{{-- Render this view inside the shared layouts/app.blade.php master layout. --}}
@extends('layouts.app')

{{-- Sets the page/browser title via the layout's @yield('title'). --}}
@section('title', 'Manage Questions')

{{-- Opens the main content block injected into the layout. --}}
@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
        <div>
            <h1 style="margin: 0;">Manage Questions</h1>
            <p class="muted">Write, categorise, and verify the items in the reviewer.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.questions.create') }}">
            <span style="margin-right: 8px;">+</span> Add Question
        </a>
    </div>

    <div class="admin-toolbar">
        <form method="GET" action="{{ route('admin.questions.index') }}" class="admin-filters">
            <div class="field">
                <label for="search">Search Question</label>
                <input type="search" id="search" name="search" value="{{ $search }}" placeholder="Search question...">
            </div>

            <div class="field">
                <label for="subject">All Subjects</label>
                <select id="subject" name="subject">
                    <option value="">All Subjects</option>
                    @foreach ($subjects as $option)
                        <option value="{{ $option->id }}" @selected($subjectId === $option->id)>
                            {{ $option->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="verification">All Difficulty Levels</label>
                <select id="verification" name="verification">
                    <option value="">All Difficulty</option>
                    <option value="easy">Easy</option>
                    <option value="medium">Medium</option>
                    <option value="hard">Hard</option>
                </select>
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a class="btn btn-secondary btn-sm" href="{{ route('admin.questions.index') }}">Reset</a>
            </div>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th>Question</th>
                    <th>Subject</th>
                    <th>Difficulty</th>
                    <th style="width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($questions as $question)
                    <tr>
                        <td class="muted">{{ str_pad($question->id, 3, '0', STR_PAD_LEFT) }}</td>
                        <td>
                            <div style="font-weight: 600; color: var(--ink);">{{ Str::limit($question->prompt, 80) }}</div>
                        </td>
                        <td>
                            @if ($question->topic)
                                <span class="pill" style="background: var(--surface-alt);">{{ $question->topic->subject->name }}</span>
                            @else
                                <span class="muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            <span class="pill {{ $question->difficulty === 'hard' ? 'is-poor' : ($question->difficulty === 'medium' ? 'is-warning' : 'is-good') }}">
                                {{ ucfirst($question->difficulty ?? 'Medium') }}
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <a href="{{ route('admin.questions.edit', $question) }}" class="btn btn-secondary btn-sm" style="padding: 6px; border-radius: 4px;">✏️</a>
                                <form method="POST" action="{{ route('admin.questions.destroy', $question) }}" onsubmit="return confirm('Are you sure?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-secondary btn-sm" style="padding: 6px; border-radius: 4px; color: var(--danger);">🗑️</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center muted">No questions found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
   @if ($questions->hasPages())
        <div style="margin-top: 24px; display: flex; justify-content: flex-end;">
            {{ $questions->links() }}
        </div>
    @endif
@endsection
