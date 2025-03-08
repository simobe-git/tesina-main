<?php
function calcolaReputazione($username, $usaPesi = false) {
    global $connessione;
    
    // query per ottenere tutti i giudizi ricevuti dall'utente
    $query = "SELECT r.id_recensione, r.codice_gioco, gr.supporto, gr.utilita, gr.username_votante, u.tipo_utente
              FROM recensioni r
              JOIN giudizi_recensioni gr ON r.id_recensione = gr.id_recensione
              JOIN utenti u ON gr.username_votante = u.username
              WHERE r.username = ?";
              
    $stmt = $connessione->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $totale_punteggio = 0;
    $num_giudizi = 0;
    
    // carichiamo gli acquisti dal file XML
    $acquisti = [];
    $xml_file = '../xml/acquisti.xml';
    if (file_exists($xml_file)) {
        $xml = simplexml_load_file($xml_file);
        foreach ($xml->acquisto as $acquisto) {
            if ((string)$acquisto->username === $username) {
                $acquisti[] = (string)$acquisto->codice_gioco;
            }
        }
    }
    
    while ($giudizio = $result->fetch_assoc()) {
        $peso = 1;
        
        if ($usaPesi) {
            // calcoliamo il peso in base al tipo di utente che ha dato il giudizio
            switch ($giudizio['tipo_utente']) {
                case 'admin':
                    $peso = 3;
                    break;
                case 'gestore':
                    $peso = 2;
                    break;
                case 'cliente':
                    // calcoliamo il peso in base alla reputazione del votante
                    $reputazione_votante = calcolaReputazione($giudizio['username_votante'], false);
                    $peso = 1 + ($reputazione_votante / 10);     // max peso 2 per clienti con reputazione massima
                    break;
            }
            
            // verifica se il giudizio è su un acquisto effettuato
            if (in_array($giudizio['codice_gioco'], $acquisti)) {
                $peso *= 1.5;    // aumenta il peso del 50% per giudizi su acquisti effettuati
            }
        }
        
        // adesso calcoliamo il punteggio del singolo giudizio
        $punteggio_giudizio = (($giudizio['supporto'] / 3) * 0.4 + ($giudizio['utilita'] / 5) * 0.6) * $peso;
        $totale_punteggio += $punteggio_giudizio;
        $num_giudizi++;
    }
    
    // e calcoliamo la reputazione finale (da 0 a 10)
    $reputazione = $num_giudizi > 0 ? min(10, ($totale_punteggio / $num_giudizi) * 10) : 0;
    
    return round($reputazione, 2);
}

// funzione per ottenere entrambe le reputazioni
function getReputazioneUtente($username) {
    $reputazione_base = calcolaReputazione($username, false);
    $reputazione_pesata = calcolaReputazione($username, true);
    
    return [
        'base' => $reputazione_base,
        'pesata' => $reputazione_pesata
    ];
}