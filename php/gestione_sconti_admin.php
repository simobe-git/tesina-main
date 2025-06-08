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
    if (!file_exists($xml_file)) {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><sconti_bonus><sconti></sconti><bonus></bonus></sconti_bonus>');
    } else {
        $xml = simplexml_load_file($xml_file);
    }
    $id = time();
    $sconto = $xml->sconti->addChild('sconto');
    $sconto->addAttribute('id', $id);
    $tipo = $_POST['tipo_sconto'];
    $sconto->addChild('tipo', $tipo);
    if ($tipo === 'crediti_spesi_periodo') {
        $sconto->addChild('periodo_mesi', $_POST['periodo_mesi']);
        $livelli = $sconto->addChild('livelli');
        foreach ($_POST['livello'] as $liv) {
            $livello = $livelli->addChild('livello');
            $livello->addChild('requisito_crediti', $liv['requisito_crediti']);
            $livello->addChild('percentuale', $liv['percentuale']);
            $livello->addChild('descrizione', $liv['descrizione']);
        }
    } elseif ($tipo === 'reputazione') {
        $sconto->addChild('percentuale', $_POST['percentuale']);
        $sconto->addChild('requisito_min', $_POST['requisito_min']);
        $sconto->addChild('requisito_max', $_POST['requisito_max']);
    } elseif ($tipo === 'anzianita') {
        $sconto->addChild('percentuale', $_POST['percentuale']);
        $sconto->addChild('requisito_mesi', $_POST['requisito_mesi']);
    } elseif ($tipo === 'acquisto_specifico') {
        $sconto->addChild('percentuale', $_POST['percentuale']);
        $sconto->addChild('codice_gioco_richiesto', $_POST['codice_gioco_richiesto']);
    }
    $sconto->addChild('data_inizio', $_POST['data_inizio']);
    $sconto->addChild('data_fine', $_POST['data_fine']);
    // Riformatta e salva con DOMDocument
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($xml->asXML());
    $dom->save($xml_file);
    header('Location: gestione_sconti_admin.php?success=1');
    exit();
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

// gestione eliminazione sconto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['elimina_sconto_id'])) {
    $idDaEliminare = $_POST['elimina_sconto_id'];
    $xml_file = '../xml/sconti_bonus.xml';
    if (file_exists($xml_file)) {
        $xml = simplexml_load_file($xml_file);
        if ($xml !== false && isset($xml->sconti) && isset($xml->sconti->sconto)) {
            $trovato = false;
            foreach ($xml->sconti->sconto as $sconto) {
                if ((string)$sconto['id'] === $idDaEliminare) {
                    $dom = dom_import_simplexml($sconto);
                    $dom->parentNode->removeChild($dom);
                    $trovato = true;
                    break;
                }
            }
            if ($trovato) {
                // riformattiamo e salviamo con DOMDocument
                $dom = new DOMDocument('1.0', 'UTF-8');
                $dom->preserveWhiteSpace = false;
                $dom->formatOutput = true;
                $dom->loadXML($xml->asXML());
                $dom->save($xml_file);
                header('Location: gestione_sconti_admin.php?deleted=1');
                exit();
            } else {
                $errore = "Sconto non trovato.";
            }
        }
    }
}

// carichiamo i giochi dal file XML
$giochi = simplexml_load_file('../xml/giochi.xml');
$risultato_giochi = [];
foreach ($giochi->gioco as $gioco) {
    $risultato_giochi[] = [
        'codice' => (string)$gioco->codice,
        'titolo' => (string)$gioco->titolo
    ];
}

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
            padding: 18px 10px 18px 10px;
            border-bottom: 3px solid #b0b0b0;
            margin-bottom: 18px;
            font-size: 1.12em;
            background: #f6f6fa;
            border-radius: 10px;
        }
        .sconto-item:last-child {
            border-bottom: none;
        }
        
        .delete-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.1em;
            display: block;
            margin: 20px auto 0 auto;
        }
        .sconto-item p, .sconto-item span, .sconto-item ul, .sconto-item li {
            font-size: 1.03em;
        }
        
        #tipo_sconto_details {
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
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
            <li><a href="gestione_catalogo.php">Modifica giochi</a></li>
            <li><a href="visualizza_utenti.php">Visualizza Utenti</a></li>
            <li><a href="gestione_forum.php">Gestione Forum</a></li>
        </ul>
    </div>
    
    <div class="container">
        <h1>Gestione Sconti e Bonus</h1>
        
        <!-- form per gli sconti -->
        <div class="form-section">
            <h2>Aggiungi Sconto</h2>
            <form method="post" id="scontoForm">
                <div class="form-group">
                    <label for="tipo_sconto">Tipo Sconto</label>
                    <select id="tipo_sconto" name="tipo_sconto" required onchange="mostraDettagliSconto()">
                        <option value="">Seleziona tipo sconto</option>
                        <option value="crediti_spesi_periodo">Crediti spesi in un determinato periodo</option>
                        <option value="reputazione">Reputazione di un cliente</option>
                        <option value="anzianita">Anzianità di un cliente</option>
                        <option value="acquisto_specifico">Acquisti specifici</option>
                    </select>
                </div>
                <div id="tipo_sconto_details"></div>
                <div class="form-group">
                    <label for="data_inizio">Data Inizio</label>
                    <input type="date" id="data_inizio" name="data_inizio" required>
                </div>
                <div class="form-group">
                    <label for="data_fine">Data Fine</label>
                    <input type="date" id="data_fine" name="data_fine" required>
                </div>
                <button type="submit" name="aggiungi_sconto" class="btn">Aggiungi Sconto</button>
            </form>
        </div>
        
        <!-- visualizzazione sconti esistenti -->
        <div class="sconti-esistenti">
            <h2>Sconti Attivi</h2>
            <?php if (isset($sconti_bonus->sconti)): foreach($sconti_bonus->sconti->sconto as $sconto): ?>
                <?php if ((string)$sconto->tipo === 'anzianita_anni') continue; ?>
                <div class="sconto-item">
                    <?php if ((string)$sconto->tipo === 'crediti_spesi_periodo'): ?>
                        <p><strong>Tipo:</strong> <?php echo htmlspecialchars($sconto->tipo); ?></p>
                        <ul style="margin-bottom: 8px;">
                        <?php if (isset($sconto->livelli)):
                            foreach ($sconto->livelli->livello as $livello): ?>
                                <li>
                                    <span style="font-weight:bold;">Sconto del <?php echo htmlspecialchars($livello->percentuale); ?>%</span>
                                    <br/>
                                    <span><?php echo htmlspecialchars($livello->descrizione); ?></span>
                                </li>
                        <?php endforeach; endif; ?>
                        </ul>
                        <span style="font-size: 90%; color: #888;">Periodo: <?php echo htmlspecialchars($sconto->periodo_mesi); ?> mesi (dal <?php echo htmlspecialchars($sconto->data_inizio); ?> al <?php echo htmlspecialchars($sconto->data_fine); ?>)<br/></span>
                        <br/>
                    <?php elseif ((string)$sconto->tipo === 'reputazione'): ?>
                        <p><strong>Tipo:</strong> <?php echo htmlspecialchars($sconto->tipo); ?></p>
                        <p style="margin: 8px 0 0 0;">Sconto del <?php echo htmlspecialchars($sconto->percentuale); ?>%</p>
                        <span style="font-size: 90%; color: #888;">Reputazione richiesta: da <?php echo htmlspecialchars($sconto->requisito_min); ?> a <?php echo htmlspecialchars($sconto->requisito_max); ?><br/></span>
                        <br/>
                    <?php elseif ((string)$sconto->tipo === 'anzianita'): ?>
                        <p><strong>Tipo:</strong> <?php echo htmlspecialchars($sconto->tipo); ?></p>
                        <p style="margin: 8px 0 0 0;">Sconto del <?php echo htmlspecialchars($sconto->percentuale); ?>%</p>
                        <span style="font-size: 90%; color: #888;">Anzianità richiesta: almeno <?php echo htmlspecialchars($sconto->requisito_mesi); ?> mesi<br/></span>
                        <br/>
                    <?php elseif ((string)$sconto->tipo === 'acquisto_specifico'): ?>
                        <p><strong>Tipo:</strong> <?php echo htmlspecialchars($sconto->tipo); ?></p>
                        <p style="margin: 8px 0 0 0;">Sconto del <?php echo htmlspecialchars($sconto->percentuale); ?>%</p>
                        <span style="font-size: 90%; color: #888;">Valido per acquisto del gioco con codice: <?php echo htmlspecialchars($sconto->codice_gioco_richiesto); ?><br/></span>
                        <br/>
                    <?php else: ?>
                        <p>Sconto del <?php echo htmlspecialchars($sconto->percentuale); ?>% - Tipo: <?php echo htmlspecialchars($sconto->tipo); ?></p>
                    <?php endif; ?>
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="elimina_sconto_id" value="<?php echo $sconto['id']; ?>">
                        <button type="submit" class="delete-btn">Elimina</button>
                    </form>
                </div>
            <?php endforeach; endif; ?>
        </div>
    

    <script>
    function mostraDettagliSconto() {
        const tipo = document.getElementById('tipo_sconto').value;
        const detailsDiv = document.getElementById('tipo_sconto_details');
        let html = '';
        if (tipo === 'crediti_spesi_periodo') {
            html = `
                <div class="form-group">
                    <label for="periodo_mesi">Periodo (mesi)</label>
                    <input type="number" id="periodo_mesi" name="periodo_mesi" min="1" required>
                </div>
                <div id="livelli-container">
                    <label>Livelli di sconto</label>
                    <div class="livello-group">
                        <input type="number" name="livello[0][requisito_crediti]" placeholder="Crediti richiesti da aver speso per applicare lo sconto" required>
                        <input type="number" name="livello[0][percentuale]" placeholder="Percentuale di sconto" required>
                        <input type="text" name="livello[0][descrizione]" placeholder="Descrizione dello sconto" required>
                    </div>
                </div>
                <button type="button" class="btn" style="margin-top:10px;" onclick="aggiungiLivello()">Aggiungi Livello</button>
            `;
        } else if (tipo === 'reputazione') {
            html = `
                <div class="form-group">
                    <label for="percentuale">Percentuale Sconto</label>
                    <input type="number" id="percentuale" name="percentuale" min="1" max="100" required>
                </div>
                <div class="form-group">
                    <label for="requisito_min">Reputazione minima</label>
                    <input type="number" id="requisito_min" name="requisito_min" min="0" max="10" step="0.1" required>
                </div>
                <div class="form-group">
                    <label for="requisito_max">Reputazione massima</label>
                    <input type="number" id="requisito_max" name="requisito_max" min="0" max="10" step="0.1" required>
                </div>
            `;
        } else if (tipo === 'anzianita') {
            html = `
                <div class="form-group">
                    <label for="percentuale">Percentuale Sconto</label>
                    <input type="number" id="percentuale" name="percentuale" min="1" max="100" required>
                </div>
                <div class="form-group">
                    <label for="requisito_mesi">Mesi di anzianità richiesti</label>
                    <input type="number" id="requisito_mesi" name="requisito_mesi" min="1" required>
                </div>
            `;
        } else if (tipo === 'acquisto_specifico') {
            html = `
                <div class="form-group">
                    <label for="percentuale">Percentuale Sconto</label>
                    <input type="number" id="percentuale" name="percentuale" min="1" max="100" required>
                </div>
                <div class="form-group">
                    <label for="codice_gioco_richiesto">Codice Gioco Richiesto</label>
                    <input type="text" id="codice_gioco_richiesto" name="codice_gioco_richiesto" required>
                </div>
            `;
        }
        detailsDiv.innerHTML = html;
    }
    let livelloIndex = 1;
    function aggiungiLivello() {
        const container = document.getElementById('livelli-container');
        const div = document.createElement('div');
        div.className = 'livello-group';
        div.innerHTML = `
            <input type="number" name="livello[${livelloIndex}][requisito_crediti]" placeholder="Crediti richiesti" required>
            <input type="number" name="livello[${livelloIndex}][percentuale]" placeholder="% Sconto" required>
            <input type="text" name="livello[${livelloIndex}][descrizione]" placeholder="Descrizione" required>
        `;
        container.appendChild(div);
        livelloIndex++;
    }
    </script>
</body>
</html>
