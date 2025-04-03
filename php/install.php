<?php
require_once('dati-connessione.php');
// creiamo una connessione senza specificare il database
$connessione = new mysqli($hostname, $user, $password);

// verifica della connessione
if ($connessione->connect_error) {
    die("Connessione fallita: " . $connessione->connect_error);
}

// e successiva creazione del database
$sql = "CREATE DATABASE IF NOT EXISTS $db";
if ($connessione->query($sql) === TRUE) {
    echo "Database creato con successo o già esistente<br>";
} else {
    echo "Errore nella creazione del database: " . $connessione->error . "<br>";
}

// selezione del database
$connessione->select_db($db);

// disabilitiamo temporaneamente i controlli delle chiavi esterne (debug)
$connessione->query('SET FOREIGN_KEY_CHECKS = 0');

// eliminiamo le tabelle nell'ordine corretto
$tabelle = [
    //'giudizi_recensioni',
    //'recensioni',
    //'bonus',
    //'gioco_tavolo',
    'utenti',
    //'faq',
    //'carrello'
]; //tabelle non più esistenti nel db perchè migrate in file xml

foreach ($tabelle as $tabella) {
    $sql = "DROP TABLE IF EXISTS $tabella";
    if ($connessione->query($sql) === TRUE) {
        echo "Tabella $tabella eliminata se esistente<br>";
    } else {
        echo "Errore nell'eliminazione della tabella $tabella: " . $connessione->error . "<br>";
    }
}

// riabilitazionw controlli delle chiavi esterne
$connessione->query('SET FOREIGN_KEY_CHECKS = 1');

// creazione della tabella utenti
$sql = "CREATE TABLE utenti (
    nome VARCHAR(30) NOT NULL,
    cognome VARCHAR(30) NOT NULL,
    username VARCHAR(30) NOT NULL PRIMARY KEY,
    email VARCHAR(50) NOT NULL,
    password VARCHAR(30) NOT NULL,
    tipo_utente ENUM('visitatore', 'cliente', 'gestore', 'admin') DEFAULT 'cliente',
    crediti DECIMAL(10,2) DEFAULT 0.00,
    data_registrazione DATETIME DEFAULT CURRENT_TIMESTAMP,
    ban BIT DEFAULT FALSE -- colonna 'ban' per indicare se l'utente è bannato. Valore predefinito: FALSE (ovvero non bannato --> utente attivo)
)";

if ($connessione->query($sql) === TRUE) {
    echo "Tabella utenti creata con successo<br>";
} else {
    echo "Errore nella creazione della tabella utenti: " . $connessione->error . "<br>";
}

// inserimento utenti
$sql = "INSERT INTO utenti (nome, cognome, username, email, password, tipo_utente, crediti, ban) VALUES 
    ('Marco', 'Neri', 'cliente2', 'marco@email.it', 'Cliente123!', 'cliente', 75.00, FALSE),
    ('Mario', 'Rossi', 'admin1', 'admin@gaming.it', 'Admin123!', 'admin', 0.00, FALSE),
    ('Anna', 'Gialli', 'cliente3', 'anna@email.it', 'Cliente123!', 'cliente', 150.00, FALSE),
    ('Paolo', 'Viola', 'gestore2', 'paolo@gaming.it', 'Gestore123!', 'gestore', 0.00, FALSE),
    ('Laura', 'Rosa', 'admin2', 'laura@gaming.it', 'Admin123!', 'admin', 0.00, FALSE),
    ('Luca', 'Marroni', 'cliente4', 'luca@email.it', 'Cliente123!', 'cliente', 200.00, FALSE),
    ('Giuseppe', 'Bianchi', 'cliente1', 'giuseppe@email.it', 'Cliente123!', 'cliente', 100.50, FALSE),
    ('Luigi', 'Verdi', 'gestore1', 'gestore@gaming.it', 'Gestore123!', 'gestore', 0.00, FALSE)";

if ($connessione->query($sql) === TRUE) {
    echo "Utenti inseriti con successo<br>";
} else {
    echo "Errore nell'inserimento degli utenti: " . $connessione->error . "<br>";
}

// chiudiamo la connessione
$connessione->close();
echo "Installazione completata!";
?>

