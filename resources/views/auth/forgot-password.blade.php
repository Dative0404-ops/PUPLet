{{-- Forgot-password page: ask for a reset link by email. --}}
@extends('layouts.app')

{{-- Sets the browser tab title. --}}
@section('title', 'Forgot password')
{{-- Sets the page description for search engines. --}}
@section('meta_description', 'Request a password reset link for your LET Reviewer account.')

@section('content')
    {{-- Reset request card --}}
    <section class="section">
        <div class="container container-narrow">
            <div class="form-card">
                <h1>Forgot your password?</h1>
                <p class="muted">
                    Enter the email address you registered with and we will send you a link to
                    choose a new password.
                </p>

                {{-- Sending this form emails the reset link. --}}
                <form method="POST" action="{{ route('password.email') }}" novalidate>
                    {{-- @csrf is required on every POST form. --}}
                    @csrf

                    <div class="field">
                        <label for="email">Email address</label>
                        {{-- old() refills the email after a failed submit; the class marks the invalid state. --}}
                        <input id="email" name="email" type="email" value="{{ old('email') }}"
                               autocomplete="username" autofocus class="@error('email') is-invalid @enderror" required>
                        @error('email')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Email password reset link</button>
                </form>

                {{-- Only shown on the local development environment. --}}
                @if (app()->environment('local'))
                    {{-- Where to find the reset link when the log mailer is used. --}}
                    <div class="notice" style="margin-top: 20px;">
                        <p>
                            Development setup: outgoing mail is written to
                            <code>storage/logs/laravel.log</code> (MAIL_MAILER=log), so open that file
                            to get the reset link.
                        </p>
                    </div>
                @endif

                <div class="form-footer">
                    <p>Remembered it? <a href="{{ route('login') }}">Back to sign in</a>.</p>
                </div>
            </div>
        </div>
    </section>
@endsection
