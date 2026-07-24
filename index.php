<?php
session_start();
$flash='';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sneha Health Care</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="topbar">
        <a class="brand brand-with-mark" href="index.php">
            <span class="hospital-mark small" aria-hidden="true"></span>
            <span>Sneha Health Care</span>
        </a>
    </header>

    <main class="home-page">
        <?php if ($flash): ?>
            <div class="notice <?php echo hms_escape($flash['type']); ?>"><?php echo hms_escape($flash['message']); ?></div>
        <?php endif; ?>

        <section class="home-hero">
            <span class="hospital-mark large" aria-hidden="true"></span>
            <p class="eyebrow">Sneha Health Care</p>
            <h1>Compassion in every appointment.</h1>
            <p class="slogan">Your trusted path to timely care.</p>
        </section>

        <section class="portal-grid" aria-label="Hospital portals">
            <a class="portal-card" href="patient-login.php">
                <span>Patient Login</span>
                <small>Book and view appointments</small>
            </a>
            <a class="portal-card" href="patient-register.php">
                <span>Patient Registration</span>
                <small>Create a patient account</small>
            </a>
            <a class="portal-card" href="doctor-login.php">
                <span>Doctor Login</span>
                <small>View assigned appointments</small>
            </a>
            <a class="portal-card" href="admin-login.php">
                <span>Admin Login</span>
                <small>Manage doctors and patients</small>
            </a>
        </section>
    </main>
</body>
</html>
