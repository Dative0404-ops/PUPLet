{{-- Public page: what the LET covers, area by area. --}}
@extends('layouts.app')

@section('title', 'LET coverage')
@section('meta_description', 'What the LET Reviewer covers: General Education, Professional Education, and the specialization areas for elementary and secondary takers.')

@section('content')
    {{-- Page heading --}}
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

    {{-- Exam structure and area weights --}}
    <section class="section">
        <div class="container">
            <h2>Exam structure</h2>
            <p class="section-intro">
                The weight of each area depends on whether you are taking the Elementary or the
                Secondary LET. The specialization area only applies to secondary takers.
            </p>

            {{-- Lets the table scroll sideways on small screens --}}
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Area</th>
                            <th>Elementary (BEED)</th>
                            <th>Secondary (BSED)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>General Education</td>
                            <td>40%</td>
                            <td>20%</td>
                        </tr>
                        <tr>
                            <td>Professional Education</td>
                            <td>60%</td>
                            <td>40%</td>
                        </tr>
                        <tr>
                            <td>Specialization</td>
                            <td>Not applicable</td>
                            <td>40%</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="notice" style="margin-top: 20px;">
                <p>
                    Percentage weights follow the PRC Table of Specifications for the LET. The
                    Commission updates the coverage and the item distribution from time to time, so
                    always confirm the figures against the latest PRC bulletin before the exam.
                </p>
            </div>
        </div>
    </section>

    {{-- General Education subjects --}}
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
                    <h3>Purposive Communication in English</h3>
                    <p class="muted">Communication for academic and professional purposes: reading, writing, speaking, and listening in English across disciplines.</p>
                </div>
                <div class="card">
                    <h3>Malayuning Komunikasyon</h3>
                    <p class="muted">Komunikasyon sa wikang Filipino para sa akademiko at propesyonal na gamit, kabilang ang pagbasa at pagsulat.</p>
                </div>
                <div class="card">
                    <h3>Science and Technology</h3>
                    <p class="muted">Core science and technology concepts and their applications to everyday life and society.</p>
                </div>
                <div class="card">
                    <h3>Mathematics in the Modern World</h3>
                    <p class="muted">Mathematics as a way of thinking: patterns, logic, data, and quantitative reasoning in real-world contexts.</p>
                </div>
                <div class="card">
                    <h3>Readings in Philippine History and Society</h3>
                    <p class="muted">Philippine history and society through primary sources, with emphasis on analysis and interpretation.</p>
                </div>
                <div class="card">
                    <h3>The Life and Works of Rizal</h3>
                    <p class="muted">The life, writings, and legacy of Jose Rizal and their role in Philippine nationhood.</p>
                </div>
                <div class="card">
                    <h3>Arts Appreciation</h3>
                    <p class="muted">Appreciation of the visual, performing, and literary arts and their cultural and aesthetic value.</p>
                </div>
                <div class="card">
                    <h3>Ethics</h3>
                    <p class="muted">Moral theories and their application to personal, professional, and societal decisions.</p>
                </div>
                <div class="card">
                    <h3>The Contemporary World</h3>
                    <p class="muted">Globalization, cultures, and the interconnected issues shaping today's world.</p>
                </div>
                <div class="card">
                    <h3>Understanding the Self</h3>
                    <p class="muted">The self from philosophical, psychological, and sociological perspectives.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Professional Education subjects --}}
    <section class="section">
        <div class="container">
            <h2>Professional Education</h2>
            <p class="section-intro">
                Professional Education carries the largest weight in the exam. It tests the
                theories, principles, and practices that define the teaching profession.
            </p>

            <div class="grid grid-2">
                <div class="card">
                    <h3>The Professional Teacher</h3>
                    <p class="muted">The teacher's roles and professional standards, the Code of Ethics for Professional Teachers, and the legal foundations of the profession.</p>
                </div>
                <div class="card">
                    <h3>The Teacher and the School Curriculum</h3>
                    <p class="muted">Curriculum planning, design, implementation, and evaluation, including the Philippine basic education curriculum.</p>
                </div>
                <div class="card">
                    <h3>Foundations of Teaching-Learning Process</h3>
                    <p class="muted">The philosophical, psychological, and sociological foundations that shape how learners learn and how teaching is organized.</p>
                </div>
                <div class="card">
                    <h3>Methods and Strategies of Teaching</h3>
                    <p class="muted">Instructional methods, learner-centered strategies, motivation, and classroom management for effective teaching.</p>
                </div>
                <div class="card">
                    <h3>Educational Technology</h3>
                    <p class="muted">Selection and integration of digital tools and instructional media, ICT-supported lesson design, and digital citizenship.</p>
                </div>
                <div class="card">
                    <h3>Headstart for Toddlers, Child, and Adolescent</h3>
                    <p class="muted">Growth and development from early childhood through adolescence, and the learner differences that shape classroom decisions.</p>
                </div>
                <div class="card">
                    <h3>Validating Theoretical Knowledge in the Actual Assessment of Learning</h3>
                    <p class="muted">Construction of assessment tools, item analysis, grading, feedback, and the use of assessment results to improve instruction.</p>
                </div>
                <div class="card">
                    <h3>Field Study and Teaching Internship</h3>
                    <p class="muted">Field study and practicum experiences that connect educational theory to real classroom practice.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Specialization: BEED content areas and BSED majors --}}
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

            {{-- How the coverage is used to build practice sets --}}
            <div class="card card-plain" style="margin-top: 20px;">
                <h3>How the reviewer uses this coverage</h3>
                <p class="muted">
                    Every practice item is tagged with its area, subject, and topic. When you choose
                    your exam type and specialization, the reviewer builds your practice sets from
                    the matching tags, so a secondary English taker and an elementary generalist get
                    different sets from the same question bank.
                </p>
                <a href="{{ route('register') }}" class="btn btn-primary">Start reviewing</a>
            </div>
        </div>
    </section>
@endsection
