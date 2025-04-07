<?php
session_start();
require_once('connessione.php');

// verifica se l'utente è loggato e se è un gestore
if (!isset($_SESSION['statoLogin'])) {
    header("Location: login.php");
    exit();
} elseif ($_SESSION['tipo_utente'] !== 'gestore') {
    header("Location: home.php");
    exit();
}

// Mostra tutti gli utenti
$sql = "SELECT * FROM utenti WHERE tipo_utente = 'cliente'";
$risultato = $connessione->query($sql);

$utenti = [];
if ($risultato && $risultato->num_rows > 0) {
    while ($row = $risultato->fetch_assoc()) {
        $utenti[] = $row;
    }
} else {
    $utenti = null; // Nessun utente trovato
}

// Gestione caricamento immagine
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $targetDir = "../isset/avatar/"; // Cartella di destinazione
    $fileName = basename($_FILES['avatar']['name']);
    $targetFilePath = $targetDir . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION); //estraiamo il tipo di file

    // Controlla il tipo di file
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
    if (in_array(strtolower($fileType), $allowedTypes)) {
        // Carica il file
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetFilePath)) {
            $uploadMessage = "Immagine caricata con successo nella cartella isset/avatar.";
        } else {
            $uploadMessage = "Errore durante il caricamento del file.";
        }
    } else {
        $uploadMessage = "Formato file non supportato. Sono ammessi solo JPG, JPEG, PNG e GIF.";
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
        }
        .utente-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center; 
            margin-bottom: 20px; 
            width: 100%; 
            max-width: 300px; 
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
            list-style-type: disc; /* aggiungiamo un pallino di finco le voci del menù */
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
        .title-container {
            text-align: center; 
            margin-bottom: 20px; 
        }
        .utente-grid {
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); 
            gap: 20px; 
            width: 100%; 
        }
        .btn { padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-primary { background: #007bff; color: white; }

        /*STILE FORM CAARICAMENTO IMMAGINI*/
        .upload-form {
            background: #f8f9fa; /* Colore di sfondo chiaro */
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Ombreggiatura */
            max-width: 400px;
            margin: 20px auto; /* Centra il form */
            text-align: center;
        }
        .upload-form label {
            display: block;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        .upload-form input[type="file"] {
            display: block;
            margin: 10px auto 20px auto;
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 100%;
        }
        .upload-form button {
            background: tomato;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s ease;
        }
        .upload-form button:hover {
            background: #e04c2f; /* Colore più scuro al passaggio del mouse */
        }
    </style>
</head>
<body>

    <div class="navbar">
        <ul>
            <li><a href="gestore_dashboard.php">Dashboard</a></li>
            <li><a href="gestione_utenti.php">Modifica Sconti e Bonus</a></li>
            <li><a href="visualizza_utenti.php">Visualizza Utenti</a></li>
            <li><a href="#">Gestione Forum</a></li>
        </ul>
    </div>
    
    <div class="container">
        <div class="title-container">
            <h1 style="font-size: 200%;">Visualizzazione Utenti</h1>
        </div>
        
        <div class="utente-grid"> <!-- Contenitore per le schede giochi -->
            <?php if ($utenti): ?>
                <?php foreach ($utenti as $utente): ?>
                    <div class="utente-card">

                        <h3>Nome: <?php echo htmlspecialchars($utente['nome']); ?></h3>
                        <h3>Cognome: <?php echo htmlspecialchars($utente['cognome']); ?></h3>
                        <h3>Username: <?php echo htmlspecialchars($utente['username']); ?></h3>
                        <h3>Email: <?php echo htmlspecialchars($utente['email']); ?></h3>
                        <h3>Crediti: <?php echo htmlspecialchars($utente['crediti']); ?></h3>
                        <h3>Data Registrazione:<?php echo htmlspecialchars($utente['data_registrazione']); ?></h3>
                        <h3>Ban: <?php echo $utente['ban'] == 1 ? 'Sì' : 'No'; ?></h3>
                        <form action="visualizza_profilo.php" method="POST">
                            <input type="hidden" name="username" value="<?php echo htmlspecialchars($utente['username']); ?>">
                            <button type="submit" class="btn btn-primary">Mostra Acquisti</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <h2>Nessun utente trovato</h2>
            <?php endif; ?>
        </div>
    </div>


    <div class="container">
        <div class="title-container">
            <h1 style="font-size: 200%;">Aggiungi Avatar</h1>
        </div>

        <?php if (isset($uploadMessage)): ?>
            <p style="color: green; font-weight: bold;"><?php echo $uploadMessage; ?></p>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data" class="upload-form">
            <input type="file" id="avatar" name="avatar" accept="image/*" required>
            <button type="submit" >Carica Avatar</button>
        </form>
    </div>
</body>
</html>