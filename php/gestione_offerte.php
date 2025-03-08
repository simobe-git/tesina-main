<?php
session_start();
require_once('connessione.php');

// verifica che l'utente sia un gestore
if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'gestore') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['azione'])) {
        switch ($_POST['azione']) {
            case 'aggiungi':
                // logica per aggiungere un'offerta
                $query = "INSERT INTO videogiochi (nome, prezzo_originale, prezzo_attuale, genere, data_rilascio, nome_editore, descrizione, immagine) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $connessione->prepare($query);
                $stmt->bind_param("sddssss", 
                    $_POST['nome'],
                    $_POST['prezzo_originale'],
                    $_POST['prezzo_attuale'],
                    $_POST['genere'],
                    $_POST['data_rilascio'],
                    $_POST['nome_editore'],
                    $_POST['descrizione'],
                    $_POST['immagine']
                );
                $stmt->execute();
                break;

            case 'modifica':
                // logica per modificare un'offerta
                $query = "UPDATE videogiochi SET 
                         nome = ?, 
                         prezzo_originale = ?,
                         prezzo_attuale = ?,
                         genere = ?,
                         data_rilascio = ?,
                         nome_editore = ?,
                         descrizione = ?,
                         immagine = ?
                         WHERE codice = ?";
                $stmt = $connessione->prepare($query);
                $stmt->bind_param("sddssssi", 
                    $_POST['nome'],
                    $_POST['prezzo_originale'],
                    $_POST['prezzo_attuale'],
                    $_POST['genere'],
                    $_POST['data_rilascio'],
                    $_POST['nome_editore'],
                    $_POST['descrizione'],
                    $_POST['immagine'],
                    $_POST['codice']
                );
                $stmt->execute();
                break;

            case 'elimina':
                // logica per eliminare un'offerta
                $query = "DELETE FROM videogiochi WHERE codice = ?";
                $stmt = $connessione->prepare($query);
                $stmt->bind_param("i", $_POST['codice']);
                $stmt->execute();
                break;
        }
    }
}

// recuperiamo tutte le offerte
$query = "SELECT * FROM videogiochi ORDER BY nome";
$risultato = $connessione->query($query);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Offerte</title>
    <link rel="stylesheet" href="../css/menu.css">
    <link rel="stylesheet" href="../css/gestione_offerte.css">
</head>
<body>
    <?php include('menu.php'); ?>

    <div class="container">
        <h1>Gestione Offerte</h1>

        <!-- form per aggiungere/modificare offerte -->
        <form id="offertaForm" method="POST" class="form-offerta">
            <input type="hidden" name="azione" value="aggiungi">
            <input type="hidden" name="codice" id="codiceGioco">

            <div class="form-group">
                <label for="nome">Nome Gioco:</label>
                <input type="text" id="nome" name="nome" required>
            </div>

            <div class="form-group">
                <label for="prezzo_originale">Prezzo Originale:</label>
                <input type="number" id="prezzo_originale" name="prezzo_originale" step="0.01" required>
            </div>

            <div class="form-group">
                <label for="prezzo_attuale">Prezzo Attuale:</label>
                <input type="number" id="prezzo_attuale" name="prezzo_attuale" step="0.01" required>
            </div>

            <button type="submit">Salva Offerta</button>
        </form>

        <table class="tabella-offerte">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Prezzo Originale</th>
                    <th>Prezzo Attuale</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($gioco = $risultato->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($gioco['nome']); ?></td>
                        <td>€<?php echo number_format($gioco['prezzo_originale'], 2); ?></td>
                        <td>€<?php echo number_format($gioco['prezzo_attuale'], 2); ?></td>
                        <td>
                            <button onclick="modificaOfferta(<?php echo $gioco['codice']; ?>)">Modifica</button>
                            <button onclick="eliminaOfferta(<?php echo $gioco['codice']; ?>)">Elimina</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <script src="../js/gestione_offerte.js"></script>
</body>
</html>