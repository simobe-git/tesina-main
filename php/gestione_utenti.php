<?php
session_start();
require_once('connessione.php');

if(isset($_SESSION['tipo_utente']) && isset($_SESSION['statoLogin'])){ // se l'utente è già loggato

    if($_SESSION['tipo_utente'] !== 'admin'){ // se l'utente è già loggato e il suo ruolo è diverso da admin 
        header("Location: home.php");
        exit();
    }
}else{
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $username = $_POST['username'];


    // ban utente
    if (isset($_POST['ban_utente'])) { 

        $query = "UPDATE utenti SET ban = 1 WHERE username = ?";
        $stmt = $connessione->prepare($query);
        $stmt->bind_param("s", $username);
        if ($stmt->execute()) {
            $messaggio = "Utente bannato con successo";
        } else {
            $messaggio = "Errore nel bannare l'utente: " . $stmt->error;
        }
    }
    
    // attiva utente
    elseif (isset($_POST['attiva_utente'])) { 

        $query = "UPDATE utenti SET ban = 0 WHERE username = ?";
        $stmt = $connessione->prepare($query);
        $stmt->bind_param("s", $username);
        if ($stmt->execute()) {
            $messaggio = "Utente attivato con successo";
        }
    }
    
    elseif (isset($_POST['modifica_dati'])) {
        $nome = $_POST['nome'];
        $cognome = $_POST['cognome'];
        $email = $_POST['email'];

        // eseguiamo l'aggiornamento nel database dopo i dati modificati dall'admin
        $query = "UPDATE utenti SET nome = ?, cognome = ?, email = ? WHERE username = ?";
        $stmt = $connessione->prepare($query);
        $stmt->bind_param("ssss", $nome, $cognome, $email, $username);

        if ($stmt->execute()) {
            $messaggio = "Dati utente aggiornati con successo";
        } else {
            $messaggio = "Errore nell'aggiornamento dei dati: " . $stmt->error;
        }
    }
}

// carichiamo la lista degli utenti dal db
$query = "SELECT * FROM utenti WHERE tipo_utente = 'cliente' ORDER BY data_registrazione DESC";
$result = $connessione->query($query);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Utenti</title>
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            display: flex; 
            flex-direction: column; 
            align-items: center; 
        }
        .utente-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center; 
            margin-bottom: 20px; 
            width: 100%; 
            max-width: 350px;
            transition: transform 0.2s;
        }
        .utente-card:hover {
            transform: scale(1.02);
        }
        .stato-bannato { color: #dc3545; }
        .stato-attivo { color: #28a745; font-weight: bold; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-primary { background: #007bff; color: white; }
        .messaggio {
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
        }

        .navbar {
            background-color: green; 
            color: #fff; 
            padding: 20px 0; 
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

        
        .title-container {
            text-align: center; 
            margin-bottom: 20px; 
        }

        .utente-grid {
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px; 
            width: 100%; 
        }

        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
            margin-top: 5px;
        }
        
        .username {
            color: red;
            font-size: 1.2em;
        }

        .form-group label {
            font-weight: bold;
            font-size: 1.1em;
        }
        .stato-label {
            font-weight: bold;
            font-size: 1.1em;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <ul>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="gestione_crediti.php">Richieste Crediti</a></li>
            <li><a href="gestione_faq.php">Gestisci FAQ</a></li>
            <li><a href="gestione_richiestaGestore.php">Gestisci richieste</a></li>
        </ul>
    </div>
    
    <div style="text-align: right; margin-right: 2%; margin-top: 20px;">
        <a href="logout.php" class="logout-link" style="display: inline-block; padding: 12px 25px; background-color: #ff4d4d; color: white; border-radius: 5px; text-decoration: none; font-size: 1.2em;">Logout</a>
    </div>
    
    <div class="container">
        <div class="title-container">
            <h1 style="font-size: 200%; color: red; margin-top: -1ex;">Gestione Utenti</h1>
            <h2 style="color: red;">Qui puoi modificare i dati anagrafici o attivare/disattivare gli utenti</h2>
        </div>
        
        <?php if (isset($messaggio)): ?>
            <div class="messaggio"><?php echo $messaggio; ?></div>
        <?php endif; ?>

        <div class="utente-grid"> <!-- contenitore per le schede utenti -->
            <?php while ($utente = $result->fetch_assoc()): ?>
                <div class="utente-card">
                    <h3 class="username"><?php echo htmlspecialchars($utente['username']); ?></h3>
                    <form method="POST">
                        <input type="hidden" name="username" value="<?php echo $utente['username']; ?>">
                        
                        <div class="form-group">
                            <label>Nome:</label>
                            <input type="text" name="nome" value="<?php echo htmlspecialchars($utente['nome']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Cognome:</label>
                            <input type="text" name="cognome" value="<?php echo htmlspecialchars($utente['cognome']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Email:</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($utente['email']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="stato-label">Stato:</label>
                            <span class="stato-<?php echo $utente['ban']; ?>">
                                <?php 
                                    if($utente['ban'] == 0){
                                        echo '<span class="stato-attivo">Attivo</span>';
                                    }else{
                                        echo '<span class="stato-bannato">Bannato</span>';
                                    }
                                ?>
                            </span>
                        </div>
                        <br>
                        <button type="submit" name="modifica_dati" class="btn btn-primary">Salva Modifiche</button>
                        
                        <?php if ($utente['ban'] == 0): ?>
                            <button type="submit" name="ban_utente" class="btn btn-danger"
                                    onclick="return confirm('Sei sicuro di voler bannare questo utente?')">
                                Banna Utente
                            </button>
                        <?php else: ?>
                            <button type="submit" name="attiva_utente" class="btn btn-success">
                                Attiva Utente
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html>