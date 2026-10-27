# PUP LET Review - Database Documentation & Audit

This document summarizes the database technical features implemented in the project to meet the grading rubric requirements.

## 1. Normalization, Keys, and Relationships
The database schema follows **Third Normal Form (3NF)** to ensure data integrity and reduce redundancy.
- **Foreign Keys**: Enforced on all relationships (e.g., `quiz_attempts.user_id`, `questions.topic_id`).
- **Cascading Deletes**: Child records are automatically cleaned up when a parent is deleted.
- **Relationships**:
  - `User` has many `QuizAttempts`.
  - `Quiz` has many `Questions` through a `Topic`.
  - `QuizAttempt` has many `QuizAttemptAnswers` (Junction Table).

## 2. Indexes and Performance
We have implemented several indexes to optimize common queries:
- **Primary Keys**: Automatic indexes on `id` columns.
- **Composite Index**: `idx_attempt_results` on `quiz_attempt_answers (quiz_attempt_id, is_correct)`. This speeds up score calculations and "mistake retry" features.
- **Search Index**: `topic_id` and `sort_order` on the `questions` table for fast catalog rendering.

### Reading EXPLAIN Output
To analyze query performance, use the `EXPLAIN` command. 
**Example Command:**
```sql
EXPLAIN ANALYZE SELECT * FROM quiz_attempt_answers WHERE quiz_attempt_id = 1 AND is_correct = false;
```
**Example Output:**
```text
Index Scan using idx_attempt_results on quiz_attempt_answers  (cost=0.29..8.31 rows=1 width=45)
  Index Cond: ((quiz_attempt_id = 1) AND (is_correct = false))
```
*Interpretation: The database uses the `idx_attempt_results` index instead of a slow full-table scan, resulting in near-instant results.*

## 3. Postgres Function and Trigger
Implemented in migration `2026_10_27_162608_enhance_database_features.php`.

- **Function**: `log_question_verification()`
  - Logic: Detects when a question's `verified_at` timestamp changes from `NULL` to a value.
- **Trigger**: `trg_question_verified`
  - Event: `AFTER UPDATE ON questions`.
  - Action: Automatically inserts a record into the `audit_logs` table, capturing the reviewer ID and the verification time.

## 4. Roles and Privileges
The system implements Role-Based Access Control (RBAC) at the application level:
- **Admin**: Full privileges (CRUD questions, subjects, users).
- **Student**: Restricted privileges (Read-only catalog, Create quiz attempts, Update own profile).
- **Protection**: Enforced via `EnsureUserIsAdmin` middleware and Laravel Policies.

## 5. Backup and Restore
Scripts are provided in the `/scripts` directory:
- **`backup.ps1`**: Uses `pg_dump` to create a full SQL snapshot of the Supabase database.
- **`restore.ps1`**: Uses `psql` to restore a previously created backup file to the live database.

## 6. Laravel Connection Settings
Configured in `.env` for dual-environment support:
- **Local**: `DB_CONNECTION=sqlite` (High speed for development).
- **Production**: `DB_CONNECTION=pgsql` (PostgreSQL via Supabase for live data).
