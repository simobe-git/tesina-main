<?php
session_start();
require_once('connessione.php');
require_once('funzioni_sconti_bonus.php');

// verifica che l'utente sia un gestore o admin
if (!isset($_SESSION['tipo_utente']) || ($_SESSION['tipo_utente'] != 'gestore' && $_SESSION['tipo_utente'] != 'admin')) {
    header('Location: index.php');
    exit();
}

// gestione della form per aggiungere/modificare sconti
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['aggiungi_sconto'])) {
    $xml_file = '../xml/sconti_bonus.xml';
    
    // carichiamo (o creiamo) il file XML
    if (!file_exists($xml_file)) {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><root><sconti></sconti><bonus></bonus></root>');
    } else {
        $xml = simplexml_load_file($xml_file);
    }
    
    $sconto = $xml->sconti->addChild('sconto');
    $sconto->addChild('percentuale', $_POST['percentuale']);
    $sconto->addChild('tipo', $_POST['tipo_sconto']);
    $sconto->addChild('codice_gioco', $_POST['codice_gioco']);
    $sconto->addChild('data_inizio', $_POST['data_inizio']);
    $sconto->addChild('data_fine', $_POST['data_fine']);
    
    // aggiungiamo requisiti specifici in base al tipo di sconto
    switch($_POST['tipo_sconto']) {
        case 'crediti_spesi':
            $sconto->addChild('requisito_crediti', $_POST['valore_minimo']);
            break;
        case 'reputazione':
            $sconto->addChild('requisito_reputazione', $_POST['valore_minimo']);
            break;
        case 'anzianita':
            $sconto->addChild('requisito_mesi', $_POST['valore_minimo']);
            break;
    }
    
    if ($xml->asXML($xml_file)) {
        $messaggio = "Sconto aggiunto con successo!";
    } else {
        $errore = "Errore nell'aggiunta dello sconto.";
    }
}

// gestione della form per aggiungere bonus
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['aggiungi_bonus'])) {
    $xml_file = '../xml/sconti_bonus.xml';
    
    if (!file_exists($xml_file)) {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><root><sconti></sconti><bonus></bonus></root>');
    } else {
        $xml = simplexml_load_file($xml_file);
    }
    
    $bonus = $xml->bonus->addChild('bonus');
    $bonus->addChild('crediti', $_POST['crediti_bonus']);
    $bonus->addChild('codice_gioco', $_POST['codice_gioco']);
    $bonus->addChild('data_inizio', $_POST['data_inizio']);
    $bonus->addChild('data_fine', $_POST['data_fine']);
    
    if ($xml->asXML($xml_file)) {
        $messaggio = "Bonus aggiunto con successo!";
    } else {
        $errore = "Errore nell'aggiunta del bonus.";
    }
}

// recuperiamo la lista dei videogiochi dal db
$query_giochi = "SELECT codice, nome FROM videogiochi ORDER BY nome";
$risultato_giochi = $connessione->query($query_giochi);

// carichiamo sconti e bonus esistenti
$sconti_bonus = [];
if (file_exists('../xml/sconti_bonus.xml')) {
    $xml = simplexml_load_file('../xml/sconti_bonus.xml');
    $sconti_bonus = $xml;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Sconti e Bonus</title>
    <link rel="stylesheet" href="../css/stile.css">
    <style>
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-section {
            background: #f5f5f5;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input, select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
        }
        .btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .messaggio {
            padding: 10px;
            margin-bottom: 10px;
        }
        .sconti-esistenti, .bonus-esistenti {
            margin-top: 20px;
            padding: 15px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .sconto-item, .bonus-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .delete-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        #tipo_sconto_details {
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <?php include('menu.php'); ?>
    
    <div class="container">
        <h1>Gestione Sconti e Bonus</h1>
        
        <!-- form per gli sconti -->
        <div class="form-section">
            <h2>Aggiungi Sconto</h2>
            <form method="post" id="scontoForm">
                <div class="form-group">
                    <label for="percentuale">Percentuale Sconto</label>
                    <input type="number" id="percentuale" name="percentuale" min="1" max="100" required>
                </div>
                
                <div class="form-group">
                    <label for="tipo_sconto">Tipo Sconto</label>
                    <select id="tipo_sconto" name="tipo_sconto" required onchange="mostraDettagliSconto()">
                        <option value="">Seleziona tipo sconto</option>
                        <option value="crediti_spesi">Basato su crediti spesi</option>
                        <option value="reputazione">Basato sulla reputazione</option>
                        <option value="anzianita">Basato sull'anzianità</option>
                        <option value="acquisto_specifico">Per acquisto specifico</option>
                    </select>
                </div>
                
                <div id="tipo_sconto_details"></div>
                
            </form>
        </div>
        
        <!-- visualizzazione sconti esistenti -->
        <div class="sconti-esistenti">
            <h2>Sconti Attivi</h2>
            <?php if (isset($sconti_bonus->sconti)): foreach($sconti_bonus->sconti->sconto as $sconto): ?>
                <div class="sconto-item">
                    <p>Sconto del <?php echo $sconto->percentuale; ?>% - 
                       Tipo: <?php echo $sconto->tipo; ?></p>
                    <button class="delete-btn" onclick="eliminaSconto('<?php echo $sconto['id']; ?>')">
                        Elimina
                    </button>
                </div>
            <?php endforeach; endif; ?>
        </div>
        
        <!-- form per i bonus -->
        <div class="form-section">
            <h2>Aggiungi Bonus</h2>
            <form method="post" id="bonusForm">
                <div class="form-group">
                    <label for="crediti_bonus">Crediti Bonus</label>
                    <input type="number" id="crediti_bonus" name="crediti_bonus" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="codice_gioco_bonus">Gioco</label>
                    <select id="codice_gioco_bonus" name="codice_gioco" required>
                        <?php while ($gioco = $risultato_giochi->fetch_assoc()): ?>
                            <option value="<?php echo $gioco['codice']; ?>"><?php echo htmlspecialchars($gioco['nome']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="data_inizio_bonus">Data Inizio</label>
                    <input type="date" id="data_inizio_bonus" name="data_inizio" required>
                </div>
                
                <div class="form-group">
                    <label for="data_fine_bonus">Data Fine</label>
                    <input type="date" id="data_fine_bonus" name="data_fine" required>
                </div>
                
                <button type="submit" name="aggiungi_bonus">Aggiungi Bonus</button>
            </form>
        </div>
        
        <!-- visualizzazione bonus esistenti -->
        <div class="bonus-esistenti">
            <h2>Bonus Attivi</h2>
            <?php if (isset($sconti_bonus->bonus)): foreach($sconti_bonus->bonus->bonus as $bonus): ?>
                <div class="bonus-item">
                    <p>Bonus di <?php echo $bonus->crediti; ?> crediti - 
                       Gioco: <?php echo $bonus->codice_gioco; ?></p>
                    <button class="delete-btn" onclick="eliminaBonus('<?php echo $bonus['id']; ?>')">
                        Elimina
                    </button>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <script>
    function mostraDettagliSconto() {
        const tipo = document.getElementById('tipo_sconto').value;
        const detailsDiv = document.getElementById('tipo_sconto_details');
        
        let html = '';
        switch(tipo) {
            case 'crediti_spesi':
                html = `
                    <div class="form-group">
                        <label for="valore_minimo">Crediti minimi spesi</label>
                        <input type="number" id="valore_minimo" name="valore_minimo" required>
                    </div>`;
                break;
            case 'reputazione':
                html = `
                    <div class="form-group">
                        <label for="valore_minimo">Reputazione minima richiesta</label>
                        <input type="number" id="valore_minimo" name="valore_minimo" min="0" max="10" step="0.1" required>
                    </div>`;
                break;
            case 'anzianita':
                html = `
                    <div class="form-group">
                        <label for="valore_minimo">Mesi di anzianità richiesti</label>
                        <input type="number" id="valore_minimo" name="valore_minimo" min="1" required>
                    </div>`;
                break;
        }
        detailsDiv.innerHTML = html;
    }
    
    function eliminaSconto(id) {
        if (confirm('Sei sicuro di voler eliminare questo sconto?')) {
            // implementare l'eliminazione (tramite AJAX?? vedere bene come)
        }
    }
    </script>
</body>
</html>
