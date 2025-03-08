<?php
session_start();
require_once('connessione.php');
require_once('funzioni_sconti_bonus.php');

$xml_file = '../xml/sconti_bonus.xml';
$sconti_bonus = [];
if (file_exists($xml_file)) {
    $sconti_bonus = simplexml_load_file($xml_file);
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Offerte e Bonus</title>
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .sconto-item, .bonus-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Offerte e Bonus Attivi</h1>
        
        <h2>Sconti</h2>
        <?php if (isset($sconti_bonus->sconti)): foreach($sconti_bonus->sconti->sconto as $sconto): ?>
            <div class="sconto-item">
                <p>Sconto del <?php echo $sconto->percentuale; ?>% - 
                   Tipo: <?php echo $sconto->tipo; ?></p>
            </div>
        <?php endforeach; endif; ?>
        
        <h2>Bonus</h2>
        <?php if (isset($sconti_bonus->bonus)): foreach($sconti_bonus->bonus->bonus as $bonus): ?>
            <div class="bonus-item">
                <p>Bonus di <?php echo $bonus->crediti; ?> crediti - 
                   Gioco: <?php echo $bonus->codice_gioco; ?></p>
            </div>
        <?php endforeach; endif; ?>
    </div>
</body>
</html>
