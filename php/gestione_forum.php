<?php
session_start();
include('connessione.php');

// verifica se l'utente è loggato e se è un gestore
if (!isset($_SESSION['statoLogin'])) {
    header("Location: login.php");
    exit();
} elseif ($_SESSION['tipo_utente'] !== 'gestore') {
    header("Location: home.php");
    exit();
}

// Caricamento file domande
$domandePath = '../xml/domande.xml';
if (!file_exists($domandePath)) {
    die("Il file domande.xml non esiste.");
} else {
    $domande = simplexml_load_file($domandePath);
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
            justify-content: center;
        }
        .utente-card {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            text-align: center; 
            margin-bottom: 20px; 
            width: 50%;
            max-width: 400px;
            transition: transform 0.2s;
        }
        .utente-card:hover {
            transform: scale(1.02);
        }
        .stato-bannato { color: #dc3545; }
        .stato-attivo { color: #28a745; font-weight: bold; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-primary { background: #007bff; color: white; }
        .messaggio {
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
        }
        .navbar {
            background-color: tomato; 
            color: #fff; 
            padding: 20px 0; 
            text-align: center; 
        }
        .navbar ul {
            list-style-type: none;
            margin: 0;
            padding: 0;
            display: inline-flex; 
            list-style-type: disc;
        }
        .navbar li {
            margin: 0 30px; 
        }
        .navbar a {
            color: #fff; 
            text-decoration: none; 
            font-weight: bold; 
            font-size: 20px; 
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
            display: flex; 
            flex-wrap: wrap;
            gap: 20px; 
            width: 100%;
            justify-content: center;
        }
        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
            margin-top: 5px;
        }
        .username {
            color: red;
            font-size: 1.2em;
        }
        .form-group label {
            font-weight: bold;
            font-size: 1.1em;
        }
        .stato-label {
            font-weight: bold;
            font-size: 1.1em;
        }
    </style>
</head>
<body>

    <div class="navbar">
        <ul>
            <li><a href="gestore_dashboard.php">Dashboard</a></li>
            <li><a href="gestione_utenti.php">Modifica Sconti e Bonus</a></li>
            <li><a href="visualizza_utenti.php">Visualizza Utenti</a></li>
            <li><a href="gestione_forum.php">Gestione Forum</a></li>
        </ul>
    </div>

    <!--Visualizzazione Discussione-->
    <div class="container">
        <div class="title-container">
            <h1 style="font-size: 200%;">Gestione Discussioni</h1>
            <h3>Visualizzazione delle discussioni legate ai giochi</h3>
        </div>
        
        <!--Visualizzazione Domanda-->
        <div class="utente-grid"> 
            <?php foreach ($domande->domanda as $domanda): ?>
                <div class="utente-card">
                    <h3><?php echo htmlspecialchars($domanda->titolo); ?></h3>
                    <form method="POST">
                        <input type="hidden" name="codice" value="<?php echo $domanda->codice_gioco; ?>">

                        <div class="form-group">
                            <label>Titolo:</label>
                            <p><?php echo htmlspecialchars($domanda->titolo); ?></p>
                        </div>
                        
                        <div class="form-group">
                            <label>Contenuto:</label>
                            <p><?php echo htmlspecialchars($domanda->contenuto); ?></p>
                        </div>
                        
                        <div class="form-group">
                            <label>Autore:</label>
                            <p><?php echo htmlspecialchars($domanda->autore); ?></p>
                        </div>
                        
                        <div class="form-group">
                            <label>Data:</label>
                            <p><?php echo htmlspecialchars($domanda->data); ?></p>
                        </div>
                    </form>

                    <!-- Visualizzazione delle risposte -->
                    <div class="risposte-container">
                        <h4>Risposte:</h4>
                        <?php foreach ($domanda->risposta as $risposta): ?>
                            <div class="form-group">
                                <label>Risposta ID <?php echo htmlspecialchars($risposta['id']); ?></label>
                            </div>
                            <div class="form-group">
                                <label>Contenuto:</label>
                                <p><?php echo htmlspecialchars($risposta->contenuto); ?></p>
                            </div>
                            <div class="form-group">
                                <label>Autore:</label>
                                <p><?php echo htmlspecialchars($risposta->autore); ?></p>
                            </div>
                            <div class="form-group">
                                <label>Data:</label>
                                <p><?php echo htmlspecialchars($risposta->data); ?></p>
                            </div>
                                
                                
                            <!-- Mostra tutti i punteggi -->
                            <?php if (isset($risposta->punteggio)): ?>
                                <div class="form-group">
                                    <label>Punteggio:</label>
                                    <p><strong>Utilità:</strong> <?php echo htmlspecialchars($risposta->punteggio->utilita); ?></p>
                                    <p><strong>Risposta:</strong> <?php echo htmlspecialchars($risposta->punteggio->risposta); ?></p>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div> 
    </div>

    <!--Visualizzazione Segnalazioni-->
    <div class="container">
        <div class="title-container">
            <h1 style="font-size: 200%;">Discussioni Segnalate</h1>
            <h3>Visualizzazione delle discussioni o risposte segnalate</h3>
        </div>
        
        <!--Domanda Segnalata-->
        <div class="utente-grid"> 
        </div>

        <!--Risposta Segnalata-->
        <div class="utente-grid"> 
        </div>
    </div>
</body>
</html>