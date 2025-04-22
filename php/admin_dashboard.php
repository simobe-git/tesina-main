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
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>

<header class="header">
    <h1>Dashboard Admin</h1>
    <nav>
        <ul>
            <li><a href="logout.php" class="logout-link">Logout</a></li>
        </ul>
    </nav>
</header>

<main class="dashboard-container">
    <div class="row">
        <section class="users-management">
            <h2>Modifica i dati e lo stato degli utenti</h2>
            <button onclick="location.href='gestione_utenti.php'" class="modify-user-button">Modifica Utente</button>
        </section>

        <section class="faq-management">
            <h2>Aggiunta, eliminazione o modifica delle FAQ</h2>
            <button onclick="location.href='gestione_faq.php'" class="create-faq-button">Gestisci FAQ</button>
        </section>
    </div>

    <div class="row">
        <section class="credits-management">
            <h2>Rispondi alle richieste di crediti</h2>
            <button onclick="location.href='gestione_crediti.php'" class="add-credits-button">Richieste crediti</button>
        </section>

        <section class="gestione-gestori">
            <h2>Gestisci richieste per diventare gestore</h2>
            <div style="text-align: center;">
                <button onclick="location.href='gestione_richiestaGestore.php'" class="manage-requests-button">Gestisci richieste</button>
            </div>
        </section>
    </div>
</main>

</body>
</html>
