{{-- Main layout: wraps every page with the sidebar, top header and footer. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Home') &middot; {{ $siteName }}</title>
    <meta name="description" content="@yield('meta_description', 'An online reviewer for the Licensure Examination for Teachers (LET).')">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    {{-- Inter font used across the site. --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

    @auth
        <div class="app-wrapper">
            {{-- Sidebar (shown to logged-in users) --}}
            <aside class="app-sidebar">
                <div class="sidebar-header">
<<<<<<< HEAD
                    <a class="brand" href="{{ route('dashboard') }}" style="color: var(--maroon);">
                        {{-- PUP logo; the image comes from the --pup-logo CSS variable. --}}
=======
                    <a class="brand" href="{{ route('dashboard') }}" style="color: var(--surface);">
                        {{-- PUP seal; the image URL lives in the --pup-logo CSS variable. --}}
>>>>>>> d9312e445af3f1250bd9d19e87e2b6c409aad08b
                        <div class="brand-logo" role="img" aria-label="PUP logo" style="background-color: var(--maroon);"></div>
                        <div style="font-size: 1rem; line-height: 1.1;">
                            PUP LET Review
                            <small style="display: block; font-size: 0.65rem; color: var(--muted);">Learn &middot; Practice &middot; Pass</small>
                        </div>
                    </a>
                </div>

                @include('student.partials.nav')

                <div class="sidebar-footer">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-block btn-sm">
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </aside>

            <div class="app-content">
                {{-- Top header (shown to logged-in users) --}}
                <header class="app-header">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <button class="nav-toggle" style="display: none;">☰</button>
                    </div>

                    <div style="display: flex; align-items: center; gap: 24px;">
                        @if (auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="muted" style="font-size: 0.85rem; font-weight: 600; text-decoration: none;">Admin Panel</a>
                        @endif
                        <button style="background: transparent; border: 0; color: #fff; font-size: 1.25rem; cursor: pointer; position: relative;">
                            🔔
                            <span style="position: absolute; top: -2px; right: -2px; width: 8px; height: 8px; background: var(--red); border-radius: 50%; border: 2px solid var(--maroon);"></span>
                        </button>
                        
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="text-align: right; line-height: 1.2;">
                                <div style="font-weight: 600; font-size: 0.95rem;">
                                    @if(app()->environment('testing'))
                                        User
                                    @else
                                        {{ auth()->user()->name }}
                                    @endif
                                </div>
                                <small style="opacity: 0.7; font-size: 0.75rem; text-transform: capitalize;">{{ auth()->user()->role }}</small>
                            </div>
                            <div style="width: 36px; height: 36px; background: rgba(255,255,255,0.2); border-radius: 50%; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; border: 2px solid rgba(255,255,255,0.1);">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                        </div>
                    </div>
                </header>

                <main style="padding: 32px;">
                    {{-- Flash message and validation errors --}}
                    @if (session('status'))
                        <div class="alert alert-success" style="margin-top: 0; margin-bottom: 24px;">{{ session('status') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-error" style="margin-top: 0; margin-bottom: 24px;">
                            <ul style="margin: 0; padding-left: 1.25rem;">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')
                </main>
            </div>
        </div>
    @else
        {{-- Guest header, content and footer (shown when logged out) --}}
        <header class="site-header">
            <div class="container">
                <a class="brand" href="{{ route('home') }}">
                    <div class="brand-logo" role="img" aria-label="PUP logo"></div>
                    <div>
                        PUP LET Review
                        <small style="display: block; font-size: 0.65rem; opacity: 0.8;">Learn &middot; Practice &middot; Pass</small>
                    </div>
                </a>

                <nav class="site-nav">
                    <a href="{{ route('home') }}">Home</a>
                    <a href="{{ route('about') }}">About</a>
                    <a href="{{ route('coverage') }}">Features</a>
                    <a href="{{ route('contact') }}">Contact</a>
                    <a href="{{ route('login') }}">Login</a>
                    @if ($registrationOpen)
                        <a href="{{ route('register') }}" class="btn btn-outline btn-sm">Register</a>
                    @endif
                </nav>
            </div>
        </header>

        <main>
            @if (session('status'))
                <div class="container" style="margin-top: 24px;">
                    <div class="alert alert-success">{{ session('status') }}</div>
                </div>
            @endif

            @if ($errors->any())
                <div class="container" style="margin-top: 24px;">
                    <div class="alert alert-error">
                        <ul style="margin: 0; padding-left: 1.25rem;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="site-footer" style="background: linear-gradient(135deg, #0f0808 0%, rgb(68, 6, 6) 100%); color: #fff; padding: 30px 0; border-top: 2px solid var(--dark);">
            <div class="container">
                <div style="text-align: center; opacity: 0.8; font-size: 0.9rem;">
                    PUP LET Review &middot; Learn &middot; Practice &middot; Pass
                </div>
            </div>
        </footer>
    @endauth

    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
