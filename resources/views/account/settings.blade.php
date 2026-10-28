{{-- Account settings: study preferences, password change, and sign out. --}}
@extends('layouts.app')

@section('title', 'Account settings')

@section('content')
    <section class="dashboard-hero">
        <div class="container">
            <span class="eyebrow">Student account</span>
            <h1>Account settings</h1>
            <p class="track-line">Manage your study preferences, change your password, and sign out.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            @include('account.partials.nav')

            {{-- Two-column layout: preferences on the left, password change on the right --}}
            <div class="grid grid-2">
                <div class="card">
                    <h2>Study preferences</h2>
                    <p class="muted">
                        These defaults shape how the reviewer works for you. They take effect the next
                        time you start a practice quiz or sign in.
                    </p>

                    <form method="POST" action="{{ route('account.settings.preferences') }}">
                        @csrf
                        @method('PUT')

                        <div class="field">
                            <label for="daily_target_items">Daily item target</label>
                            <input id="daily_target_items" type="number" name="daily_target_items" min="5" max="500"
                                value="{{ old('daily_target_items', $user->daily_target_items) }}" required>
                            <small class="muted">How many questions you aim to answer each day (5 to 500).</small>
                        </div>

                        <div class="field">
                            <label for="practice_time_limit">Practice timer</label>
                            <select id="practice_time_limit" name="practice_time_limit" required>
                                @foreach ($timeLimitChoices as $value => $label)
                                    <option value="{{ $value }}" @selected(old('practice_time_limit', $timeLimitChoice) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <small class="muted">
                                Mock examinations always keep the full 150-minute limit, whichever option
                                you choose here.
                            </small>
                        </div>

                        <div class="field">
                            <label for="default_landing_page">Page after signing in</label>
                            <select id="default_landing_page" name="default_landing_page" required>
                                @foreach ($landingPages as $value => $page)
                                    <option value="{{ $value }}" @selected(old('default_landing_page', $user->default_landing_page) === $value)>{{ $page['label'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">Save preferences</button>
                    </form>
                </div>

                <div class="card">
                    <h2>Change password</h2>
                    <p class="muted">Use at least eight characters. You will stay signed in after changing it.</p>

                    <form method="POST" action="{{ route('account.settings.password') }}">
                        @csrf
                        @method('PUT')

                        <div class="field">
                            <label for="current_password">Current password</label>
                            <input id="current_password" type="password" name="current_password" autocomplete="current-password" required>
                        </div>

                        <div class="field">
                            <label for="password">New password</label>
                            <input id="password" type="password" name="password" autocomplete="new-password" required>
                        </div>

                        <div class="field">
                            <label for="password_confirmation">Confirm new password</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
                        </div>

                        <button type="submit" class="btn btn-primary">Change password</button>
                    </form>
                </div>
            </div>

            {{-- Sign out card --}}
            <div class="card signout-card">
                <div>
                    <h2>Sign out</h2>
                    <p class="muted">Sign out of this device. Your progress and history are saved.</p>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary">Sign out</button>
                </form>
            </div>
        </div>
    </section>
@endsection
