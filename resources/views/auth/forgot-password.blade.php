{{-- @extends makes this page inherit the shared layout from layouts/app.blade.php. --}}
@extends('layouts.app')

{{-- Sets the browser-tab title via the layout's @yield('title'). --}}
@section('title', 'Forgot password')
{{-- Fills the layout's <meta name="description"> slot. --}}
@section('meta_description', 'Request a password reset link for your LET Reviewer account.')

{{-- Opens the main content block. --}}
@section('content')
    {{-- Centred section holding the password-reset request card. --}}
    <section class="section">
        {{-- container-narrow narrows the form width. --}}
        <div class="container container-narrow">
            <div class="form-card">
                <h1>Forgot your password?</h1>
                <p class="muted">
                    Enter the email address you registered with and we will send you a link to
                    choose a new password.
                </p>

                {{-- Posts the email to the password.email route, which triggers the reset email. --}}
                <form method="POST" action="{{ route('password.email') }}" novalidate>
                    {{-- @csrf adds the required anti-CSRF token. --}}
                    @csrf

                    <div class="field">
                        <label for="email">Email address</label>
                        {{-- Repopulates the email on a failed submit; class flags the invalid state. --}}
                        <input id="email" name="email" type="email" value="{{ old('email') }}"
                               autocomplete="username" autofocus class="@error('email') is-invalid @enderror" required>
                        @error('email')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Email password reset link</button>
                </form>

                {{-- app()->environment('local') is true only in the local dev environment. --}}
                @if (app()->environment('local'))
                    {{-- Dev-only helper explaining where the mail is written when using the log mailer. --}}
                    <div class="notice" style="margin-top: 20px;">
                        <p>
                            Development setup: outgoing mail is written to
                            <code>storage/logs/laravel.log</code> (MAIL_MAILER=log), so open that file
                            to get the reset link.
                        </p>
                    </div>
                @endif

                <div class="form-footer">
                    {{-- Link back to the sign-in page. --}}
                    <p>Remembered it? <a href="{{ route('login') }}">Back to sign in</a>.</p>
                </div>
            </div>
        </div>
    </section>
{{-- Closes the content section. --}}
@endsection
