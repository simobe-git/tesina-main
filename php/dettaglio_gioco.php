<?php
session_start();
require_once('connessione.php');
require_once('funzioni_sconti_bonus.php');

// verifica se l'utente non è admin o gestore

if(isset($_SESSION['statoLogin']) === false) { // se l'utente non è loggato

}elseif(isset($_SESSION['tipo_utente'])){

    if($_SESSION['tipo_utente'] === 'admin' || $_SESSION['tipo_utente'] === 'gestore'){ // se l'utente è già loggato e il suo ruolo è admin o gestore, reindirizzalo alla home
        header("Location: home.php");
        exit();
    }
}

$id_gioco = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// caricamento dei giochi dal file XML
$xml = simplexml_load_file('../xml/giochi.xml'); // carichiamo il file XML
$giochi = json_decode(json_encode($xml), true); // questa riga converte l'XML in un array

// troviamo il gioco specifico
$gioco = null;
foreach ($giochi['gioco'] as $g) {
    if ($g['codice'] == $id_gioco) {
        $gioco = $g;
        break;
    }
}

if (!$gioco) {
    header('Location: catalogo.php');
    exit();
}

// calcola sconto e bonus
$sconto = calcolaSconto($_SESSION['username'] ?? null, $gioco['prezzo_originale']);
//PER ORA NO     $bonus = getBonusDisponibili($id_gioco); 

// caricamento delle recensioni dal file XML
$recensioniXml = simplexml_load_file('../xml/recensioni.xml'); // carichiamo il file XML delle recensioni
$recensioni = []; // è un array per memorizzare le recensioni filtrate

foreach ($recensioniXml->recensione as $rec) {
    if ((int)$rec->codice_gioco === $id_gioco) {
        $recensioni[] = [
            'username' => (string)$rec->username,
            'testo' => (string)$rec->testo,
            'data' => (string)$rec->data,
            'giudizi' => $rec->giudizi->giudizio // per gestire i giudizi (PIU AVANTI)
        ];
    }
}

// caricamento delle discussioni dal file XML
$domandeXml = simplexml_load_file('../xml/domande.xml'); // carichimo il file XML delle domande
$discussioni = [];   // è un array per memorizzare le discussioni filtrate

foreach ($domandeXml->domanda as $dom) {
    if ((int)$dom->codice_gioco === $id_gioco) {
        $risposte = [];
        foreach ($dom->risposta as $risposta) {
            $risposte[] = [
                'contenuto' => (string)$risposta->contenuto,
                'autore' => (string)$risposta->autore,
                'data' => (string)$risposta->data,
            ];
        }

        $discussioni[] = [
            'titolo' => (string)$dom->titolo,
            'contenuto' => (string)$dom->contenuto,
            'autore' => (string)$dom->autore,
            'data' => (string)$dom->data,
            'risposte' => $risposte // aggiungiamo le risposte caricate nell'xmll
        ];
    }
}

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($gioco['titolo']); ?></title>
    <link rel="stylesheet" href="../css/dettaglio.css">
    <link rel="stylesheet" href="../css/menu.css">
    <style>
        
        .btn-primary {
            background: linear-gradient(45deg, #2196F3, #1976D2);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, #1976D2, #1565C0);
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            transform: translateY(-2px);
        }

        /* bottone del carrello */
        .btn-carrello {
            background: linear-gradient(45deg, #4CAF50, #45a049);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            max-width: 250px;
            margin: 20px 0;
        }

        .btn-carrello:before {
            content: '🛒';
            font-size: 1.2rem;
        }

        .btn-carrello:hover {
            background: linear-gradient(45deg, #45a049, #388E3C);
        }

        .form-recensione button,
        .form-discussione button {
            background: linear-gradient(45deg, #FF5722, #F4511E);
            padding: 10px 20px;
            margin-top: 10px;
            width: auto;
            min-width: 150px;
        }

        .form-recensione button:hover,
        .form-discussione button:hover {
            background: linear-gradient(45deg, #F4511E, #E64A19);
        }

        .form-recensione textarea,
        .form-discussione textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            resize: vertical;
            min-height: 100px;
        }

        .form-recensione textarea:focus,
        .form-discussione textarea:focus {
            border-color: #2196F3;
            outline: none;
            box-shadow: 0 0 5px rgba(33, 150, 243, 0.3);
        }
        
        .btn-login {
            background: linear-gradient(45deg, #9C27B0, #7B1FA2);
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-login:hover {
            background: linear-gradient(45deg, #7B1FA2, #6A1B9A);
        }

        .gioco-immagine {
            width: 100%;
            max-width: 300px;
            height: 300px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .forum-section {
            margin-top: 20px; 
        }

        .discussione {
            background-color: #f0f8ff; 
            border: 1px solid #b0e0e6; 
            border-radius: 8px; 
            padding: 15px; 
            margin-bottom: 15px; 
        }

        .risposta {
            background-color: #e6ffe6; 
            border: 1px solid #c1e1c1; 
            border-radius: 8px; 
            padding: 10px; 
            margin-top: 10px; 
        }

        .risposta-header {
            display: flex; 
            justify-content: space-between; 
            font-weight: bold; 
            color: blue; 
            margin-bottom: 5px; 
        }

        .risposta-data {
            font-weight: bold; 
            color: #333; 
        }

        .risposta-testo {
            margin-top: 5px; 
        }

        .discussione-header {
            font-weight: bold; 
            margin-bottom: 5px; 
        }

        .btn-rispondi {
            background-color: #007bff; 
            color: white; 
            border: none; 
            padding: 8px 12px; 
            border-radius: 5px; 
            cursor: pointer; 
            margin-top: 10px;
        }

        .btn-rispondi:hover {
            background-color: #0056b3; 
        }

        .btn-mostra-altro {
            background-color: #e0e0e0; 
            color: black; 
            border: none; 
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px; 
        }

        .btn-mostra-altro:hover {
            background-color: #c0c0c0; 
        }

        .prezzo-originale {
            text-decoration: line-through;
            color: gray;
            font-size: 1.5rem;
        }

        .prezzo-scontato {
            color: green;
            font-size: 1.8rem;
            font-weight: bold;
        }

    </style>
</head>
<body>

    <?php include('menu.php'); ?>

    <div class="dettaglio-container">
        <div class="dettaglio-gioco">
            <h1 class="titolo-gioco"><?php echo htmlspecialchars($gioco['titolo']); ?></h1>
            
            <div class="gioco-content">
                <img class="gioco-immagine" src="<?php echo htmlspecialchars($gioco['immagine']); ?>" alt="<?php echo htmlspecialchars($gioco['titolo']); ?>">
                
                <div class="gioco-info">
                    <p class="descrizione"><?php echo htmlspecialchars($gioco['descrizione']); ?></p>
                    <div class="dettagli">
                        <p><strong>Categoria:</strong> <?php echo htmlspecialchars($gioco['categoria']); ?></p>
                        <p><strong>Giocatori:</strong> <?php echo htmlspecialchars($gioco['min_num_giocatori']); ?> - <?php echo htmlspecialchars($gioco['max_num_giocatori']); ?></p>
                        <p><strong>Età Minima:</strong> <?php echo htmlspecialchars($gioco['min_eta']); ?></p>
                        <p><strong>Durata Partita:</strong> <?php echo htmlspecialchars($gioco['avg_partita']); ?> min</p>
                        <p><strong>Pubblicazione:</strong> <?php
                                                            $date = new DateTime($gioco['data_rilascio']);  
                                                            $anno = $date->format('Y');
                                                            echo htmlspecialchars($anno); ?>
                        </p>
                        <p><strong>Editore:</strong> <?php echo htmlspecialchars($gioco['nome_editore']); ?></p>
                        <p><strong>Autore:</strong> <?php echo htmlspecialchars($gioco['autore']); ?></p>
                        <p><strong>Meccaniche:</strong> <?php echo htmlspecialchars(implode(', ', explode(',', $gioco['meccaniche']))); ?></p>
                        <p><strong>Ambientazione:</strong> <?php echo htmlspecialchars($gioco['ambientazione']); ?></p>
                    </div>
                    
                    <!-- variazione di prezzo con sconto -->
                    <div class="prezzi-acquisto">
                        <?php if ($sconto['percentuale'] > 0): ?>
                            <div class="prezzo-originale" style="text-decoration: line-through; color: gray; font-size: 1.5rem;">
                                <?php echo htmlspecialchars($gioco['prezzo_originale']); ?> crediti
                            </div>
                            <div class="prezzo-scontato" style="color: green; font-size: 1.8rem; font-weight: bold;">
                                <?php echo $sconto['prezzo_finale']; ?> crediti
                                <span class="sconto-info">(-<?php echo $sconto['percentuale']; ?>%)</span>
                            </div>
                            <div class="sconto-motivo"><?php echo $sconto['motivo']; ?></div>

                        <!-- gioco in offerta (mostriamo la variazione di prezzo)-->
                        <?php elseif($gioco['prezzo_originale'] != $gioco['prezzo_attuale']): ?>
                            <div class="prezzo-originale" style="text-decoration: line-through; color: gray; font-size: 1.2rem;">
                                <?php echo htmlspecialchars($gioco['prezzo_originale']); ?> crediti
                            </div>
                            <div class="prezzo-scontato" style="color: #2ecc71; font-size: 1.5rem; font-weight: bold;">
                                <?php echo htmlspecialchars($gioco['prezzo_attuale']); ?> crediti
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($bonus)): ?>
                            <div class="bonus-info">
                                <p class="bonus-titolo">Bonus all'acquisto:</p>
                                <?php foreach ($bonus as $b): ?>
                                    <div class="bonus-badge">
                                        <span class="bonus-ammontare">+<?php echo htmlspecialchars($b['crediti_bonus']); ?>€ in crediti</span>
                                        <span class="bonus-date">
                                            (Valido dal <?php echo date('d/m/Y', strtotime($b['data_inizio'])); ?> 
                                            al <?php echo date('d/m/Y', strtotime($b['data_fine'])); ?>)
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['username'])): ?>
                            <form method="POST" action="carrello.php">
                                <input type="hidden" name="codice_gioco" value="<?php echo $gioco['codice']; ?>">
                                <button type="submit" name="aggiungi" class="btn-primary btn-carrello">Aggiungi al Carrello</button>
                            </form>
                        <?php else: ?>
                            <a href="login.php" class="btn-primary btn-login" style="margin-top: 1ex;">Accedi per acquistare</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- sezione recensioni -->
            <div class="recensioni-section">
                <h2>Recensioni</h2>
                <?php if (isset($_SESSION['username'])): ?>
                    <form method="POST" action="aggiungi_recensione.php" class="form-recensione">
                        <input type="hidden" name="codice_gioco" value="<?php echo $id_gioco; ?>">
                        <textarea name="testo" required placeholder="Scrivi la tua recensione..."></textarea>
                        <button type="submit" class="btn-primary">Pubblica recensione</button>
                    </form>
                <?php endif; ?>
                
                <div class="recensioni-container">
                    <?php
                    if (empty($recensioni)){ // facciamo controllo che non ci siano recensioni
                        echo "<p>Non ci sono ancora recensioni per questo gioco.</p>";
                    } else {
                        $count = 0;
                        foreach ($recensioni as $recensione): 
                            if ($count < 2): // mostriamo solo le prime 2 recensioni
                    ?>
                        <div class="recensione">
                            <div class="recensione-header">
                                <span class="recensione-autore"><?php echo htmlspecialchars($recensione['username']); ?></span>
                                <span class="recensione-data"><?php echo date('d/m/Y', strtotime($recensione['data'])); ?></span>
                                <button class="btn-valuta" style="background-color: #2196F3; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer;">Valuta</button>
                            </div>
                            <p class="recensione-testo"><?php echo htmlspecialchars($recensione['testo']); ?></p>
                        </div>
                    <?php 
                            endif;
                            $count++;
                        endforeach; 
                        if ($count > 2):   // mostriamo il pulsante di ampliamento solo se ci sono più di 2 recensioni
                    ?>
                        <button class="btn-mostra-altro" onclick="mostraAltreRecensioni()">Mostra altre recensioni</button>
                        <div class="recensioni-nascoste" style="display: none;">
                            <?php 
                            foreach ($recensioni as $index => $recensione): 
                                if ($index >= 2):   // mostriamo solo le recensioni nascoste (dalla terza in poi)
                            ?>
                                <div class="recensione">
                                    <div class="recensione-header">
                                        <span class="recensione-autore"><?php echo htmlspecialchars($recensione['username']); ?></span>
                                        <span class="recensione-data"><?php echo date('d/m/Y', strtotime($recensione['data'])); ?></span>
                                        <button class="btn-valuta" style="background-color: #2196F3; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer;">Valuta</button>
                                    </div>
                                    <p class="recensione-testo"><?php echo htmlspecialchars($recensione['testo']); ?></p>
                                </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    <?php endif; ?>
                    <?php } ?>
                </div>
            </div>

            <!-- sezione forum -->
            <div class="forum-section">
                <h2>Discussioni</h2>
                <?php if (empty($discussioni)): ?>
                    <p>Non ci sono ancora discussioni per questo gioco.</p>
                <?php else: ?>
                    <div class="discussioni-container">
                        <?php 
                        $count = 0; // contatore per le domande
                        foreach ($discussioni as $discussione): 
                            if ($count < 2): // mostriamo solo le prime 2 domande
                        ?>
                            <div class="discussione">
                                <div class="discussione-header" style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <span class="discussione-autore" style="color: red;"><?php echo htmlspecialchars($discussione['autore']); ?></span>, 
                                        <span class="discussione-data"><?php echo date('d/m/Y', strtotime($discussione['data'])); ?></span>
                                    </div>
                                    <button class="btn-segnala" style="background-color: #FF9800; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer;">Segnala</button>
                                </div>
                                <p class="discussione-testo"><?php echo htmlspecialchars($discussione['contenuto']); ?></p>
                                
                                <!-- mostriamo le risposte alle domande dei forum -->
                                <?php if (!empty($discussione['risposte'])): ?>
                                    <div class="risposte-container">
                                        <?php 
                                        $hiddenCount = 0; // contatore per le risposte
                                        foreach ($discussione['risposte'] as $risposta): 
                                            if ($hiddenCount < 2): // mostriamo solo le prime 2 risposte
                                        ?>
                                            <div class="risposta">
                                                <div class="risposta-header" style="display: flex; justify-content: space-between; align-items: center;">
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($risposta['autore']); ?></strong>
                                                        <span class="risposta-data"><?php echo date('d/m/Y', strtotime($risposta['data'])); ?></span>
                                                    </div>
                                                    <button class="btn-valuta" style="background-color: #2196F3; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer;">Valuta</button>
                                                </div>
                                                <p class="risposta-testo"><?php echo htmlspecialchars($risposta['contenuto']); ?></p>
                                            </div>
                                        <?php 
                                            endif;
                                            $hiddenCount++;
                                        endforeach; 
                                        ?>
                                        
                                        <?php if ($hiddenCount > 2): // se ci sono più di 2 risposte, mostriamo pulsante ?>
                                            <button class="btn-mostra-altro" onclick="mostraAltreRisposte(this)">Mostra altre risposte</button>
                                            <div class="risposte-nascoste" style="display: none;">
                                                <?php 
                                                $hiddenCount = 0; // reset del contatore per le risposte nascoste
                                                foreach ($discussione['risposte'] as $risposta): 
                                                    if ($hiddenCount >= 2): // mostriamo solo le risposte nascoste
                                                ?>
                                                    <div class="risposta">
                                                        <div class="risposta-header" style="display: flex; justify-content: space-between; align-items: center;">
                                                            <div>
                                                                <strong><?php echo htmlspecialchars($risposta['autore']); ?></strong>
                                                                <span class="risposta-data"><?php echo date('d/m/Y', strtotime($risposta['data'])); ?></span>
                                                            </div>
                                                            <button class="btn-valuta" style="background-color: #2196F3; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer;">Valuta</button>
                                                        </div>
                                                        <p class="risposta-testo"><?php echo htmlspecialchars($risposta['contenuto']); ?></p>
                                                    </div>
                                                <?php 
                                                    endif;
                                                    $hiddenCount++;
                                                endforeach; 
                                                ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php 
                            endif;
                            $count++;
                        endforeach; 
                        if ($count > 2): // se ci sono più di 2 domande, mostriamo pulsante
                        ?>
                            <button class="btn-mostra-altro" onclick="mostraAltreDomande(this)">Mostra altre domande</button>
                            <div class="domande-nascoste" style="display: none;">
                                <?php 
                                foreach ($discussioni as $index => $discussione): 
                                    if ($index >= 2): // mostriamo solo le domande nascoste (dalla terza in poi)
                                ?>
                                    <div class="discussione">
                                        <div class="discussione-header" style="display: flex; justify-content: space-between; align-items: center;">
                                            <div>
                                                <span class="discussione-autore" style="color: red;"><?php echo htmlspecialchars($discussione['autore']); ?></span>, 
                                                <span class="discussione-data"><?php echo date('d/m/Y', strtotime($discussione['data'])); ?></span>
                                            </div>
                                            <button class="btn-segnala" style="background-color: #FF9800; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer;">Segnala</button>
                                        </div>
                                        <p class="discussione-testo"><?php echo htmlspecialchars($discussione['contenuto']); ?></p>
                                        
                                        <!-- mostriamo le risposte alle domande dei forum -->
                                        <?php if (!empty($discussione['risposte'])): ?>
                                            <div class="risposte-container">
                                                <?php 
                                                foreach ($discussione['risposte'] as $risposta): 
                                                ?>
                                                    <div class="risposta">
                                                        <div class="risposta-header" style="display: flex; justify-content: space-between; align-items: center;">
                                                            <div>
                                                                <strong><?php echo htmlspecialchars($risposta['autore']); ?></strong>
                                                                <span class="risposta-data"><?php echo date('d/m/Y', strtotime($risposta['data'])); ?></span>
                                                            </div>
                                                            <button class="btn-valuta" style="background-color: #2196F3; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer;">Valuta</button>
                                                        </div>
                                                        <p class="risposta-testo"><?php echo htmlspecialchars($risposta['contenuto']); ?></p>
                                                    </div>
                                                <?php 
                                                endforeach; 
                                                ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function mostraAltreRecensioni() {
            const recensioniNascoste = document.querySelector('.recensioni-nascoste'); // troviamo la sezione delle recensioni nascoste
            const btnMostraAltro = document.querySelector('.btn-mostra-altro'); // e troviamo il pulsante
            if (recensioniNascoste.style.display === "none") {
                recensioniNascoste.style.display = "block"; // mostriamo le recensioni
                btnMostraAltro.innerHTML = "Nascondi altre recensioni"; // cambio testo del pulsante dopo aver cliccato "mostra altre recensioni"
            } else {
                recensioniNascoste.style.display = "none"; // nascondiamo le recensioni
                btnMostraAltro.innerHTML = "Mostra altre recensioni";   // e in tal caso ripristiniamo il testo del pulsante
            }
        }

        function mostraFormRisposta(button) {
            const form = button.nextElementSibling;
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }

        function mostraAltreRisposte(button) {
            const risposteNascoste = button.nextElementSibling; // troviamo il contenitore delle risposte nascoste
            if (risposteNascoste.style.display === "none") {
                risposteNascoste.style.display = "block"; // mostriamo le risposte
                button.innerHTML = "Nascondi altre risposte"; // cambiamo il testo del pulsante dopo aver cliccato su "mostra altre risposte"
            } else {
                risposteNascoste.style.display = "none"; // nascondiamo le risposte
                button.innerHTML = "Mostra altre risposte"; // e ripristiniamo il testo del pulsante
            }
        }

        function mostraAltreDomande(button) {
            const domandeNascoste = button.nextElementSibling; // troviamo la sezione delle domande nascoste
            if (domandeNascoste.style.display === "none") {
                domandeNascoste.style.display = "block"; // mostriamo domande
                button.innerHTML = "Nascondi altre domande"; // cambiamo il testo del pulsante dopo aver cliccato "mostra altee domande"
            } else {
                domandeNascoste.style.display = "none"; // nascondiamo le domande
                button.innerHTML = "Mostra altre domande"; // ripristiniamo il testo iniziale del pulsante
            }
        }
// implementare funzioni che gestiscono la segnalazione degli utenti e la valutazione di recensioni e risposte nelle discussioni
    </script>
</body>
</html>