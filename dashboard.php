<?php
require_once __DIR__ . '/config.php';

if (!isLoggedIn()) redirect('login.php');

$user = currentUser();
$role = $user['role'];
$messages = [];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $messages[] = ['type' => 'error', 'text' => 'Invalid CSRF token.'];
    } else {
        // Create event (Organizer)
        if (isset($_POST['action']) && $_POST['action'] === 'create_event' && $role === 'organizer') {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $date = trim($_POST['date'] ?? '');
            $capacity = (int)($_POST['capacity'] ?? 0);

            if ($title === '' || $date === '' || $capacity <= 0) {
                $messages[] = ['type' => 'error', 'text' => 'Please fill title, date and capacity >=1'];
            } else {
                if (createEvent($title, $description, $date, $capacity, $user['id'])) {
                    $messages[] = ['type' => 'success', 'text' => 'Event created.'];
                } else {
                    $messages[] = ['type' => 'error', 'text' => 'Could not create event.'];
                }
            }
        }

        // Add organizer (Admin)
        if (isset($_POST['action']) && $_POST['action'] === 'add_organizer' && $role === 'admin') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
                $messages[] = ['type' => 'error', 'text' => 'Provide name, valid email, password >= 6 chars.'];
            } else {
                if (emailExists($email)) {
                    $messages[] = ['type' => 'error', 'text' => 'Email already exists.'];
                } elseif (addOrganizer($name, $email, $password)) {
                    $messages[] = ['type' => 'success', 'text' => 'Organizer added.'];
                } else {
                    $messages[] = ['type' => 'error', 'text' => 'Could not add organizer.'];
                }
            }
        }

        // Buy ticket (Participant)
        if (isset($_POST['action']) && $_POST['action'] === 'buy' && $role === 'participant') {
            $event_id = (int)($_POST['event_id'] ?? 0);
            if ($event_id > 0) {
                $res = buyTicket($user['id'], $event_id);
                if ($res['success']) $messages[] = ['type' => 'success', 'text' => 'Ticket purchased.'];
                else $messages[] = ['type' => 'error', 'text' => $res['message'] ?? 'Could not buy ticket.'];
            }
        }

        // Cancel ticket (Participant)
        if (isset($_POST['action']) && $_POST['action'] === 'cancel' && $role === 'participant') {
            $ticket_id = (int)($_POST['ticket_id'] ?? 0);
            if ($ticket_id > 0) {
                if (cancelTicket($user['id'], $ticket_id)) $messages[] = ['type' => 'success', 'text' => 'Ticket canceled.'];
                else $messages[] = ['type' => 'error', 'text' => 'Could not cancel ticket.'];
            }
        }

        // Close event (Admin/Organizer)
        if (isset($_POST['action']) && $_POST['action'] === 'close' && in_array($role, ['admin','organizer'])) {
            $event_id = (int)($_POST['event_id'] ?? 0);
            if ($event_id > 0) {
                if (closeEvent($event_id)) $messages[] = ['type' => 'success', 'text' => 'Event closed.'];
                else $messages[] = ['type' => 'error', 'text' => 'Could not close event.'];
            }
        }
    }
} 

// (GET actions removed) Actions are handled via POST forms to include CSRF tokens and confirmations.

$events = getEvents($role === 'admin');
$tickets = getTicketsByUser($user['id']);

?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Dashboard</h1>
    <p>Welcome <?php echo e($user['name']); ?> (<?php echo e($role); ?>) — <a href="logout.php">Logout</a></p>

    <?php if ($messages): ?>
        <div class="messages">
            <?php foreach ($messages as $m): ?>
                <p class="<?php echo e($m['type']); ?>"><?php echo e($m['text']); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <hr>

    <h2>Events</h2>
    <?php if ($role === 'organizer'): ?>
        <h3>Create Event</h3>
        <form method="post" action="">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="action" value="create_event">
            <label>Title:<br><input type="text" name="title"></label><br>
            <label>Description:<br><textarea name="description"></textarea></label><br>
            <label>Date (YYYY-MM-DD HH:MM:SS):<br><input type="text" name="date"></label><br>
            <label>Capacity:<br><input type="number" name="capacity" min="1"></label><br>
            <button type="submit">Create</button>
        </form> 
    <?php endif; ?>

    <table class="simple">
        <thead><tr><th>Title</th><th>Date</th><th>Capacity</th><th>Organizer</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($events as $ev): ?>
            <tr>
                <td><?php echo e($ev['title']); ?></td>
                <td><?php echo e($ev['date']); ?></td>
                <td><?php echo e($ev['capacity']); ?> (<?php echo count(getParticipantsByEvent($ev['id'])); ?> booked)</td>
                <td><?php echo e($ev['organizer_name']); ?></td>
                <td>
                    <?php if ($role === 'participant' && !$ev['is_closed']): ?>
                        <form method="post" action="" style="display:inline; margin:0; padding:0;">
                            <?php echo csrfInput(); ?>
                            <input type="hidden" name="action" value="buy">
                            <input type="hidden" name="event_id" value="<?php echo e($ev['id']); ?>">
                            <button type="submit" class="btn btn-link">Buy Ticket</button>
                        </form>
                    <?php endif; ?>
                    <?php if (in_array($role, ['admin','organizer'])): ?>
                        <form method="post" action="" style="display:inline; margin:0; padding:0;" class="confirm" data-confirm="Close this event?">
                            <?php echo csrfInput(); ?>
                            <input type="hidden" name="action" value="close">
                            <input type="hidden" name="event_id" value="<?php echo e($ev['id']); ?>">
                            <button type="submit" class="btn btn-link">Close</button>
                        </form>
                        <?php if ($role === 'organizer' && $ev['created_by']===$user['id']): ?>
                            | <a href="#" onclick="alert('Participants:\n<?php
                                $parts = getParticipantsByEvent($ev['id']);
                                $list = array_map(function($p){ return addslashes($p['name'].' <'.$p['email'].'>'); }, $parts);
                                echo e(implode("\n", $list));
                            ?>')">View Participants</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <hr>

    <?php if ($role === 'participant'): ?>
        <h2>Your Tickets</h2>
        <?php if ($tickets): ?>
            <ul>
                <?php foreach ($tickets as $t): ?>
                    <li><?php echo e($t['title']); ?> — <?php echo e($t['date']); ?>
                        <form method="post" action="" style="display:inline; margin:0; padding:0;" class="confirm" data-confirm="Cancel this ticket?">
                            <?php echo csrfInput(); ?>
                            <input type="hidden" name="action" value="cancel">
                            <input type="hidden" name="ticket_id" value="<?php echo e($t['id']); ?>">
                            <button type="submit" class="btn btn-link">Cancel</button>
                        </form>
                    </li> 
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No tickets yet.</p>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($role === 'admin'): ?>
        <h2>Admin: Add Organizer</h2>
        <form method="post" action="">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="action" value="add_organizer">
            <label>Name:<br><input type="text" name="name"></label><br>
            <label>Email:<br><input type="email" name="email"></label><br>
            <label>Password:<br><input type="password" name="password"></label><br>
            <button type="submit">Add Organizer</button>
        </form>

        <h3>System Settings</h3>
        <p>Basic settings are not implemented. (This is a simple demo.)</p>
    <?php endif; ?>

</div>

<script>
// Simple confirmation handler for destructive actions
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('form.confirm').forEach(function(f){
        f.addEventListener('submit', function(e){
            var msg = f.getAttribute('data-confirm') || 'Are you sure?';
            if (!confirm(msg)) e.preventDefault();
        });
    });
});
</script>

</body>
</html>