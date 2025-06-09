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

// caricamento file giochi
$giochiPath = '../xml/giochi.xml';
if (!file_exists($giochiPath)) {
    die("Il file giochi.xml non esiste.");
} else {
    $giochi = simplexml_load_file($giochiPath);
}

// caricamento file domande
$domandePath = '../xml/domande.xml';
if (!file_exists($domandePath)) {
    die("Il file domande.xml non esiste.");
} else {
    $domande = simplexml_load_file($domandePath);
}

// caricamento file FAQ
$faqPath = '../xml/faq.xml';
if (!file_exists($faqPath)) {
    die("Il file faq.xml non esiste.");
} else {
    $faq = simplexml_load_file($faqPath);
}

// caricamento file segnalazioni
$segnalazioniPath = '../xml/segnalazioni.xml';
if (!file_exists($segnalazioniPath)) {
    die("Il file segnalazioni.xml non esiste.");
} else {
    $segnalazioni = simplexml_load_file($segnalazioniPath);
}

// funzione per salvare le FAQ
function salvaFAQ($faq) {
    global $faqPath;
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($faq->asXML());
    $dom->save($faqPath);
}

// caricamento file che riporta le valutazioni date dagli altri utenti alle domande fatte
$valutazione_domanda = '../xml/valuta_discussioni.xml';
if (!file_exists($valutazione_domanda)) {
    die("Il file valutazione_domanda.xml non esiste.");
} else {
    $valutazione_domanda = simplexml_load_file($valutazione_domanda);
}

/* 
 Calcoliamo una media del punteggio assegnato dagli utenti alle domanda, in modo tale da mostrare
 al gestore il punteggio medio di ogni domanda, con il numero di valutazioni ricevute facilitando la 
 scelta di quale domanda elevare a FAQ. 

foreach($domande->domanda as $domanda){
    $punteggio = 0;
    $numero_valutazioni = 0;

    foreach($domanda->risposta as $risposta){ //per ogni domanda
        foreach($valutazione_domanda->valutazione as $valutazione){ //cerchiamo le valutazioni
            if ((string)$valutazione->id_risposta === (string)$risposta['id']){
                $punteggio += (int)$valutazione->stelle;
                $numero_valutazioni++;
            }
        }
    }

    $media = $numero_valutazioni > 0 ? $punteggio / $numero_valutazioni : 0; // calcolo media
}*/

// filtraggio delle domande in base al gioco selezionato
$domandeFiltrate = [];
if (isset($_POST['gioco'])) {
    $giocoSelezionato = $_POST['gioco'];
    foreach ($domande->domanda as $domanda) {
        if ((string)$domanda->codice_gioco === $giocoSelezionato) {
            $domandeFiltrate[] = $domanda;
        }
    }
}

// gestione dell'elevazione a FAQ
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['eleva_faq'])) {
    $domandaId = $_POST['domanda_id'];
    $rispostaContenuto = $_POST['risposta_contenuto'];
    $domandaContenuto = $_POST['domanda_contenuto'];
    
    // aggiungimo nuova FAQ
    $nuova_faq = $faq->addChild('faq');
    $nuova_faq->addAttribute('id', time()); // Usare il timestamp come ID
    $nuova_faq->addChild('domanda', htmlspecialchars($domandaContenuto));
    $nuova_faq->addChild('risposta', htmlspecialchars($rispostaContenuto));
    $nuova_faq->addChild('data_creazione', date('Y-m-d'));
    $nuova_faq->addChild('fonte', 'forum');
    
    // salvataggio delle FAQ aggiornate
    salvaFAQ($faq);

}

// funzione per salvare le segnalazioni
function salvaSegnalazioni($segnalazioni) {
    global $segnalazioniPath;
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($segnalazioni->asXML());
    
    // salviamo file XML
    if ($dom->save($segnalazioniPath)) {
        echo "File XML salvato con successo.<br>";
    } else {
        echo "Errore nel salvataggio del file XML.<br>";
    }
}

// gestione dell'annullamento della segnalazione
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['annulla'])) {
    $usernameSegnalato = $_POST['username_segnalato'];
    $usernameSegnalante = $_POST['username_segnalante'];
    $motivo = $_POST['motivo'];

    $segnalazioneTrovata = false;
    foreach ($segnalazioni->segnalazione as $idx => $el) {
        if ((string)$el->username_segnalato === $usernameSegnalato &&
            (string)$el->username_segnalante === $usernameSegnalante &&
            (string)$el->motivo === $motivo) {
            // rimozione
            $dom = dom_import_simplexml($el);
            $dom->parentNode->removeChild($dom);
            $segnalazioneTrovata = true;
            break;
        }
    }

    if ($segnalazioneTrovata) {
        salvaSegnalazioni($segnalazioni);
        echo "Segnalazione annullata con successo.";
    } else {
        echo "Nessuna segnalazione trovata per l'utente $usernameSegnalato.";
    }
}

// gestione del bando dell'utente
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['conferma_ban'])) {
    $usernameSegnalato = $_POST['username_segnalato'];

    // query per bannare l'utente
    $connessione = new mysqli($hostname, $user, $password, $db);
    if ($connessione->connect_error) {
        die("Connessione fallita: " . $connessione->connect_error);
    }

    $sql = "UPDATE utenti SET ban = TRUE WHERE username = ?";
    $stmt = $connessione->prepare($sql);
    $stmt->bind_param("s", $usernameSegnalato);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        
        // rimozione segnalazione dal file XML
        $segnalazioneTrovata = false; // per verificare se la segnalazione è stata trovata
        foreach ($segnalazioni->segnalazione as $segnalazione) {
            if ((string)$segnalazione->username_segnalato === $usernameSegnalato) {
                unset($segnalazioni->segnalazione);
                $segnalazioneTrovata = true; // se la segnalazione è stata rimossa
                break; 
            }
        }

        // salviamo le segnalazioni aggiornate solo se è stata trovata e rimossa una segnalazione
        if ($segnalazioneTrovata) {
            salvaSegnalazioni($segnalazioni);
        } else { //debug
            echo "<br/><br/><br/><br/><br/><br/>Nessuna segnalazione trovata per l'utente $usernameSegnalato.";
        }
    }

    $stmt->close();
    $connessione->close();
}

// gestione della penalità nella reputazione
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['diminuisci_reputazione_valore'])) {
    $usernameSegnalato = $_POST['username_segnalato'];
    $valore_penalita = floatval($_POST['valore_penalita']);
    if ($valore_penalita < 1) $valore_penalita = 1;
    if ($valore_penalita > 5) $valore_penalita = 5;

    $penalitaPath = '../xml/penalita_reputazione.xml';
    if (file_exists($penalitaPath)) {
        $penalitaXml = simplexml_load_file($penalitaPath);
    } else {
        $penalitaXml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><penalitaReputazione></penalitaReputazione>');
    }
    $penalita = $penalitaXml->addChild('penalita');
    $penalita->addChild('username', htmlspecialchars($usernameSegnalato));
    $penalita->addChild('valore', $valore_penalita);
    // salvataggio file XML formattato
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($penalitaXml->asXML());
    $dom->save($penalitaPath);
    echo "Penalità di reputazione assegnata con successo.";
}

// x mostrare discussione relativa a una segnalazione
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['vedi_discussione'])) {
    $usernameSegnalato = $_POST['username_segnalato'];
    $tipoSegnalazione = isset($_POST['tipo']) ? $_POST['tipo'] : '';
    $contenutoSegnalato = isset($_POST['contenuto']) ? $_POST['contenuto'] : '';
    $discussioniTrovate = [];
    if ($tipoSegnalazione === 'domanda') {
        // cerchiamo la domanda con quel contenuto
        foreach ($domande->domanda as $domanda) {
            if (trim((string)$domanda->contenuto) === trim($contenutoSegnalato)) {
                $discussioniTrovate[] = $domanda;
                break;
            }
        }
    } elseif ($tipoSegnalazione === 'risposta') {
        // cerchamo la risposta con quel contenuto e mostriamo la domanda a cui appartiene
        foreach ($domande->domanda as $domanda) {
            foreach ($domanda->risposta as $risposta) {
                if (trim((string)$risposta->contenuto) === trim($contenutoSegnalato)) {
                    $discussioniTrovate[] = $domanda;
                    break 2;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Forum</title>
    <link rel="stylesheet" href="../css/giochi.css">
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
            margin-right: 50px;
            width: 50%;
            max-width: 400px;
            transition: transform 0.2s;
        }
        .utente-card:hover {
            transform: scale(1.01);
            background: #ffffff; 
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .stato-bannato { color: #dc3545; }
        .stato-attivo { color: #28a745; font-weight: bold; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-primary { background: #007bff; color: white; }
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
            display: inline-flex; /* Cambiato da inline a inline-flex per centrare */
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
        .risposte-nascoste {
            display: none;
        }
        .toggle-button {
            cursor: pointer;
            color: blue;
            text-decoration: underline;
        }
        .form-container {
            background-color: #f8f9fa; 
            border-radius: 8px; 
            padding: 20px; 
            margin: 20px 0; 
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
        }

        select {
            width: 100%; 
            padding: 10px; 
            border-radius: 5px; 
            border: 1px solid #ccc; 
            font-size: 16px; 
            margin-bottom: 10px; 
        }

        button {
            padding: 10px 20px;
            border: none; 
            border-radius: 5px;
            background-color: #007bff; 
            color: white; 
            font-size: 16px; 
            cursor: pointer; 
            transition: background-color 0.3s; 
        }

        button:hover {
            background-color: #0056b3; 
        }

        .risposte-container {
            background-color: #e7f3ff; 
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px; 
        }

        .risposta-item {
            background-color: #d1ecf1; 
            padding: 10px;
            border-radius: 4px; 
            margin-top: 5px; 
        }

      
        .btn {
            padding: 10px 20px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 16px; 
            margin: 5px; 
        }
        .btn-danger { background: #dc3545; color: white; } 
        .btn-primary { background: #007bff; color: white; } 
        .btn-success { background: #28a745; color: white; } 

        
        .utente-card {
            background-color: #f8f9fa; 
            border: 1px solid #ccc; 
            border-radius: 8px; 
            padding: 20px; 
            margin: 20px auto; 
            width: 80%; 
            max-width: 600px; 
            text-align: left; 
        }
    </style>
    <script>
        function confermaBan(username) {
            if (confirm("Sei sicuro di voler bannare l'utente " + username + " dall'applicazione?")) {
                document.getElementById('conferma-bannare-' + username).click(); // Clicca sul pulsante di conferma
            }
        }
    </script>
</head>
<body>

    <div class="navbar">
        <ul style=" text-align: center; margin-left: 20%;">
            <li><a href="gestore_dashboard.php">Dashboard</a></li>
            <li><a href="gestione_catalogo.php">Modifica giochi</a></li>
            <li><a href="gestione_sconti_admin.php">Modifica Sconti e Bonus</a></li>
            <li><a href="visualizza_utenti.php">Visualizza Utenti</a></li>
        </ul>
    </div>

    <!-- visualizzazione discussioni -->
    <div class="container" style="margin-top: 100px;">
        <div class="form-container">
            <form method="POST" action="">
                <label for="gioco">Seleziona un gioco:</label>
                <select name="gioco" id="gioco" required>
                    <option value="">-- Scegli un gioco --</option>
                    <?php foreach ($giochi->gioco as $gioco): ?>
                        <option value="<?php echo htmlspecialchars($gioco->codice); ?>">
                            <?php echo htmlspecialchars($gioco->titolo); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Visualizza Discussioni</button>
            </form>
        </div>

        <?php if (!empty($domandeFiltrate)): ?>
            <h2 style="margin-top: 30px;">Discussioni per il gioco selezionato:</h2>
            <div class="utente-grid" style="margin-top: 50px;">
                <?php foreach ($domandeFiltrate as $key => $domanda): ?>
                    <div class="utente-card">
                        <h3 style="color: red; font-size: 140%;"><?php echo htmlspecialchars($domanda->titolo); ?></h3>
                        <div class="form-group" style="margin-top: 20px;">
                            <label>Autore:</label>
                            <p style="color: red; font-size: 120%;"><?php echo htmlspecialchars($domanda->autore); ?></p>
                        </div>
                        <div class="form-group">
                            <label>Data:</label>
                            <p><?php echo htmlspecialchars($domanda->data); ?></p>
                        </div>
                        <div class="form-group">
                            <label>Contenuto:</label>
                            <p><?php echo htmlspecialchars($domanda->contenuto); ?></p>
                        </div>

                        <!-- visualizzazione delle risposte -->
                        <div class="risposte-container">
                            <h4>Risposte:</h4>
                            <?php 
                            $risposte = $domanda->risposta;
                            $numRisposte = count($risposte);
                            // mostriamo le prime due risposte
                            for ($i = 0; $i < $numRisposte && $i < 2; $i++): ?>
                                <div class="risposta-item">
                                    <div class="form-group">
                                        <label>Contenuto:</label>
                                        <p><?php echo htmlspecialchars($risposte[$i]->contenuto); ?></p>
                                    </div>
                                    <div class="form-group">
                                        <label>Autore:</label>
                                        <p><?php echo htmlspecialchars($risposte[$i]->autore); ?></p>
                                    </div>
                                    <div class="form-group">
                                        <label>Data:</label>
                                        <p><?php echo htmlspecialchars($risposte[$i]->data); ?></p>
                                    </div>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="domanda_id" value="<?php echo htmlspecialchars($domanda->codice); ?>">
                                        <input type="hidden" name="domanda_contenuto" value="<?php echo htmlspecialchars($domanda->contenuto); ?>">
                                        <input type="hidden" name="risposta_contenuto" value="<?php echo htmlspecialchars($risposte[$i]->contenuto); ?>">
                                        <button type="submit" name="eleva_faq" class="btn btn-primary">Eleva a FAQ</button>
                                    </form>
                                </div>
                            <?php endfor; ?>
                            <?php if ($numRisposte > 2): ?>
                                <div class="risposte-nascoste" id="risposte-<?php echo $key; ?>" style="display: none;">
                                    <?php for ($i = 2; $i < $numRisposte; $i++): ?>
                                        <div class="risposta-item">
                                            <div class="form-group">
                                                <label>Contenuto:</label>
                                                <p><?php echo htmlspecialchars($risposte[$i]->contenuto); ?></p>
                                            </div>
                                            <div class="form-group">
                                                <label>Autore:</label>
                                                <p><?php echo htmlspecialchars($risposte[$i]->autore); ?></p>
                                            </div>
                                            <div class="form-group">
                                                <label>Data:</label>
                                                <p><?php echo htmlspecialchars($risposte[$i]->data); ?></p>
                                            </div>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="domanda_id" value="<?php echo htmlspecialchars($domanda->codice); ?>">
                                                <input type="hidden" name="domanda_contenuto" value="<?php echo htmlspecialchars($domanda->contenuto); ?>">
                                                <input type="hidden" name="risposta_contenuto" value="<?php echo htmlspecialchars($risposte[$i]->contenuto); ?>">
                                                <button type="submit" name="eleva_faq" class="btn btn-primary">Eleva a FAQ</button>
                                            </form>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                                <span class="toggle-button" onclick="toggleRisposte('<?php echo $key; ?>', this)">Mostra altre risposte</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif (isset($_POST['gioco'])): ?>
            <p>Nessuna discussione trovata per il gioco selezionato.</p>
        <?php endif; ?>
    </div>

    <!--visualizzazione segnalazioni-->
    <div class="container">
        <div class="title-container">
            <h1 style="font-size: 200%;">Segnalazioni Utenti</h1>
            <h3>Visualizzazione delle segnalazioni effettuate</h3>
        </div>
        <!---->
        <div class="utente-grid">
            <?php if (empty($segnalazioni->segnalazione)): ?>
                <p style="font-size: 160%; color: red;">Non ci sono segnalazioni pervenute dagli utenti.</p>
            <?php else: ?>
                <?php foreach ($segnalazioni->segnalazione as $segnalazione): ?>
                    <div class="utente-card">
                        <p>Utente Segnalato: <strong style="color: red;"><?php echo htmlspecialchars($segnalazione->username_segnalato); ?></strong></p><br/>
                        <p>Utente segnalato da: <strong style="color: blue;"><?php echo htmlspecialchars($segnalazione->username_segnalante);?></strong></p><br/>
                        <p>Motivazione:</p>
                        <p><?php echo htmlspecialchars($segnalazione->motivo); ?></p><br/>
                        <?php if (isset($segnalazione->contenuto) && trim($segnalazione->contenuto) !== ''): ?>
                            <p><strong>Contenuto segnalato:</strong></p>
                            <p style="color: #007bff;"><?php echo htmlspecialchars($segnalazione->contenuto); ?></p><br/>
                        <?php endif; ?>
                        <form id="ban-form-<?php echo htmlspecialchars($segnalazione->username_segnalato); ?>" method="POST" style="display: flex; justify-content: space-between;">
                            <input type="hidden" name="username_segnalato" value="<?php echo htmlspecialchars($segnalazione->username_segnalato); ?>">
                            <input type="hidden" name="username_segnalante" value="<?php echo htmlspecialchars($segnalazione->username_segnalante); ?>">
                            <input type="hidden" name="motivo" value="<?php echo htmlspecialchars($segnalazione->motivo); ?>">
                            <input type="hidden" name="tipo" value="<?php echo isset($segnalazione->tipo) ? htmlspecialchars($segnalazione->tipo) : ''; ?>">
                            <input type="hidden" name="contenuto" value="<?php echo isset($segnalazione->contenuto) ? htmlspecialchars($segnalazione->contenuto) : ''; ?>">
                            <button type="button" onclick="confermaBan('<?php echo htmlspecialchars($segnalazione->username_segnalato); ?>')" class="btn btn-danger">Banna Utente</button>
                            <button type="button" class="btn btn-primary" onclick="apriPenalitaModal('<?php echo htmlspecialchars($segnalazione->username_segnalato); ?>')">Diminuisci Reputazione</button>
                            <button type="submit" name="annulla" class="btn btn-success">Annulla</button>
                            <button type="submit" name="conferma_ban" style="display: none;" id="conferma-bannare-<?php echo htmlspecialchars($segnalazione->username_segnalato); ?>">Conferma</button>
                            <button type="submit" name="vedi_discussione" class="btn btn-secondary">Vedi discussione</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- finestra per la penalità -->
    <div id="penalitaModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; z-index:9999;">
        <div style="background:#fff; padding:30px; border-radius:10px; min-width:300px; text-align:center;">
            <h3>Inserisci il valore della penalità</h3>
            <form method="POST" id="penalitaForm">
                <input type="hidden" name="username_segnalato" id="penalitaUsername">
                <input type="number" name="valore_penalita" id="valorePenalita" min="1" max="5" step="0.1" required style="width:80px; font-size:1.2em;">
                <p style="font-size:0.9em; color:#555;">Inserisci un valore tra 1 e 5</p>
                <button type="submit" name="diminuisci_reputazione_valore" class="btn btn-primary">Conferma Penalità</button>
                <button type="button" class="btn btn-success" onclick="chiudiPenalitaModal()">Annulla</button>
            </form>
        </div>
    </div>

    <script>
        function toggleRisposte(codiceDomanda, button) {
            const risposteDiv = document.getElementById('risposte-' + codiceDomanda);
            if (risposteDiv.style.display === 'none' || risposteDiv.style.display === '') {
                risposteDiv.style.display = 'block';
                button.textContent = 'Mostra meno'; // cambiamo il testo del pulsante
            } else {
                risposteDiv.style.display = 'none';
                button.textContent = 'Mostra altre risposte'; // ripristino testo pulsante
            }
        }

        function apriPenalitaModal(username) {
            document.getElementById('penalitaUsername').value = username;
            document.getElementById('valorePenalita').value = '';
            document.getElementById('penalitaModal').style.display = 'flex';
        }
        function chiudiPenalitaModal() {
            document.getElementById('penalitaModal').style.display = 'none';
        }
        // cchiudi la modale se si clicca fuori
        window.onclick = function(event) {
            var modal = document.getElementById('penalitaModal');
            if (event.target == modal) {
                chiudiPenalitaModal();
            }
        }
    </script>

    <?php if (isset($discussioniTrovate) && !empty($discussioniTrovate)): ?>
        <div class="container" style="margin-top: 30px;">
            <h2>Discussioni di <?php echo htmlspecialchars($usernameSegnalato); ?></h2>
            <?php foreach ($discussioniTrovate as $domanda): ?>
                <div class="utente-card">
                    <h3><?php echo htmlspecialchars($domanda->titolo); ?></h3>
                    <p><strong>Autore:</strong> <?php echo htmlspecialchars($domanda->autore); ?></p>
                    <p><strong>Data:</strong> <?php echo htmlspecialchars($domanda->data); ?></p>
                    <p><strong>Contenuto:</strong> <?php echo htmlspecialchars($domanda->contenuto); ?></p>
                    <h4>Risposte:</h4>
                    <?php foreach ($domanda->risposta as $risposta): ?>
                        <div class="risposta-item" style="margin-bottom:10px;">
                            <p><strong>Autore:</strong> <?php echo htmlspecialchars($risposta->autore); ?></p>
                            <p><strong>Data:</strong> <?php echo htmlspecialchars($risposta->data); ?></p>
                            <p><strong>Contenuto:</strong> <?php echo htmlspecialchars($risposta->contenuto); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</body>
</html>