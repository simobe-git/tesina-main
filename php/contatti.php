<?php
session_start();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagina di Contatto</title>
    <link rel="stylesheet" href="../css/menu.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #f0f0f0;
            min-height: 100vh;
            margin: 0;
            padding-top: 60px;
        }

        .contact-page {
            display: flex;
            width: 80%;
            min-height: 80vh;
            border-radius: 10px;
            overflow: auto;
            margin: 20px auto;
        }

        .left-section {
            background-image: url('../isset/background-contact.jpg');
            background-size: cover;
            background-position: center;
            width: 50%;
            padding: 50px;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
        }

        .content h2 {
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .content p {
            font-size: 18px;
            margin-bottom: 20px;
        }

        .learn-more-btn {
            background-color: #FFC600;
            color: #000;
            padding: 10px 20px;
            text-decoration: none;
            font-weight: bold;
            border-radius: 5px;
        }

        .right-section {
            background-color: #FFC600;
            width: 50%;
            padding: 20px;
            color: #000;
            display: flex;
            justify-content: flex-start;
            align-items: flex-start;
            overflow-y: auto;
        }

        .contact-info {
            width: 100%;
            padding: 10px;
        }

        .contact-info h2 {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #000;
        }

        .contact-info p {
            font-size: 14px;
            margin-bottom: 10px;
        }

        .contact-section {
            margin-bottom: 15px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            backdrop-filter: blur(5px);
        }

        .contact-section h3 {
            color: #2196F3;
            margin-bottom: 15px;
            font-size: 20px;
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }

        .social-links a {
            padding: 8px 15px;
            background: #2196F3;
            color: white;
            text-decoration: none;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background: #1976D2;
            transform: translateY(-2px);
        }

        .support-hours {
            margin-top: 30px;
            padding: 20px;
            background: rgba(33, 150, 243, 0.1);
            border-radius: 10px;
        }

        .support-hours p {
            margin: 5px 0;
        }

        .social-section {
            margin-bottom: 30px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            backdrop-filter: blur(5px);
        }

        .social-section h3 {
            color: #2196F3;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <?php include('menu.php'); ?>

    <div class="contact-page">
        <!-- Sezione a sinistra -->
        <div class="left-section">

            <div class="content">
                <h2>Tutto quello che cerchi</h2>
                <p>Il nostro catalogo può soddisfare qualsiasi esigenza</p>
                <a href="catalogo.php" class="learn-more-btn">VAI AL CATALOGO</a>
            </div>
        </div>

        
       <div class="right-section">
            <div class="contact-info">
                <h2>Assistenza Clienti</h2>
                
                <div class="contact-section">
                    <h3>🎮 Supporto Tecnico</h3>
                    <p>Problemi con i download o l'attivazione dei giochi?</p>
                    <p>Email: supporto.tecnico@gameshop.com</p>
                    <p>Tempo di risposta medio: 24 ore</p>
                </div>

                <div class="contact-section">
                    <h3>💳 Assistenza Pagamenti</h3>   <!-- emoji inserito mediante combinazione Windows + . -->
                    <p>Domande su pagamenti, rimborsi o crediti?</p>
                    <p>Email: pagamenti@gameshop.com</p>
                    <p>Tempo di risposta medio: 12 ore</p>
                </div>

                <div class="support-hours">
                    <h3>⏰ Orari Supporto Online</h3>
                    <p>Il nostro team di supporto è disponibile:</p>
                    <p>Lunedì - Venerdì: 9:00 - 22:00</p>
                    <p>Sabato - Domenica: 10:00 - 18:00</p>
                </div>

                <div class="social-section">
                    <h3>📱 Seguici sui Social</h3>
                    <div class="social-links">
                        <a href="#" title="Discord"><i class="fab fa-discord"></i></a>
                        <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" title="YouTube"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
