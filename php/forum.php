<?php
session_start();
require_once('connessione.php');

// redirect se admin o gestore
if ($_SESSION['tipo_utente'] === 'admin') {
    header("Location: home.php");
    exit();

}elseif($_SESSION['tipo_utente'] === 'gestore'){
    header("Location: home.php");
    exit();
}

// funzione per caricare il file XML delle domande
function caricaDomande() {
    $xmlFile = '../xml/domande.xml';
    if (file_exists($xmlFile)) {
        return simplexml_load_file($xmlFile);
    }
    return false;
}

// funzione per caricare il file XML delle risposte
function caricaRisposte() {
    $xmlFile = '../xml/risposte.xml';
    if (file_exists($xmlFile)) {
        return simplexml_load_file($xmlFile);
    }
    return false;
}

// funzione per salvare una nuova domanda
function salvaDomanda($username, $testo, $codiceGioco) {
    $xmlFile = '../xml/domande.xml';
    $domande = caricaDomande();
    
    if (!$domande) {
        $domande = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><domande></domande>');
    }
    
    $domanda = $domande->addChild('domanda');
    $domanda->addChild('id', time());   // usiamo il timestamp come ID (prima idea)
    $domanda->addChild('username', $username);
    $domanda->addChild('testo', $testo);
    $domanda->addChild('codiceGioco', $codiceGioco);
    $domanda->addChild('data', date('Y-m-d H:i:s'));
    
    $domande->asXML($xmlFile);
}

// funzione per salvare una nuova risposta
function salvaRisposta($idDomanda, $username, $testo) {
    $xmlFile = '../xml/risposte.xml';
    $risposte = caricaRisposte();
    
    if (!$risposte) {
        $risposte = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><risposte></risposte>');
    }
    
    $risposta = $risposte->addChild('risposta');
    $risposta->addChild('id', time());
    $risposta->addChild('idDomanda', $idDomanda);
    $risposta->addChild('username', $username);
    $risposta->addChild('testo', $testo);
    $risposta->addChild('data', date('Y-m-d H:i:s'));
    
    $risposte->asXML($xmlFile);
}

// gestione invio domanda
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuova_domanda'])) {
    if (isset($_SESSION['username'])) {
        salvaDomanda(
            $_SESSION['username'],
            $_POST['testo'],
            $_POST['codice_gioco']
        );
        header('Location: forum.php');
        exit();
    }
}

// gestione invio risposta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuova_risposta'])) {
    if (isset($_SESSION['username'])) {
        salvaRisposta(
            $_POST['id_domanda'],
            $_SESSION['username'],
            $_POST['testo']
        );
        header('Location: forum.php');
        exit();
    }
}

$domande = caricaDomande();
$risposte = caricaRisposte();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Forum Videogiochi</title>
    <link rel="stylesheet" href="../css/giochi.css">
</head>
<body>
    <div class="forum-container">
        <h1>Forum Videogiochi</h1>
        
        <?php if (isset($_SESSION['username'])): ?>

            <!-- form per una nuova domanda -->
            <div class="nuova-domanda">
                <h2>Fai una domanda</h2>
                <form method="POST">
                    <input type="hidden" name="nuova_domanda" value="1">
                    <select name="codice_gioco" required>
                        <?php
                        $query = "SELECT codice, nome FROM videogiochi ORDER BY nome";
                        $risultato = $connessione->query($query);
                        while ($gioco = $risultato->fetch_assoc()) {
                            echo "<option value='{$gioco['codice']}'>{$gioco['nome']}</option>";
                        }
                        ?>
                    </select>
                    <textarea name="testo" required placeholder="Scrivi qui la tua domanda..."></textarea>
                    <button type="submit">Pubblica Domanda</button>
                </form>
            </div>
        <?php endif; ?>

        <!-- lista delle domande e delle risposte -->
        <div class="domande-lista">
            <?php if ($domande): foreach ($domande->domanda as $domanda): ?>
                <div class="domanda-box">
                    <div class="domanda-info">
                        <strong><?php echo $domanda->username; ?></strong>
                        <span class="data"><?php echo $domanda->data; ?></span>
                    </div>
                    <p class="domanda-testo"><?php echo $domanda->testo; ?></p>
                    
                    <!-- risposte alla domanda -->
                    <div class="risposte-lista">
                        <?php if ($risposte): foreach ($risposte->risposta as $risposta):
                            if ($risposta->idDomanda == $domanda->id): ?>
                                <div class="risposta-box">
                                    <div class="risposta-info">
                                        <strong><?php echo $risposta->username; ?></strong>
                                        <span class="data"><?php echo $risposta->data; ?></span>
                                    </div>
                                    <p class="risposta-testo"><?php echo $risposta->testo; ?></p>
                                </div>
                            <?php endif;
                        endforeach; endif; ?>
                    </div>
                    
                    <?php if (isset($_SESSION['username'])): ?>
                        <!-- form per nuova risposta -->
                        <form method="POST" class="risposta-form">
                            <input type="hidden" name="nuova_risposta" value="1">
                            <input type="hidden" name="id_domanda" value="<?php echo $domanda->id; ?>">
                            <textarea name="testo" required placeholder="Scrivi qui la tua risposta..."></textarea>
                            <button type="submit">Rispondi</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</body>
</html>