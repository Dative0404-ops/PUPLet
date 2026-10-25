{{-- @extends makes this page inherit the shared layout from layouts/app.blade.php. --}}
@extends('layouts.app')

{{-- Sets the browser-tab title via the layout's @yield('title'). --}}
@section('title', 'Contact and feedback')
{{-- Fills the layout's <meta name="description"> slot. --}}
@section('meta_description', 'Send a suggestion, report a problem, or ask a question about the LET Reviewer.')

{{-- Opens the main content block. --}}
@section('content')
    {{-- @php ... @endphp is a multi-line PHP block; here it reads the support contact details from system settings. --}}
    @php
        $email = \App\Support\SystemSettings::supportEmail();
        $phone = \App\Support\SystemSettings::supportPhone();
        $hours = \App\Support\SystemSettings::supportHours();
    @endphp

    {{-- Page header band. --}}
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

    {{-- Two-column layout: contact details on the left, the feedback form on the right. --}}
    <section class="section">
        <div class="container">
            <div class="grid grid-2">
                <div class="card">
                    <h2>Contact information</h2>
                    {{-- Styled definition-style list of contact channels. --}}
                    <ul class="info-list">
                        <li>
                            <strong>Email</strong>
                            {{-- mailto link built from the $email value read in the @php block above. --}}
                            <a href="mailto:{{ $email }}">{{ $email }}</a>
                        </li>
                        {{-- Phone is optional: this list item only renders when $phone is not null. --}}
                        @if ($phone)
                            <li>
                                <strong>Telephone</strong>
                                {{-- Prints the configured support phone number. --}}
                                {{ $phone }}
                            </li>
                        @endif
                        <li>
                            <strong>Office</strong>
                            Capstone Project Office, College of Education<br>
                            Philippines
                        </li>
                        {{-- Office hours are optional: rendered only when configured. --}}
                        @if ($hours)
                            <li>
                                <strong>Office hours</strong>
                                {{-- Prints the configured support hours. --}}
                                {{ $hours }}
                            </li>
                        @endif
                        <li>
                            <strong>Response time</strong>
                            We usually reply within two to three working days.
                        </li>
                    </ul>

                    {{-- Helper paragraph; inline style adds top margin. --}}
                    <p class="muted" style="margin-top: 16px;">
                        For account problems, include the email address you registered with so we can
                        look up your account faster.
                    </p>
                </div>

                <div class="form-card">
                    <h2>Send a message</h2>
                    <p class="muted">All fields are required. Your message is stored and reviewed by the project team.</p>

                    {{-- Feedback form posts to the contact.store route; novalidate disables native browser validation (Laravel validates server-side). --}}
                    <form method="POST" action="{{ route('contact.store') }}" novalidate>
                        {{-- @csrf injects the hidden token that protects the POST from cross-site request forgery. --}}
                        @csrf

                        <div class="field">
                            <label for="name">Full name</label>
                            {{-- old('name') re-fills the field with the previous input after a failed submit. --}}
                            {{-- class expression adds "is-invalid" only when the "name" field has an error. --}}
                            <input id="name" name="name" type="text" value="{{ old('name') }}"
                                   class="@error('name') is-invalid @enderror" required>
                            {{-- @error shows this span only when validation failed for "name"; $message is the error text. --}}
                            @error('name')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="field">
                            <label for="email">Email address</label>
                            {{-- Repopulates the email input on validation failure. --}}
                            <input id="email" name="email" type="email" value="{{ old('email') }}"
                                   class="@error('email') is-invalid @enderror" required>
                            @error('email')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="field">
                            <label for="category">What is this about?</label>
                            {{-- Select whose invalid state mirrors the "category" validation errors. --}}
                            <select id="category" name="category" class="@error('category') is-invalid @enderror" required>
                                <option value="">Select a category</option>
                                {{-- Iterates the Feedback::CATEGORIES constant (key => human label) to build the options. --}}
                                @foreach (\App\Models\Feedback::CATEGORIES as $value => $label)
                                    {{-- @selected(old('category') === $value) marks the previously chosen option as selected. --}}
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
                            {{-- Textarea content is the old input; escaped by {{ }} for safety. --}}
                            <textarea id="message" name="message"
                                      class="@error('message') is-invalid @enderror" required>{{ old('message') }}</textarea>
                            @error('message')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- btn-block makes the submit button full width. --}}
                        <button type="submit" class="btn btn-primary btn-block">Submit message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
{{-- Closes the content section. --}}
@endsection
