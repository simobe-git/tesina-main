<?php
session_start();
require_once"connessione.php";

// controlla tasto reset premuto e metodo form è post
if(isset($_POST['reset']) && $_SERVER['REQUEST_METHOD'] == 'POST'){

    // controlla che il campo email non sia vuoto
    if(!empty($_POST['email'])){

        $email = $_POST['email'];

        // controlla che il formato dell email sia del tipo "nomemail@dominio"
        if(filter_var($email, FILTER_VALIDATE_EMAIL)){

            
            $password = $_POST['password'];
            $confirm_password = $_POST['Newpassword'];
            
            // controllo passaword reset coincidano
            if($password === $confirm_password){

                $query_ricerca = "SELECT * FROM utenti WHERE email='$email'";
                $result = mysqli_query($connessione,$query_ricerca);
                
                // se la query resituisce esattamente una riga allora utente esiste
                if(mysqli_num_rows($result)==1){

                    //aggiorna la password
                    $old_password = "UPDATE utenti SET password='$password' WHERE email='$email'";
                    
                    if(mysqli_query($connessione,$old_password)){

                        header('Location: login.php');
                        exit();
                        
                    }else{
                        echo "Errore".$sql.mysqli_error($connessione);
                    }
                }else{
                    echo"Utente non esiste";
                }
            }else{
                echo"Password non coincidono";
            }
        }else{
            echo"Formato email non valido";
        }
    }else{
        echo"Campo email vuoto";
    }
}else{
    echo"Errore";
}
?>