<?php

session_start();
// non viene eseguito il controllo sullo stato login poiché un utente 
// può accedere al catalogo in modo anonimo ma per effettuare acquisti 
// dovrà necessariamente identificarsi


if(isset($_SESSION['statoLogin']) === false) { //se l'utente non è loggato

}elseif(isset($_SESSION['tipo_utente'])){ // se l'utente è già loggato e il suo ruolo è admin o gestore, reindirizzalo alla home

    if($_SESSION['tipo_utente'] === 'admin' || $_SESSION['tipo_utente'] === 'gestore'){
        header("Location: home.php");
        exit();
    }
}


// caricamento dei giochi dal file XML
$xml = simplexml_load_file('../xml/giochi.xml'); // Carica il file XML

$giochi = json_decode(json_encode($xml), true); // convertiamo l'XML in un array

// filtraggio dei giochi in offerta
$giochiInOfferta = array_filter($giochi['gioco'], function($gioco) {
    return $gioco['prezzo_attuale'] < $gioco['prezzo_originale'];
});

// se utente è un admin lo reindirizziamo alla home
if (isset($_SESSION['ruolo']) && $_SESSION['ruolo'] === 'admin') {
    header('Location: home.php');
    exit();
}

// gestione dell'aggiunta al carrello
if(isset($_POST['aggiungi_al_carrello']) && isset($_POST['codice_gioco'])) {
    if(!isset($_SESSION['username'])) {

        // se l'utente non è loggato, reindirizza al login
        header('Location: login.php');
        exit();
    }
    
    $codice_gioco = $_POST['codice_gioco'];
    $username = $_SESSION['username'];
    
    // reindirizza al carrello dopo l'aggiunta
    header('Location: carrello.php');
    exit();
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
$genere = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$editore = isset($_GET['editore']) ? $_GET['editore'] : '';

// filtraggio dei giochi in offerta
if ($genere) {
    $giochiInOfferta = array_filter($giochiInOfferta, function($gioco) use ($genere) {
        return $gioco['categoria'] === $genere;
    });
}
if ($editore) {
    $giochiInOfferta = array_filter($giochiInOfferta, function($gioco) use ($editore) {
        return $gioco['nome_editore'] === $editore;
    });
}

// Genera gli editori disponibili in base ai giochi filtrati
$editoriDisponibili = array_unique(array_column($giochiInOfferta, 'nome_editore'));

// ordinamento dei giochi in offerta
usort($giochiInOfferta, function($a, $b) use ($ordinamento, $direzione) {
    if ($ordinamento === 'prezzo') {
        $prezzoA = $a['prezzo_attuale'] ?? $a['prezzo_originale'];
        $prezzoB = $b['prezzo_attuale'] ?? $b['prezzo_originale'];
        return $direzione === 'ASC' ? $prezzoA <=> $prezzoB : $prezzoB <=> $prezzoA;
    } elseif ($ordinamento === 'data_rilascio') {
        return $direzione === 'ASC' ? $a['data_rilascio'] <=> $b['data_rilascio'] : $b['data_rilascio'] <=> $a['data_rilascio'];
    } else {    // ordinamento per titolo
        return $direzione === 'ASC' ? strcmp($a['titolo'], $b['titolo']) : strcmp($b['titolo'], $a['titolo']);
    }
});

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutti gli Articoli del Negozio</title>
    <link rel="stylesheet" href="../css/giochi.css">
    <link rel="stylesheet" href="../css/menu.css">
    <script src="../js/filtri.js"></script>
</head>
<body>
    <?php include('menu.php'); ?>

    <header class="shop-header">
        <h1>Tutti gli Articoli in Offerta</h1>
    </header>

    <div class="filtri-sezione">
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
                <span class="filtro-label">Genere:</span>
                <select class="filtro-select" id="genere" onchange="applicaFiltri()">
                    <option value="">Tutti i generi</option>
                    <?php 
                    // andiamo a generarr le opzioni per i generi dal file XML
                    $generi = array_unique(array_column($giochi['gioco'], 'categoria'));
                    foreach ($generi as $genereOpzione): ?>
                        <option value="<?php echo htmlspecialchars($genereOpzione); ?>"
                                <?php echo isset($_GET['categoria']) && $_GET['categoria'] === $genereOpzione ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($genereOpzione); ?>
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
                    $editori = array_unique(array_column($giochiInOfferta, 'nome_editore'));
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
    <div class="product-grid">
        <?php
        if (!empty($giochiInOfferta)) {
            foreach ($giochiInOfferta as $gioco) {
                ?>
                <div class="product-item">
                    <a href="dettaglio_gioco.php?id=<?php echo $gioco['codice']; ?>">
                        <img src="<?php echo htmlspecialchars($gioco['immagine']); ?>" 
                             alt="<?php echo htmlspecialchars($gioco['titolo']); ?>">
                    </a>
                    <h2><?php echo htmlspecialchars($gioco['titolo']); ?></h2>
                    <p class="descrizione"><?php echo htmlspecialchars($gioco['descrizione']); ?></p>
                    
                    <div style="display: flex; flex-direction: column; align-items: center; height: 90px; justify-content: center;">
                        <div style="display: flex; flex-direction: column; align-items: center;">
                            <span style="font-size: 1.4em; color: #2ecc71; font-weight: bold;"><?php echo $gioco['prezzo_originale']; ?> crediti</span>
                            <br />
                            <span style="font-size: 1.2em; color: #999; text-decoration: line-through;"><?php echo $gioco['prezzo_attuale']; ?> crediti</span>
                        </div>
                    </div>
                    
                    <form method="GET" action="dettaglio_gioco.php">
                        <input type="hidden" name="id" value="<?php echo $gioco['codice']; ?>">
                        <button type="submit" class="btn-acquista">Visualizza Dettagli</button>
                    </form>
                </div>
                <?php 
            }
        } else {
            echo "<p>Nessun gioco in offerta trovato</p>";
        }  
        ?>
    </div>
    <script>
        function applicaFiltri() {
            const ordinamento = document.getElementById('ordinamento').value;
            const direzione = document.getElementById('direzione').value;
            const genere = document.getElementById('genere').value;
            const editore = document.getElementById('editore').value;

            const url = new URL(window.location.href);
            url.searchParams.set('ordinamento', ordinamento);
            url.searchParams.set('direzione', direzione);
            url.searchParams.set('categoria', genere);
            url.searchParams.set('editore', editore);
            
            window.location.href = url.toString();
        }
    </script>
    
</body>
</html>
