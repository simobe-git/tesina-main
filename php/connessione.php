<?php 

require_once("dati-connessione.php");   

$connessione = mysqli_connect($hostname,$user,$password,$db);

if(mysqli_connect_errno()){

    printf("Errore di connessione: %s",mysqli_connect_error());
    exit();
}

?>