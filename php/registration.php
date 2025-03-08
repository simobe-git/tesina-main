<?php
session_start();

// se utente ha effettuato il login non gli pemettiamo di accedere alla pagina di registrazione
if(isset($_SESSION['statoLogin'])){
    header('Location: home.php');
    exit();
}

if(isset($_POST['registerBtn'])){ ?>
    <h1>Scegli username: </h1>
    <form action="registration-form.php" method="post">
        <input type="text" name="usernameScelto">
        <input type="hidden" name="email" value="<?php echo $_POST['email']?>">
        <input type="hidden" name="name" value="<?php echo $_POST['nome']?>">
        <input type="hidden" name="password" value="<?php echo $_POST['password']?>">
    </form>
<?php }

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration</title>
    <link rel="stylesheet" href="../css/registration.css">
</head>
<body>
    <?php include('menu.php'); ?>

    <div class="login-container">
        <div class="login-form">
            <h2>CREA UN <span class="highlight">ACCOUNT</span></h2>
            <form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">
                <div class="form-group">
                    <input type="email" id="email" name="email" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <input type="text" id="nome" name="nome" placeholder="Nome" required>
                </div>
                <div class="form-group">
                    <input type="password" id="password" name="password" placeholder="Repeat new password" required>
                </div>
                <button type="submit" name="registerBtn" class="cta-button">REGISTRATI</button>
                <p class="signup-link">Hai un account? <a href="login.php">Login</a></p>
            </form>
        </div>
        <div class="login-image">
            <img src="../isset/background-login.jpg" alt="background">
        </div>
    </div>
</body>
</html>