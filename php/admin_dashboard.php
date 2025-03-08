<?php
session_start();
include('connessione.php');
/*
Operazioni che un admin può fare nella dashboard:
    - Vede/modifica i dati anagrafici, username e password degli utenti.
    - (FATTA) Disattiva (banna) e riattiva utenti.
    - (FATTA) Accetta richieste di crediti.
    - Eleva una domanda (e la risposta migliore, o quella scelta dall'admin) nelle FAQ.

La funzione per poter far diventare un utente un admin è stata rimossa, in quanto sembra non essere richiesta tale funzionalità.
*/

// verifica se l'utente è loggato e se è un admin
if (!isset($_SESSION['statoLogin'])) {
    header("Location: login.php");
    exit();

}elseif($_SESSION['tipo_utente'] !== 'admin'){
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
    </header>

    <main class="dashboard-container">
        <div class="row">
            <section class="users-management">
                <h2>Modifica i dati personali degli utenti</h2>
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

            <section class="ban-management">
                <h2>Disattiva/Attiva gli utenti</h2>
                <button onclick="location.href='gestione_utenti.php'" class="ban-user-button">Ban utenti</button>
            </section>
        </div>
        
        <!-- Pulsante di logout centrato -->
        <div style="text-align: center; margin-top: 20px;">
            <a href="logout.php" class="logout-link" style="display: inline-block; padding: 10px 20px; background-color: #ff4d4d; color: white; border-radius: 5px; text-decoration: none;">Logout</a>
        </div>
    </main>
</body>
</html>
