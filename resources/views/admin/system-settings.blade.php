{{-- Admin page: site-wide settings for the whole platform. --}}
@extends('layouts.app')

@section('title', 'System Settings')

@section('content')
    {{-- Page heading --}}
    <section class="dashboard-hero admin-hero">
        <div class="container">
            <span class="eyebrow">Admin panel</span>
            <h1>System Settings</h1>
            <p class="track-line">
                How the site introduces itself, the defaults new accounts start with, and whether
                registration is open.
            </p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            {{-- Tab bar shared by every admin page. --}}
            @include('admin.partials.nav')

            <div class="admin-form-layout">
                {{-- Form holding all the system settings --}}
                <div class="form-card">
                    <form method="POST" action="{{ route('admin.system-settings.update') }}">
                        @csrf
                        @method('PUT')

                        <h3>Site details</h3>

                        <div class="field">
                            <label for="name">Site name</label>
                            <input type="text"
                                   id="name"
                                   name="name"
                                   value="{{ old('name', $settings['name']) }}"
                                   maxlength="80"
                                   required>
                            <span class="field-help muted small">
                                Shown in the browser title and the footer of every page.
                            </span>
                        </div>

                        <div class="field">
                            <label for="support_email">Support email</label>
                            <input type="email"
                                   id="support_email"
                                   name="support_email"
                                   value="{{ old('support_email', $settings['support_email']) }}"
                                   maxlength="150"
                                   required>
                        </div>

                        <div class="grid grid-2">
                            <div class="field">
                                <label for="support_phone">Support telephone</label>
                                <input type="text"
                                       id="support_phone"
                                       name="support_phone"
                                       value="{{ old('support_phone', $settings['support_phone']) }}"
                                       maxlength="40"
                                       placeholder="Leave blank to hide">
                            </div>

                            <div class="field">
                                <label for="support_hours">Support hours</label>
                                <input type="text"
                                       id="support_hours"
                                       name="support_hours"
                                       value="{{ old('support_hours', $settings['support_hours']) }}"
                                       maxlength="80"
                                       placeholder="Leave blank to hide">
                            </div>
                        </div>

                        <p class="muted small">
                            The email, telephone, and hours appear on the contact page, the about page, and in the
                            site footer.
                        </p>

                        <h3>New account defaults</h3>

                        <div class="grid grid-2">
                            <div class="field">
                                <label for="default_daily_target_items">Daily item target</label>
                                <input type="number"
                                       id="default_daily_target_items"
                                       name="default_daily_target_items"
                                       value="{{ old('default_daily_target_items', $settings['default_daily_target_items']) }}"
                                       min="1"
                                       max="999"
                                       required>
                                <span class="field-help muted small">Students can change this on their profile.</span>
                            </div>

                            <div class="field">
                                <label for="default_practice_timer">Practice quiz timer</label>
                                <select id="default_practice_timer" name="default_practice_timer" required>
                                    @foreach ($timerChoices as $value => $label)
                                        <option value="{{ $value }}" @selected(old('default_practice_timer', $settings['default_practice_timer']) === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="field-help muted small">Students can change this in their settings.</span>
                            </div>
                        </div>

                        <h3>Examination and access</h3>

                        <div class="field">
                            <label for="mock_time_limit_minutes">Mock examination time limit (minutes)</label>
                            <input type="number"
                                   id="mock_time_limit_minutes"
                                   name="mock_time_limit_minutes"
                                   value="{{ old('mock_time_limit_minutes', $settings['mock_time_limit_minutes']) }}"
                                   min="1"
                                   max="600"
                                   required>
                            <span class="field-help muted small">
                                The PRC allots 150 minutes for the mock examination. Attempts already in progress keep
                                the limit they started with.
                            </span>
                        </div>

                        <div class="field">
                            <label class="option-correct">
                                <input type="checkbox"
                                       name="registration_enabled"
                                       value="1"
                                       @checked((string) old('registration_enabled', $settings['registration_enabled']) === '1')>
                                <span>Allow new students to register</span>
                            </label>
                            <span class="field-help muted small">
                                When this is off, the registration page is closed to new accounts. Existing students
                                keep their access.
                            </span>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Save the settings</button>
                            <a class="btn btn-secondary" href="{{ route('admin.dashboard') }}">Back to the dashboard</a>
                        </div>
                    </form>
                </div>

                {{-- Sidebar summarising what the settings control --}}
                <aside class="admin-form-side">
                    <div class="card">
                        <h3>What these settings change</h3>
                        <ul class="plain-list">
                            <li>The site name in the header, footer, and page titles.</li>
                            <li>The support email, telephone, and hours shown to visitors.</li>
                            <li>The daily item target and practice timer given to a new account.</li>
                            <li>The countdown on a new mock examination.</li>
                            <li>Whether the registration page accepts new students.</li>
                        </ul>
                    </div>

                    <div class="card">
                        <h3>Recommendation rules</h3>
                        <p class="muted small">
                            The rules behind the student recommendations live on the
                            <a href="{{ route('admin.dss-settings') }}">DSS Settings</a> page.
                        </p>
                    </div>
                </aside>
            </div>
        </div>
    </section>
@endsection
