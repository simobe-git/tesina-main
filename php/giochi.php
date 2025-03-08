<?php

session_start();
// non viene eseguito il controllo sullo stato login poiché un utente 
// può accedere al catalogo in modo anonimo ma per effettuare acquisti 
// dovrà necessariamente identificarsi

require_once("connessione.php");

// verifica se l'utente è loggato e se è un cliente
if (!isset($_SESSION['statoLogin'])) {
    header("Location: login.php");
    exit();

}elseif($_SESSION['tipo_utente'] !== 'cliente'){
    header("Location: home.php");
    exit();
}


$sql = "SELECT * FROM videogiochi";
$result = mysqli_query($connessione,$sql);

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutti gli Articoli del Negozio</title>
    <link rel="stylesheet" href="../css/giochi.css">
</head>
<body>
    <!-- barra di navigazione -->
    <nav class="navbar">
        <div class="logo">
            <a href="#">GameShop</a>
        </div>
        <ul class="nav-links">
            <li><a href="home.php">Home</a></li>
            <li><a href="offerte.php">Offerte</a></li>
            <?php if(isset($_SESSION['statoLogin'])) : ?>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
            <?php endif; ?>
            <li><a href="contatti.php">Contatti</a></li>
        </ul>
        <div class="hamburger-menu">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </nav>
    
    <header class="shop-header">
        <h1>Tutti gli Articoli</h1>
    </header>

    <div class="product-grid">
        <?php
            if(mysqli_num_rows($result) > 0){

                while($row = mysqli_fetch_assoc($result)){
                    
                    echo '<div class="product-item">';

                    // mostriamo l'immagine richiamando il link nel database
                    echo '<img src="' . $row['immagine'] . '" alt="' . $row['nome'] . '">';                                    
                    
                    echo '<h3>' . $row['nome'] . '</h3>';
                    echo '<p class="price">';

                    // se il prezzo non subisce alcuno sconto stampo quello di partenza 
                    // altrimenti mostro la variazione di prezzo da originale ad attuale
                    if($row['prezzo_attuale'] == $row['prezzo_originale']){
                        echo ' <span class="current-price">€ ' . $row['prezzo_originale'] . '</span>';
                    }else{
                        
                        echo '<span class="original-price">€ ' . $row['prezzo_originale'] . '</span>';
                        echo ' <span class="current-price">€ ' . $row['prezzo_attuale'] . '</span>';
                    }

                    echo '</p>';

                    // passaggio del codice di ogni gioco 
                    // in modo da potervi creare una pagina per le informazioni  
                    echo '<a href="pagina-videogioco.php?id='.$row['codice'].'" class="btn-acquista">Acquista</a>';
                    echo '</div>';

                }
            }else{
                echo '<p>Nessun prodotto trovato</p>';
            }
        ?>
    </div>
    
</body>
</html>
