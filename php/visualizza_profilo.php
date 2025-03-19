<?php
session_start();
require_once('connessione.php');

// Verifica accesso
if (!isset($_SESSION['statoLogin'])) {
    header("Location: login.php");
    exit();
} elseif ($_SESSION['tipo_utente'] !== 'gestore') {
    header("Location: home.php");
    exit();
}

// Controllo su POST
if (!isset($_POST['username'])) {
    die("Errore: nessun username fornito.");
}
$username = $_POST['username'];

// Carica il file XML
$xml = simplexml_load_file('../xml/acquisti.xml');
if ($xml === false) {
    die("Errore nel caricamento del file XML.");
}

// Cerca gli acquisti dell'utente
$acquisti = [];
foreach ($xml->acquisto as $acquisto) {
    if ((string)$acquisto->username === $username) {
        $acquisti[] = $acquisto;
    }
}


?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizzazione Acquisti e Profilo</title>
    <style>
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            text-align: center;
        }
        .acquisto-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 10px;
        }
        .navbar {
            background-color: #000; 
            color: #fff; 
            padding: 20px 0; 
            text-align: center; 
        }
        .navbar ul {
            list-style-type: none;
            margin: 0;
            padding: 0;
            display: inline-flex; 
            list-style-type: disc; /* aggiungiamo un pallino di finco le voci del menù */
        }
        .navbar li {
            margin: 0 30px; 
        }
        .navbar a {
            color: #fff; 
            text-decoration: none; 
            font-weight: bold; 
            font-size: 18px; 
            transition: all 0.3s ease; 
        }
        .navbar a:hover {
            background-color: #555; 
            transform: scale(1.1); 
            padding: 5px; 
            border-radius: 5px; 
        }
    </style>
</head>
<body>
    <div class="navbar">
        <ul>
            <li><a href="gestore_dashboard.php">Dashboard</a></li>
            <li><a href="gestione_utenti.php">Modifica Sconti e Bonus</a></li>
            <li><a href="visualizza_utenti.php">Visualizza Utenti</a></li>
            <li><a href="#">Gestione Forum</a></li>
        </ul>
    </div>

    <div class="container">
        <h1>Acquisti di <?php echo htmlspecialchars($username); ?></h1>

        <?php if (!empty($acquisti)): ?>
            <?php foreach ($acquisti as $acquisto): ?>
                <div class="acquisto-card">
                    <p><strong>Codice Gioco:</strong> <?php echo htmlspecialchars($acquisto->codice_gioco); ?></p>
                    <p><strong>Prezzo Originale:</strong> <?php echo htmlspecialchars($acquisto->prezzo_originale); ?> Crediti</p>
                    <p><strong>Prezzo Pagato:</strong> <?php echo htmlspecialchars($acquisto->prezzo_pagato); ?> Crediti</p>
                    <p><strong>Sconto Applicato:</strong> <?php echo htmlspecialchars($acquisto->sconto_applicato); ?></p>
                    <p><strong>Bonus Ottenuti:</strong> <?php echo htmlspecialchars($acquisto->bonus_ottenuti); ?></p>
                    <p><strong>Data:</strong> <?php echo htmlspecialchars($acquisto->data); ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Nessun acquisto trovato per l'utente <?php echo htmlspecialchars($username); ?>.</p>
        <?php endif; ?>
    </div>
    
    <div class="container">
        <h1>Profilo di <?php echo htmlspecialchars($username); ?></h1>

    </div>

</body>
</html>
