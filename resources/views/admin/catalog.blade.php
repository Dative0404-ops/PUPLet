{{-- Render this view inside the shared layouts/app.blade.php master layout. --}}
@extends('layouts.app')

{{-- Sets the page/browser title via the layout's @yield('title'). --}}
@section('title', 'Manage Subjects and Topics')

{{-- Opens the main content block injected into the layout. --}}
@section('content')
    {{-- Hero banner for the admin page. --}}
    <section class="dashboard-hero admin-hero">
        {{-- Centring container. --}}
        <div class="container">
            {{-- Small label above the heading. --}}
            <span class="eyebrow">Admin panel</span>
            {{-- Page heading. --}}
            <h1>Manage Subjects and Topics</h1>
            {{-- Explanatory sub-line. --}}
            <p class="track-line">Keep the question bank organised along the three LET areas.</p>
        </div>
    </section>

    {{-- Main body section. --}}
    <section class="section">
        {{-- Centring container. --}}
        <div class="container">
            {{-- Shared admin tab bar. --}}
            @include('admin.partials.nav')

            {{-- Card containing the "add a subject" form. --}}
            <div class="card">
                <h3>Add a subject</h3>
                {{-- Form posts to the subject-store route; forms cannot send PUT/DELETE without help. --}}
                <form method="POST" action="{{ route('admin.catalog.subjects.store') }}" class="inline-form">
                    {{-- @csrf inserts Laravel's hidden CSRF token field to block cross-site request forgery. --}}
                    @csrf

                    {{-- Field group for the LET area. --}}
                    <div class="field">
                        <label for="new-subject-area">Area</label>
                        <select id="new-subject-area" name="area" required>
                            {{-- $areaLabels is a value=>label map; loops over each LET area. --}}
                            @foreach ($areaLabels as $value => $label)
                                {{-- @selected($cond) adds "selected" only for the option that matches old('area'). --}}
                                <option value="{{ $value }}" @selected(old('area') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Field group for the subject name. --}}
                    <div class="field">
                        <label for="new-subject-name">Subject name</label>
                        {{-- old('name') re-fills the field with the previous submission after a validation error. --}}
                        <input type="text" id="new-subject-name" name="name" value="{{ old('name') }}" maxlength="120" required>
                    </div>

                    {{-- Field group for the optional display order. --}}
                    <div class="field">
                        <label for="new-subject-order">Order</label>
                        {{-- Number input; placeholder "End" hints that leaving it blank appends the subject. --}}
                        <input type="number" id="new-subject-order" name="sort_order" min="0" max="9999" placeholder="End">
                    </div>

                    {{-- Submit row for the form. --}}
                    <div class="field inline-form-actions">
                        <button type="submit" class="btn btn-primary btn-sm">Add subject</button>
                    </div>
                </form>
            </div>

            {{-- One card per LET area returned by the controller. --}}
            @foreach ($areas as $area)
                <div class="card">
                    {{-- Area heading. --}}
                    <h3>{{ $area['label'] }}</h3>

                    {{-- @forelse lists the area's subjects and shows @empty when there are none. --}}
                    @forelse ($area['subjects'] as $subject)
                        <div class="catalog-subject">
                            {{-- Header row of the subject: name, counts, and its action buttons. --}}
                            <div class="catalog-head">
                                <div>
                                    {{-- Subject name, bold. --}}
                                    <strong>{{ $subject->name }}</strong>
                                    <small class="muted">
                                        {{-- Prints the topic count; Str::plural picks topic/topics correctly. --}}
                                        {{ $subject->topics_count }} {{ Str::plural('topic', $subject->topics_count) }}
                                        {{-- Middle dot separator, then the subject's sort order. --}}
                                        &middot; order {{ $subject->sort_order }}
                                    </small>
                                </div>

                                {{-- Action buttons for this subject. --}}
                                <div class="row-actions">
                                    {{-- <details> is a native collapsible panel; <summary> is its clickable title. --}}
                                    <details class="inline-details">
                                        <summary class="btn btn-secondary btn-sm">Edit</summary>
                                        {{-- Edit form posts to the update route for this specific subject. --}}
                                        <form method="POST" action="{{ route('admin.catalog.subjects.update', $subject) }}" class="inline-form">
                                            {{-- CSRF token required on every state-changing form. --}}
                                            @csrf
                                            {{-- @method('PUT') spoofs the PUT verb since HTML forms only send GET/POST. --}}
                                            @method('PUT')

                                            {{-- Field group for the area. --}}
                                            <div class="field">
                                                <label for="subject-area-{{ $subject->id }}">Area</label>
                                                <select id="subject-area-{{ $subject->id }}" name="area" required>
                                                    {{-- Loop the LET areas for this select. --}}
                                                    @foreach ($areaLabels as $value => $label)
                                                        {{-- Pre-selects the option matching this subject's current area. --}}
                                                        <option value="{{ $value }}" @selected($subject->area === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            {{-- Field group for the name. --}}
                                            <div class="field">
                                                <label for="subject-name-{{ $subject->id }}">Name</label>
                                                {{-- Pre-filled with the subject's current name. --}}
                                                <input type="text" id="subject-name-{{ $subject->id }}" name="name"
                                                       value="{{ $subject->name }}" maxlength="120" required>
                                            </div>

                                            {{-- Field group for the display order. --}}
                                            <div class="field">
                                                <label for="subject-order-{{ $subject->id }}">Order</label>
                                                {{-- Pre-filled with the subject's current sort order. --}}
                                                <input type="number" id="subject-order-{{ $subject->id }}" name="sort_order"
                                                       min="0" max="9999" value="{{ $subject->sort_order }}">
                                            </div>

                                            <button type="submit" class="btn btn-primary btn-sm">Save subject</button>
                                        </form>
                                    </details>

                                    {{-- Delete form; a nested form cannot be inside the edit form above. --}}
                                    <form method="POST" action="{{ route('admin.catalog.subjects.destroy', $subject) }}">
                                        {{-- CSRF token. --}}
                                        @csrf
                                        {{-- Spoofs the DELETE verb. --}}
                                        @method('DELETE')
                                        {{-- The controller refuses to delete a subject that still has topics. --}}
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </div>
                            </div>

                            {{-- List of topics that belong to this subject. --}}
                            <ul class="catalog-topics">
                                {{-- Loop the subject's topics; @empty shows a message when none exist. --}}
                                @forelse ($subject->topics as $topic)
                                    <li>
                                        <span>
                                            {{-- Topic name, bold. --}}
                                            <strong>{{ $topic->name }}</strong>
                                            {{-- Question count with correctly pluralised noun. --}}
                                            <small class="muted">{{ $topic->questions_count }} {{ Str::plural('question', $topic->questions_count) }}</small>
                                        </span>

                                        {{-- Row actions for this topic. --}}
                                        <div class="row-actions">
                                            {{-- Link to the question list filtered to this topic. --}}
                                            <a class="btn btn-secondary btn-sm"
                                               href="{{ route('admin.questions.index', ['topic' => $topic->id]) }}">Questions</a>

                                            {{-- Collapsible panel holding the topic edit form. --}}
                                            <details class="inline-details">
                                                <summary class="btn btn-secondary btn-sm">Edit</summary>
                                                {{-- Posts to this topic's update route. --}}
                                                <form method="POST" action="{{ route('admin.catalog.topics.update', $topic) }}" class="inline-form">
                                                    {{-- CSRF token. --}}
                                                    @csrf
                                                    {{-- Spoofs the PUT verb. --}}
                                                    @method('PUT')

                                                    {{-- Field group for the parent subject. --}}
                                                    <div class="field">
                                                        <label for="topic-subject-{{ $topic->id }}">Subject</label>
                                                        <select id="topic-subject-{{ $topic->id }}" name="subject_id" required>
                                                            {{-- Nested loops flatten every area into its subjects. --}}
                                                            @foreach ($areas as $option)
                                                                @foreach ($option['subjects'] as $subjectOption)
                                                                    {{-- Marks the option matching this topic's current subject. --}}
                                                                    <option value="{{ $subjectOption->id }}" @selected($topic->subject_id === $subjectOption->id)>
                                                                        {{ $subjectOption->name }}
                                                                    </option>
                                                                @endforeach
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    {{-- Field group for the topic name. --}}
                                                    <div class="field">
                                                        <label for="topic-name-{{ $topic->id }}">Name</label>
                                                        {{-- Pre-filled with the topic's current name. --}}
                                                        <input type="text" id="topic-name-{{ $topic->id }}" name="name"
                                                               value="{{ $topic->name }}" maxlength="120" required>
                                                    </div>

                                                    {{-- Field group for the topic's display order. --}}
                                                    <div class="field">
                                                        <label for="topic-order-{{ $topic->id }}">Order</label>
                                                        {{-- Pre-filled with the topic's current sort order. --}}
                                                        <input type="number" id="topic-order-{{ $topic->id }}" name="sort_order"
                                                               min="0" max="9999" value="{{ $topic->sort_order }}">
                                                    </div>

                                                    <button type="submit" class="btn btn-primary btn-sm">Save topic</button>
                                                </form>
                                            </details>

                                            {{-- Delete form for this topic. --}}
                                            <form method="POST" action="{{ route('admin.catalog.topics.destroy', $topic) }}">
                                                {{-- CSRF token. --}}
                                                @csrf
                                                {{-- Spoofs the DELETE verb. --}}
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                            </form>
                                        </div>
                                    </li>
                                {{-- Fallback row when the subject has no topics. --}}
                                @empty
                                    <li><span class="muted">No topics under this subject yet.</span></li>
                                @endforelse
                            </ul>

                            {{-- Inline form to create a new topic under this subject. --}}
                            <form method="POST" action="{{ route('admin.catalog.topics.store') }}" class="inline-form">
                                {{-- CSRF token. --}}
                                @csrf
                                {{-- Hidden field telling the controller which subject the new topic belongs to. --}}
                                <input type="hidden" name="subject_id" value="{{ $subject->id }}">

                                {{-- Field group for the new topic's name. --}}
                                <div class="field">
                                    <label for="topic-new-{{ $subject->id }}">New topic</label>
                                    <input type="text" id="topic-new-{{ $subject->id }}" name="name" maxlength="120"
                                           placeholder="Topic name" required>
                                </div>

                                {{-- Field group for the new topic's optional order. --}}
                                <div class="field">
                                    <label for="topic-new-order-{{ $subject->id }}">Order</label>
                                    <input type="number" id="topic-new-order-{{ $subject->id }}" name="sort_order"
                                           min="0" max="9999" placeholder="End">
                                </div>

                                {{-- Submit row. --}}
                                <div class="field inline-form-actions">
                                    <button type="submit" class="btn btn-primary btn-sm">Add topic</button>
                                </div>
                            </form>
                        </div>
                    {{-- Fallback when this area has no subjects at all. --}}
                    @empty
                        <p class="muted">No subjects in this area yet.</p>
                    @endforelse
                </div>
            @endforeach
        </div>
    </section>
@endsection
