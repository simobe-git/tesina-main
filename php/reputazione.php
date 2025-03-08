<?php
session_start();
require_once('funzioni_reputazione.php');

// se viene richiesto un utente specifico
$username_richiesto = isset($_GET['username']) ? $_GET['username'] : null;

// se l'utente è loggato, mostra la sua reputazione
$utente_corrente = isset($_SESSION['username']) ? $_SESSION['username'] : null;

// ottieniamo i migliori recensori
$migliori_recensori = getMiglioriRecensori(5);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Sistema Reputazione</title>
    <style>
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .reputazione-card {
            background: #f8f9fa;
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .punteggio {
            font-size: 2em;
            color: #28a745;
            margin: 10px 0;
        }
        .stelle {
            color: #ffc107;
            font-size: 1.5em;
        }
        .migliori-recensori {
            margin-top: 30px;
        }
        .recensore {
            background: #fff;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        .badge {
            background: #007bff;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8em;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($utente_corrente): ?>
            <div class="reputazione-card">
                <h2>La tua Reputazione</h2>
                <?php
                $reputazione = calcolaReputazione($utente_corrente);
                $stelle = str_repeat('★', round($reputazione['punteggio'])) . 
                         str_repeat('☆', 5 - round($reputazione['punteggio']));
                ?>
                <div class="punteggio">
                    <?php echo $reputazione['punteggio']; ?>/5.00
                </div>
                <div class="stelle"><?php echo $stelle; ?></div>
                <p>Basato su <?php echo $reputazione['numero_giudizi']; ?> giudizi ricevuti</p>
            </div>
        <?php endif; ?>

        <?php if ($username_richiesto && $username_richiesto !== $utente_corrente): ?>
            <div class="reputazione-card">
                <h2>Reputazione di <?php echo htmlspecialchars($username_richiesto); ?></h2>
                <?php
                $reputazione = calcolaReputazione($username_richiesto);
                $stelle = str_repeat('★', round($reputazione['punteggio'])) . 
                         str_repeat('☆', 5 - round($reputazione['punteggio']));
                ?>
                <div class="punteggio">
                    <?php echo $reputazione['punteggio']; ?>/5.00
                </div>
                <div class="stelle"><?php echo $stelle; ?></div>
                <p>Basato su <?php echo $reputazione['numero_giudizi']; ?> giudizi ricevuti</p>
            </div>
        <?php endif; ?>

        <div class="migliori-recensori">
            <h2>Migliori Recensori</h2>
            <?php foreach ($migliori_recensori as $recensore): ?>
                <div class="recensore">
                    <h3><?php echo htmlspecialchars($recensore['username']); ?></h3>
                    <div class="stelle">
                        <?php 
                        echo str_repeat('★', round($recensore['reputazione'])) . 
                             str_repeat('☆', 5 - round($recensore['reputazione']));
                        ?>
                    </div>
                    <p>
                        Reputazione: <?php echo $recensore['reputazione']; ?>/5.00
                        <span class="badge">
                            <?php echo $recensore['numero_giudizi']; ?> giudizi ricevuti
                        </span>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
