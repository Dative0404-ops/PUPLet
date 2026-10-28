{{-- Profile page: edit your details and see a summary of your activity. --}}
@extends('layouts.app')

@section('title', 'My profile')

@section('content')
    <section class="dashboard-hero">
        <div class="container">
            <span class="eyebrow">Student account</span>
            <h1>My profile</h1>
            <p class="track-line">
                Signed in as <strong>{{ $user->name }}</strong>
                &middot; LET track: <span class="badge">{{ $user->trackLabel() }}</span>
            </p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            @include('account.partials.nav')

            <div class="grid grid-2">
                {{-- Form to update your details --}}
                <div class="card">
                    <h2>Your details</h2>
                    <p class="muted">Change your display name, email address, and LET track.</p>

                    <form method="POST" action="{{ route('account.profile.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="field">
                            <label for="name">Full name</label>
                            <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required>
                        </div>

                        <div class="field">
                            <label for="email">Email address</label>
                            <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                        </div>

                        <div class="field">
                            <label for="exam_type">Exam type</label>
                            <select id="exam_type" name="exam_type" required>
                                @foreach ($examTypes as $value => $label)
                                    <option value="{{ $value }}" @selected(old('exam_type', $user->exam_type) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field">
                            <label for="specialization">Specialization</label>
                            <select id="specialization" name="specialization" required>
                                @foreach ($specializations as $examType => $options)
                                    <optgroup label="{{ $examTypes[$examType] }}" data-exam-type="{{ $examType }}">
                                        @foreach ($options as $option)
                                            <option value="{{ $option }}" data-exam-type="{{ $examType }}"
                                                @selected(old('specialization', $user->specialization) === $option)>{{ $option }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </form>
                </div>

                {{-- Snapshot of your reviewer activity --}}
                <div class="card">
                    <h2>Review at a glance</h2>
                    <p class="muted">A summary of everything you have done in the reviewer so far.</p>

                    <div class="account-facts">
                        <div class="fact">
                            <span class="fact-value">{{ $summary['attempts'] }}</span>
                            <span class="fact-label">Completed quizzes</span>
                        </div>
                        <div class="fact">
                            <span class="fact-value">{{ $summary['items'] }}</span>
                            <span class="fact-label">Items answered</span>
                        </div>
                        <div class="fact">
                            <span class="fact-value">{{ $summary['lessons'] }}</span>
                            <span class="fact-label">Lessons reviewed</span>
                        </div>
                        <div class="fact">
                            <span class="fact-value">{{ $summary['bookmarks'] }}</span>
                            <span class="fact-label">Saved questions</span>
                        </div>
                    </div>

                    <ul class="plain-list fact-list">
                        <li>
                            <span>Daily item target</span>
                            <strong>{{ $summary['daily_target'] }} items</strong>
                        </li>
                        <li>
                            <span>Practice timer</span>
                            <strong>{{ $summary['time_limit'] }}</strong>
                        </li>
                        <li>
                            <span>Page after signing in</span>
                            <strong>{{ $summary['landing'] }}</strong>
                        </li>
                    </ul>

                    <p class="muted">
                        Study target, timer, and landing page are set in
                        <a href="{{ route('account.settings') }}">account settings</a>.
                    </p>
                </div>
            </div>
        </div>
    </section>
@endsection
