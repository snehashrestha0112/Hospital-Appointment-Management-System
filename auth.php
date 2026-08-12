<?php
require_once __DIR__ . '/hms.php';
hms_start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    hms_redirect('index.php');
}

$action = hms_post('action');
$con = hms_db();

try {
    if ($action === 'register_patient') {
        $fname = hms_post('fname');
        $lname = hms_post('lname');
        $gender = hms_post('gender');
        $email = hms_post('email');
        $contact = hms_post('contact');
        $password = hms_post('password');
        $cpassword = hms_post('cpassword');

        if ($password !== $cpassword) {
            hms_flash('Passwords do not match.', 'error');
            hms_redirect('patient-register.php');
        }

        $stmt = $con->prepare('SELECT pid FROM patreg WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            hms_flash('A patient already exists with that email.', 'error');
            hms_redirect('patient-register.php');
        }

        $stmt = $con->prepare('INSERT INTO patreg (fname, lname, gender, email, contact, password, cpassword) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('sssssss', $fname, $lname, $gender, $email, $contact, $password, $cpassword);
        $stmt->execute();

        $_SESSION['role'] = 'patient';
        $_SESSION['pid'] = $con->insert_id;
        $_SESSION['patient_name'] = $fname . ' ' . $lname;
        $_SESSION['fname'] = $fname;
        $_SESSION['lname'] = $lname;
        $_SESSION['gender'] = $gender;
        $_SESSION['email'] = $email;
        $_SESSION['contact'] = $contact;
        hms_redirect('admin-panel.php');
    }

    if ($action === 'login_patient') {
        $email = hms_post('email');
        $password = hms_post('password');
        $stmt = $con->prepare('SELECT * FROM patreg WHERE email = ? AND password = ? LIMIT 1');
        $stmt->bind_param('ss', $email, $password);
        $stmt->execute();
        $patient = $stmt->get_result()->fetch_assoc();

        if (!$patient) {
            hms_flash('Invalid patient email or password.', 'error');
            hms_redirect('patient-login.php');
        }

        $_SESSION['role'] = 'patient';
        $_SESSION['pid'] = $patient['pid'];
        $_SESSION['patient_name'] = $patient['fname'] . ' ' . $patient['lname'];
        $_SESSION['fname'] = $patient['fname'];
        $_SESSION['lname'] = $patient['lname'];
        $_SESSION['gender'] = $patient['gender'];
        $_SESSION['email'] = $patient['email'];
        $_SESSION['contact'] = $patient['contact'];
        hms_redirect('admin-panel.php');
    }

    if ($action === 'login_doctor') {
        $username = hms_post('username');
        $password = hms_post('password');
        $stmt = $con->prepare('SELECT username FROM doctb WHERE username = ? AND password = ? LIMIT 1');
        $stmt->bind_param('ss', $username, $password);
        $stmt->execute();
        $doctor = $stmt->get_result()->fetch_assoc();

        if (!$doctor) {
            hms_flash('Invalid doctor username or password.', 'error');
            hms_redirect('doctor-login.php');
        }

        $_SESSION['role'] = 'doctor';
        $_SESSION['doctor_username'] = $doctor['username'];
        hms_redirect('doctor-panel.php');
    }

    if ($action === 'login_admin') {
        $username = hms_post('username');
        $password = hms_post('password');
        $stmt = $con->prepare('SELECT username FROM admintb WHERE username = ? AND password = ? LIMIT 1');
        $stmt->bind_param('ss', $username, $password);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();

        if (!$admin) {
            hms_flash('Invalid admin username or password.', 'error');
            hms_redirect('admin-login.php');
        }

        $_SESSION['role'] = 'admin';
        $_SESSION['admin_username'] = $admin['username'];
        hms_redirect('admin-panel1.php');
    }

    hms_flash('Unknown action.', 'error');
    hms_redirect('index.php');
} catch (mysqli_sql_exception $e) {
    hms_flash('Database error: ' . $e->getMessage(), 'error');
    if ($action === 'register_patient') {
        hms_redirect('patient-register.php');
    }
    if ($action === 'login_patient') {
        hms_redirect('patient-login.php');
    }
    if ($action === 'login_doctor') {
        hms_redirect('doctor-login.php');
    }
    if ($action === 'login_admin') {
        hms_redirect('admin-login.php');
    }
    hms_redirect('index.php');
}
?>
