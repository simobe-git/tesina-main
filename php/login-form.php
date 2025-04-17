<?php
session_start();
require_once("connessione.php");

if(isset($_POST['login']) && $_SERVER["REQUEST_METHOD"] === "POST"){ 
    $email = mysqli_real_escape_string($connessione, $_POST['email']);     // previene SQL injection (problemi)
    $password = mysqli_real_escape_string($connessione, $_POST['password']);

    $query_utente = "SELECT * FROM utenti WHERE email = '$email' AND password = '$password'";
    $result = mysqli_query($connessione, $query_utente);

    if(mysqli_num_rows($result) === 1){

        $row = mysqli_fetch_assoc($result);
        $username = $row['username'];
        $tipo_utente = $row['tipo_utente'];
        $ban = $row['ban'];

        // Controllo se l'utente è bannato
        if ($ban == 1) {
            header("Location: login.php?error=2"); // Reindirizza con errore di bannato
            exit();
        }

        $_SESSION['username'] = $username;
        $_SESSION['tipo_utente'] = $tipo_utente;
        $_SESSION['statoLogin'] = true;

        if($tipo_utente === 'admin' && $ban != 1) { //verifico che sia un admin e che non sia bannato
            header("Location: admin_dashboard.php");
            exit();
        } elseif($tipo_utente === 'gestore' && $ban != 1) { //verifico che sia un gestore e che non sia bannato
            header("Location: gestore_dashboard.php");
        } elseif ($tipo_utente === 'cliente') {
            header("Location: home.php");
        }
        exit();
    } else {
        // in caso di credenziali errate
        header("Location: login.php?error=1");
        exit();
    }
} else {
    // accesso non autorizzato: andiamo alla login
    header("Location: login.php");
    exit();
}
?>