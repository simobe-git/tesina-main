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

<!--Titolo con pulsante Logout -->
    <header class="header">
        <h1>Dashboard Admin</h1>
        <nav>
            <ul>
                <li><a href="logout.php" class="logout-link">Logout</a></li>
            </ul>
        </nav>
    </header>

<!-- Funzionalità gestite -->
    <main class="dashboard-container">
        <div class="row">
            <!-- Richiesta degli utenti per diventare Admin-->
            <section class="users-management">
                <h2>Gestione Utenti</h2>
                <button onclick="location.href='gestione_utenti.php'" class="modify-user-button">Modifica Utente</button>
            </section>

            <!-- Creazione di una FAQ-->
            <section class="faq-management">
                <h2>Gestione FAQ</h2>
                <button onclick="location.href='gestione_faq.php'" class="create-faq-button">Crea FAQ</button>
            </section>
        </div>
        <div class="row">
            <!-- Richieste acquisto numero personalizzati di crediti -->
            <section class="credits-management">
                <h2>Gestione Crediti</h2>
                <button onclick="location.href='gestione_crediti.php'" class="add-credits-button">Richieste Crediti</button>
            </section>

            <!-- Ban o riattivazione account utente -->
            <section class="ban-management">
                <h2>Gestione Ban Utenti</h2>
                <button onclick="location.href='gestione_utenti.php'" class="ban-user-button">Ban Utente</button>
            </section>
        </div>
    </main>
</body>
<script>
        function openEditForm(username, email, nome, cognome) {
            document.getElementById('edit-username').value = username;
            document.getElementById('edit-email').value = email;
            document.getElementById('edit-nome').value = nome;
            document.getElementById('edit-cognome').value = cognome;
            document.getElementById('popup-overlay').style.display = 'flex';
        }

        function closeEditForm() {
            document.getElementById('popup-overlay').style.display = 'none';
        }
    </script>
</html>
