<?php
session_start();
include('connessione.php');

// verifica se l'utente è loggato e se è un gestore
if (!isset($_SESSION['statoLogin'])) {
    header("Location: login.php");
    exit();
} elseif ($_SESSION['tipo_utente'] !== 'gestore') {
    header("Location: home.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<style>
    .header {
        background-color: tomato;
    }
</style>
<body>

<header class="header">
    <h1>Dashboard Gestore</h1>
</header>

<main class="dashboard-container">
    <div class="row">
        <section class="users-management">
            <h2>Modifica Giochi e Offerte nel Catalogo</h2>
            <button onclick="location.href='gestione_catalogo.php'" class="bottone">Gestione Giochi</button>
        </section>

        <section class="faq-management">
            <h2>Modifica Sconti e Bonus</h2>
            <button onclick="location.href='gestione_sconti_admin.php'" class="bottone">Gestione Sconti o Bonus</button>
        </section>
    </div>
    <div class="row">
        <section class="credits-management">
            <h2>Visualizza Utenti o Aggiungi Avatar</h2>
            <button onclick="location.href='visualizza_utenti.php'" class="bottone">Visualizza Utenti</button>
        </section>

        <section class="ban-management">
            <h2>Gestione Forum</h2>
            <button onclick="location.href=''" class="bottone">Gestione dei Forum</button>
        </section>
    </div>
    
    <!-- Pulsante di logout centrato -->
    <div style="text-align: center; margin-top: 20px;">
        <a href="logout.php" class="logout-link" style="display: inline-block; padding: 10px 20px; background-color: tomato; color: white; border-radius: 5px; text-decoration: none;">Logout</a>
    </div>
</main>
</body>
</html>