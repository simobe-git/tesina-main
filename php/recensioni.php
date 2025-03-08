<?php
session_start();
require_once('connessione.php');

// verifica se l'utente è loggato e se è un cliente
if (!isset($_SESSION['statoLogin'])) {
    header("Location: login.php");
    exit();

}elseif($_SESSION['tipo_utente'] !== 'cliente'){
    header("Location: home.php");
    exit();
}


$xml_recensioni = 'xml/recensioni.xml';

function caricaXML($file) {
    if (file_exists($file)) {
        return simplexml_load_file($file);
    }
    return false;
}

function salvaXML($xml, $file) {
    $xml->asXML($file);
}

// gestione invio recensione
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['invia_recensione'])) {
    if (!isset($_SESSION['username'])) {
        $errore = "Devi effettuare il login per scrivere una recensione";
    } else {
        $xml = caricaXML($xml_recensioni);
        if (!$xml) {
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><recensioni></recensioni>');
        }

        $recensione = $xml->addChild('recensione');
        $recensione->addAttribute('id', time());
        $recensione->addChild('username', $_SESSION['username']);
        $recensione->addChild('codice_gioco', $_POST['codice_gioco']);
        $recensione->addChild('testo', $_POST['testo']);
        $recensione->addChild('data', date('Y-m-d'));
        $recensione->addChild('giudizi');

        salvaXML($xml, $xml_recensioni);
        $messaggio = "Recensione pubblicata con successo!";
    }
}

// gestione giudizi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['invia_giudizio'])) {
    if (!isset($_SESSION['username'])) {
        $errore = "Devi effettuare il login per dare un giudizio";
    } else {
        $xml = caricaXML($xml_recensioni);
        foreach ($xml->recensione as $recensione) {
            if ((string)$recensione['id'] === $_POST['id_recensione']) {
                $giudizio = $recensione->giudizi->addChild('giudizio');
                $giudizio->addChild('username_votante', $_SESSION['username']);
                $giudizio->addChild('supporto', $_POST['supporto']);
                $giudizio->addChild('utilita', $_POST['utilita']);
                break;
            }
        }
        salvaXML($xml, $xml_recensioni);
        $messaggio = "Giudizio registrato con successo!";
    }
}

// recuperiamo il gioco specifico
$codice_gioco = isset($_GET['codice']) ? $_GET['codice'] : null;
if ($codice_gioco) {
    $query = "SELECT * FROM videogiochi WHERE codice = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $codice_gioco);
    $stmt->execute();
    $gioco = $stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Recensioni</title>
    <style>
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .recensione { background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 8px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group textarea { width: 100%; min-height: 100px; }
        .giudizi { margin-top: 10px; padding-top: 10px; border-top: 1px solid #ddd; }
        .btn { padding: 8px 15px; background: #007bff; color: white; border: none; cursor: pointer; }
        .messaggio { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .successo { background: #d4edda; color: #155724; }
        .errore { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($gioco): ?>
            <h1>Recensioni per <?php echo htmlspecialchars($gioco['nome']); ?></h1>
            
            <?php if (isset($messaggio)): ?>
                <div class="messaggio successo"><?php echo $messaggio; ?></div>
            <?php endif; ?>
            
            <?php if (isset($errore)): ?>
                <div class="messaggio errore"><?php echo $errore; ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['username'])): ?>
                <div class="scrivi-recensione">
                    <h2>Scrivi una recensione</h2>
                    <form method="POST">
                        <input type="hidden" name="codice_gioco" value="<?php echo $codice_gioco; ?>">
                        <div class="form-group">
                            <label>La tua recensione:</label>
                            <textarea name="testo" required></textarea>
                        </div>
                        <button type="submit" name="invia_recensione" class="btn">Pubblica recensione</button>
                    </form>
                </div>
            <?php endif; ?>

            <div class="recensioni-lista">
                <h2>Tutte le recensioni</h2>
                <?php
                $xml = caricaXML($xml_recensioni);
                if ($xml): foreach ($xml->recensione as $recensione):
                    if ((string)$recensione->codice_gioco === $codice_gioco):
                ?>
                    <div class="recensione">
                        <div class="recensione-testo">
                            <?php echo htmlspecialchars($recensione->testo); ?>
                        </div>
                        <div class="recensione-info">
                            Scritta da: <?php echo htmlspecialchars($recensione->username); ?>
                            il <?php echo date('d/m/Y', strtotime($recensione->data)); ?>
                        </div>
                        
                        <?php if (isset($_SESSION['username']) && 
                                $_SESSION['username'] !== (string)$recensione->username): ?>
                            <div class="giudizio-form">
                                <h3>Dai un giudizio</h3>
                                <form method="POST">
                                    <input type="hidden" name="id_recensione" 
                                           value="<?php echo $recensione['id']; ?>">
                                    <div class="form-group">
                                        <label>Supporto (1-3):</label>
                                        <input type="number" name="supporto" min="1" max="3" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Utilità (1-5):</label>
                                        <input type="number" name="utilita" min="1" max="5" required>
                                    </div>
                                    <button type="submit" name="invia_giudizio" class="btn">
                                        Invia giudizio
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>

                        <div class="giudizi">
                            <h3>Giudizi ricevuti</h3>
                            <?php foreach ($recensione->giudizi->giudizio as $giudizio): ?>
                                <div class="giudizio">
                                    Utente: <?php echo htmlspecialchars($giudizio->username_votante); ?><br>
                                    Supporto: <?php echo $giudizio->supporto; ?>/3<br>
                                    Utilità: <?php echo $giudizio->utilita; ?>/5
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php 
                    endif;
                endforeach; endif; 
                ?>
            </div>
        <?php else: ?>
            <p>Gioco non trovato.</p>
        <?php endif; ?>
    </div>
</body>
</html>
