@extends('layouts.app')

@section('title', 'Login')
@section('meta_description', 'Sign in to your PUPLeT account.')

@section('content')
    <div class="auth-layout">
        <div class="auth-sidebar">
            <div class="brand" style="position: absolute; top: 60px; left: 60px;">
                <div class="brand-logo" role="img" aria-label="PUP logo"></div>
                <div>
                    PUPLeT
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
                    <h1 style="margin-bottom: 8px;">Login to Your Account</h1>
                    <p class="muted">Enter your credentials to access your dashboard.</p>
                </div>

                <form method="POST" action="{{ route('login') }}" novalidate>
                    @csrf

                    <div class="field">
                        <label for="email">Email Address</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}"
                               autocomplete="username" autofocus class="@error('email') is-invalid @enderror" required
                               placeholder="name@example.com">
                        @error('email')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <label for="password" style="margin-bottom: 0;">Password</label>
                            <a href="{{ route('password.request') }}" style="font-size: 0.85rem; color: var(--red);">Forgot password?</a>
                        </div>
                        <input id="password" name="password" type="password" autocomplete="current-password"
                               class="@error('password') is-invalid @enderror" required
                               placeholder="Enter your password">
                        @error('password')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field">
                        <label class="checkbox">
                            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                            Remember me
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" style="padding: 14px; border-radius: 8px;">Login</button>
                </form>

                <div style="text-align: center; margin: 32px 0; position: relative;">
                    <hr style="border: 0; border-top: 1px solid var(--line);">
                    <span style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 0 16px; color: var(--muted); font-size: 0.85rem;">or continue with</span>
                </div>

                <div class="social-login">
                    <button type="button" class="social-btn">
                        <img src="https://www.google.com/favicon.ico" alt="Google" width="16"> Google
                    </button>
                    <button type="button" class="social-btn">
                        <img src="https://www.microsoft.com/favicon.ico" alt="Microsoft" width="16"> Microsoft
                    </button>
                </div>

                <div style="text-align: center; margin-top: 40px;">
                    @if (\App\Support\SystemSettings::registrationEnabled())
                        <p class="muted">Don't have an account? <a href="{{ route('register') }}" style="color: var(--maroon); font-weight: 700;">Register</a></p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
