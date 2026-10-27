# PUP LET Review - Technical Documentation

## 1. System Overview
The **PUP LET Reviewer** is a web-based adaptive learning platform designed to help students prepare for the Licensure Examination for Teachers (LET). It provides a structured review environment with personalized recommendations, mock exams, and progress tracking.

## 2. Technology Stack
- **Framework**: Laravel 12.x
- **Language**: PHP 8.2+
- **Database**: 
  - **Local**: SQLite (for high-speed development)
  - **Production**: PostgreSQL (via Supabase)
- **Frontend**: Blade Templates, Native CSS (institutional maroon theme)
- **Testing**: PHPUnit (Feature and Unit testing)

## 3. Architecture & Request Flow
The application follows the standard **Laravel MVC** (Model-View-Controller) architecture:

1.  **Routing**: Routes are defined in [web.php](file:///c:/Users/Dell/Desktop/Capstone/routes/web.php), separated into Public, Guest, Student, and Admin groups.
2.  **Middleware**: 
    - `auth`: Restricts access to signed-in users.
    - `admin`: Restricts access to administrators ([EnsureUserIsAdmin.php](file:///c:/Users/Dell/Desktop/Capstone/app/Http/Middleware/EnsureUserIsAdmin.php)).
3.  **Controllers**: Bridge logic between models and views. Invokable controllers are used for single-action pages like recommendations.
4.  **Models**: Eloquent models in `app/Models` represent the database structure and handle relationships.
5.  **Support Layer**: Complex business logic is abstracted into `app/Support` classes:
    - `AttemptBuilder`: Orchestrates the creation of practice and mock exams.
    - `DssRecommender`: The Decision Support System engine for personalized study advice.
    - `PerformanceAnalytics`: Aggregates user scores for charts and reports.

## 4. Core Features & Logic

### **Decision Support System (DSS)**
The DSS identifies study gaps by analyzing:
- **Weak Areas**: Subjects where accuracy is below the threshold (default 60%).
- **Untouched Areas**: Topics within the student's LET track that haven't been attempted.
- **Settings**: Admins can tune the threshold and item limits in the Admin Panel.

### **Quiz & Mock Exam Engine**
- **Practice Mode**: Focused drills on specific topics.
- **Mock Exam**: A 100-item simulation following the PRC blueprint:
  - **BEED**: 40% Gen Ed, 60% Prof Ed.
  - **BSED**: 20% Gen Ed, 40% Prof Ed, 40% Specialization.
- **Attempt Lifecycle**: Handled by `AttemptController`, including real-time timers and automatic submission on timeout.

### **Content Management**
- **Admin Panel**: CRUD for Questions, Subjects, and Topics.
- **Verification Workflow**: New or edited questions require administrator verification before appearing in the reviewer.
- **Audit Logs**: A PostgreSQL trigger logs every time a question is verified for security and accountability.

## 5. Security & Access Control
- **Role-Based Access (RBAC)**: Distinct roles for `student` and `admin`.
- **Encryption**: Passwords are hashed using Bcrypt.
- **CSRF Protection**: All form submissions are protected by Laravel's built-in CSRF middleware.
- **Data Isolation**: Students can only view their own attempts, bookmarks, and analytics.

## 6. Database Features
- **Postgres Triggers**: Automate audit logging for question verification.
- **Optimized Indexes**: `idx_attempt_results` for fast retrieval of quiz performance.
- **Normalization**: Schema follows 3rd Normal Form (3NF) to ensure data integrity.

## 7. Developer Workflow
- **Environment**: Managed via `.env` file (supports both SQLite and Supabase).
- **Seeding**: Comprehensive seeders in `database/seeders` for setting up a full review catalog and demo data.
- **Testing**: Run `php artisan test` to verify the entire system (108+ tests).
