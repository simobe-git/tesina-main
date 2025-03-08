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

$xml_file = 'xml/faq.xml';

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
    $xml->asXML($xml_file);
}

$forum_file = '../xml/domande.xml';
$forum = simplexml_load_file($forum_file);


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $xml = caricaXML();
    
    if (isset($_POST['aggiungi_faq'])) {
        $nuova_faq = $xml->addChild('faq');
        $nuova_faq->addAttribute('id', time());    // usiamo il timestamp come ID (prima idea)
        $nuova_faq->addChild('domanda', $_POST['domanda']);
        $nuova_faq->addChild('risposta', $_POST['risposta']);
        $nuova_faq->addChild('data_creazione', date('Y-m-d'));
        $nuova_faq->addChild('fonte', 'admin');
        
        salvaXML($xml);
        $messaggio = "FAQ aggiunta con successo!";
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
        salvaXML($xml);
        $messaggio = "FAQ eliminata con successo!";
    }
    
    elseif (isset($_POST['modifica_faq'])) {
        foreach ($xml->faq as $faq) {
            if ((string)$faq['id'] === $_POST['id_faq']) {
                $faq->domanda = $_POST['domanda'];
                $faq->risposta = $_POST['risposta'];
                break;
            }
        }
        salvaXML($xml);
        $messaggio = "FAQ modificata con successo!";
    }
    
    elseif (isset($_POST['eleva_a_faq'])) {
        $id_domanda = $_POST['id_domanda'];
        $id_risposta = $_POST['id_risposta'];
        
        // cerca la domanda e la risposta nel forum
        foreach ($forum->thread as $thread) {
            if ((string)$thread['id'] === $id_domanda) {
                $domanda = (string)$thread->contenuto;
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
            }
        }
    }
}

$xml = caricaXML();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione FAQ</title>
    <style>
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group textarea { width: 100%; min-height: 100px; padding: 8px; }
        .btn { padding: 8px 15px; margin-right: 5px; cursor: pointer; }
        .btn-primary { background: #007bff; color: white; border: none; }
        .btn-danger { background: #dc3545; color: white; border: none; }
        .faq-item { background: #f8f9fa; padding: 15px; margin-bottom: 15px; border-radius: 4px; }
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
        }
        
        .risposta-item {
            margin-left: 20px;
            padding: 10px;
            background: #f8f9fa;
            border-left: 3px solid #007bff;
            margin-top: 10px;
        }
        
        .meta-info {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }
        
        .stats {
            display: inline-block;
            margin-left: 20px;
            padding: 3px 8px;
            background: #e9ecef;
        }
        /* stile per la barra di navigazione (menu) */
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
            list-style-type: disc;
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
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="gestione_utenti.php">Modifica Utente</a></li>
            <li><a href="gestione_crediti.php">Richieste Crediti</a></li>
            <li><a href="gestione_utenti.php">Ban utenti</a></li>
        </ul>
    </div>
    
    <div class="container">
        <h1 style="text-align: center; font-size: 200%; color: red">Gestione FAQ</h1>
        <h3 style="text-align: center; color: red; font-size: 150%;">Qui puoi aggiungere, eliminare o modificare le FAQ</h3>
        
        <?php if (isset($messaggio)): ?>
            <div class="messaggio"><?php echo $messaggio; ?></div>
        <?php endif; ?>

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

        <h2>FAQ Esistenti</h2>
        <?php if ($xml): foreach ($xml->faq as $faq): ?>
            <div class="faq-item">
                <form method="POST">
                    <input type="hidden" name="id_faq" value="<?php echo $faq['id']; ?>">
                    <div class="form-group">
                        <label>Domanda:</label>
                        <textarea name="domanda"><?php echo $faq->domanda; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Risposta:</label>
                        <textarea name="risposta"><?php echo $faq->risposta; ?></textarea>
                    </div>
                    <button type="submit" name="modifica_faq" class="btn btn-primary">Modifica</button>
                    <button type="submit" name="elimina_faq" class="btn btn-danger" 
                            onclick="return confirm('Sei sicuro di voler eliminare questa FAQ?')">
                        Elimina
                    </button>
                </form>
            </div>
        <?php endforeach; endif; ?>

        <h2>Domande dal Forum</h2>
        <div class="forum-domande">
            <?php if ($forum): foreach ($forum->thread as $thread): ?>
                <div class="thread-item">
                    <h3><?php echo $thread['id']; ?> - <?php echo $thread->titolo; ?></h3>
                    <p><?php echo $thread->contenuto; ?></p>
                    <div class="meta-info">
                        <span class="stats">Voti: <?php echo $thread->voti; ?></span>
                        <span class="stats">Risposte: <?php echo count($thread->risposte->risposta); ?></span>
                        <span class="stats">Visite: <?php echo $thread->visite; ?></span>
                    </div>
                    <div class="risposta-item">
                        <form method="POST">
                            <input type="hidden" name="id_domanda" value="<?php echo $thread['id']; ?>">
                            <input type="hidden" name="id_risposta" value="<?php echo $thread->risposte->risposta[0]['id']; ?>">
                            <button type="submit" name="eleva_a_faq" class="btn btn-primary">Eleva a FAQ</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</body>
</html>
