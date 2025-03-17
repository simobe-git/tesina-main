<?php
session_start();
require_once('funzioni_sconti_bonus.php');


if(isset($_SESSION['statoLogin']) === false) {//se l'utente non è loggato

}elseif(isset($_SESSION['tipo_utente'])){

    if($_SESSION['tipo_utente'] === 'admin' || $_SESSION['tipo_utente'] === 'gestore'){ // se l'utente è già loggato e il suo ruolo è admin o gestore, reindirizzalo alla home
        header("Location: home.php");
        exit();
    }
}

// caricamento dei giochi dal file XML
$xml = simplexml_load_file('../xml/giochi.xml'); 
$giochi = json_decode(json_encode($xml), true); // convertiamo l'XML in un array

// controlliamo dapprima se l'array contiene i giochi
if (isset($giochi['gioco'])) {
    // se l'array è presente, accesso ai giochi
    $giochi = $giochi['gioco'];
} else {
    // se invece non ci sono giochi, mostra un messaggio
    echo "<p>Nessun gioco trovato nel catalogo.</p>";
    exit;
}

function calcolaBonus($codiceGioco) {
    global $connessione;
    $bonus = [];
    
    // verifica se esiste un bonus nel database
    $query = "SELECT b.*, v.titolo as nome_gioco 
              FROM bonus b 
              // JOIN gioco_tavolo v ON b.codice_gioco = v.codice 
              WHERE b.codice_gioco = ? 
              AND b.data_inizio <= CURRENT_DATE 
              AND b.data_fine >= CURRENT_DATE";
              
    $stmt = $connessione->prepare($query);
    $stmt->bind_param("i", $codiceGioco);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $bonus[] = [
            'id' => $row['id_bonus'],
            'tipo' => 'crediti',
            'ammontare' => $row['crediti_bonus'],
            'nome_gioco' => $row['nome_gioco'],
            'data_inizio' => $row['data_inizio'],
            'data_fine' => $row['data_fine']
        ];
    }
    
    return $bonus;
}

// gestione dell'ordinamento
$ordinamento = isset($_GET['ordinamento']) ? $_GET['ordinamento'] : 'titolo'; // default ordinamento per nome
$direzione = isset($_GET['direzione']) ? $_GET['direzione'] : 'ASC'; // default crescente

// parametri di ordinamento
$ordinamenti_permessi = ['titolo', 'prezzo', 'data_rilascio'];
$direzioni_permesse = ['ASC', 'DESC'];

if (!in_array($ordinamento, $ordinamenti_permessi)) {
    $ordinamento = 'titolo';
}
if (!in_array($direzione, $direzioni_permesse)) {
    $direzione = 'ASC';
}

// gestione dei filtri
$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$editore = isset($_GET['editore']) ? $_GET['editore'] : '';

/* debug: Stampa il valore della categoria selezionata
echo "<pre>Categoria selezionata: " . htmlspecialchars($categoria) . "</pre>";
*/

// filtraggio dei giochi
if ($categoria) {
    $giochiFiltrati = array_filter($giochi, function($gioco) use ($categoria) {
        return $gioco['categoria'] === $categoria;
    });
    
    /* debug: Stampa i giochi filtrati
    echo "<pre>Giochi filtrati per categoria: " . htmlspecialchars($categoria) . "</pre>";
    print_r($giochiFiltrati); */
    
    $giochi = $giochiFiltrati; // aggiornamento array dei giochi
}
if ($editore) {
    $giochi = array_filter($giochi, function($gioco) use ($editore) {
        return $gioco['nome_editore'] === $editore;
    });
}

// ordinamento dei giochi
usort($giochi, function($a, $b) use ($ordinamento, $direzione) {
    if ($ordinamento === 'prezzo') {
        $prezzoA = $a['prezzo_attuale'] ?? $a['prezzo_originale'];
        $prezzoB = $b['prezzo_attuale'] ?? $b['prezzo_originale'];
        return $direzione === 'ASC' ? $prezzoA <=> $prezzoB : $prezzoB <=> $prezzoA;
    } elseif ($ordinamento === 'data_rilascio') {
        return $direzione === 'ASC' ? $a['data_rilascio'] <=> $b['data_rilascio'] : $b['data_rilascio'] <=> $a['data_rilascio'];
    } else { // ordinamento per titolo
        return $direzione === 'ASC' ? strcmp($a['titolo'], $b['titolo']) : strcmp($b['titolo'], $a['titolo']);
    }
});

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogo Giochi da Tavolo</title>
    <link rel="stylesheet" href="../css/giochi.css">
    <link rel="stylesheet" href="../css/menu.css">
    <script src="../js/filtri.js"></script>
</head>
<body>
    <?php include('menu.php'); ?>

    <header class="intestazione-negozio">
        <h1 style="margin-top: 7ex;">Catalogo Giochi da Tavolo</h1>
    </header>

    <div class="filtri-sezione" style="margin-top: 3em;">
        <div class="filtri-wrapper">
            <div class="filtro-box">
                <span class="filtro-label">Ordina per:</span>
                <select class="filtro-select" id="ordinamento" onchange="applicaFiltri()">
                    <option value="titolo" <?php echo $ordinamento === 'titolo' ? 'selected' : ''; ?>>Nome</option>
                    <option value="prezzo" <?php echo $ordinamento === 'prezzo' ? 'selected' : ''; ?>>Prezzo</option>
                    <option value="data_rilascio" <?php echo $ordinamento === 'data_rilascio' ? 'selected' : ''; ?>>Anno di uscita</option>
                </select>
            </div>

            <div class="filtro-box">
                <span class="filtro-label">Ordine:</span>
                <select class="filtro-select" id="direzione" onchange="applicaFiltri()">
                    <option value="ASC" <?php echo $direzione === 'ASC' ? 'selected' : ''; ?>>Crescente ↑</option>
                    <option value="DESC" <?php echo $direzione === 'DESC' ? 'selected' : ''; ?>>Decrescente ↓</option>
                </select>
            </div>

            <div class="filtro-box">
                <span class="filtro-label">Categoria:</span>
                <select class="filtro-select" id="categoria" onchange="applicaFiltri()">
                    <option value="">Tutti i generi</option>
                    <?php 
                    // quiu generiamo le opzioni per le categorie dal file XML
                    $categorie = array_unique(array_column($giochi, 'categoria'));
                    foreach ($categorie as $categoriaOpzione): ?>
                        <option value="<?php echo htmlspecialchars($categoriaOpzione); ?>"
                                <?php echo isset($_GET['categoria']) && $_GET['categoria'] === $categoriaOpzione ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($categoriaOpzione); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filtro-box">
                <span class="filtro-label">Editore:</span>
                <select class="filtro-select" id="editore" onchange="applicaFiltri()">
                    <option value="">Tutti gli editori</option>
                    <?php 
                    // generiamo le opzioni per gli editori dal file XML
                    $editori = array_unique(array_column($giochi, 'nome_editore'));
                    foreach ($editori as $editoreOpzione): ?>
                        <option value="<?php echo htmlspecialchars($editoreOpzione); ?>"
                                <?php echo isset($_GET['editore']) && $_GET['editore'] === $editoreOpzione ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($editoreOpzione); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- griglia dei giochi da tavolo -->
    <div class="product-grid" style="margin-top: 3em;">
        <?php
        // mostriamo i giochi filtrati
        if (empty($giochi)) {
            echo "<p>Nessun gioco trovato nel catalogo.</p>";
        } else {
            foreach ($giochi as $gioco) {
                $titolo = $gioco['titolo'] ?? 'Titolo non disponibile';
                $descrizione = $gioco['descrizione'] ?? 'Descrizione non disponibile';
                $prezzo_originale = $gioco['prezzo_originale'] ?? 'N/A';
                $prezzo_attuale = $gioco['prezzo_attuale'] ?? 'N/A';

                $prezzo_base = $prezzo_attuale !== 'N/A' ? $prezzo_attuale : $prezzo_originale;
                $sconto = calcolaSconto($_SESSION['username'] ?? null, $prezzo_base);
                $prezzo_finale = $sconto['prezzo_finale'] ?? $prezzo_base; 

                ?>
                <div class="product-item">
                    <a href="dettaglio_gioco.php?id=<?php echo $gioco['codice']; ?>">
                        <img src="<?php echo htmlspecialchars($gioco['immagine']); ?>" 
                             alt="<?php echo htmlspecialchars($titolo); ?>">
                    </a>
                    <h2><?php echo htmlspecialchars($titolo); ?></h2>
                    <p class="descrizione"><?php echo htmlspecialchars($descrizione); ?></p>
                    
                    <div class="prezzi">
                        <div class="prezzo-container">
                            
                            <?php if (isset($sconto['percentuale']) && $sconto['percentuale'] > 0): ?>
                                <div class="prezzo-originale"><?php echo $prezzo_originale; ?> crediti</div>
                                <div class="prezzo-scontato">
                                <span style="font-size: 1.4em; color: #2ecc71; font-weight: bold;"><?php echo $prezzo_finale; ?> crediti</span>
                                    <span class="sconto-info">(-<?php echo $sconto['percentuale']; ?>%)</span>
                                </div>
                                <div class="sconto-motivo"><?php echo $sconto['motivo'] ?? ''; ?></div>
                            <?php else: //nessuno sconto, mostriamo solo il prezzo dei giochi    ?> 
                                <span style="font-size: 1.4em; color: #2ecc71; font-weight: bold;"><?php echo $prezzo_finale; ?> crediti</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <button class="btn-acquista" onclick="location.href='dettaglio_gioco.php?id=<?php echo $gioco['codice']; ?>'">Visualizza Dettagli</button>
                </div>
                <?php 
            }
        }
        ?>
    </div>

    <script>
        const menuHamburger = document.querySelector('.hamburger-menu');
        const linkNav = document.querySelector('.nav-links');

        menuHamburger.addEventListener('click', () => {
            linkNav.classList.toggle('attivo');
        });

        // funzione per l'orrdinamento dei giochi in base ai criteri esistenti (categorie ed editori esistenti)
        function applicaFiltri() {
            const ordinamento = document.getElementById('ordinamento').value;
            const direzione = document.getElementById('direzione').value;
            const categoria = document.getElementById('categoria').value;
            const editore = document.getElementById('editore').value;

            const url = new URL(window.location.href);
            url.searchParams.set('ordinamento', ordinamento);
            url.searchParams.set('direzione', direzione);
            url.searchParams.set('categoria', categoria);
            url.searchParams.set('editore', editore);
            
            window.location.href = url.toString();
        }
    </script>
</body>
</html>
