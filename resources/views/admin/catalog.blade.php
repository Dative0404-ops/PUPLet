{{-- Admin page: organise the subjects and topics in the question bank. --}}
@extends('layouts.app')

@section('title', 'Manage Subjects and Topics')

@section('content')
    {{-- Page heading --}}
    <section class="dashboard-hero admin-hero">
        <div class="container">
            <span class="eyebrow">Admin panel</span>
            <h1>Manage Subjects and Topics</h1>
            <p class="track-line">Keep the question bank organised along the three LET areas.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            {{-- Tab bar shared by every admin page. --}}
            @include('admin.partials.nav')

            {{-- Form to add a new subject --}}
            <div class="card">
                <h3>Add a subject</h3>
                <form method="POST" action="{{ route('admin.catalog.subjects.store') }}" class="inline-form">
                    {{-- @csrf adds the hidden token that blocks cross-site request forgery. --}}
                    @csrf

                    <div class="field">
                        <label for="new-subject-area">Area</label>
                        <select id="new-subject-area" name="area" required>
                            @foreach ($areaLabels as $value => $label)
                                <option value="{{ $value }}" @selected(old('area') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="new-subject-name">Subject name</label>
                        {{-- old() re-fills the field with the previous entry after a validation error. --}}
                        <input type="text" id="new-subject-name" name="name" value="{{ old('name') }}" maxlength="120" required>
                    </div>

                    <div class="field">
                        <label for="new-subject-order">Order</label>
                        {{-- Leaving "Order" blank puts the new subject at the end. --}}
                        <input type="number" id="new-subject-order" name="sort_order" min="0" max="9999" placeholder="End">
                    </div>

                    <div class="field inline-form-actions">
                        <button type="submit" class="btn btn-primary btn-sm">Add subject</button>
                    </div>
                </form>
            </div>

            {{-- One card for each LET area --}}
            @foreach ($areas as $area)
                <div class="card">
                    <h3>{{ $area['label'] }}</h3>

                    @forelse ($area['subjects'] as $subject)
                        <div class="catalog-subject">
                            <div class="catalog-head">
                                <div>
                                    <strong>{{ $subject->name }}</strong>
                                    <small class="muted">
                                        {{ $subject->topics_count }} {{ Str::plural('topic', $subject->topics_count) }}
                                        &middot; order {{ $subject->sort_order }}
                                    </small>
                                </div>

                                {{-- Edit and delete buttons for this subject --}}
                                <div class="row-actions">
                                    <details class="inline-details">
                                        <summary class="btn btn-secondary btn-sm">Edit</summary>
                                        <form method="POST" action="{{ route('admin.catalog.subjects.update', $subject) }}" class="inline-form">
                                            {{-- CSRF token, required on any form that changes data. --}}
                                            @csrf
                                            {{-- @method('PUT') tells Laravel this is an update; HTML forms only send GET and POST. --}}
                                            @method('PUT')

                                            <div class="field">
                                                <label for="subject-area-{{ $subject->id }}">Area</label>
                                                <select id="subject-area-{{ $subject->id }}" name="area" required>
                                                    @foreach ($areaLabels as $value => $label)
                                                        <option value="{{ $value }}" @selected($subject->area === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="field">
                                                <label for="subject-name-{{ $subject->id }}">Name</label>
                                                <input type="text" id="subject-name-{{ $subject->id }}" name="name"
                                                       value="{{ $subject->name }}" maxlength="120" required>
                                            </div>

                                            <div class="field">
                                                <label for="subject-order-{{ $subject->id }}">Order</label>
                                                <input type="number" id="subject-order-{{ $subject->id }}" name="sort_order"
                                                       min="0" max="9999" value="{{ $subject->sort_order }}">
                                            </div>

                                            <button type="submit" class="btn btn-primary btn-sm">Save subject</button>
                                        </form>
                                    </details>

                                    {{-- Separate form, because one form cannot be nested inside another. --}}
                                    <form method="POST" action="{{ route('admin.catalog.subjects.destroy', $subject) }}">
                                        @csrf
                                        @method('DELETE')
                                        {{-- The controller refuses to delete a subject that still has topics. --}}
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </div>
                            </div>

                            {{-- Topics that belong to this subject --}}
                            <ul class="catalog-topics">
                                @forelse ($subject->topics as $topic)
                                    <li>
                                        <span>
                                            <strong>{{ $topic->name }}</strong>
                                            <small class="muted">{{ $topic->questions_count }} {{ Str::plural('question', $topic->questions_count) }}</small>
                                        </span>

                                        <div class="row-actions">
                                            <a class="btn btn-secondary btn-sm"
                                               href="{{ route('admin.questions.index', ['topic' => $topic->id]) }}">Questions</a>

                                            {{-- Collapsible panel holding the topic edit form --}}
                                            <details class="inline-details">
                                                <summary class="btn btn-secondary btn-sm">Edit</summary>
                                                <form method="POST" action="{{ route('admin.catalog.topics.update', $topic) }}" class="inline-form">
                                                    @csrf
                                                    @method('PUT')

                                                    <div class="field">
                                                        <label for="topic-subject-{{ $topic->id }}">Subject</label>
                                                        <select id="topic-subject-{{ $topic->id }}" name="subject_id" required>
                                                            {{-- Flattens every area into a list of its subjects. --}}
                                                            @foreach ($areas as $option)
                                                                @foreach ($option['subjects'] as $subjectOption)
                                                                    <option value="{{ $subjectOption->id }}" @selected($topic->subject_id === $subjectOption->id)>
                                                                        {{ $subjectOption->name }}
                                                                    </option>
                                                                @endforeach
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <div class="field">
                                                        <label for="topic-name-{{ $topic->id }}">Name</label>
                                                        <input type="text" id="topic-name-{{ $topic->id }}" name="name"
                                                               value="{{ $topic->name }}" maxlength="120" required>
                                                    </div>

                                                    <div class="field">
                                                        <label for="topic-order-{{ $topic->id }}">Order</label>
                                                        <input type="number" id="topic-order-{{ $topic->id }}" name="sort_order"
                                                               min="0" max="9999" value="{{ $topic->sort_order }}">
                                                    </div>

                                                    <button type="submit" class="btn btn-primary btn-sm">Save topic</button>
                                                </form>
                                            </details>

                                            {{-- Delete form for this topic --}}
                                            <form method="POST" action="{{ route('admin.catalog.topics.destroy', $topic) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                            </form>
                                        </div>
                                    </li>
                                @empty
                                    <li><span class="muted">No topics under this subject yet.</span></li>
                                @endforelse
                            </ul>

                            {{-- Form to add a new topic under this subject --}}
                            <form method="POST" action="{{ route('admin.catalog.topics.store') }}" class="inline-form">
                                @csrf
                                {{-- Tells the controller which subject the new topic belongs to. --}}
                                <input type="hidden" name="subject_id" value="{{ $subject->id }}">

                                <div class="field">
                                    <label for="topic-new-{{ $subject->id }}">New topic</label>
                                    <input type="text" id="topic-new-{{ $subject->id }}" name="name" maxlength="120"
                                           placeholder="Topic name" required>
                                </div>

                                <div class="field">
                                    <label for="topic-new-order-{{ $subject->id }}">Order</label>
                                    <input type="number" id="topic-new-order-{{ $subject->id }}" name="sort_order"
                                           min="0" max="9999" placeholder="End">
                                </div>

                                <div class="field inline-form-actions">
                                    <button type="submit" class="btn btn-primary btn-sm">Add topic</button>
                                </div>
                            </form>
                        </div>
                    @empty
                        <p class="muted">No subjects in this area yet.</p>
                    @endforelse
                </div>
            @endforeach
        </div>
    </section>
@endsection
