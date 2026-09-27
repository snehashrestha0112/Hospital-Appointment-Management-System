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
    <title>Patient Login - Sneha Health Care</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="topbar">
        <a class="brand brand-with-mark" href="index.php">
            <span class="hospital-mark small" aria-hidden="true"></span>
            <span>Sneha Health Care</span>
        </a>
        <nav><a href="patient-register.php">Register</a></nav>
    </header>

    <main class="single-auth">
        <?php if ($flash): ?>
            <div class="notice <?php echo hms_escape($flash['type']); ?>"><?php echo hms_escape($flash['message']); ?></div>
        <?php endif; ?>

        <section class="panel auth-panel">
            <h1>Patient Login</h1>
            <form method="post" action="auth.php">
                <input type="hidden" name="action" value="login_patient">
                <label>Email
                    <input type="email" name="email" required>
                </label>
                <label>Password
                    <input type="password" name="password" required>
                </label>
                <button type="submit">Login</button>
            </form>
            <p class="form-link">New patient? <a href="patient-register.php">Create an account</a></p>
        </section>
    </main>
</body>
</html>
