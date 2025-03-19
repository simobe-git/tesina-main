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

// caricamento dati giochi da file xml
$xml = simplexml_load_file('../xml/giochi.xml');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    if (isset($_POST['aggiungi_gioco'])) {
        // creazione nuovo elemento gioco
        $nuovo_gioco = $xml->addChild('gioco');
        $nuovo_gioco->addChild('codice', $_POST['codice']);
        $nuovo_gioco->addChild('titolo', $_POST['titolo']);
        $nuovo_gioco->addChild('prezzo_originale', $_POST['prezzo_originale']);
        $nuovo_gioco->addChild('prezzo_attuale', $_POST['prezzo_attuale']);
        $nuovo_gioco->addChild('disponibile', $_POST['disponibile']);
        $nuovo_gioco->addChild('categoria', $_POST['categoria']);
        $nuovo_gioco->addChild('min_num_giocatori', $_POST['min_num_giocatori']);
        $nuovo_gioco->addChild('max_num_giocatori', $_POST['max_num_giocatori']);
        $nuovo_gioco->addChild('min_eta', $_POST['min_eta']);
        $nuovo_gioco->addChild('avg_partita', $_POST['avg_partita']);
        $nuovo_gioco->addChild('data_rilascio', $_POST['data_rilascio']);
        $nuovo_gioco->addChild('nome_editore', $_POST['nome_editore']);
        $nuovo_gioco->addChild('autore', $_POST['autore']);
        $nuovo_gioco->addChild('descrizione', $_POST['descrizione']);
        $nuovo_gioco->addChild('meccaniche', $_POST['meccaniche']);
        $nuovo_gioco->addChild('ambientazione', $_POST['ambientazione']);
        $nuovo_gioco->addChild('immagine', $_POST['immagine']);

        // salvataggio file XML aggiornato
        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());
        $dom->save('../xml/giochi.xml');

        $messaggio = "Nuovo gioco aggiunto con successo";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aggiungi Gioco</title>
    <style>
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
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 50px;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .form-group textarea {
            resize: vertical;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            background-color: #007bff;
            color: white;
            font-size: 16px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .messaggio {
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
        }
    </style>
</head>
<body>

<div class="navbar">
        <ul>
            <li><a href="gestore_dashboard.php">Dashboard</a></li>
            <li><a href="gestione_utenti.php">Modifica Sconti e Bonus</a></li>
            <li><a href="#">Visualizza Utenti</a></li>
            <li><a href="#">Gestione Forum</a></li>
        </ul>
    </div>
    <br>
    <div class="container">
        <h1>Aggiungi Nuovo Gioco</h1>
        
        <?php if (isset($messaggio)): ?>
            <div class="messaggio"><?php echo $messaggio; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Codice:</label>
                <input type="text" name="codice" required>
            </div>
            <div class="form-group">
                <label>Titolo:</label>
                <input type="text" name="titolo" required>
            </div>
            <div class="form-group">
                <label>Prezzo Originale:</label>
                <input type="text" name="prezzo_originale" required>
            </div>
            <div class="form-group">
                <label>Prezzo Attuale:</label>
                <input type="text" name="prezzo_attuale" required>
            </div>
            <div class="form-group">
                <label>Disponibile:</label>
                <input type="text" name="disponibile" required>
            </div>
            <div class="form-group">
                <label>Categoria:</label>
                <input type="text" name="categoria" required>
            </div>
            <div class="form-group">
                <label>Minimo Numero di Giocatori:</label>
                <input type="text" name="min_num_giocatori" required>
            </div>
            <div class="form-group">
                <label>Massimo Numero di Giocatori:</label>
                <input type="text" name="max_num_giocatori" required>
            </div>
            <div class="form-group">
                <label>Età Minima:</label>
                <input type="text" name="min_eta" required>
            </div>
            <div class="form-group">
                <label>Durata Media della Partita (minuti):</label>
                <input type="text" name="avg_partita" required>
            </div>
            <div class="form-group">
                <label>Data di Rilascio:</label>
                <input type="date" name="data_rilascio" required>
            </div>
            <div class="form-group">
                <label>Nome Editore:</label>
                <input type="text" name="nome_editore" required>
            </div>
            <div class="form-group">
                <label>Autore:</label>
                <input type="text" name="autore" required>
            </div>
            <div class="form-group">
                <label>Descrizione:</label>
                <textarea name="descrizione" rows="4" required></textarea>
            </div>
            <div class="form-group">
                <label>Meccaniche:</label>
                <input type="text" name="meccaniche" required>
            </div>
            <div class="form-group">
                <label>Ambientazione:</label>
                <input type="text" name="ambientazione" required>
            </div>
            <div class="form-group">
                <label>Immagine (URL):</label>
                <input type="text" name="immagine" required>
            </div>
            <button type="submit" name="aggiungi_gioco" class="btn">Aggiungi Gioco</button>
        </form>
    </div>

</body>
</html>

