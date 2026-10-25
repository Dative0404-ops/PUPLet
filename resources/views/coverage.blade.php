{{-- @extends makes this static content page inherit the shared layout (header, footer, styling). --}}
@extends('layouts.app')

{{-- Sets the browser-tab title for this page via the layout's @yield('title'). --}}
@section('title', 'LET coverage')
{{-- Fills the layout's <meta name="description"> slot with this page's summary. --}}
@section('meta_description', 'What the LET Reviewer covers: General Education, Professional Education, and the specialization areas for elementary and secondary takers.')

{{-- Opens the main content block rendered inside the layout's <main>. --}}
@section('content')
    {{-- Page header band with the eyebrow label, heading and introduction. --}}
    <section class="page-header">
        <div class="container">
            <span class="eyebrow">Coverage</span>
            <h1>What the reviewer covers</h1>
            <p>
                The review materials are grouped into the same areas used in the Licensure
                Examination for Teachers, so you can review the competencies that actually appear
                in your exam.
            </p>
        </div>
    </section>

    {{-- Section explaining the exam structure with a weight table. --}}
    <section class="section">
        <div class="container">
            <h2>Exam structure</h2>
            <p class="section-intro">
                The weight of each area depends on whether you are taking the Elementary or the
                Secondary LET. The specialization area only applies to secondary takers.
            </p>

            {{-- Wrapper that allows the table to scroll horizontally on small screens. --}}
            <div class="table-wrap">
                <table>
                    {{-- Table head row with one column per exam type. --}}
                    <thead>
                        <tr>
                            <th>Area</th>
                            <th>Elementary (BEED)</th>
                            <th>Secondary (BSED)</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Row: General Education weighting differs by track. --}}
                        <tr>
                            <td>General Education</td>
                            <td>40%</td>
                            <td>20%</td>
                        </tr>
                        {{-- Row: Professional Education weighting. --}}
                        <tr>
                            <td>Professional Education</td>
                            <td>60%</td>
                            <td>40%</td>
                        </tr>
                        {{-- Row: Specialization applies only to the secondary track. --}}
                        <tr>
                            <td>Specialization</td>
                            <td>Not applicable</td>
                            <td>40%</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- "notice" box styled as a callout; inline style adds top spacing. --}}
            <div class="notice" style="margin-top: 20px;">
                <p>
                    Percentage weights follow the PRC Table of Specifications for the LET. The
                    Commission updates the coverage and the item distribution from time to time, so
                    always confirm the figures against the latest PRC bulletin before the exam.
                </p>
            </div>
        </div>
    </section>

    {{-- General Education subjects in a three-column card grid; section-alt gives a contrasting band. --}}
    <section class="section section-alt">
        <div class="container">
            <h2>General Education</h2>
            <p class="section-intro">
                General Education covers the foundational content taught in the undergraduate
                teacher education curriculum, and it is part of both the elementary and secondary
                exams.
            </p>

            <div class="grid grid-3">
                <div class="card">
                    <h3>English</h3>
                    <p class="muted">Grammar and usage, reading comprehension, study and thinking skills, speech and oral communication, and Philippine and world literature.</p>
                </div>
                <div class="card">
                    <h3>Filipino</h3>
                    <p class="muted">Komunikasyon sa akademikong Filipino, masining na pagpapahayag, pagbasa at pagsulat, at panitikang Filipino.</p>
                </div>
                <div class="card">
                    <h3>Mathematics</h3>
                    <p class="muted">Fundamentals of mathematics, number theory, plane geometry, and basic statistics and probability.</p>
                </div>
                <div class="card">
                    <h3>Science</h3>
                    <p class="muted">General science, biological science, physical science, and earth science with emphasis on everyday applications.</p>
                </div>
                <div class="card">
                    <h3>Social Studies</h3>
                    <p class="muted">Philippine history, government and the Constitution, economics, society and culture, and basic geography.</p>
                </div>
                <div class="card">
                    <h3>Information and Communications Technology</h3>
                    <p class="muted">Basic computer concepts, productivity tools, internet literacy, and the responsible use of technology.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Professional Education subjects in a two-column card grid. --}}
    <section class="section">
        <div class="container">
            <h2>Professional Education</h2>
            <p class="section-intro">
                Professional Education carries the largest weight in the exam. It tests the
                theories, principles, and practices that define the teaching profession.
            </p>

            <div class="grid grid-2">
                <div class="card">
                    <h3>The Teaching Profession</h3>
                    <p class="muted">Philosophical, historical, legal, and sociological foundations of education, the Code of Ethics for Professional Teachers, and the professional standards for teachers.</p>
                </div>
                <div class="card">
                    <h3>Child and Adolescent Learners and Learning Principles</h3>
                    <p class="muted">Growth and development across the life span, learner differences, and the theories of development that shape classroom decisions.</p>
                </div>
                <div class="card">
                    <h3>Facilitating Learner-Centered Teaching</h3>
                    <p class="muted">Learning theories and their classroom applications, motivation, learner-centered strategies, and the management of learning environments.</p>
                </div>
                <div class="card">
                    <h3>The Teacher and the School Curriculum</h3>
                    <p class="muted">Curriculum planning, design, implementation, and evaluation, including the Philippine basic education curriculum.</p>
                </div>
                <div class="card">
                    <h3>Assessment in Learning</h3>
                    <p class="muted">Construction of assessment tools, item analysis, grading, feedback, and the use of assessment results to improve instruction.</p>
                </div>
                <div class="card">
                    <h3>Technology for Teaching and Learning</h3>
                    <p class="muted">Selection and integration of digital tools and instructional media, ICT-supported lesson design, and digital citizenship.</p>
                </div>
                <div class="card">
                    <h3>The Teacher and the Community</h3>
                    <p class="muted">School culture, organizational leadership, community partnerships, and the social dimensions of education.</p>
                </div>
                <div class="card">
                    <h3>Research and Reflective Practice</h3>
                    <p class="muted">Action research, reflective teaching, and the use of evidence to refine classroom practice.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Specialization section listing BEED content areas and BSED majors. --}}
    <section class="section section-alt">
        <div class="container">
            <h2>Specialization</h2>
            <p class="section-intro">
                Secondary takers answer a specialization test based on their major. Elementary takers
                review the content areas they will be teaching across grade levels.
            </p>

            <div class="grid grid-2">
                <div class="card">
                    <h3>Elementary (BEED) content areas</h3>
                    {{-- tag-list renders each item as a small pill/tag. --}}
                    <ul class="tag-list">
                        <li>English</li>
                        <li>Filipino</li>
                        <li>Mathematics</li>
                        <li>Science</li>
                        <li>Social Studies / Araling Panlipunan</li>
                        <li>MAPEH</li>
                        <li>EPP / TLE</li>
                        <li>Values Education</li>
                    </ul>
                </div>
                <div class="card">
                    <h3>Secondary (BSED) majors</h3>
                    <ul class="tag-list">
                        <li>English</li>
                        <li>Filipino</li>
                        <li>Mathematics</li>
                        <li>Science</li>
                        <li>Social Studies</li>
                        <li>MAPEH</li>
                        <li>TLE</li>
                        <li>Values Education</li>
                    </ul>
                </div>
            </div>

            {{-- Plain card explaining how the coverage drives practice-set generation; inline top margin. --}}
            <div class="card card-plain" style="margin-top: 20px;">
                <h3>How the reviewer uses this coverage</h3>
                <p class="muted">
                    Every practice item is tagged with its area, subject, and topic. When you choose
                    your exam type and specialization, the reviewer builds your practice sets from
                    the matching tags, so a secondary English taker and an elementary generalist get
                    different sets from the same question bank.
                </p>
                {{-- Call-to-action button into the registration page. --}}
                <a href="{{ route('register') }}" class="btn btn-primary">Start reviewing</a>
            </div>
        </div>
    </section>
{{-- Closes the content section opened near the top of this file. --}}
@endsection
