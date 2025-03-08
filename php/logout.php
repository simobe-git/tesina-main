<?php 
/*
- pulizia esplicita delle variabili di sessione importanti
- rimozione del cookie di sessione
- pulizia completa dell'array $_SESSION
questo assicura che:
- non rimangano residui della sessione precedente
- il cookie di sessione venga rimosso dal browser
- non ci siano problemi di sicurezza con sessioni "zombie"
- la transizione tra utente loggato e non loggato sia pulita
*/
session_start();

// puliamo il carrello e altre variabili di sessione specifiche
if (isset($_SESSION['carrello'])) {
    unset($_SESSION['carrello']);
}
if (isset($_SESSION['username'])) {
    unset($_SESSION['username']);
}
if (isset($_SESSION['ruolo'])) {
    unset($_SESSION['ruolo']);
}
if (isset($_SESSION['statoLogin'])) {
    unset($_SESSION['statoLogin']);
}

// distruggi completamente la sessione
$_SESSION = array();

// distruggi anche il cookie di sessione (se esiste)
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// distruggi la sessione
session_destroy();

// reindirizza alla home
header('Location: home.php');
exit();
?>