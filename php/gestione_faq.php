<?php 
/* 
QUESTA PAGINA VIENE APERTA QUANDO UN ADMIN, DALLA PROPRIA DASHBOARD, CLICCA SU 'GESTISCI FAQ'
questo file fa le seguenti cose:
1- aggiunge una nuova sezione "Domande dal Forum" sotto la gestione FAQ esistente
2- mostra solo le domande che:
    - non sono già state elevate a FAQ
    - hanno almeno una risposta
3- per ogni domanda mostra:
    - il contenuto della domanda
    - l'autore e la data
    - tutte le risposte ricevute
4- per ogni risposta:
    - il contenuto
    - l'autore e la data
    - i punteggi di supporto e utilità
    - un bottone per elevare quella specifica risposta a FAQ
5- quando una risposta viene elevata a FAQ:
    - viene creata una nuova FAQ
    - viene mantenuto il riferimento al thread e alla risposta originale
    - la domanda non apparirà più in questa lista */

session_start();

if(isset($_SESSION['tipo_utente']) && isset($_SESSION['statoLogin'])){ // se l'utente è già loggato

    if($_SESSION['tipo_utente'] !== 'admin'){ // se l'utente è già loggato e il suo ruolo è diverso da admin
        header("Location: home.php");
        exit();
    }
}else{
    header("Location: login.php");
    exit();
}

$xml_file = '../xml/faq.xml';

// caricamento del file XML
function caricaXML() {
    global $xml_file;
    if (file_exists($xml_file)) {
        return simplexml_load_file($xml_file);
    }
    return false;
}

// salvataggio del file XML
function salvaXML($xml) {
    global $xml_file;

    // formattiamo correttamente nel file xml

    // creazione nuovo oggetto DOMDocument
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false; // non preserviamo gli spazi bianchi
    $dom->formatOutput = true; // abilitiamo la formattazione

    // caricamento dell'XML esistente
    $dom->loadXML($xml->asXML());

    // salviamo il file XML
    $dom->save($xml_file);
}

$forum_file = '../xml/domande.xml';
$forum = simplexml_load_file($forum_file);


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $xml = caricaXML();
    
    if (isset($_POST['aggiungi_faq'])) {
        // controlliamo se la domanda esiste già
        $domanda_esistente = false;
        foreach ($xml->faq as $faq) {
            if ((string)$faq->domanda === $_POST['domanda']) {
                $domanda_esistente = true;
                break;
            }
        }

        if ($domanda_esistente) {
            $messaggio = "Questa domanda esiste già nel sistema.";
        } else {
            $nuova_faq = $xml->addChild('faq');
            $nuova_faq->addAttribute('id', time()); // usiamo il timestamp come ID
            $nuova_faq->addChild('domanda', $_POST['domanda']);
            $nuova_faq->addChild('risposta', $_POST['risposta']);
            $nuova_faq->addChild('data_creazione', date('Y-m-d'));
            $nuova_faq->addChild('fonte', 'admin');
            
            salvaXML($xml);
            $messaggio = "FAQ aggiunta con successo!";
        }
    }
    
    elseif (isset($_POST['elimina_faq'])) {
        $id_da_eliminare = $_POST['id_faq'];
        $indice = 0;
        foreach ($xml->faq as $faq) {
            if ((string)$faq['id'] === $id_da_eliminare) {
                unset($xml->faq[$indice]);
                break;
            }
            $indice++;
        }
        // salviamo file XML dopo l'eliminazione
        salvaXML($xml);
        $messaggio = "FAQ eliminata con successo!";
    }
    
    elseif (isset($_POST['modifica_faq'])) {
        // controllo se i dati sono stati inviati
        if (isset($_POST['id_faq'], $_POST['domanda'], $_POST['risposta'])) {
            foreach ($xml->faq as $faq) {
                if ((string)$faq['id'] === $_POST['id_faq']) {
                    $faq->domanda = $_POST['domanda'];
                    $faq->risposta = $_POST['risposta'];
                    break;
                }
            }
            salvaXML($xml);
            $messaggio = "FAQ modificata con successo!";
            
            // reindirizzamento alla sezione principale
            header("Location: gestione_faq.php");
            exit();
        } else {
            if (!isset($_POST['id_faq'])) {
                $messaggio = "Errore: dati non validi per la modifica della FAQ.";
            }
        }
    }
    
    elseif (isset($_POST['eleva_a_faq'])) {
        $id_domanda = $_POST['id_domanda'];
        $id_risposta = $_POST['id_risposta'];
        
        // cerca la domanda e la risposta nel forum
        foreach ($forum->thread as $thread) {
            if ((string)$thread['id'] === $id_domanda) {
                $domanda = (string)$thread->contenuto;
                // controlliamo se la FAQ esiste già prima di aggiungerla
                $faq_esistente = false;
                foreach ($xml->faq as $faq) {
                    if ((string)$faq->domanda === $domanda) {
                        $faq_esistente = true;
                        break;
                    }
                }
                if (!$faq_esistente) {
                    foreach ($thread->risposte->risposta as $risposta) {
                        if ((string)$risposta['id'] === $id_risposta) {
                            // crea nuova FAQ
                            $nuova_faq = $xml->addChild('faq');
                            $nuova_faq->addAttribute('id', time());
                            $nuova_faq->addChild('domanda', $domanda);
                            $nuova_faq->addChild('risposta', (string)$risposta->contenuto);
                            $nuova_faq->addChild('data_creazione', date('Y-m-d'));
                            $nuova_faq->addChild('fonte', 'forum');
                            $nuova_faq->addChild('id_thread', $id_domanda);
                            $nuova_faq->addChild('id_risposta', $id_risposta);
                            
                            salvaXML($xml);
                            $messaggio = "Domanda e risposta elevate a FAQ con successo!";
                            break 2;
                        }
                    }
                } else {
                    $messaggio = "Questa domanda è già stata elevata a FAQ.";
                }
            }
        }
    }
}

// caricamento file XML per visualizzarlo
$xml = caricaXML();

if (isset($messaggio)): ?>
    <div class="messaggio"><?php echo $messaggio; ?></div>
<?php endif; ?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione FAQ</title>
    <style>
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 1.2em; }
        .form-group textarea { width: 100%; min-height: 100px; padding: 8px; font-size: 1em; }
        .btn { padding: 8px 15px; margin-right: 5px; cursor: pointer; border-radius: 5px; }
        .btn-primary { background: #007bff; color: white; border: none; transition: background-color 0.3s; }
        .btn-primary:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; color: white; border: none; transition: background-color 0.3s; }
        .btn-danger:hover { background: #c82333; }
        .faq-item {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
            border: 1px solid #ddd;
            font-size: 1.1em;
        }
        .faq-item h3 {
            color: #007bff;
            margin: 0 0 10px;
        }
        .meta-info {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }
        .messaggio { padding: 10px; margin: 10px 0; background: #d4edda; color: #155724; border-radius: 4px; }
        
        .forum-domande {
            margin-top: 40px;
            border-top: 2px solid #eee;
            padding-top: 20px;
        }
        
        .thread-item {
            background: #fff;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            font-size: 1.1em;
        }
        
        .risposta-item {
            margin-left: 20px;
            padding: 10px;
            background: #f8f9fa;
            border-left: 3px solid #007bff;
            margin-top: 10px;
        }
        
        .stats {
            display: inline-block;
            margin-left: 20px;
            padding: 3px 8px;
            background: #e9ecef;
        }
        /* stile per la barra di navigazione (menu) */
        .navbar {
            background-color: green; 
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
    </style>
</head>
<body>
    <div class="navbar">
        <ul>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="gestione_utenti.php">Modifica utenti</a></li>
            <li><a href="gestione_crediti.php">Richieste Crediti</a></li>
            <li><a href="gestione_richiestaGestore.php">Richieste utenti</a></li>
        </ul>
    </div>
    
    <div style="text-align: right; margin-right: 2%; margin-top: 20px;">
        <a href="logout.php" class="logout-link" style="display: inline-block; padding: 12px 25px; background-color: #ff4d4d; color: white; border-radius: 5px; text-decoration: none; font-size: 1.2em;">Logout</a>
    </div>
    
    <div class="container">
        <h1 style="text-align: center; font-size: 200%; color: red">Gestione FAQ</h1>
        <h3 style="text-align: center; color: red; font-size: 150%;">Qui puoi aggiungere, eliminare o modificare le FAQ</h3>
        
        <h2>Aggiungi nuova FAQ</h2>
        <form method="POST">
            <div class="form-group">
                <label>Domanda:</label>
                <textarea name="domanda" required></textarea>
            </div>
            <div class="form-group">
                <label>Risposta:</label>
                <textarea name="risposta" required></textarea>
            </div>
            <button type="submit" name="aggiungi_faq" class="btn btn-primary">Aggiungi FAQ</button>
        </form>

        <h2 style="margin-top: 2ex; text-align: center; font-size: 200%;">FAQ Esistenti</h2>
        <?php if ($xml): foreach ($xml->faq as $faq): ?>
            <div class="faq-item">
                <h3><?php echo htmlspecialchars($faq->domanda); ?></h3>
                <p><?php echo htmlspecialchars($faq->risposta); ?></p>
                <p class="meta-info">
                    <strong>Data Creazione:</strong> <?php echo htmlspecialchars($faq->data_creazione); ?>
                </p>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="id_faq" value="<?php echo $faq['id']; ?>">
                    <button type="submit" name="modifica_faq" class="btn btn-primary">Modifica</button>
                </form>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="id_faq" value="<?php echo $faq['id']; ?>">
                    <button type="submit" name="elimina_faq" class="btn btn-danger" 
                            onclick="return confirm('Sei sicuro di voler eliminare questa FAQ?')">
                        Elimina
                    </button>
                </form>
                <?php if (isset($_POST['id_faq']) && $_POST['id_faq'] == $faq['id']): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="id_faq" value="<?php echo $faq['id']; ?>">
                        <div class="form-group">
                            <label>Domanda:</label>
                            <textarea name="domanda" required><?php echo htmlspecialchars($faq->domanda); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Risposta:</label>
                            <textarea name="risposta" required><?php echo htmlspecialchars($faq->risposta); ?></textarea>
                        </div>
                        <button type="submit" name="modifica_faq" class="btn btn-primary">Salva Modifiche</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; else: ?>
            <p>Nessuna FAQ disponibile.</p>
        <?php endif; ?>

        
    </div>
</body>
</html>
