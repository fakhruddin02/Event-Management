# Simple University Events (PHP + MySQL)

This is a very simple PHP + MySQL demo for a university project. It uses PDO, sessions, password hashing and minimal HTML/CSS.

## Setup
1. Create a MySQL database and import `schema.sql`:
   - mysql> SOURCE /path/to/schema.sql
2. Update DB credentials in `config.php` (DB_HOST, DB_NAME, DB_USER, DB_PASS).
3. Start a local PHP server (or place files in your webserver's document root):
   - `php -S localhost:8000` (from the project folder)
4. Register a user. Create an `admin` by registering then manually updating the `role` column in the DB to `admin`, or use the `users` table.

## Pages
- `index.php` — redirects to `login.php` or `dashboard.php`.
- `register.php` — register new users (roles: participant, organizer).
- `login.php` — login.
- `logout.php` — logout.
- `dashboard.php` — role-based actions: create events (organizer), buy/cancel tickets (participant), add organizer and close events (admin).

## Notes
- All user input is validated simply; for production, add more rigorous checks and CSRF protection.
- Passwords are hashed using `password_hash()` and verified with `password_verify()`.
- Use `config.php` to add extra helper functions if needed.

Enjoy! (This project is intentionally simple for learning purposes.)
