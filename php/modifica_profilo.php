<?php
session_start();
require_once('connessione.php');

// verifica se l'utente è loggato e se è un cliente
if (!isset($_SESSION['statoLogin'])) {
    header("Location: login.php");
    exit();

}elseif($_SESSION['tipo_utente'] !== 'cliente'){
    header("Location: home.php");
    exit();
}


// recuperiamo i dati dell'utente dal database
$query = "SELECT * FROM utenti WHERE username = ?";
$stmt = $connessione->prepare($query);
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$dati_utente = $stmt->get_result()->fetch_assoc();

// funzione per ottenere l'avatar attuale
function getAvatarUtente($username) {
    $xml_file = '../xml/avatar.xml';
    if (file_exists($xml_file)) {
        $xml = simplexml_load_file($xml_file);
        foreach ($xml->utente as $utente) {
            if ((string)$utente->username === $username) {
                return isset($utente->avatar) ? (string)$utente->avatar : null;
            }
        }
    }
    return null;
}

// funzione per ottenere tutti gli avatar disponibili
function getAvatarDisponibili() {
    $avatar_dir = '../isset/avatar/';
    $avatars = [];

    // controllo se directory esiste
    if (is_dir($avatar_dir)) {
        // scansiona la directory per ottenere i file
        $files = scandir($avatar_dir);

        // filtra solo i file immagine (es. jpg, png, gif)
        foreach ($files as $file) {
            if (in_array(pathinfo($file, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif'])) {
                $avatars[] = $file; // restituisce solo il nome del file
            }
        }
    }

    return $avatars;
}

// funzione per aggiornare l'avatar nel file XML
function updateAvatarXML($username, $nuovo_avatar) {
    $xml_file = '../xml/avatar.xml';

    // verifica se l'utente esiste nel file XML
    $xml = simplexml_load_file($xml_file);
    if ($xml === false) {
        error_log("Errore nel caricamento del file XML");
        return false;
    }

    $utente_trovato = false;
    foreach ($xml->utente as $utente) {
        if ((string)$utente->username === $username) {
            $utente_trovato = true;

            // se l'utente non ha un tag avatar, lo creiamo
            if (!isset($utente->avatar)) {
                $utente->addChild('avatar', $nuovo_avatar);
            } else {

                // altrimenti aggiorniamo quello esistente
                $utente->avatar = $nuovo_avatar;
            }
            break;
        }
    }

    // se l'utente non è stato trovato, lo aggiungiamo
    if (!$utente_trovato) {
        $nuovo_utente = $xml->addChild('utente');
        $nuovo_utente->addChild('username', $username);
        $nuovo_utente->addChild('ruolo', 'cliente');
        $nuovo_utente->addChild('avatar', $nuovo_avatar);
    }

    // salviamo il file XML con giusta formattazione
    $dom = new DOMDocument('1.0');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($xml->asXML());
    return $dom->save($xml_file);
}

// gestione del cambio avatar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cambia_avatar'])) {
    $nuovo_avatar = $_POST['nuovo_avatar'];
    if (updateAvatarXML($_SESSION['username'], $nuovo_avatar)) {
        $messaggio_successo = "Avatar aggiornato con successo!";
    } else {
        $errore = "Errore nell'aggiornamento dell'avatar.";
    }
}

$avatar_attuale = getAvatarUtente($_SESSION['username']);
$avatars_disponibili = getAvatarDisponibili();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Modifica Profilo - GameShop</title>
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="../css/menu.css">
    <style>
        .container { 
            margin-top: 100px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
            padding: 20px;
        }
        
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }
        
        .left-column {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 8px;
        }
        
        .dati-personali {
            margin-bottom: 40px;
            text-align: center;
        }
        
        .dati-personali p {
            margin: 15px 0;
            font-size: 1.1em;
        }
        
        .password-section {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
        
        .btn-password {
            background: #007bff;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
            margin-top: 15px;
        }
        
        .btn-password:hover {
            background: #0056b3;
        }
        
        .avatar-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .avatar-grid {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        .avatar-option {
            cursor: pointer;
            text-align: center;
            padding: 10px;
            border-radius: 8px;
            transition: background-color 0.3s;
        }
        .avatar-option:hover {
            background-color: #e9ecef;
        }
        .avatar-option input[type="radio"] {
            display: none;
        }
        .avatar-option img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid transparent;
            transition: all 0.3s;
            object-fit: cover;
        }
        .avatar-option input[type="radio"]:checked + img {
            border-color: #007bff;
            box-shadow: 0 0 10px rgba(0,123,255,0.5);
        }
        .avatar-attuale {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: #e9ecef;
            border-radius: 8px;
        }
        .avatar-attuale img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin-bottom: 15px;
            object-fit: cover;
        }
        .btn-salva {
            background: #28a745;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
            display: block;
            margin: 20px auto;
            width: 200px;
        }
        .btn-salva:hover {
            background: #218838;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <?php include('menu.php'); ?>

    <div class="container">
        <?php if (isset($messaggio_successo)): ?>
            <div class="alert alert-success"><?php echo $messaggio_successo; ?></div>
        <?php endif; ?>
        
        <?php if (isset($errore)): ?>
            <div class="alert alert-danger"><?php echo $errore; ?></div>
        <?php endif; ?>

        <h1 style="text-align: center; margin-bottom: 30px;">Modifica Profilo</h1>
        
        <div class="profile-grid">
            <div class="left-column">
                <div class="dati-personali">
                    <h2>Dati Personali</h2>
                    <p><strong>Username:</strong> <?php echo htmlspecialchars($dati_utente['username']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($dati_utente['email']); ?></p>
                    <p><strong>Nome:</strong> <?php echo htmlspecialchars($dati_utente['nome']); ?></p>
                    <p><strong>Cognome:</strong> <?php echo htmlspecialchars($dati_utente['cognome']); ?></p>
                </div>

                <div class="password-section">
                    <h2>Modifica Password</h2>
                    <p>Vuoi cambiare la password del tuo account?</p>
                    <a href="modifica_password.php" style="text-decoration: none;" class="btn-password">Modifica Password</a>
                </div>
            </div>

            <div class="avatar-section">
                <h2>Modifica Avatar</h2>
                
                <div class="avatar-attuale">
                    <h3>Avatar Attuale</h3>
                    <?php if ($avatar_attuale): ?>
                        <img src="../isset/avatar/<?php echo htmlspecialchars($avatar_attuale); ?>" 
                             alt="Avatar attuale"
                             onerror="this.src='../isset/avatar/default_avatar.jpg';">
                    <?php else: ?>
                        <img src="../isset/avatar/default_avatar.jpg" 
                             alt="Avatar default">
                    <?php endif; ?>
                </div>

                <form method="POST" action="">
                    <h3>Scegli un nuovo avatar</h3>
                    <div class="avatar-grid">
                        <?php foreach ($avatars_disponibili as $avatar): ?>
                            <label class="avatar-option">
                                <input type="radio" name="nuovo_avatar" value="<?php echo htmlspecialchars($avatar); ?>"
                                       <?php echo ($avatar === $avatar_attuale) ? 'checked' : ''; ?>>
                                <img src="../isset/avatar/<?php echo htmlspecialchars($avatar); ?>" 
                                     alt="Avatar opzione"
                                     onerror="this.onerror=null; this.src='../isset/avatar/default_avatar.jpg';">
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" name="cambia_avatar" class="btn-salva">Salva</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>