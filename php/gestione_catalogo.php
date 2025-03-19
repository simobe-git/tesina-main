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

// Carica dati giochi da file xml
$xml = simplexml_load_file('../xml/giochi.xml');

// Si ipotizza di modificare i dati di un gioco alla volta 
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    if(isset($_POST['modifica_dati'])){

        // recupero id inviato dal form
        $id = $_POST['codice'];

        // ricerca gioco da modificare nel file xml
        $giochi = $xml->xpath("//gioco[codice='$id']");

        if (!empty($giochi)) {
            $gioco = $giochi[0];

            // aggiornamento dati gioco
            $gioco->titolo = $_POST['titolo'];
            $gioco->prezzo_originale = $_POST['prezzo_originale'];
            $gioco->prezzo_attuale = $_POST['prezzo_attuale'];
            $gioco->disponibile = $_POST['disponibile'];
            $gioco->categoria = $_POST['categoria'];
            $gioco->min_num_giocatori = $_POST['min_num_giocatori'];
            $gioco->max_num_giocatori = $_POST['max_num_giocatori'];
            $gioco->min_eta = $_POST['min_eta'];
            $gioco->avg_partita = $_POST['avg_partita'];
            $gioco->data_rilascio = $_POST['data_rilascio'];
            $gioco->nome_editore = $_POST['nome_editore'];
            $gioco->autore = $_POST['autore'];
            $gioco->descrizione = $_POST['descrizione'];
            $gioco->meccaniche = $_POST['meccaniche'];
            $gioco->ambientazione = $_POST['ambientazione'];
            $gioco->immagine = $_POST['immagine'];

            // Salva il file XML aggiornato
            $xml->asXML('../xml/giochi.xml');
    
            $messaggio = "Dati del gioco aggiornati con successo";
        } else {
            $messaggio = "Gioco non trovato";
        }

    }elseif(isset($_POST['elimina_gioco'])){
            
        // recupero id inviato dal form
        $id = $_POST['codice'];
    
        // ricerca gioco da eliminare nel file xml
        $giochi = $xml->xpath("//gioco[codice='$id']");
            
        if (!empty($giochi)) {
            $gioco = $giochi[0];
             
            $dom = dom_import_simplexml($gioco); //dom_import_simplexml converte l'elemento SimpleXML upporta direttamente la rimozione di nodi
            $dom->parentNode->removeChild($dom); //rimuove il nodo figlio
            $xml->asXML('../xml/giochi.xml'); 

            $messaggio = "Gioco eliminato con successo";
        } else {
            $messaggio = "Gioco non trovato";
        }
    }elseif(isset($_POST['aggiungi_gioco'])){
        header("Location: aggiungi_gioco.php");
        exit();
    }
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
        .btn { padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; }
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
            <h1 style="font-size: 200%;">Gestione Giochi</h1>
            <h3>Qui puoi modificare i dati dei giochi</h3>
            <form method="POST">
                <button type="submit" name="aggiungi_gioco" class="btn btn-success">Aggiungi Gioco</button>
            </form>
        </div>
        
        <?php if (isset($messaggio)): ?>
            <div class="messaggio"><?php echo $messaggio; ?></div>
        <?php endif; ?>

        <div class="utente-grid"> <!-- Contenitore per le schede giochi -->
            <?php foreach ($xml->gioco as $game): ?>
                <div class="utente-card">
                    <h3><?php echo htmlspecialchars($game->titolo); ?></h3>
                    <form method="POST">
                        
                        <input type="hidden" name="codice" value="<?php echo $game->codice; ?>">

                        <div class="form-group">
                            <label>Titolo:</label>
                            <input type="text" name="titolo" value="<?php echo htmlspecialchars($game->titolo); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Prezzo Originale:</label>
                            <input type="text" name="prezzo_originale" value="<?php echo htmlspecialchars($game->prezzo_originale); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Prezzo Attuale:</label>
                            <input type="text" name="prezzo_attuale" value="<?php echo htmlspecialchars($game->prezzo_attuale); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Disponibile:</label>
                            <input type="text" name="disponibile" value="<?php echo htmlspecialchars($game->disponibile); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Categoria:</label>
                            <input type="text" name="categoria" value="<?php echo htmlspecialchars($game->categoria); ?>">
                        </div>

                        <div class="form-group">
                            <label>Minimo Giocatori:</label>
                            <input type="text" name="min_num_giocatori" value="<?php echo htmlspecialchars($game->min_num_giocatori); ?>">
                        </div>

                        <div class="form-group">
                            <label>Massimo Giocatori:</label>
                            <input type="text" name="max_num_giocatori" value="<?php echo htmlspecialchars($game->max_num_giocatori); ?>">
                        </div>

                        <div class="form-group">
                            <label>Minima Et&acute;:</label>
                            <input type="text" name="min_eta" value="<?php echo htmlspecialchars($game->min_eta); ?>">
                        </div>

                        <div class="form-group">
                            <label>Durata Media Partita:</label>
                            <input type="text" name="avg_partita" value="<?php echo htmlspecialchars($game->avg_partita); ?>">
                        </div>

                        <div class="form-group">
                            <label>Data Rilascio:</label>
                            <input type="text" name="data_rilascio" value="<?php echo htmlspecialchars($game->data_rilascio); ?>">
                        </div>

                        <div class="form-group">
                            <label>Nome Editore:</label>
                            <input type="text" name="nome_editore" value="<?php echo htmlspecialchars($game->nome_editore); ?>">
                        </div>

                        <div class="form-group">
                            <label>Autore:</label>
                            <input type="text" name="autore" value="<?php echo htmlspecialchars($game->autore); ?>">
                        </div>

                        <div class="form-group">
                            <label>Descrizione:</label>
                            <input type="text" name="descrizione" value="<?php echo htmlspecialchars($game->descrizione); ?>">
                        </div>

                        <div class="form-group">
                            <label>Meccaniche:</label>
                            <input type="text" name="meccaniche" value="<?php echo htmlspecialchars($game->meccaniche); ?>">
                        </div>

                        <div class="form-group">
                            <label>Ambientazione:</label>
                            <input type="text" name="ambientazione" value="<?php echo htmlspecialchars($game->ambientazione); ?>">
                        </div>

                        <div class="form-group">
                            <label>Immagine:</label>
                            <input type="text" name="immagine" value="<?php echo htmlspecialchars($game->immagine); ?>">
                        </div>
                        <br>
                        
                        <button type="submit" name="modifica_dati" class="btn btn-primary">Salva Modifiche</button>
                        <button type="submit" name="elimina_gioco" class="btn btn-danger">Elimina Gioco</button>

                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</body>
</html>