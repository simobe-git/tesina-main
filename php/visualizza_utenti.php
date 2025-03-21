<?php
session_start();
require_once('connessione.php');

// verifica se l'utente è loggato e se è un gestore
if (!isset($_SESSION['statoLogin'])) {
    header("Location: login.php");
    exit();
} elseif ($_SESSION['tipo_utente'] !== 'gestore') {
    header("Location: home.php");
    exit();
}

// Mostra tutti gli utenti
$sql = "SELECT * FROM utenti WHERE tipo_utente = 'cliente'";
$risultato = $connessione->query($sql);

$utenti = [];
if ($risultato && $risultato->num_rows > 0) {
    while ($row = $risultato->fetch_assoc()) {
        $utenti[] = $row;
    }
} else {
    $utenti = null; // Nessun utente trovato
}
?>


<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            display: flex; 
            flex-direction: column; 
            align-items: center; 
        }
        .utente-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center; 
            margin-bottom: 20px; 
            width: 100%; 
            max-width: 300px; 
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
        .title-container {
            text-align: center; 
            margin-bottom: 20px; 
        }
        .utente-grid {
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); 
            gap: 20px; 
            width: 100%; 
        }
        .btn { padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-primary { background: #007bff; color: white; }
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
        <div class="title-container">
            <h1 style="font-size: 200%;">Visualizzazione Utenti</h1>
        </div>
        
        <div class="utente-grid"> <!-- Contenitore per le schede giochi -->
            <?php if ($utenti): ?>
                <?php foreach ($utenti as $utente): ?>
                    <div class="utente-card">

                        <h3>Nome: <?php echo htmlspecialchars($utente['nome']); ?></h3>
                        <h3>Cognome: <?php echo htmlspecialchars($utente['cognome']); ?></h3>
                        <h3>Username: <?php echo htmlspecialchars($utente['username']); ?></h3>
                        <h3>Email: <?php echo htmlspecialchars($utente['email']); ?></h3>
                        <h3>Crediti: <?php echo htmlspecialchars($utente['crediti']); ?></h3>
                        <h3>Data Registrazione:<?php echo htmlspecialchars($utente['data_registrazione']); ?></h3>
                        <h3>Ban: <?php echo $utente['ban'] == 1 ? 'Sì' : 'No'; ?></h3>
                        <form action="visualizza_profilo.php" method="POST">
                            <input type="hidden" name="username" value="<?php echo htmlspecialchars($utente['username']); ?>">
                            <button type="submit" class="btn btn-primary">Invia</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <h2>Nessun utente trovato</h2>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>