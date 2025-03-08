<?php
session_start();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset</title>
    <link rel="stylesheet" href="../css/reset.css">
</head>
<body>
    <?php include('menu.php'); ?>

    <div class="login-container">
        <div class="login-form">
            <h2>RESET <span class="highlight">PASSWORD</span></h2>
            <form action="reset-password-form.php" method="POST">
                <div class="form-group">
                    <input type="email" id="email" name="email" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <input type="password" id="password" name="password" placeholder=" New password" required>
                </div>
                <div class="form-group">
                    <input type="password" id="password" name="Newpassword" placeholder="Repeat new password" required>
                </div>
                <button type="submit" name="reset" class="cta-button">RESET</button>
            </form>
        </div>
        <div class="login-image">
            <img src="isset/background-login.jpg" alt="background">
        </div>
    </div>
</body>
</html>