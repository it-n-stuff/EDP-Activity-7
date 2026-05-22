# EDP-Activity-7
[ IT 120 Event Driven Programming ] Lab Activity 7: GitHub

# LIBRI — School Library Management System

A web-based library management system for schools, built with a vanilla HTML/JS frontend and a PHP + MySQL backend.

---

## Features

- **Authentication** — Staff login, logout, and password recovery
- **Books** — Add, edit, and search the book catalogue with author linking
- **Students** — Register and manage student records
- **Borrow Records** — Log book checkouts and mark returns; prevents double-borrowing
- **Reports** — Currently borrowed books, popular titles, full borrow history, student activity, and book inventory
- **Account Management** — Admin can create and activate/deactivate staff accounts
- **Profile** — Each user can update their info and change their password

---

## File Structure

```
├── index.html        # Single-page frontend (UI, forms, and all client-side logic)
├── api.php           # Backend API — handles all client ↔ database operations
├── db_connection.php # MySQL PDO connection (Singleton pattern)
└── style.css         # Stylesheet (referenced by index.html)
```

---

## How It Works

```
Browser (index.html)
      │
      │  JSON requests  (action + payload)
      ▼
   api.php             ← single entry point for all actions
      │
      │  PDO queries
      ▼
db_connection.php  →  MySQL (library_db)
```

`index.html` sends all requests to `api.php` as JSON with an `action` field (e.g. `login`, `add_book`, `get_borrows`). `api.php` routes each action to the appropriate handler function, which uses the shared PDO connection from `db_connection.php` to query the database and return a JSON response.

---

## Requirements

- PHP 8.0+
- MySQL 5.7+ / MariaDB
- A web server (Apache, Nginx, or PHP's built-in server)

---

## Setup

1. **Clone the repository** and place the files in your web server's root directory.

2. **Create the database:**
   ```sql
   CREATE DATABASE library_db;
   ```

3. **Configure the connection** in `db_connection.php`:
   ```php
   public string $host     = '127.0.0.1';
   public string $dbname   = 'library_db';
   public string $username = 'root';
   public string $password = '';
   ```

4. **Import the database schema** (tables: `user`, `book`, `author`, `book_author`, `student`, `borrow`).

5. Open `index.html` in your browser and sign in with your staff credentials.

---

## Security Notes

- Passwords are hashed using PHP's `password_hash` / `password_verify`.
- All database queries use **PDO prepared statements** to prevent SQL injection.
- Session IDs are regenerated on login to prevent session fixation.
- Token comparison for password recovery uses `hash_equals` to prevent timing attacks.
- All protected endpoints call `require_auth()` to enforce an active session.
