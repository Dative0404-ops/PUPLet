{{-- Public page: contact details and a feedback form. --}}
@extends('layouts.app')

@section('title', 'Contact and feedback')
@section('meta_description', 'Send a suggestion, report a problem, or ask a question about the LET Reviewer.')

@section('content')
    {{-- Reads the support contact details from the system settings --}}
    @php
        $email = \App\Support\SystemSettings::supportEmail();
        $phone = \App\Support\SystemSettings::supportPhone();
        $hours = \App\Support\SystemSettings::supportHours();
    @endphp

    {{-- Page heading --}}
    <section class="page-header">
        <div class="container">
            <span class="eyebrow">Contact</span>
            <h1>Contact and feedback</h1>
            <p>
                Found an error in a review item, have a topic suggestion, or need help with your
                account? Send us a message and we will get back to you.
            </p>
        </div>
    </section>

    {{-- Contact details on the left, feedback form on the right --}}
    <section class="section">
        <div class="container">
            <div class="grid grid-2">
                <div class="card">
                    <h2>Contact information</h2>
                    <ul class="info-list">
                        <li>
                            <strong>Email</strong>
                            <a href="mailto:{{ $email }}">{{ $email }}</a>
                        </li>
                        {{-- Only shown when a phone number is set --}}
                        @if ($phone)
                            <li>
                                <strong>Telephone</strong>
                                {{ $phone }}
                            </li>
                        @endif
                        <li>
                            <strong>Office</strong>
                            Capstone Project Office, College of Education<br>
                            Philippines
                        </li>
                        {{-- Only shown when office hours are set --}}
                        @if ($hours)
                            <li>
                                <strong>Office hours</strong>
                                {{ $hours }}
                            </li>
                        @endif
                        <li>
                            <strong>Response time</strong>
                            We usually reply within two to three working days.
                        </li>
                    </ul>

                    <p class="muted" style="margin-top: 16px;">
                        For account problems, include the email address you registered with so we can
                        look up your account faster.
                    </p>
                </div>

                <div class="form-card">
                    <h2>Send a message</h2>
                    <p class="muted">All fields are required. Your message is stored and reviewed by the project team.</p>

                    {{-- novalidate skips the browser's own checks; Laravel validates on the server. --}}
                    <form method="POST" action="{{ route('contact.store') }}" novalidate>
                        {{-- @csrf adds the hidden token that blocks cross-site request forgery. --}}
                        @csrf

                        <div class="field">
                            <label for="name">Full name</label>
                            {{-- old() re-fills the field with the previous entry after a failed submit. --}}
                            <input id="name" name="name" type="text" value="{{ old('name') }}"
                                   class="@error('name') is-invalid @enderror" required>
                            {{-- @error shows this text only when the field failed validation. --}}
                            @error('name')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="field">
                            <label for="email">Email address</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}"
                                   class="@error('email') is-invalid @enderror" required>
                            @error('email')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="field">
                            <label for="category">What is this about?</label>
                            <select id="category" name="category" class="@error('category') is-invalid @enderror" required>
                                <option value="">Select a category</option>
                                {{-- Builds the options from the Feedback::CATEGORIES list --}}
                                @foreach (\App\Models\Feedback::CATEGORIES as $value => $label)
                                    <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('category')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="field">
                            <label for="subject">Subject</label>
                            <input id="subject" name="subject" type="text" value="{{ old('subject') }}"
                                   class="@error('subject') is-invalid @enderror" required>
                            @error('subject')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="field">
                            <label for="message">Message</label>
                            <textarea id="message" name="message"
                                      class="@error('message') is-invalid @enderror" required>{{ old('message') }}</textarea>
                            @error('message')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Submit message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
