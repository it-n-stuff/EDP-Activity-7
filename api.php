<?php
// ═══════════════════════════════════════════════════════════════
//  api.php  —  LIBRI Library Management System · Back-End API
//  Single entry-point for all client ↔ database operations.
// ═══════════════════════════════════════════════════════════════

session_start();

// ── Response headers ─────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// ── Load DB connection class ──────────────────────────────────
require_once __DIR__ . '/db_connection.php';

// ── Parse incoming JSON body ──────────────────────────────────
$raw    = file_get_contents('php://input');
$body   = json_decode($raw, true) ?? [];
$action = trim($body['action'] ?? $_GET['action'] ?? '');

// ── Route to handler ─────────────────────────────────────────
switch ($action) {

    // Authentication
    case 'login':           handle_login($body);           break;
    case 'logout':          handle_logout();               break;

    // Password Recovery
    case 'recover_check':   handle_recover_check($body);   break;
    case 'reset_password':  handle_reset_password($body);  break;

    // User Management
    case 'add_account':     handle_add_account($body);     break;
    case 'update_profile':  handle_update_profile($body);  break;
    case 'change_password': handle_change_password($body); break;
    case 'set_status':      handle_set_status($body);      break;
    case 'get_accounts':    handle_get_accounts($body);    break;
    case 'get_account':     handle_get_account($body);     break;

    // Library Data
    case 'get_dashboard_stats': handle_get_dashboard_stats(); break;
    case 'get_book':           handle_get_book($body);      break;
    case 'get_authors':         handle_get_authors($body);    break;
    case 'get_student':        handle_get_student($body);   break;
    case 'get_borrows':         handle_get_borrows($body);    break;
    case 'mark_returned':       handle_mark_returned($body);  break;

    // Books CRUD
    case 'add_book':         handle_add_book($body);        break;
    case 'update_book':      handle_update_book($body);     break;

    // Students CRUD
    case 'add_student':      handle_add_student($body);     break;
    case 'update_student':   handle_update_student($body);  break;

    // Borrow Records
    case 'update_borrow':    handle_update_borrow($body);   break;
    case 'add_borrow':       handle_add_borrow($body);      break;

    // Reports
    case 'get_report_borrowed': handle_get_report_borrowed(); break;
    case 'get_report_popular':  handle_get_report_popular();  break;
    case 'get_report_history':  handle_get_report_history();  break;
    case 'get_report_students': handle_get_report_students(); break;
    case 'get_report_books':    handle_get_report_books();    break;

    default:
        respond('error', 'Unknown action: "' . htmlspecialchars($action) . '".');
}


// ═══════════════════════════════════════════════════════════════
//  SHARED HELPERS
// ═══════════════════════════════════════════════════════════════

/**
 * Emit a JSON response and terminate.
 *
 * @param string $status  'success' | 'error'
 * @param string $message Human-readable message.
 * @param array  $extra   Additional top-level fields merged into response.
 */
function respond(string $status, string $message, array $extra = []): void
{
    echo json_encode(
        array_merge(['status' => $status, 'message' => $message], $extra),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

/** Abort with 401 if no active session exists. */
function require_auth(): void
{
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        respond('error', 'Unauthorized. Please log in first.');
    }
}

/** Return the shared PDO connection. */
function db(): PDO
{
    return Database::getInstance()->getConnection();
}


// ═══════════════════════════════════════════════════════════════
//  AUTHENTICATION
// ═══════════════════════════════════════════════════════════════

// ── 1. LOGIN ─────────────────────────────────────────────────
//  Body: { action, username, password }
// ─────────────────────────────────────────────────────────────
function handle_login(array $body): void
{
    $username = trim($body['username'] ?? '');
    $password = $body['password']      ?? '';

    if ($username === '' || $password === '') {
        respond('error', 'Username and password are required.');
    }

    $stmt = db()->prepare(
        "SELECT UserID, FirstName, LastName, Role, Username, Password, Email, Status
           FROM `user`
          WHERE Username = ?
          LIMIT 1"
    );
    $stmt->execute([$username]);
    $staff = $stmt->fetch();

    // Generic message to avoid username enumeration
    if (!$staff || !password_verify($password, $staff['Password'])) {
        respond('error', 'Invalid username or password. Please try again.');
    }

    if ($staff['Status'] !== 'active') {
        respond('error', 'This account has been deactivated. Please contact an administrator.');
    }

    // Regenerate session ID to prevent fixation attacks
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int) $staff['UserID'];
    $_SESSION['username'] = $staff['Username'];
    $_SESSION['fn']       = $staff['FirstName'];
    $_SESSION['ln']       = $staff['LastName'];
    $_SESSION['role']     = $staff['Role'];

    respond('success', 'Login successful.', [
        'user' => [
            'id'       => (int) $staff['UserID'],
            'username' => $staff['Username'],
            'fn'       => $staff['FirstName'],
            'ln'       => $staff['LastName'],
            'role'     => $staff['Role'],
            'email'    => $staff['Email'] ?? '',
        ]
    ]);
}

// ── 2. LOGOUT ────────────────────────────────────────────────
//  Body: { action }
// ─────────────────────────────────────────────────────────────
function handle_logout(): void
{
    $_SESSION = [];

    // Remove session cookie from browser
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']
        );
    }

    session_destroy();
    respond('success', 'You have been logged out.');
}


// ═══════════════════════════════════════════════════════════════
//  PASSWORD RECOVERY
// ═══════════════════════════════════════════════════════════════

// ── 3. RECOVER — CHECK USERNAME ──────────────────────────────
//  Body: { action, username }
//  Stores a short-lived token in the session so reset_password
//  can only be called after a successful check.
// ─────────────────────────────────────────────────────────────
function handle_recover_check(array $body): void
{
    $username = trim($body['username'] ?? '');

    if ($username === '') {
        respond('error', 'Username is required.');
    }

    $stmt = db()->prepare(
        "SELECT UserID, FirstName, LastName
           FROM `user`
          WHERE Username = ?
          LIMIT 1"
    );
    $stmt->execute([$username]);
    $staff = $stmt->fetch();

    if (!$staff) {
        respond('error', 'No account found with that username.');
    }

    // Store recovery target and a one-time token in the session
    $_SESSION['recover_user_id'] = (int) $staff['UserID'];
    $_SESSION['recover_token']    = bin2hex(random_bytes(16));

    respond('success', 'Account found. You may now reset your password.', [
        'name'          => $staff['FirstName'] . ' ' . $staff['LastName'],
        'recover_token' => $_SESSION['recover_token'],
    ]);
}

// ── 4. RECOVER — RESET PASSWORD ──────────────────────────────
//  Body: { action, recover_token, new_password, conf_password }
//  Requires a valid recover_check to have been called first.
// ─────────────────────────────────────────────────────────────
function handle_reset_password(array $body): void
{
    if (empty($_SESSION['recover_user_id']) || empty($_SESSION['recover_token'])) {
        respond('error', 'Recovery session expired or missing. Please start over.');
    }

    $token    = $body['recover_token'] ?? '';
    $newPass  = $body['new_password']  ?? '';
    $confPass = $body['conf_password'] ?? '';

    // Constant-time token comparison to prevent timing attacks
    if (!hash_equals($_SESSION['recover_token'], $token)) {
        respond('error', 'Invalid recovery token. Please start over.');
    }
    if ($newPass === '') {
        respond('error', 'New password is required.');
    }
    if ($newPass !== $confPass) {
        respond('error', 'Passwords do not match.');
    }
    if (strlen($newPass) < 6) {
        respond('error', 'Password must be at least 6 characters long.');
    }

    $hash = password_hash($newPass, PASSWORD_BCRYPT);
    $stmt = db()->prepare("UPDATE `user` SET Password = ? WHERE UserID = ?");
    $stmt->execute([$hash, $_SESSION['recover_user_id']]);

    // Invalidate recovery session immediately after use
    unset($_SESSION['recover_user_id'], $_SESSION['recover_token']);

    respond('success', 'Password has been reset successfully. You may now log in.');
}


// ═══════════════════════════════════════════════════════════════
//  USER MANAGEMENT  [all handlers below call require_auth()]
// ═══════════════════════════════════════════════════════════════

// ── 5. ADD ACCOUNT ───────────────────────────────────────────
//  Body: { action, first_name, last_name, username, password, email? }
// ─────────────────────────────────────────────────────────────
function handle_add_account(array $body): void
{
    require_auth();

    if (($_SESSION['role'] ?? '') !== 'admin') {
        respond('error', 'Only administrators can create new accounts.');
    }

    $fn       = trim($body['first_name'] ?? '');
    $ln       = trim($body['last_name']  ?? '');
    $role     = in_array($body['role'] ?? '', ['admin','staff'], true) ? $body['role'] : 'staff';
    $username = trim($body['username']   ?? '');
    $password = $body['password']        ?? '';
    $email    = trim($body['email']      ?? '') ?: null;

    // Validation
    if ($fn === '' || $ln === '' || $username === '' || $password === '') {
        respond('error', 'First name, last name, username, and password are all required.');
    }
    if (strlen($password) < 6) {
        respond('error', 'Password must be at least 6 characters long.');
    }
    if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond('error', 'Please enter a valid email address.');
    }

    // Username uniqueness check
    $chk = db()->prepare("SELECT UserID FROM `user` WHERE Username = ? LIMIT 1");
    $chk->execute([$username]);
    if ($chk->fetch()) {
        respond('error', 'That username is already taken. Please choose a different one.');
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = db()->prepare(
        "INSERT INTO `user` (FirstName, LastName, Role, Username, Password, Email, Status)
         VALUES (?, ?, ?, ?, ?, ?, 'active')"
    );
    $stmt->execute([$fn, $ln, $role, $username, $hash, $email]);

    respond('success', 'Account created successfully.', [
        'user_id' => (int) db()->lastInsertId()
    ]);
}

// ── 6. UPDATE PROFILE ────────────────────────────────────────
//  Body: { action, first_name, last_name, username, email?, staff_id? }
//  user_id defaults to the currently logged-in user.
// ─────────────────────────────────────────────────────────────
function handle_update_profile(array $body): void
{
    require_auth();

    $targetId = isset($body['user_id'])
        ? (int) $body['user_id']
        : (int) $_SESSION['user_id'];

    // Non-admins may only edit their own profile
    if ($targetId !== (int) $_SESSION['user_id'] && ($_SESSION['role'] ?? '') !== 'admin') {
        respond('error', 'Only administrators can edit other accounts.');
    }

    $fn       = trim($body['first_name'] ?? '');
    $ln       = trim($body['last_name']  ?? '');
    $username = trim($body['username']   ?? '');
    $email    = trim($body['email']      ?? '') ?: null;

    // Validation
    if ($fn === '' || $ln === '' || $username === '') {
        respond('error', 'First name, last name, and username are required.');
    }
    if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond('error', 'Please enter a valid email address.');
    }

    // Username uniqueness — exclude the account being updated
    $chk = db()->prepare(
        "SELECT UserID FROM `user` WHERE Username = ? AND UserID <> ? LIMIT 1"
    );
    $chk->execute([$username, $targetId]);
    if ($chk->fetch()) {
        respond('error', 'That username is already taken by another account.');
    }

    $stmt = db()->prepare(
        "UPDATE `user`
            SET FirstName = ?,
                LastName  = ?,
                Username  = ?,
                Email     = ?
          WHERE UserID = ?"
    );
    $stmt->execute([$fn, $ln, $username, $email, $targetId]);

    // Keep session in sync when the user edits their own profile
    if ($targetId === (int) $_SESSION['user_id']) {
        $_SESSION['fn']       = $fn;
        $_SESSION['ln']       = $ln;
        $_SESSION['username'] = $username;
    }

    respond('success', 'Profile updated successfully.', [
        'user' => [
            'id'       => $targetId,
            'fn'       => $fn,
            'ln'       => $ln,
            'username' => $username,
            'email'    => $email,
        ]
    ]);
}

// ── 7. CHANGE PASSWORD ───────────────────────────────────────
//  Body: { action, current_password, new_password, conf_password, user_id? }
//  user_id defaults to the currently logged-in user.
// ─────────────────────────────────────────────────────────────
function handle_change_password(array $body): void
{
    require_auth();

    $targetId = isset($body['user_id'])
        ? (int) $body['user_id']
        : (int) $_SESSION['user_id'];

    $curPass  = $body['current_password'] ?? '';
    $newPass  = $body['new_password']     ?? '';
    $confPass = $body['conf_password']    ?? '';

    // Validation
    if ($curPass === '' || $newPass === '' || $confPass === '') {
        respond('error', 'All three password fields are required.');
    }
    if ($newPass !== $confPass) {
        respond('error', 'New passwords do not match.');
    }
    if (strlen($newPass) < 6) {
        respond('error', 'New password must be at least 6 characters long.');
    }

    // Verify current password against stored hash
    $stmt = db()->prepare("SELECT Password FROM `user` WHERE UserID = ? LIMIT 1");
    $stmt->execute([$targetId]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($curPass, $row['Password'])) {
        respond('error', 'Current password is incorrect.');
    }

    $hash = password_hash($newPass, PASSWORD_BCRYPT);
    $upd  = db()->prepare("UPDATE `user` SET Password = ? WHERE UserID = ?");
    $upd->execute([$hash, $targetId]);

    respond('success', 'Password changed successfully.');
}

// ── 8. SET STATUS (ACTIVATE / DEACTIVATE) ────────────────────
//  Body: { action, user_id, status }  status = 'active'|'inactive'
// ─────────────────────────────────────────────────────────────
function handle_set_status(array $body): void
{
    require_auth();

    if (($_SESSION['role'] ?? '') !== 'admin') {
        respond('error', 'Only administrators can change account status.');
    }

    $targetId = (int) ($body['user_id'] ?? 0);
    $status   = $body['status'] ?? '';

    if ($targetId === 0) {
        respond('error', 'user_id is required.');
    }
    if (!in_array($status, ['active', 'inactive'], true)) {
        respond('error', 'Status must be "active" or "inactive".');
    }

    // Prevent a user from locking themselves out
    if ($targetId === (int) $_SESSION['user_id'] && $status === 'inactive') {
        respond('error', 'You cannot deactivate your own account.');
    }

    // Confirm the account exists before updating
    $chk = db()->prepare("SELECT UserID FROM `user` WHERE UserID = ? LIMIT 1");
    $chk->execute([$targetId]);
    if (!$chk->fetch()) {
        respond('error', 'Account not found.');
    }

    $stmt = db()->prepare("UPDATE `user` SET Status = ? WHERE UserID = ?");
    $stmt->execute([$status, $targetId]);

    $label = ($status === 'active') ? 'activated' : 'deactivated';
    respond('success', "Account {$label} successfully.", ['new_status' => $status]);
}

// ── 9. GET ACCOUNTS (LIST / SEARCH) ──────────────────────────
//  Body / GET params: { action, search?, status? }
//  Returns all staff filtered by an optional keyword and/or status.
// ─────────────────────────────────────────────────────────────
function handle_get_accounts(array $body): void
{
    require_auth();

    $search = trim($body['search'] ?? $_GET['search'] ?? '');
    $status = trim($body['status'] ?? $_GET['status'] ?? '');

    $sql    = "SELECT UserID, FirstName, LastName, Role, Username, Email, Status
                 FROM `user`
                WHERE 1=1";
    $params = [];

    // Keyword filter across name, username, and email
    if ($search !== '') {
        $like = '%' . $search . '%';
        $sql .= " AND (FirstName LIKE ? OR LastName  LIKE ?
                       OR Username  LIKE ? OR Email LIKE ?)";
        array_push($params, $like, $like, $like, $like);
    }

    // Status filter
    if (in_array($status, ['active', 'inactive'], true)) {
        $sql     .= " AND Status = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY UserID ASC";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $accounts = $stmt->fetchAll();

    respond('success', '', [
        'accounts' => $accounts,
        'total'    => count($accounts),
    ]);
}

// ── 10. GET SINGLE ACCOUNT ───────────────────────────────────
//  Body / GET params: { action, user_id }
// ─────────────────────────────────────────────────────────────
function handle_get_account(array $body): void
{
    require_auth();

    $targetId = (int) ($body['user_id'] ?? $_GET['user_id'] ?? 0);

    if ($targetId === 0) {
        respond('error', 'user_id is required.');
    }

    $stmt = db()->prepare(
        "SELECT UserID, FirstName, LastName, Role, Username, Email, Status
           FROM `user`
          WHERE UserID = ?
          LIMIT 1"
    );
    $stmt->execute([$targetId]);
    $account = $stmt->fetch();

    if (!$account) {
        respond('error', 'Account not found.');
    }

    respond('success', '', ['account' => $account]);
}

// ═══════════════════════════════════════════════════════════════
//  LIBRARY DATA  [all handlers below call require_auth()]
// ═══════════════════════════════════════════════════════════════

function handle_get_dashboard_stats(): void
{
    require_auth();

    $db = db();

    $book = $db->query("SELECT COUNT(*) total FROM book")
                ->fetch(PDO::FETCH_ASSOC)['total'];

    $student = $db->query("SELECT COUNT(*) total FROM student")
                   ->fetch(PDO::FETCH_ASSOC)['total'];

    $borrow = $db->query("SELECT COUNT(*) total FROM borrow WHERE ReturnDate IS NULL")
                   ->fetch(PDO::FETCH_ASSOC)['total'];

    // Currently borrowed books for dashboard widget (up to 5)
    $cb_stmt = $db->query("
        SELECT br.BorrowID,
               CONCAT(s.Student_FN,' ',s.Student_LN) AS StudentName,
               b.Title, br.BorrowDate
        FROM borrow br
        JOIN student s ON br.StudentID = s.StudentID
        JOIN book b    ON br.BookID    = b.BookID
        WHERE br.ReturnDate IS NULL
        ORDER BY br.BorrowDate ASC
        LIMIT 5
    ");
    $currently_borrowed = $cb_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Most popular books for dashboard widget (up to 5)
    $pop_stmt = $db->query("
        SELECT b.Title,
               b.Publisher,
               GROUP_CONCAT(CONCAT(a.Author_FN,' ',a.Author_LN) SEPARATOR ', ') AS Authors,
               COUNT(br.BorrowID) AS TimesBorrowed
        FROM book b
        LEFT JOIN borrow br      ON b.BookID    = br.BookID
        LEFT JOIN book_author ba ON b.BookID    = ba.BookID
        LEFT JOIN author a       ON ba.AuthorID = a.AuthorID
        GROUP BY b.BookID
        ORDER BY TimesBorrowed DESC
        LIMIT 5
    ");
    $popular_books = $pop_stmt->fetchAll(PDO::FETCH_ASSOC);

    respond('success', 'Dashboard loaded.', [
        'stats' => [
            'book'     => (int)$book,
            'student'  => (int)$student,
            'borrow'   => (int)$borrow,
            'overdue'  => (int)$borrow
        ],
        'currently_borrowed' => $currently_borrowed,
        'popular_books'      => $popular_books
    ]);
}

function handle_get_book(array $body): void
{
    require_auth();

    $stmt = db()->query("
        SELECT
            b.BookID,
            b.ISBN,
            b.Title,
            b.Publisher,
            b.PublicationYear,
            GROUP_CONCAT(CONCAT(a.Author_FN, ' ', a.Author_LN) SEPARATOR ', ') AS Authors
        FROM book b
        LEFT JOIN book_author ba ON b.BookID = ba.BookID
        LEFT JOIN author a ON ba.AuthorID = a.AuthorID
        GROUP BY b.BookID
        ORDER BY b.BookID ASC
    ");

    respond('success', 'Books loaded.', [
        'book' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
}

function handle_get_authors(array $body): void
{
    require_auth();

    $stmt = db()->query("
        SELECT
            a.AuthorID,
            a.Author_FN,
            a.Author_LN,
            GROUP_CONCAT(b.Title SEPARATOR ', ') AS Books
        FROM author a
        LEFT JOIN book_author ba ON a.AuthorID = ba.AuthorID
        LEFT JOIN book b ON ba.BookID = b.BookID
        GROUP BY a.AuthorID
        ORDER BY a.AuthorID ASC
    ");

    respond('success', 'Authors loaded.', [
        'authors' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
}

function handle_get_student(array $body): void
{
    require_auth();

    $stmt = db()->query("
        SELECT *
        FROM student
        ORDER BY StudentID ASC
    ");

    respond('success', 'Students loaded.', [
        'student' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
}

function handle_get_borrows(array $body): void
{
    require_auth();

    $stmt = db()->query("
        SELECT
            br.BorrowID,
            s.StudentNumber,
            CONCAT(s.Student_FN, ' ', s.Student_LN) AS StudentName,
            b.Title,
            br.BorrowDate,
            br.ReturnDate
        FROM borrow br
        JOIN student s ON br.StudentID = s.StudentID
        JOIN book b ON br.BookID = b.BookID
        ORDER BY br.BorrowID ASC
    ");

    respond('success', 'Borrow records loaded.', [
        'borrows' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
}

function handle_mark_returned(array $body): void
{
    require_auth();

    $borrowId = (int)($body['borrow_id'] ?? 0);

    if ($borrowId === 0) {
        respond('error', 'borrow_id is required.');
    }

    $stmt = db()->prepare("
        UPDATE borrow
        SET ReturnDate = CURDATE()
        WHERE BorrowID = ?
    ");

    $stmt->execute([$borrowId]);

    respond('success', 'Book marked as returned.');
}

function handle_add_book(array $body): void
{
    require_auth();

    $isbn      = trim($body['isbn']      ?? '');
    $title     = trim($body['title']     ?? '');
    $publisher = trim($body['publisher'] ?? '');
    $year      = trim($body['year']      ?? '');
    $authorId  = isset($body['author_id']) && $body['author_id'] ? (int)$body['author_id'] : null;

    if ($isbn === '' || $title === '' || $publisher === '' || $year === '') {
        respond('error', 'ISBN, title, publisher, and year are required.');
    }

    $pdo = db();

    $stmt = $pdo->prepare("
        INSERT INTO book (ISBN, Title, Publisher, PublicationYear)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$isbn, $title, $publisher, (int)$year]);
    $bookId = (int)$pdo->lastInsertId();

    // Link author if one was selected
    if ($authorId) {
        $link = $pdo->prepare("INSERT IGNORE INTO book_author (BookID, AuthorID) VALUES (?, ?)");
        $link->execute([$bookId, $authorId]);
    }

    respond('success', 'Book added successfully.', ['book_id' => $bookId]);
}

function handle_update_book(array $body): void
{
    require_auth();

    $bookId = (int)($body['book_id'] ?? 0);

    $stmt = db()->prepare("
        UPDATE book
        SET ISBN = ?,
            Title = ?,
            Publisher = ?,
            PublicationYear = ?
        WHERE BookID = ?
    ");

    $stmt->execute([
        $body['isbn'],
        $body['title'],
        $body['publisher'],
        $body['year'],
        $bookId
    ]);

    respond('success', 'Book updated successfully.');
}

function handle_add_student(array $body): void
{
    require_auth();

    $stmt = db()->prepare("
        INSERT INTO student
        (StudentNumber, Student_FN, Student_LN, YearLevel, Course)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $body['student_no'],
        $body['first_name'],
        $body['last_name'],
        $body['year_level'],
        $body['course']
    ]);

    respond('success', 'Student added successfully.');
}

function handle_update_student(array $body): void
{
    require_auth();

    $stmt = db()->prepare("
        UPDATE student
        SET StudentNumber = ?,
            Student_FN = ?,
            Student_LN = ?,
            YearLevel = ?,
            Course = ?
        WHERE StudentID = ?
    ");

    $stmt->execute([
        $body['student_no'],
        $body['first_name'],
        $body['last_name'],
        $body['year_level'],
        $body['course'],
        $body['student_id']
    ]);

    respond('success', 'Student updated successfully.');
}

function handle_update_borrow(array $body): void
{
    require_auth();

    $stmt = db()->prepare("
        UPDATE borrow
        SET ReturnDate = ?
        WHERE BorrowID = ?
    ");

    $stmt->execute([
        $body['return_date'] ?: null,
        $body['borrow_id']
    ]);

    respond('success', 'Borrow record updated successfully.');
}

// ── ADD BORROW ────────────────────────────────────────────────
//  Body: { action, student_id, book_id, borrow_date, return_date? }
// ─────────────────────────────────────────────────────────────
function handle_add_borrow(array $body): void
{
    require_auth();

    $studentId  = (int)($body['student_id']  ?? 0);
    $bookId     = (int)($body['book_id']     ?? 0);
    $borrowDate = trim($body['borrow_date']  ?? '');
    $returnDate = trim($body['return_date']  ?? '') ?: null;

    if (!$studentId || !$bookId || $borrowDate === '') {
        respond('error', 'Student, book, and borrow date are required.');
    }

    // Check whether the book is already borrowed
    $chk = db()->prepare("SELECT COUNT(*) total FROM borrow WHERE BookID = ? AND ReturnDate IS NULL");
    $chk->execute([$bookId]);
    if ((int)$chk->fetch(PDO::FETCH_ASSOC)['total'] > 0) {
        respond('error', 'This book is currently borrowed by another student.');
    }

    $stmt = db()->prepare("
        INSERT INTO borrow (StudentID, BookID, BorrowDate, ReturnDate)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$studentId, $bookId, $borrowDate, $returnDate]);

    respond('success', 'Borrow record added successfully.', [
        'borrow_id' => (int)db()->lastInsertId()
    ]);
}

// ═══════════════════════════════════════════════════════════════
//  REPORTS
// ═══════════════════════════════════════════════════════════════

function handle_get_report_borrowed(): void
{
    require_auth();

    $stmt = db()->query("
        SELECT br.BorrowID,
               s.StudentNumber,
               CONCAT(s.Student_FN,' ',s.Student_LN) AS StudentName,
               b.Title AS BookTitle,
               br.BorrowDate
        FROM borrow br
        JOIN student s ON br.StudentID = s.StudentID
        JOIN book b    ON br.BookID    = b.BookID
        WHERE br.ReturnDate IS NULL
        ORDER BY br.BorrowDate ASC
    ");

    respond('success', '', ['rows' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function handle_get_report_popular(): void
{
    require_auth();

    $stmt = db()->query("
        SELECT b.Title AS BookTitle,
               COUNT(br.BorrowID) AS TimesBorrowed
        FROM book b
        LEFT JOIN borrow br ON b.BookID = br.BookID
        GROUP BY b.BookID
        ORDER BY TimesBorrowed DESC
    ");

    respond('success', '', ['rows' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function handle_get_report_history(): void
{
    require_auth();

    $stmt = db()->query("
        SELECT s.StudentNumber,
               CONCAT(s.Student_FN,' ',s.Student_LN) AS StudentName,
               b.Title AS BookTitle,
               br.BorrowDate,
               COALESCE(br.ReturnDate,'—') AS ReturnDate
        FROM borrow br
        JOIN student s ON br.StudentID = s.StudentID
        JOIN book b    ON br.BookID    = b.BookID
        ORDER BY br.BorrowDate DESC
    ");

    respond('success', '', ['rows' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function handle_get_report_students(): void
{
    require_auth();

    $stmt = db()->query("
        SELECT s.StudentNumber,
               CONCAT(s.Student_FN,' ',s.Student_LN) AS StudentName,
               s.YearLevel,
               s.Course,
               COUNT(br.BorrowID) AS TotalBorrows
        FROM student s
        LEFT JOIN borrow br ON s.StudentID = br.StudentID
        GROUP BY s.StudentID
        ORDER BY s.StudentNumber ASC
    ");

    respond('success', '', ['rows' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

// ── REPORT: BOOK INVENTORY ────────────────────────────────────
//  Returns full book catalogue with authors for the inventory report.
// ─────────────────────────────────────────────────────────────
function handle_get_report_books(): void
{
    require_auth();

    $stmt = db()->query("
        SELECT
            b.BookID,
            b.ISBN,
            b.Title,
            b.Publisher,
            b.PublicationYear,
            COALESCE(
                GROUP_CONCAT(CONCAT(a.Author_FN,' ',a.Author_LN) SEPARATOR ', '),
                '—'
            ) AS Authors
        FROM book b
        LEFT JOIN book_author ba ON b.BookID  = ba.BookID
        LEFT JOIN author a       ON ba.AuthorID = a.AuthorID
        GROUP BY b.BookID
        ORDER BY b.BookID ASC
    ");

    respond('success', '', ['rows' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}