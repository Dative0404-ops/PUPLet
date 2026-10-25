{{-- @extends makes this page inherit the shared layout from layouts/app.blade.php. --}}
@extends('layouts.app')

{{-- Sets the browser-tab title via the layout's @yield('title'). --}}
@section('title', 'Reset password')
{{-- Fills the layout's <meta name="description"> slot. --}}
@section('meta_description', 'Choose a new password for your LET Reviewer account.')

{{-- Opens the main content block. --}}
@section('content')
    {{-- Centred section holding the reset-password card. --}}
    <section class="section">
        {{-- container-narrow narrows the form width. --}}
        <div class="container container-narrow">
            <div class="form-card">
                <h1>Choose a new password</h1>
                <p class="muted">Set a new password for your account and sign in again afterwards.</p>

                {{-- Posts the new password to the password.store route to complete the reset. --}}
                <form method="POST" action="{{ route('password.store') }}" novalidate>
                    {{-- @csrf adds the required anti-CSRF token. --}}
                    @csrf
                    {{-- Hidden field carrying the one-time reset token taken from the signed reset link URL. --}}
                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                    <div class="field">
                        <label for="email">Email address</label>
                        {{-- old('email', $request->email): use previous input, else the email passed by the reset link. --}}
                        <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}"
                               autocomplete="username" class="@error('email') is-invalid @enderror" required>
                        @error('email')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="password">New password</label>
                        {{-- The new password; its invalid state mirrors the "password" validation errors. --}}
                        <input id="password" name="password" type="password" autocomplete="new-password"
                               class="@error('password') is-invalid @enderror" required>
                        @error('password')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="password_confirmation">Confirm new password</label>
                        {{-- Must match the password field; Laravel's "confirmed" rule checks this. --}}
                        <input id="password_confirmation" name="password_confirmation" type="password"
                               autocomplete="new-password" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Reset password</button>
                </form>
            </div>
        </div>
    </section>
{{-- Closes the content section. --}}
@endsection
