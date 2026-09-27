<?php
require_once __DIR__ . '/hms.php';
hms_require_role('admin');

$con = hms_db();
$flash = hms_flash();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && hms_post('action') === 'add_doctor') {
    $username = hms_post('username');
    $password = hms_post('password');
    $email = hms_post('email');
    $spec = hms_post('spec');
    $docFees = (int) hms_post('docFees');
    $availableDays = hms_normalize_available_days($_POST['available_days'] ?? []);
    $availableStart = hms_normalize_time(hms_post('available_start'));
    $availableEnd = hms_normalize_time(hms_post('available_end'));
    $startMinutes = hms_time_to_minutes($availableStart);
    $endMinutes = hms_time_to_minutes($availableEnd);

    if ($availableDays === '') {
        hms_flash('Please select at least one available day for the doctor.', 'error');
        hms_redirect('admin-panel1.php');
    }

    if ($availableStart === '' || $availableEnd === '' || $startMinutes === null || $endMinutes === null || $startMinutes >= $endMinutes) {
        hms_flash('Please enter a valid availability time range.', 'error');
        hms_redirect('admin-panel1.php');
    }

    $stmt = $con->prepare('SELECT username FROM doctor_table WHERE username = ? LIMIT 1');
    $stmt->bind_param('s', $username);
    $stmt->execute();

    if ($stmt->get_result()->num_rows > 0) {
        hms_flash('A doctor with that username already exists.', 'error');
        hms_redirect('admin-panel1.php');
    }

    $stmt = $con->prepare('INSERT INTO doctor_table (username, password, email, spec, docFees, available_days, available_start, available_end) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('ssssisss', $username, $password, $email, $spec, $docFees, $availableDays, $availableStart, $availableEnd);
    $stmt->execute();

    hms_flash('Doctor added successfully.');
    hms_redirect('admin-panel1.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && hms_post('action') === 'remove_doctor') {
    $username = hms_post('username');
    $stmt = $con->prepare('DELETE FROM doctor_table WHERE username = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    hms_flash('Doctor removed successfully.');
    hms_redirect('admin-panel1.php');
}

$doctors = hms_get_doctors();
$patients = $con->query('SELECT pid, fname, lname, gender, email, contact FROM patient_registration ORDER BY pid DESC')->fetch_all(MYSQLI_ASSOC);
$weekdayOptions = hms_weekday_options();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sneha Health Care</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="topbar">
        <a class="brand brand-with-mark" href="admin-panel1.php">
            <span class="hospital-mark small" aria-hidden="true"></span>
            <span>Sneha Health Care</span>
        </a>
        <nav>
            <span><?php echo hms_escape($_SESSION['admin_username']); ?></span>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <main class="dashboard">
        <?php if ($flash): ?>
            <div class="notice <?php echo hms_escape($flash['type']); ?>"><?php echo hms_escape($flash['message']); ?></div>
        <?php endif; ?>

        <section class="panel">
            <h1>Manage Doctors</h1>
            <form method="post" action="admin-panel1.php">
                <input type="hidden" name="action" value="add_doctor">
                <div class="five-col">
                    <label>Username
                        <input type="text" name="username" required>
                    </label>
                    <label>Password
                        <input type="password" name="password" minlength="6" required>
                    </label>
                    <label>Email
                        <input type="email" name="email" required>
                    </label>
                    <label>Specialization
                        <input type="text" name="spec" required>
                    </label>
                    <label>Fee
                        <input type="number" name="docFees" min="0" required>
                    </label>
                </div>
                <div class="schedule-fields">
                    <fieldset>
                        <legend>Available Days</legend>
                        <div class="weekday-grid">
                            <?php foreach ($weekdayOptions as $dayNumber => $dayName): ?>
                                <label class="check-option">
                                    <input type="checkbox" name="available_days[]" value="<?php echo hms_escape($dayNumber); ?>" <?php echo ($dayNumber >= 1 && $dayNumber <= 5) ? 'checked' : ''; ?>>
                                    <span><?php echo hms_escape($dayName); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                    <div class="two-col">
                        <label>Available From
                            <input type="time" name="available_start" value="09:00" required>
                        </label>
                        <label>Available Until
                            <input type="time" name="available_end" value="17:00" required>
                        </label>
                    </div>
                </div>
                <button type="submit">Add Doctor</button>
            </form>
        </section>

        <section class="panel">
            <h1>Doctor List</h1>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Specialization</th>
                            <th>Fee</th>
                            <th>Availability</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$doctors): ?>
                            <tr><td colspan="6">No doctors found.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($doctors as $doctor): ?>
                            <tr>
                                <td><?php echo hms_escape($doctor['username']); ?></td>
                                <td><?php echo hms_escape($doctor['email']); ?></td>
                                <td><?php echo hms_escape($doctor['spec']); ?></td>
                                <td><?php echo hms_escape($doctor['docFees']); ?></td>
                                <td><?php echo hms_escape(hms_format_doctor_availability($doctor)); ?></td>
                                <td>
                                    <form class="inline-form" method="post" action="admin-panel1.php">
                                        <input type="hidden" name="action" value="remove_doctor">
                                        <input type="hidden" name="username" value="<?php echo hms_escape($doctor['username']); ?>">
                                        <button class="danger" type="submit">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel">
            <h1>Patient List</h1>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Gender</th>
                            <th>Email</th>
                            <th>Contact</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$patients): ?>
                            <tr><td colspan="5">No patients found.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($patients as $patient): ?>
                            <tr>
                                <td><?php echo hms_escape($patient['pid']); ?></td>
                                <td><?php echo hms_escape($patient['fname'] . ' ' . $patient['lname']); ?></td>
                                <td><?php echo hms_escape($patient['gender']); ?></td>
                                <td><?php echo hms_escape($patient['email']); ?></td>
                                <td><?php echo hms_escape($patient['contact']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
