<?php
session_start();
include('connessione.php');

// verifica se l'utente è loggato e se è un admin
if (!isset($_SESSION['statoLogin'])) {
    header("Location: login.php");
    exit();
} elseif ($_SESSION['tipo_utente'] !== 'admin') {
    header("Location: home.php");
    exit();
}

// caricamento file XML contenente le richieste 
$xml_file = '../xml/richieste_gestore.xml';
$richieste = [];
if (file_exists($xml_file)) {
    $xml = simplexml_load_file($xml_file);
    foreach ($xml->richiesta as $richiesta) {
        $richieste[] = [
            'username' => (string)$richiesta->username,
            'data' => (string)$richiesta->data,
            'status' => (string) $richiesta->status //valore aggiunto dalla pagina profilo.php
        ];
    }
}

/**
 * Quando la richiesta per diventare gestore viene rifutata modifichiamo nel file xml il campo 
 * status inserendo il valore "rifiutata" per poi mostrare un messaggio di modifica avvenuta e salvare il file xml.
*/
if (isset($_POST['action']) && $_POST['action'] === 'rimuovi') {
    
    $username = $_POST['username'];
    $xml = simplexml_load_file($xml_file);

    foreach ($xml->richiesta as $key => $richiesta) { 
        if ($richiesta->username == $username && $richiesta->status == 'attesa') { //ricerca per username e status
            $richiesta->status = 'rifiutata';   //modifica status
            $xml->asXML($xml_file); 
            break;
        }
    }
    // Mostra un messaggio di successo
    echo "<script>alert('Richiesta rifiutata con successo');</script>";
    echo "<script>setTimeout(function(){ window.location.href= 'gestione_richiestaGestore.php'; }, 2000);</script>";
    exit();
}


// gestione della risposta alla richiesta
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $action = $_POST['action'];

    // gestiamo l'azione di accettazione della richiesta
    if ($action === 'promuovi') {
        // aggiornamento del tipo utente nel database
        $stmt = $connessione->prepare("UPDATE utenti SET tipo_utente = 'gestore' WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();

        // aggiornamento status richiesta file xml (utile per mostrare che la richiesta è stata accettata)
        $xml = simplexml_load_file($xml_file);

        foreach ($xml->richiesta as $richiesta) {
            if ($richiesta->username == $username) {
                $richiesta->status = 'accettata'; //modifica status
                break;
            }
        }
        

        // e mostriamo un messaggio di successo
        echo "<script>alert('Promozione a gestore avvenuta con successo');</script>";
        echo "<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>";
    } elseif ($action === 'declassa') {
        // Aggiorna il tipo utente nel database per declassare a cliente
        $stmt = $connessione->prepare("UPDATE utenti SET tipo_utente = 'cliente' WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();

        // mostriamo un messaggio di successo
        echo "<script>alert('Declassamento a cliente avvenuto con successo');</script>";
        echo "<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>";
    }

    // ricaricamento pagina per vedere le modifiche
    header("Location: gestione_richiestaGestore.php");
    exit();
}

// carichiamo i ruoli degli utenti dal db
$ruoli = [];
$query = "SELECT username, tipo_utente FROM utenti"; 
$result = $connessione->query($query);
while ($row = $result->fetch_assoc()) {
    $ruoli[$row['username']] = $row['tipo_utente'];
}

// recuperiamo tutti i gestori dal database per mostrarli a schermo
$gestori = [];
$query_gestori = "SELECT username FROM utenti WHERE tipo_utente = 'gestore'";
$result_gestori = $connessione->query($query_gestori);
while ($row = $result_gestori->fetch_assoc()) {
    $gestori[] = $row['username'];
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Richieste Gestore</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .navbar {
            background-color: green; 
            color: #fff; 
            padding: 25px 0; 
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
            <li><a href="gestione_utenti.php">Gestione Utenti</a></li>
            <li><a href="gestione_faq.php">Gestione FAQ</a></li>
            <li><a href="gestione_crediti.php">Richieste Crediti</a></li>
        </ul>
    </div>

    <div style="text-align: right; margin-right: 2%; margin-top: 20px;">
        <a href="logout.php" class="logout-link" style="display: inline-block; padding: 12px 25px; background-color: #ff4d4d; color: white; border-radius: 5px; text-decoration: none; font-size: 1.2em;">Logout</a>
    </div>

    <main class="dashboard-container" style="margin-top: -5ex;">
        <h2 style="text-align: center; color: red;">Gestisci le richieste arrivate</h2>
        <h2 style="text-align: center; color: red;">Promuovi a Gestore oppure declassa a Cliente</h2>
        <table class="table-requests" style="width: 100%; border-collapse: collapse; margin-top: 1ex;">
            <thead>
                <tr>
                    <th style="text-align: center; font-size: 1.2em;">Username</th>
                    <th style="text-align: center; font-size: 1.2em;">Data Richiesta</th>
                    <th style="text-align: center; font-size: 1.2em;">Ruolo attuale</th>
                    <th style="text-align: center; font-size: 1.2em;">Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($richieste as $richiesta): ?>
                    <?php if ($richiesta['status'] === 'rifiutata') continue; // Salta le richieste rifiutate ?>
                    <tr>
                        <td style="padding: 8px; text-align: center; font-size: 1.3em; color: blue;"><?php echo htmlspecialchars($richiesta['username']); ?></td>
                        <td style="padding: 8px; text-align: center; font-size: 1.1em;"><?php echo htmlspecialchars($richiesta['data']); ?></td>
                        <td style="padding: 8px; text-align: center; font-size: 1.1em;"><?php echo htmlspecialchars('cliente'); ?></td>
                        <td style="padding: 8px; text-align: center; font-size: 1.1em;">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="username" value="<?php echo htmlspecialchars($richiesta['username']); ?>">
                                <button type="submit" name="action" value="promuovi">Promuovi a Gestore</button>
                                <button type="submit" name="action" value="rimuovi" style="background-color: red; color: white;">Rifiuta richiesta</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2 style="text-align: center; color: red; margin-top: 6ex;">Gestori Attuali</h2>
        <table class="table-gestori" style="width: 100%; border-collapse: collapse; margin-top: 1ex;">
            <thead>
                <tr>
                    <th style="text-align: center; font-size: 1.2em;">Username</th>
                    <th style="text-align: center; font-size: 1.2em;">Ruolo attuale</th>
                    <th style="text-align: center; font-size: 1.2em;">Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($gestori as $gestore): ?>
                    <tr>
                        <td style="padding: 8px; text-align: center; font-size: 1.3em; color: blue;"><?php echo htmlspecialchars($gestore); ?></td>
                        <td style="padding: 8px; text-align: center; font-size: 1.1em;">Gestore</td>
                        <td style="padding: 8px; text-align: center; font-size: 1.1em;">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="username" value="<?php echo htmlspecialchars($gestore); ?>">
                                <button type="submit" name="action" value="declassa" style="background-color: red; color: white;">Declassa a Cliente</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
</body>
</html>
