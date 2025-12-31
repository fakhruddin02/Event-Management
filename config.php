<?php
// config.php
// Central DB connection and common functions

session_start();

// Update these with your DB credentials
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'university_events');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Create and return a PDO connection
 * @return PDO
 */
function dbConnect() {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            // In production, don't reveal details
            die('Database connection failed: ' . $e->getMessage());
        }
    }

    return $pdo;
}

/** Redirect helper */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/** Check login state */
function isLoggedIn() {
    return isset($_SESSION['user']) && !empty($_SESSION['user']);
}

/** Return current user data from session */
function currentUser() {
    return $_SESSION['user'] ?? null;
}

/** Check if an email exists in users table */
function emailExists($email) {
    $pdo = dbConnect();
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    return (bool)$stmt->fetch();
}

/** Create user with hashed password */
function createUser($name, $email, $password, $role = 'participant') {
    $pdo = dbConnect();
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
    return $stmt->execute([$name, $email, $hash, $role]);
}

/** Attempt login: verify password and set session */
function loginUser($email, $password) {
    $pdo = dbConnect();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        // Avoid keeping the password in session
        unset($user['password']);
        $_SESSION['user'] = $user;
        return true;
    }
    return false;
}

/** Logout user and clear session */
function logoutUser() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

/** Create a new event (Organizer only) */
function createEvent($title, $description, $date, $capacity, $created_by) {
    $pdo = dbConnect();
    $stmt = $pdo->prepare('INSERT INTO events (title, description, date, capacity, created_by) VALUES (?, ?, ?, ?, ?)');
    return $stmt->execute([$title, $description, $date, $capacity, $created_by]);
}

/** Get events; optional parameter to include closed events */
function getEvents($includeClosed = false) {
    $pdo = dbConnect();
    if ($includeClosed) {
        $stmt = $pdo->query('SELECT e.*, u.name AS organizer_name FROM events e JOIN users u ON e.created_by = u.id ORDER BY e.date ASC');
    } else {
        $stmt = $pdo->query('SELECT e.*, u.name AS organizer_name FROM events e JOIN users u ON e.created_by = u.id WHERE e.is_closed = 0 ORDER BY e.date ASC');
    }
    return $stmt->fetchAll();
}

/** Get single event by id */
function getEvent($id) {
    $pdo = dbConnect();
    $stmt = $pdo->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/** Count tickets for an event */
function countTickets($event_id) {
    $pdo = dbConnect();
    $stmt = $pdo->prepare('SELECT COUNT(*) as c FROM tickets WHERE event_id = ?');
    $stmt->execute([$event_id]);
    $row = $stmt->fetch();
    return (int)$row['c'];
}

/** Buy ticket for a given user and event */
function buyTicket($user_id, $event_id) {
    $pdo = dbConnect();
    $event = getEvent($event_id);
    if (!$event || $event['is_closed']) {
        return ['success' => false, 'message' => 'Event does not exist or is closed.'];
    }
    $tickets = countTickets($event_id);
    if ($tickets >= $event['capacity']) {
        return ['success' => false, 'message' => 'Event is sold out.'];
    }
    // Check duplicate
    $stmt = $pdo->prepare('SELECT id FROM tickets WHERE user_id = ? AND event_id = ? LIMIT 1');
    $stmt->execute([$user_id, $event_id]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'You already have a ticket for this event.'];
    }

    $stmt = $pdo->prepare('INSERT INTO tickets (user_id, event_id) VALUES (?, ?)');
    try {
        $stmt->execute([$user_id, $event_id]);
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Could not purchase ticket.'];
    }
}

/** Cancel a ticket for a user */
function cancelTicket($user_id, $ticket_id) {
    $pdo = dbConnect();
    $stmt = $pdo->prepare('DELETE FROM tickets WHERE id = ? AND user_id = ?');
    $stmt->execute([$ticket_id, $user_id]);
    return $stmt->rowCount() > 0;
}

/** Get tickets for a user */
function getTicketsByUser($user_id) {
    $pdo = dbConnect();
    $stmt = $pdo->prepare('SELECT t.*, e.title, e.date FROM tickets t JOIN events e ON t.event_id = e.id WHERE t.user_id = ? ORDER BY e.date ASC');
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

/** Get participants for an event (Organizer only) */
function getParticipantsByEvent($event_id) {
    $pdo = dbConnect();
    $stmt = $pdo->prepare('SELECT u.id, u.name, u.email FROM tickets t JOIN users u ON t.user_id = u.id WHERE t.event_id = ?');
    $stmt->execute([$event_id]);
    return $stmt->fetchAll();
}

/** Add an organizer (Admin only) */
function addOrganizer($name, $email, $password) {
    return createUser($name, $email, $password, 'organizer');
}

/** Close an event (Admin or Organizer) */
function closeEvent($event_id) {
    $pdo = dbConnect();
    $stmt = $pdo->prepare('UPDATE events SET is_closed = 1 WHERE id = ?');
    $stmt->execute([$event_id]);
    return $stmt->rowCount() > 0;
}

/** Simple helper to sanitize outputs */
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/** CSRF helpers */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfInput() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

function requireCsrfOrDie() {
    if (empty($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        die('Invalid CSRF token.');
    }
}

?>"},{ 