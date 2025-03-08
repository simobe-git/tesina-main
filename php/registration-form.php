<?php
session_start();
require_once("connessione.php");

// funzione che servirà più avanti per aggiornare il file XML degli utenti, inserendone username e ruolo
function aggiorna_file_xml($username, $password, $ruolo = 'cliente') {
    $xml_file = '../xml/utenti.xml';

    // caricamento del file XML esistente
    $xml = simplexml_load_file($xml_file);
    
    // creazione di un nuovo nodo utente
    $nuovo_utente = $xml->addChild('utente');
    $nuovo_utente->addChild('username', $username);  //va aggiunto in fase di registrazione (e poi mostrato nella home quando loggati)
    $nuovo_utente->addChild('ruolo', $ruolo);

    // salvataggio dell'XML aggiornato
    $xml->asXML($xml_file);
}


    $name = $_POST['name']; 
    $email = $_POST['email'];
    $password = $_POST['password'];


    // controllo che tutti i campi non siano vuoti
    if(!empty($email) || !empty($name) || !empty($password)){
        
        // controllo tasto premuto e che il metodo form sia 'post'
        if(isset($_POST['usernameScelto']) && $_SERVER['REQUEST_METHOD'] === 'POST'){  // in tal caso ci siamo registrati
        
            $username = $_POST['usernameScelto'];
        
        // verifica che email inserita non sia già collegata ad un altro account
        $result = mysqli_query($connessione,"SELECT * FROM utenti WHERE email = '$email'");
        

        if(mysqli_num_rows($result) == 0){
            
            // controlla che il formato dell email sia del tipo "nomemail@dominio"
            // FILTER_VALIDATE_EMAIL è il fitro usato per le email
            if(filter_var($email, FILTER_VALIDATE_EMAIL)){

                $sql = "INSERT INTO utenti(email,password,username) VALUES ('$email','$password','$username')";

                // verifica se l'aggiunta è andata bene
                if(mysqli_query($connessione,$sql)){

                    $ruolo = 'cliente';

                    aggiorna_file_xml($username, $password, $ruolo);
                    header("Location: login.php");
                    exit();
                }else{
                    echo "Errore".$sql.mysqli_error($connessione);
                }

            }else{
                echo"Formato email usato non valido";
            }
        }else{
            echo"Email collegata ad un altro account";
        }
    }else{
        echo"Campo vuoto da compilare";
    }   
}else{
    echo"Errore";
}

?>