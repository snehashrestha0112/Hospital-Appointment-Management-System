<?php
require_once __DIR__ . '/hms.php';
hms_start_session();
$flash = hms_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Registration - Sneha Health Care</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="topbar">
        <a class="brand brand-with-mark" href="index.php">
            <span class="hospital-mark small" aria-hidden="true"></span>
            <span>Sneha Health Care</span>
        </a>
        <nav><a href="patient-login.php">Patient Login</a></nav>
    </header>

    <main class="single-auth wide">
        <?php if ($flash): ?>
            <div class="notice <?php echo hms_escape($flash['type']); ?>"><?php echo hms_escape($flash['message']); ?></div>
        <?php endif; ?>

        <section class="panel auth-panel">
            <h1>Patient Registration</h1>
            <form method="post" action="auth.php">
                <input type="hidden" name="action" value="register_patient">
                <div class="two-col">
                    <label>First Name
                        <input type="text" name="fname" required>
                    </label>
                    <label>Last Name
                        <input type="text" name="lname" required>
                    </label>
                </div>
                <div class="two-col">
                    <label>Email
                        <input type="email" name="email" required>
                    </label>
                    <label>Contact
                        <input type="tel" name="contact" maxlength="15" required>
                    </label>
                </div>
                <label>Gender
                    <select name="gender" required>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </label>
                <div class="two-col">
                    <label>Password
                        <input type="password" name="password" minlength="6" required>
                    </label>
                    <label>Confirm Password
                        <input type="password" name="cpassword" minlength="6" required>
                    </label>
                </div>
                <button type="submit">Register</button>
            </form>
            <p class="form-link">Already registered? <a href="patient-login.php">Login here</a></p>
        </section>
    </main>
</body>
</html>
