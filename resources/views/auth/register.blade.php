@extends('layouts.app')

@section('title', 'Create Account')
@section('meta_description', 'Register for a free PUP LET Reviewer account.')

@section('content')
    <div class="auth-layout">
        <div class="auth-sidebar">
            <div class="brand" style="position: absolute; top: 60px; left: 60px;">
                <div class="brand-logo" role="img" aria-label="PUP logo"></div>
                <div>
                    PUP LET Review
                    <small style="display: block; font-size: 0.65rem; opacity: 0.8;">Learn &middot; Practice &middot; Pass</small>
                </div>
            </div>

            <div style="max-width: 400px;">
                <p style="font-style: italic; font-size: 1.1rem; margin-bottom: 12px; line-height: 1.4;">
                    "Education is the most powerful weapon which you can use to change the world."
                </p>
                <p style="font-weight: 600; opacity: 0.8;">— Nelson Mandela</p>
            </div>
        </div>

        <div class="auth-content">
            <div class="auth-card">
                <div class="auth-header">
                    <h1 style="margin-bottom: 8px;">Create Your Account</h1>
                    <p class="muted">Join thousands of students preparing for the LET.</p>
                </div>

                <form method="POST" action="{{ route('register') }}" novalidate>
                    @csrf

                    <div class="field">
                        <label for="name">Full Name</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}"
                               autocomplete="name" autofocus class="@error('name') is-invalid @enderror" required
                               placeholder="Juan Dela Cruz">
                        @error('name')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="email">Email Address</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}"
                               autocomplete="username" class="@error('email') is-invalid @enderror" required
                               placeholder="name@example.com">
                        @error('email')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" autocomplete="new-password"
                               class="@error('password') is-invalid @enderror" required
                               placeholder="Min. 8 characters">
                        @error('password')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="password_confirmation">Confirm Password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password"
                               autocomplete="new-password" required
                               placeholder="Repeat your password">
                    </div>

                    <div class="field">
                        <label for="exam_type">Select Role</label>
                        <select id="exam_type" name="exam_type" class="@error('exam_type') is-invalid @enderror" required>
                            <option value="student">Student</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" style="padding: 14px; border-radius: 8px;">Register</button>
                </form>

                <div style="text-align: center; margin-top: 40px;">
                    <p class="muted">Already have an account? <a href="{{ route('login') }}" style="color: var(--maroon); font-weight: 700;">Login</a></p>
                </div>
            </div>
        </div>
    </div>
@endsection
