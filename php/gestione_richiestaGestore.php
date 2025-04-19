<?php
session_start();
include('connessione.php');

// Attiva l'errore reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
            'status' => 'in attesa', // imposta lo stato come "in attesa"
        ];
    }
}
echo "ciao";

// gestione della risposta alla richiesta
if ($action) {
    echo "Richiesta POST ricevuta<br>"; // Messaggio di debug
    $username = trim($_POST['username']);
    $action = $_POST['action'];

    echo "Username: $username, Action: $action<br>"; // Messaggio di debug

    if ($action === 'promuovi') {
        // aggiornamento del tipo utente nel database
        $stmt = $connessione->prepare("UPDATE utenti SET tipo_utente = 'gestore' WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();

        // rimuoviamo la richiesta dal file XML
        foreach ($xml->richiesta as $key => $richiesta) {
            echo "Controllo richiesta: " . (string)$richiesta->username . "<br>"; // Messaggio di debug
            if ((string)$richiesta->username === $username) {
                unset($xml->richiesta[$key]);
                echo "Richiesta di $username rimossa.<br>"; // Messaggio di debug
                break; // Esci dal ciclo dopo aver trovato e rimosso la richiesta
            }
        }
        $xml->asXML($xml_file); // Salva le modifiche nel file XML

        // e mostriamo un messaggio di successo
        echo "<script>alert('Promozione a gestore avvenuta con successo');</script>";
        echo "<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>";
    } elseif ($action === 'rifiuta') {
        // Rimuoviamo la richiesta dal file XML
        foreach ($xml->richiesta as $key => $richiesta) {
            echo "Controllo richiesta: " . (string)$richiesta->username . "<br>"; // Messaggio di debug
            if ((string)$richiesta->username === $username) {
                unset($xml->richiesta[$key]);
                echo "Richiesta di $username rifiutata.<br>"; // Messaggio di debug
                break; // Esci dal ciclo dopo aver trovato e rimosso la richiesta
            }
        }
        $xml->asXML($xml_file); // Salva le modifiche nel file XML

        // e mostriamo un messaggio di successo
        echo "<script>alert('Richiesta rifiutata con successo');</script>";
        echo "<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>";
    } elseif ($action === 'declassa') {
        // Logica per declassare a cliente
        $stmt = $connessione->prepare("UPDATE utenti SET tipo_utente = 'cliente' WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        echo "<script>alert('Declassamento a cliente avvenuto con successo');</script>";
        echo "<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>";
    }

    // ricaricamento pagina per vedere le modifiche
    header("Location: gestione_richiestaGestore.php");
    exit();
}else{
    echo "</br>here";
}

echo "</br>";
echo "ciao3";

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
                    <tr>
                        <td style="padding: 8px; text-align: center; font-size: 1.3em; color: blue;"><?php echo htmlspecialchars($richiesta['username']); ?></td>
                        <td style="padding: 8px; text-align: center; font-size: 1.1em;"><?php echo htmlspecialchars($richiesta['data']); ?></td>
                        <td style="padding: 8px; text-align: center; font-size: 1.1em;">cliente</td>
                        <td style="padding: 8px; text-align: center; font-size: 1.1em;">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="username" value="<?php echo htmlspecialchars($richiesta['username']); ?>">
                                <button type="submit" name="action" value="promuovi">Promuovi a Gestore</button>
                                <button type="submit" name="action" value="rifiuta" style="background-color: red; color: white;">Rifiuta Richiesta</button>
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