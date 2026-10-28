{{-- Reset-password page: choose a new password from the emailed link. --}}
@extends('layouts.app')

{{-- Sets the browser tab title. --}}
@section('title', 'Reset password')
{{-- Sets the page description for search engines. --}}
@section('meta_description', 'Choose a new password for your LET Reviewer account.')

@section('content')
    {{-- Reset card --}}
    <section class="section">
        <div class="container container-narrow">
            <div class="form-card">
                <h1>Choose a new password</h1>
                <p class="muted">Set a new password for your account and sign in again afterwards.</p>

                {{-- Submitting this form saves the new password. --}}
                <form method="POST" action="{{ route('password.store') }}" novalidate>
                    {{-- @csrf is required on every POST form. --}}
                    @csrf
                    {{-- Hidden token from the reset link; the server checks it. --}}
                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                    <div class="field">
                        <label for="email">Email address</label>
                        {{-- old() refills the email after a failed submit; otherwise use the email from the link. --}}
                        <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}"
                               autocomplete="username" class="@error('email') is-invalid @enderror" required>
                        @error('email')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="password">New password</label>
                        {{-- Shows the invalid style when validation fails. --}}
                        <input id="password" name="password" type="password" autocomplete="new-password"
                               class="@error('password') is-invalid @enderror" required>
                        @error('password')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="password_confirmation">Confirm new password</label>
                        {{-- Must match the password field; Laravel checks this automatically. --}}
                        <input id="password_confirmation" name="password_confirmation" type="password"
                               autocomplete="new-password" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Reset password</button>
                </form>
            </div>
        </div>
    </section>
@endsection
