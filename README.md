# PUP LET Reviewer

A modern, adaptive web-based review system for the Licensure Examination for Teachers (LET), built for PUP students.

## 🚀 Quick Start

### 1. Prerequisites
- **PHP 8.2+**
- **Composer**
- **SQLite** (for local dev) or **PostgreSQL** (for production)

### 2. Installation
```bash
# Clone the repository
git clone <repository-url>
cd Capstone

# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Initialize local database (SQLite)
touch database/database.sqlite
php artisan migrate:fresh --seed
```

### 3. Running Locally
```bash
php artisan serve
```
Visit `http://localhost:8000` to view the application.

## 🛠 Database Configuration

The project supports two environments via the `.env` file:

- **Local Development (Default)**: Uses `database/database.sqlite`. It is extremely fast for coding and testing.
- **Production (Supabase)**: Connects to a live PostgreSQL database. 
  - To switch, comment out `DB_CONNECTION=sqlite` and uncomment the `pgsql` block in your `.env`.
  - See [DATABASE_DOCUMENTATION.md](DATABASE_DOCUMENTATION.md) for details on triggers and backups.

## 🧪 Testing
We use PHPUnit for end-to-end feature testing. Tests are isolated and run in-memory, so they won't affect your local or live data.
```bash
php artisan test
```

## 📖 Documentation
- **[TECH_DOC.md](TECH_DOC.md)**: Full technical architecture, request flow, and core logic (DSS/Quiz Engine).
- **[DATABASE_DOCUMENTATION.md](DATABASE_DOCUMENTATION.md)**: Normalization, ER diagrams, triggers, and backup/restore guides.

## 👥 Roles
- **Student**: Register, take quizzes, view analytics, and get personalized recommendations.
- **Administrator**: Manage the question bank, subjects, users, and system settings.
  - *Default Admin*: `admin@letreviewer.test` / `password`
  - *Demo Student*: `demo@letreviewer.test` / `password`

## ⚖️ License
This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
