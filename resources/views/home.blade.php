@extends('layouts.app')

@section('title', 'Home')
@section('meta_description', 'LET Reviewer is an online review platform for the Licensure Examination for Teachers.')

@section('content')
    {{-- Modern Home Hero Section --}}
    <section class="home-hero">
        <div class="container">
            <div class="hero-content">
                <h1 style="line-height: 1.1; margin-bottom: 24px;">Your Path to Passing the LET Starts Here</h1>
                <p style="font-size: 1.25rem; margin-bottom: 40px; opacity: 0.9;">
                    An adaptive review system designed for PUP students, helping you study smarter, practice better, and achieve your dreams.
                </p>
                <div class="hero-actions">
                    <a href="{{ route('register') }}" class="btn btn-primary" style="padding: 14px 32px; border-radius: 100px;">Get Started</a>
                    <a href="{{ route('about') }}" class="btn btn-outline" style="padding: 14px 32px; border-radius: 100px;">Learn More</a>
                </div>
            </div>
        </div>
    </section>

    {{-- Feature Icons Strip --}}
    <section style="background: #fff; padding: 40px 0; border-bottom: 1px solid var(--line);">
        <div class="container">
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px;">
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div style="width: 50px; height: 50px; background: var(--maroon-light); color: var(--maroon); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px;">⚙️</div>
                    <div>
                        <div style="font-weight: 700; font-size: 1.1rem; margin-bottom: 4px; color: var(--maroon);">Adaptive Learning</div>
                        <p class="muted mb-0" style="font-size: 0.85rem;">Personalized study plan based on your performance.</p>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div style="width: 50px; height: 50px; background: var(--maroon-light); color: var(--maroon); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px;">📄</div>
                    <div>
                        <div style="font-weight: 700; font-size: 1.1rem; margin-bottom: 4px; color: var(--maroon);">Practice Tests</div>
                        <p class="muted mb-0" style="font-size: 0.85rem;">Simulated LET-style questions with detailed explanations.</p>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div style="width: 50px; height: 50px; background: var(--maroon-light); color: var(--maroon); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px;">📈</div>
                    <div>
                        <div style="font-weight: 700; font-size: 1.1rem; margin-bottom: 4px; color: var(--maroon);">Track Your Progress</div>
                        <p class="muted mb-0" style="font-size: 0.85rem;">Monitor your scores and improve over time.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="section">
        <div class="container text-center">
            <h2 style="margin-bottom: 48px;">Complete Review Coverage</h2>
            <div class="grid grid-3">
                <div class="card">
                    <div class="subject-icon">📝</div>
                    <h3>General Education</h3>
                    <p class="muted">Purposive Communication, Malayuning Komunikasyon, Mathematics, Science and Technology, and more.</p>
                </div>
                <div class="card">
                    <div class="subject-icon">🎓</div>
                    <h3>Professional Education</h3>
                    <p class="muted">The Professional Teacher, Curriculum, Teaching Methods, Assessment, and more.</p>
                </div>
                <div class="card">
                    <div class="subject-icon">⭐</div>
                    <h3>Specialization</h3>
                    <p class="muted">Major subjects for BSED students across various disciplines.</p>
                </div>
            </div>
        </div>
    </section>
@endsection
