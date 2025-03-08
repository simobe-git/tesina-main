<?php
session_start();
require_once("connessione.php");

/*
function carica_utenti() {
    return simplexml_load_file('../xml/utenti.xml');
}
*/

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

        $_SESSION['username'] = $username;
        $_SESSION['tipo_utente'] = $tipo_utente;

        $_SESSION['statoLogin'] = true;

        if($tipo_utente === 'admin' && $ban != 1) { //verifico che sia un admin e che non sia bannato
            
            header("Location: admin_dashboard.php");
            exit();

        } elseif($tipo_utente === 'gestore' && $ban != 1) { //verifico che sia un gestore e che non sia bannato
            
            header("Location: home.php");
            exit();
        
        }elseif($tipo_utente === 'cliente' && $ban != 1){ //verifico che sia un cliente e che non sia bannato
            
            header("Location: home.php");
            exit();
        
        }else{//utente bannato
            
            header("Location: login.php?error=2"); 
            exit();
        }
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