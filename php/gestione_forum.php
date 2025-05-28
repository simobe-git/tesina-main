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

// caricamento file domande
$domandePath = '../xml/domande.xml';
if (!file_exists($domandePath)) {
    die("Il file domande.xml non esiste.");
} else {
    $domande = simplexml_load_file($domandePath);
}

//Caricamento file che riporta le valutazioni date dagli altri utenti alle domande fatte
$valutazione_domanda = '../xml/valuta_discussioni.xml';
if (!file_exists($valutazione_domanda)) {
    die("Il file valutazione_domanda.xml non esiste.");
} else {
    $valutazione_domanda = simplexml_load_file($valutazione_domanda);
}

/** 
 * Calcoliamo una media del punteggio assegnato dagli utenti alle domanda, in modo tale da mostrare
 * al gestore il punteggio medio di ogni domanda, con il numero di valutazioni ricevute facilitando la 
 * scelta di quale domanda elevare a FAQ. 
*/
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
}

/**
 * Quando viene premuto sul pulsante per elevare domanda e risposta come FAQ 
 * salviamo tali informazioni nel file xml xml delle faq così che venga visualizzato nella pagina faq.
 * Dom è usato per una corretta formattazione dei nuovi elementi nel file xml, inoltre dopo il salvataggio
 * viene mostrato un messaggio di successo e dopo 3 secondi si viene reindirizzati alla pagina di gestione discussioni. 
 */
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['elevaFaq'])) {

    $faq_file = '../xml/faq.xml';
    if(!file_exists($faq_file)){
        die("Il file faq.xml non esiste.");
    }

    // Carica il file XML con DOMDocument
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->load($faq_file);

    // Recupera i dati inviati dal form
    $codiceDomanda = $_POST['codice'];
    $idRisposta = $_POST['id_risposta'];
    
    // Verifica se la domanda è già presente nel file FAQ
    $faqs = $dom->getElementsByTagName('faq');
    foreach ($faqs as $faq) {
        $domandaEsistente = $faq->getElementsByTagName('domanda')->item(0);
        if ($domandaEsistente && $domandaEsistente->nodeValue === htmlspecialchars($domande->xpath("//domanda[codice_gioco='$codiceDomanda']/contenuto")[0])) {
            die("Errore: La domanda è già presente nel file FAQ.");
        }
    }
       
    // Trova la domanda e la risposta corrispondenti
    $domandaTrovata = false;
    $rispostaTrovata = false;

    foreach ($domande->domanda as $domanda){
        $domandaTrovata = true;
        if ((string)$domanda->codice_gioco === $codiceDomanda){
            foreach ($domanda->risposta as $risposta){
                if ((string)$risposta['id'] === $idRisposta){
                    
                    $rispostaTrovata = true;
                    // Crea un nuovo elemento FAQ
                    $faqs = $dom->getElementsByTagName('faqs')->item(0);
                    $faq = $dom->createElement('faq');
                    $faq->setAttribute('id', uniqid());
                    $domandaElement = $dom->createElement('domanda', htmlspecialchars($domanda->contenuto));
                    $rispostaElement = $dom->createElement('risposta', htmlspecialchars($risposta->contenuto));
                    $dataCreazioneElement = $dom->createElement('data_creazione', date('Y-m-d'));
                    $fonteElement = $dom->createElement('fonte', 'forum');

                    $faq->appendChild($domandaElement);
                    $faq->appendChild($rispostaElement);
                    $faq->appendChild($dataCreazioneElement);
                    $faq->appendChild($fonteElement);
                    $faqs->appendChild($faq);

                    // Salva il file XML aggiornato
                    if ($dom->save($faq_file) === false) {
                        die("Errore durante il salvataggio del file faq.xml.");
                    } else {
                        echo "<div class='messaggio'>FAQ elevata con successo!</div>";
                        echo "<script>
                                setTimeout(function() {
                                    window.location.href = '" . $_SERVER['PHP_SELF'] . "';
                                }, 3000);
                              </script>";
                        exit();
                    }
                    break 2; // Esci dai due cicli
                }
            }
        }
    }
    
    // Mostra messaggi di errore se la domanda o la risposta non sono state trovate
    if (!$domandaTrovata) {
        die("Errore: Domanda non trovata.");
    }
    if (!$rispostaTrovata) {
        die("Errore: Risposta non trovata.");
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
                    <h3 style="color: red; font-size: 140%;"><?php echo htmlspecialchars($domanda->titolo); ?></h3>
                    <form method="POST" action="">
                        <input type="hidden" name="codice" value="<?php echo $domanda->codice_gioco; ?>">
                        
                        <div class="form-group">
                            <label style="font-size:120%;">Contenuto:</label>
                            <p><?php echo htmlspecialchars($domanda->contenuto); ?></p>
                        </div>
                        
                        <div class="form-group">
                            <label style="font-size:120%;">Autore:</label>
                            <p style="color: red; font-size: 120%; font-style: bold;"><?php echo htmlspecialchars($domanda->autore); ?></p>
                        </div>
                        
                        <div class="form-group">
                            <label>Data:</label>
                            <p><?php echo htmlspecialchars($domanda->data); ?></p>
                        </div>
                    </form>

                    <!-- Visualizzazione delle risposte -->
                    <div class="risposte-container">
                        <h2>Risposte</h2>
                        <?php foreach ($domanda->risposta as $risposta): ?>
                            <div class="form-group">
                                <label style="color: blue;">Risposta ID <?php echo htmlspecialchars($risposta['id']); ?></label>
                            </div>
                            <div class="form-group">
                                <label>Contenuto</label>
                                <p><?php echo htmlspecialchars($risposta->contenuto); ?></p>
                            </div>
                            <div class="form-group">
                                <label>Autore</label>
                                <p><?php echo htmlspecialchars($risposta->autore); ?></p>
                            </div>
                            <div class="form-group">
                                <label>Data</label>
                                <p><?php echo htmlspecialchars($risposta->data); ?></p>
                            </div>

                            <!-- Mostriamo tutti i punteggi -->
                            <?php foreach($valutazione_domanda->valutazione as $valutazione):?>
                                <?php if ((string)$valutazione->id_risposta === (string)$risposta['id']): ?>
                                <div class="form-group">
                                    <label>Punteggio Medio</label>
                                    <p><?php echo htmlspecialchars(number_format($media, 2)); ?></p>
                                </div>

                                <div class="form-group">
                                    <label>Numero di Valutazioni</label>
                                    <p><?php echo htmlspecialchars($numero_valutazioni); ?></p>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            
                            <!--Pulsante per elevare a faq -->
                            <form method="post" action="">
                                <input type="hidden" name="codice" value="<?php echo $domanda->codice_gioco; ?>">
                                <input type="hidden" name="id_risposta" value="<?php echo $risposta['id']; ?>">
                                <button type="submit" name="elevaFaq" class="btn btn-primary">Eleva a FAQ</button>
                            </form>
                            
                            <br><br>
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