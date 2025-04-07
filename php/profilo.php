<?php
session_start();
require_once('connessione.php');
require_once('calcola_reputazione.php');

// verifica se l'utente è loggato e se è un cliente
if (!isset($_SESSION['statoLogin'])) {
    header("Location: login.php");
    exit();

}elseif($_SESSION['tipo_utente'] !== 'cliente'){
    header("Location: home.php");
    exit();
}


// verifica se l'utente è loggato
if (!isset($_SESSION['statoLogin']) || !isset($_SESSION['username'])) {
    // salviamo la pagina corrente per il reindirizzamento post-login
    $_SESSION['redirect_after_login'] = 'profilo.php';
    header('Location: login.php');
    exit();
}

require_once('connessione.php');
require_once('calcola_reputazione.php');

$xml_file = '../xml/richieste_crediti.xml';

// calcolo della reputazione dell'utente
$reputazione = getReputazioneUtente($_SESSION['username']);

// gestione della richiesta crediti
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['richiedi_crediti'])) {
    $importo = $_POST['importo'];
    $motivazione = $_POST['motivazione'] ?? '';

    // salviamo la richiesta nel file XML contenente le richieste
    $xml_file = '../xml/richieste_crediti.xml';
    if (file_exists($xml_file)) {
        $xml = simplexml_load_file($xml_file);
    } else {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><richiesteCrediti></richiesteCrediti>');
    }

    $richiesta = $xml->addChild('richiesta');
    $richiesta->addChild('username', $_SESSION['username']);
    $richiesta->addChild('crediti', $importo);
    $richiesta->addChild('data', date('Y-m-d'));
    $richiesta->addChild('status', 'in attesa');
    $richiesta->addChild('note', $motivazione);

    // formattazione con DOMDocument
    $dom = new DOMDocument('1.0');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($xml->asXML());

    // salvataggio del file
    if ($dom->save($xml_file)) {
        $messaggio_successo = "Richiesta di crediti inviata con successo!";
    } else {
        $errore = "Si è verificato un errore durante l'invio della richiesta.";
    }
}

// recupera informazioni dell'utente
$query = "SELECT crediti FROM utenti WHERE username = ?";
$stmt = $connessione->prepare($query);
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$crediti_attuali = $stmt->get_result()->fetch_assoc()['crediti'];

// recupera le richieste crediti dal file XML
$richieste_array = [];
if (file_exists($xml_file)) {
    $xml = simplexml_load_file($xml_file);
    foreach ($xml->richiesta as $richiesta) {
        if ((string)$richiesta->username === $_SESSION['username']) {
            $richieste_array[] = [
                'data_richiesta' => (string)$richiesta->data,
                'importo' => (float)$richiesta->crediti,
                'stato' => (string)$richiesta->status,
                'motivazione' => isset($richiesta->note) ? (string)$richiesta->note : ''
            ];
        }
    }
}

// funzione per ottenere l'avatar attuale dell'utente dal file XML
function getAvatarUtente($username) {
    $xml_file = '../xml/avatar.xml';
    if (file_exists($xml_file)) {
        $xml = simplexml_load_file($xml_file);
        foreach ($xml->utente as $utente) {
            if ((string)$utente->username === $username) {
                return isset($utente->avatar) ? (string)$utente->avatar : null;
            }
        }
    }
    return null;
}

// funzione per aggiornare l'avatar nel file XML
function updateAvatarXML($username, $nuovo_avatar) {
    $xml_file = '../xml/avatar.xml';
    $xml = simplexml_load_file($xml_file);
    $aggiornato = false;

    foreach ($xml->utente as $utente) {
        if ((string)$utente->username === $username) {
            if (!isset($utente->avatar)) {
                $utente->addChild('avatar', $nuovo_avatar);
            } else {
                $utente->avatar = $nuovo_avatar;
            }
            $aggiornato = true;
            break;
        }
    }

    if ($aggiornato) {
        return $xml->asXML($xml_file);
    }
    return false;
}

// gestione del cambio avatar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cambia_avatar'])) {
    $nuovo_avatar = $_POST['nuovo_avatar'];
    if (updateAvatarXML($_SESSION['username'], $nuovo_avatar)) {
        $messaggio_successo = "Avatar aggiornato con successo!";
    } else {
        $errore = "Errore nell'aggiornamento dell'avatar.";
    }
}

// recupera l'avatar attuale
$avatar_attuale = getAvatarUtente($_SESSION['username']);

// gestione della richiesta per diventare gestore
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['richesta_admin'])) {
    $username = $_SESSION['username'];
    $data_richiesta = date('Y-m-d H:i:s');

    // caricamento file XML delle richieste gestore
    $xml_file_gestore = '../xml/richieste_gestore.xml';
    if (file_exists($xml_file_gestore)) {
        $xml_gestore = simplexml_load_file($xml_file_gestore);
    } else {
        $xml_gestore = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><richiesteGestore></richiesteGestore>');
    }

    // aggiungi la richiesta
    $richiesta = $xml_gestore->addChild('richiesta');
    $richiesta->addChild('username', $username);
    $richiesta->addChild('data', $data_richiesta);

    // salva il file XML
    $dom = new DOMDocument('1.0');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($xml_gestore->asXML());

    if ($dom->save($xml_file_gestore)) {
        $messaggio_successo = "Richiesta per diventare gestore inviata con successo!";
    } else {
        $errore = "Si è verificato un errore durante l'invio della richiesta.";
    }
}

// visualizza storico acquisti
if (isset($_POST['storico_acquisti'])) {
    header('Location: storico_acquisti.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilo Utente - GameShop</title>
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="../css/menu.css">
    <style>
        .container {
            margin-top: 100px; 
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
            padding: 20px;
        }
        .profilo-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .sezione {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .crediti-info {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.2em;
            margin-bottom: 20px;
        }
        .crediti-amount {
            font-weight: bold;
            color: #28a745;
        }
        .form-richiesta {
            margin-top: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .btn-richiedi {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .richieste-lista {
            margin-top: 20px;
        }
        .richiesta-item {
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        .stato-in-attesa {
            color: #ffc107;
        }
        .stato-approvata {
            color: #28a745;
        }
        .stato-rifiutata {
            color: #dc3545;
        }
        .avatar-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .avatar-option {
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .avatar-option input[type="radio"] {
            display: none;
        }
        .avatar-option img {
            border: 3px solid transparent;
            transition: border-color 0.3s;
        }
        .avatar-option input[type="radio"]:checked + img {
            border-color: #007bff;
        }
        .btn-avatar {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 15px;
        }
        .btn-avatar:hover {
            background: #0056b3;
        }
        .btn-mostra-altro {
            background: #6c757d;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
            width: 100%;
        }
        .btn-mostra-altro:hover {
            background: #5a6268;
        }
        .pacchetti-info {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn-pacchetti {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 15px 30px;
            border-radius: 5px;
            border: none;
            text-decoration: none;
            margin: 25px 0;
            font-weight: bold;
            cursor: pointer;
            font-size: 18px;
            transition: all 0.3s ease;
        }
        .btn-pacchetti:hover {
            background: #0056b3;
        }
        .input-con-preview {
            margin-bottom: 20px;
        }

        #importo {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .prezzo-info {
            padding: 10px;
            background: #e9ecef;
            border-radius: 4px;
            margin-top: 5px;
            color: #495057;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .reputazione-info {
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .reputazione-item {
            margin-bottom: 25px;
        }

        .reputazione-item h3 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 1.2em;
        }

        .reputazione-valore {
            display: flex;
            align-items: baseline;
            margin-bottom: 10px;
        }

        .punteggio {
            font-size: 2em;
            font-weight: bold;
            color: #3498db;
        }

        .max {
            color: #7f8c8d;
            margin-left: 5px;
        }

        .progress-bar {
            width: 100%;
            height: 10px;
            background: #ecf0f1;
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        .progress {
            height: 100%;
            background: #3498db;
            border-radius: 5px;
            transition: width 0.3s ease;
        }

        .descrizione {
            color: #7f8c8d;
            font-size: 0.9em;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <?php include('menu.php'); ?>

    <div class="container">
        <div class="profilo-header">
            <div class="avatar-nome">
                <?php if ($avatar_attuale): ?>
                    <img src="../isset/avatar/<?php echo htmlspecialchars($avatar_attuale); ?>" 
                         alt="Avatar" 
                         class="header-avatar"
                         onerror="this.src='../isset/avatar/default_avatar.jpg';">
                <?php else: ?>
                    <img src="../isset/avatar/default_avatar.jpg" 
                         alt="Avatar default" 
                         class="header-avatar">
                <?php endif; ?>
                <h1>Profilo di <strong style="color: #007bff;"><?php echo htmlspecialchars($_SESSION['username']); ?></strong></h1>
            </div>
            <a href="modifica_profilo.php" class="btn-modifica">Modifica Profilo</a>
        </div>
        
        <style>
        .profilo-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .avatar-nome {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .header-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }
        .btn-modifica {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
        }
        .btn-modifica:hover {
            background: #0056b3;
        }
        </style>
        
        <?php if (isset($messaggio_successo)): ?>
            <div class="alert alert-success"><?php echo $messaggio_successo; ?></div>
        <?php endif; ?>
        
        <?php if (isset($errore)): ?>
            <div class="alert alert-danger"><?php echo $errore; ?></div>
        <?php endif; ?>

        <div class="profilo-grid">
            <div class="sezione">
                <h2>I tuoi Crediti</h2>
                <div class="crediti-info">
                    <span>Saldo attuale:</span>
                    <span class="crediti-amount">€<?php echo number_format($crediti_attuali, 2); ?></span>
                </div>

                <div class="pacchetti-info">
                    <h3>Acquista Crediti</h3>
                    <p>Scopri i nostri pacchetti di crediti a prezzi vantaggiosi!</p>
                    <button onclick="window.location.href='richiesta_crediti.php'" class="btn-pacchetti">
                        Acquista Pacchetti Crediti
                    </button>
                </div>

                <div class="richiesta-personalizzata">
                    <h3>Richiedi Crediti Personalizzati</h3>
                    <form method="POST" class="form-richiesta" onsubmit="return validateForm()">
                        <div class="form-group">
                            <label for="importo">Numero di crediti da richiedere:</label>
                            <div class="input-con-preview">
                                <input type="number" 
                                       id="importo" 
                                       name="importo" 
                                       min="1" 
                                       step="1" 
                                       required 
                                       oninput="calcolaPrezzo(this.value)">
                                <div id="prezzo-calcolato" class="prezzo-info"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="motivazione">Motivazione (opzionale):</label>
                            <textarea id="motivazione" name="motivazione" rows="3"></textarea>
                        </div>
                        <button type="submit" name="richiedi_crediti" class="btn-richiedi">
                            Invia Richiesta
                        </button>
                    </form>
                </div>
            </div>

            <div class="sezione">
                <h2>La tua Reputazione</h2>
                <div class="reputazione-info">
                    <div class="reputazione-item">
                        <h3>Reputazione Base</h3>
                        <div class="reputazione-valore">
                            <span class="punteggio"><?php echo $reputazione['base']; ?></span>
                            <span class="max">/10</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress" style="width: <?php echo ($reputazione['base'] * 10); ?>%"></div>
                        </div>
                        <p class="descrizione">Calcolata in base ai giudizi ricevuti</p>
                    </div>

                    <div class="reputazione-item">
                        <h3>Reputazione Pesata</h3>
                        <div class="reputazione-valore">
                            <span class="punteggio"><?php echo $reputazione['pesata']; ?></span>
                            <span class="max">/10</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress" style="width: <?php echo ($reputazione['pesata'] * 10); ?>%"></div>
                        </div>
                        <p class="descrizione">Tiene conto del peso dei giudizi in base a chi li ha dati</p>
                    </div>

                    <!-- Pulsante per richiesta diventare admin con minimo 9 di reputazione base e pesata-->
                    <div class="reputazione-item">
                        <?php if($reputazione['base'] >= 6 && $reputazione['pesata'] >= 6){ //modificare i valori e mettere a 9?>
                            <h3>Richiesta per diventare un Gestore</h3>
                            <form method="post" action="profilo.php">
                                <button type="submit" name="richesta_admin" class="btn-richiedi">Invia richiesta</button>
                            </form>
                        <?php } else{ ?>
                            <h3>Richiesta per diventare un Gestore</h3>
                            <p>Non puoi richiedere di diventare un Gestore perchè il punteggio di reputazione base e pesata deve essere minimo 9</p>
                        <?php } ?>
                    </div>
                </div>
                <h2><br>Storico Acquisti</h2>
                <form method="POST" action="profilo.php">
                    <button type="submit" name="storico_acquisti" class="btn-richiedi">
                        Visualizza
                    </button>
                </form>
            </div>

            <div class="sezione">
                <h2>Storico Richieste</h2>
                <div class="richieste-lista">
                    <?php if (empty($richieste_array)): ?>
                        <p>Non hai ancora effettuato richieste di crediti.</p>
                    <?php else: ?>
                        <?php 
                        $richieste_visibili = array_slice($richieste_array, 0, 3); // mostra solo le prime 3
                        $richieste_nascoste = array_slice($richieste_array, 3);    // il resto delle richieste
                        ?>
                        
                        <?php foreach ($richieste_visibili as $richiesta): ?>
                            <div class="richiesta-item">
                                <p>
                                    <strong>Data:</strong> 
                                    <?php echo date('d/m/Y H:i', strtotime($richiesta['data_richiesta'])); ?>
                                </p>
                                <p>
                                    <strong>Importo:</strong> 
                                    €<?php echo number_format($richiesta['importo'], 2); ?>
                                </p>
                                <p>
                                    <strong>Stato:</strong> 
                                    <span class="stato-<?php echo $richiesta['stato']; ?>">
                                        <?php echo ucfirst($richiesta['stato']); ?>
                                    </span>
                                </p>
                                <?php if ($richiesta['motivazione']): ?>
                                    <p>
                                        <strong>Motivazione:</strong> 
                                        <?php echo htmlspecialchars($richiesta['motivazione']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <?php if (!empty($richieste_nascoste)): ?>
                            <div id="richieste-nascoste" style="display: none;">
                                <?php foreach ($richieste_nascoste as $richiesta): ?>
                                    <div class="richiesta-item">
                                        <p>
                                            <strong>Data:</strong> 
                                            <?php echo date('d/m/Y H:i', strtotime($richiesta['data_richiesta'])); ?>
                                        </p>
                                        <p>
                                            <strong>Importo:</strong> 
                                            €<?php echo number_format($richiesta['importo'], 2); ?>
                                        </p>
                                        <p>
                                            <strong>Stato:</strong> 
                                            <span class="stato-<?php echo $richiesta['stato']; ?>">
                                                <?php echo ucfirst($richiesta['stato']); ?>
                                            </span>
                                        </p>
                                        <?php if ($richiesta['motivazione']): ?>
                                            <p>
                                                <strong>Motivazione:</strong> 
                                                <?php echo htmlspecialchars($richiesta['motivazione']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button id="btnMostraAltro" class="btn-mostra-altro">
                                Mostra altre richieste (<?php echo count($richieste_nascoste); ?>)
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        const hamburgerMenu = document.querySelector('.hamburger-menu');
        const navLinks = document.querySelector('.nav-links');

        hamburgerMenu.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });

        document.getElementById('btnMostraAltro')?.addEventListener('click', function() {
            const richiesteNascoste = document.getElementById('richieste-nascoste');
            const btn = this;
            
            if (richiesteNascoste.style.display === 'none') {
                richiesteNascoste.style.display = 'block';
                btn.textContent = 'Mostra meno';
            } else {
                richiesteNascoste.style.display = 'none';
                btn.textContent = 'Mostra altre richieste';
            }
        });

        // funzione per validare la form
        function validateForm() {
            const importo = document.getElementById('importo').value;
            if (!Number.isInteger(Number(importo))) {
                alert('Inserisci un numero intero di crediti');
                return false;
            }
            return true;
        }

        // funzione per calcolare e mostrare il prezzo
        function calcolaPrezzo(valore) {
            const crediti = parseInt(valore) || 0;
            const prezzo = (crediti * 0.30).toFixed(2);
            const prezzoInfo = document.getElementById('prezzo-calcolato');
            
            if (crediti > 0) {
                prezzoInfo.innerHTML = `
                    <div class="prezzo-info">
                        <strong>Riepilogo:</strong><br>
                        ${crediti} crediti = €${prezzo}<br>
                        <small style="color: #6c757d">Prezzo unitario: €0.30 per credito<br>
                        Scopri prezzi più vantaggiosi nei nostri pacchetti!</small>
                    </div>`;
            } else {
                prezzoInfo.innerHTML = '';
            }
        }

        // aggiungiamo l'evento Listener quando il documento è pronto
        document.addEventListener('DOMContentLoaded', function() {
            const inputCrediti = document.getElementById('importo');
            if (inputCrediti) {
                inputCrediti.addEventListener('input', function() {
                    calcolaPrezzo(this.value);
                });
            }
        });
    </script>
</body>
</html>
