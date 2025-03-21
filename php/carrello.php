<?php
session_start();
require_once('connessione.php');
require_once('funzioni_sconti_bonus.php');


// verifica se l'utente è loggato e se è un cliente
if (!isset($_SESSION['statoLogin'])) {
    header("Location: login.php");
    exit();
} elseif ($_SESSION['tipo_utente'] !== 'cliente') {
    header("Location: home.php");
    exit();
}

// inizializziamo il carrello nella sessione (se non esiste)
if (!isset($_SESSION['carrello'])) {
    $_SESSION['carrello'] = array();
}

// caricamento dei giochi dal file XML
$xml_file = '../xml/giochi.xml';
if (file_exists($xml_file)) {
    $xml = simplexml_load_file($xml_file);
    if ($xml === false) {
        echo "Errore nel caricamento del file XML.";
        foreach(libxml_get_errors() as $error) {
            echo "<br>", $error->message;
        }
    }
} else {
    echo "Il file XML non esiste.";
}

// andiamo a gestire le varie azioni
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['aggiungi']) && isset($_POST['codice_gioco'])) {
        $_SESSION['carrello'][] = $_POST['codice_gioco'];
        $_SESSION['carrello'] = array_unique($_SESSION['carrello']); // evitiamo duplicati
    } elseif (isset($_POST['rimuovi']) && isset($_POST['codice_gioco'])) {
        // rimozione dal carrello nella sessione in corso
        $key = array_search($_POST['codice_gioco'], $_SESSION['carrello']);
        if ($key !== false) {
            unset($_SESSION['carrello'][$key]);
        }
    } elseif (isset($_POST['acquista'])) {
        // caricamento dei giochi dal file XML
        $xml_file = '../xml/giochi.xml';
        if (file_exists($xml_file)) {
            $xml = simplexml_load_file($xml_file);
            if ($xml === false) {
                echo "Errore nel caricamento del file XML.";
                foreach(libxml_get_errors() as $error) {
                    echo "<br>", $error->message;
                }
            }
            $totale = 0;

            // calcolo del totale
            foreach ($_SESSION['carrello'] as $codice_gioco) {
                foreach ($xml->gioco as $gioco) {
                    if ((int)$gioco->codice === (int)$codice_gioco) {
                        $sconto = calcolaSconto($_SESSION['username'], (float)$gioco->prezzo_attuale);
                        $prezzo_scontato = (float)$gioco->prezzo_attuale * (1 - $sconto['percentuale'] / 100);
                        $totale += $prezzo_scontato;
                    }
                }
            }

            // recuperiamo il numero di crediti dell'utente
            $query_crediti = "SELECT crediti FROM utenti WHERE username = ?";
            $stmt = $connessione->prepare($query_crediti);
            $stmt->bind_param("s", $_SESSION['username']);
            $stmt->execute();
            $crediti_disponibili = $stmt->get_result()->fetch_assoc()['crediti'];

            if ($crediti_disponibili >= $totale) {
                $connessione->begin_transaction();
                try {
                    // aggiorniamo i crediti dell'utente
                    $nuovi_crediti = $crediti_disponibili - $totale;
                    $query_update = "UPDATE utenti SET crediti = ? WHERE username = ?";
                    $stmt = $connessione->prepare($query_update);
                    $stmt->bind_param("ds", $nuovi_crediti, $_SESSION['username']);
                    $stmt->execute();

                    // registriamo gli acquisti in un file XML
                    $xml_file = '../xml/acquisti.xml';
                    if (!file_exists($xml_file)) {
                        $xml = new SimpleXMLElement('<?xml version="1.0"?><acquisti></acquisti>');
                    } else {
                        $xml = simplexml_load_file($xml_file);
                    }

                    foreach ($_SESSION['carrello'] as $codice_gioco) {
                        foreach ($xml->gioco as $gioco) {
                            if ((int)$gioco->codice === (int)$codice_gioco) {
                                $acquisto = $xml->addChild('acquisto');
                                $acquisto->addAttribute('id', uniqid());
                                $acquisto->addChild('username', $_SESSION['username']);
                                $acquisto->addChild('codice_gioco', $codice_gioco);
                                $acquisto->addChild('prezzo_originale', $gioco->prezzo_originale);
                                $acquisto->addChild('prezzo_pagato', $prezzo_scontato);
                                $acquisto->addChild('sconto_applicato', $sconto['percentuale']);
                                $acquisto->addChild('data', date('Y-m-d'));
                            }
                        }
                    }

                    // formattazione con DOMDocument
                    $dom = new DOMDocument('1.0');
                    $dom->preserveWhiteSpace = false;
                    $dom->formatOutput = true;
                    $dom->loadXML($xml->asXML());
                    $dom->save($xml_file);

                    // svuotiamo il carrello
                    $_SESSION['carrello'] = array();
                    $connessione->commit();
                    $messaggio_successo = "Acquisto completato con successo!";
                } catch (Exception $e) {
                    $connessione->rollback();
                    $errore = "Errore durante l'acquisto: " . $e->getMessage();
                }
            } else {
                $errore = "Crediti insufficienti per completare l'acquisto <a href='profilo.php'>Ricarica ora</a>";
            }
        } else {
            $errore = "Il file dei giochi non è stato trovato.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Il tuo Carrello</title>
    <link rel="stylesheet" href="../css/menu.css">
    <link rel="stylesheet" href="../css/home.css">
    <style>
        .container {
            max-width: 1000px;
            margin: 100px auto 0;
            padding: 20px;
        }
        .gioco-carrello {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .gioco-immagine {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        .info-gioco {
            flex-grow: 1;
        }
        .prezzo-originale {
            text-decoration: line-through;
            color: #6c757d;
        }
        .prezzo-scontato {
            color: #28a745;
            font-weight: bold;
        }
        .btn-rimuovi {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        .totale {
            text-align: right;
            font-size: 1.2em;
            margin: 20px 0;
        }
        .carrello-vuoto {
            text-align: center;
            padding: 50px;
        }
        .btn-acquista {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            margin-left: 10px;
            width: 200px;
        }

        .btn-continua-shopping {
            background: #007bff;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            display: inline-block;
        }

        .azioni-finali {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
            align-items: center;
        }

        .messaggio {
            padding: 15px;
            margin-bottom: 10px;
        }

        .messaggio.successo {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
        }

        .messaggio.errore {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
        }

        .btn-catalogo {
            background-color: #28a745; /* colore verde */
            color: white; 
            text-decoration: none; 
            padding: 10px 20px;
            border-radius: 5px; 
            display: inline-block; 
            font-size: 1.1em; 
            transition: background-color 0.3s ease; 
        }

        .btn-catalogo:hover {
            background-color: #218838; 
        }
    </style>
</head>
<body>
    <?php include('menu.php'); ?>

    <div class="container">
        <h1 style="text-align: center;">Il tuo Carrello</h1>

        <?php if (isset($messaggio_successo)): ?>
            <div class="messaggio successo"><?php echo $messaggio_successo; ?></div>
        <?php endif; ?>

        <?php if (isset($errore)): ?>
            <div class="messaggio errore"><?php echo $errore; ?></div>
        <?php endif; ?>

        <?php 
        $totale = 0;
        $ha_prodotti = !empty($_SESSION['carrello']);
        
        if ($ha_prodotti):
            foreach ($_SESSION['carrello'] as $codice_gioco):
                $found = false; // variabile per controllare se il gioco è stato trovato
                foreach ($xml->gioco as $gioco):
                    if ((int)$gioco->codice === (int)$codice_gioco) {
                        $found = true; // gioco trovato
                        $totale += (float)$gioco->prezzo_attuale; // aggiornamento del totale
                        ?>
                        <div class="gioco-carrello">
                            <img src="<?php echo htmlspecialchars($gioco->immagine); ?>" 
                                 alt="<?php echo htmlspecialchars($gioco->titolo); ?>"
                                 class="gioco-immagine">
                            <div class="info-gioco">
                                <h3><?php echo htmlspecialchars($gioco->titolo); ?></h3>
                                <p class="genere"><?php echo htmlspecialchars($gioco->categoria); ?></p>
                                <div class="prezzo-originale">€<?php echo number_format((float)$gioco->prezzo_attuale, 2); ?></div>
                                <div class="prezzo-scontato">
                                    Crediti: <?php echo number_format((float)$gioco->prezzo_attuale, 2); ?>
                                </div>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="codice_gioco" value="<?php echo $gioco->codice; ?>">
                                <button type="submit" name="rimuovi" class="btn-rimuovi">Rimuovi</button>
                            </form>
                        </div>
                        <?php
                    }
                endforeach;
                if (!$found) {
                    echo "Gioco con codice $codice_gioco non trovato nel file XML.";
                }
            endforeach;
        endif;
        
        if (!$ha_prodotti): ?>
            <div class="carrello-vuoto">
                <h2 style="text-align: center;">Il tuo carrello è vuoto</h2><br />
                <p style="font-size: 120%; text-align: center;">Aggiungi alcuni giochi dal nostro catalogo per iniziare lo shopping!</p><br />
                <a href="catalogo.php" class="btn-catalogo">Vai al Catalogo</a>
            </div>
        <?php endif; ?>

        <?php if ($totale > 0): ?>
            <div class="totale">
                Crediti Totali: <?php echo number_format($totale, 2); ?>
            </div>
            <div class="azioni-finali">
                <a href="catalogo.php" class="btn-continua-shopping">Continua ad acquistare</a>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="acquista" class="btn-acquista">Completa l'acquisto</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
