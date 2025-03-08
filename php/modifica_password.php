<?php
session_start();
require_once('connessione.php');

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$messaggio = '';
$errore = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nuova_password = $_POST['nuova_password'];
    $conferma_password = $_POST['conferma_password'];
    
    if (strlen($nuova_password) < 8) {
        $errore = "La password deve essere di almeno 8 caratteri";
    } elseif ($nuova_password !== $conferma_password) {
        $errore = "Le password non coincidono";
    } else {
        // hash della nuova password
        $password_hash = password_hash($nuova_password, PASSWORD_DEFAULT);
        
        // aggiornamento della password nel database
        $query = "UPDATE utenti SET password = ? WHERE username = ?";
        $stmt = $connessione->prepare($query);
        $stmt->bind_param("ss", $password_hash, $_SESSION['username']);
        
        if ($stmt->execute()) {
            $messaggio = "Password aggiornata con successo!";
        } else {
            $errore = "Errore durante l'aggiornamento della password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Modifica Password - GameShop</title>
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="../css/menu.css">
    <style>
        .container {
            margin-top: 100px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
            padding: 20px;
        }
        .form-password {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .btn-submit {
            background: #28a745;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            margin-top: 20px;
        }
        .btn-submit:hover {
            background: #218838;
        }
        .messaggio {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
        }
        .successo {
            background: #d4edda;
            color: #155724;
        }
        .errore {
            background: #f8d7da;
            color: #721c24;
        }
        .torna-profilo {
            text-align: center;
            margin-top: 20px;
        }
        .torna-profilo a {
            color: #007bff;
            text-decoration: none;
        }
        .torna-profilo a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include('menu.php'); ?>

    <div class="container">
        <h1 style="text-align: center; margin-bottom: 30px;">Modifica Password</h1>
        
        <?php if ($messaggio): ?>
            <div class="messaggio successo"><?php echo $messaggio; ?></div>
        <?php endif; ?>
        
        <?php if ($errore): ?>
            <div class="messaggio errore"><?php echo $errore; ?></div>
        <?php endif; ?>
        
        <div class="form-password">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="nuova_password">Nuova Password</label>
                    <input type="password" id="nuova_password" name="nuova_password" required 
                           minlength="8" placeholder="Inserisci la nuova password">
                </div>
                
                <div class="form-group">
                    <label for="conferma_password">Conferma Password</label>
                    <input type="password" id="conferma_password" name="conferma_password" required 
                           minlength="8" placeholder="Conferma la nuova password">
                </div>
                
                <button type="submit" class="btn-submit">Aggiorna Password</button>
            </form>
        </div>
        
        <div class="torna-profilo">
            <a href="modifica_profilo.php">← Torna al profilo</a>
        </div>
    </div>
</body>
</html>