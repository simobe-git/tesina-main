<?php
session_start();

// caricamento del file XML
$xml_file = '../xml/faq.xml';
$xml = simplexml_load_file($xml_file);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>FAQ - Domande Frequenti</title>
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="../css/menu.css">
    <style>
        .container {
            margin-top: 100px; 
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .faq-item {
            background: #f8f9fa;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .faq-domanda {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 1.1em;
        }
        .faq-risposta {
            color: #34495e;
            line-height: 1.6;
        }
        .faq-data {
            color: #7f8c8d;
            font-size: 0.9em;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <?php include('menu.php'); ?>

    <div class="container">
        <h1>Domande Frequenti (FAQ)</h1>

        <?php if ($xml): foreach ($xml->faq as $faq): ?>
            <div class="faq-item">
                <div class="faq-domanda">
                    D: <?php echo htmlspecialchars($faq->domanda); ?>
                </div>
                <div class="faq-risposta">
                    R: <?php echo htmlspecialchars($faq->risposta); ?>
                </div>
                <div class="faq-data">
                    Aggiornato il: <?php echo date('d/m/Y', strtotime($faq->data_creazione)); ?>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>
</body>
</html>
