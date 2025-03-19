<?php
function calcolaReputazione($username, $usaPesi = false) {

    // carichiamo le recensioni dal file XML
    $xml_file = '../xml/recensioni.xml';
    $recensioni = [];
    if (file_exists($xml_file)) {
        $xml = simplexml_load_file($xml_file);
        foreach ($xml->recensione as $recensione) {
            if ((string)$recensione->username === $username) {
                $recensioni[] = [
                    'codice_gioco' => (string)$recensione->codice_gioco,
                    'supporto' => (int)$recensione->giudizi->giudizio->supporto,
                    'utilita' => (int)$recensione->giudizi->giudizio->utilita,
                    'username_votante' => (string)$recensione->giudizi->giudizio->username_votante
                ];
            }
        }
    }

    $totale_punteggio = 0;
    $num_giudizi = count($recensioni);

    // calcolo del punteggio
    foreach ($recensioni as $giudizio) {
        $peso = 1;

        if ($usaPesi) {
            // calcolaimo il peso in base al tipo di utente che ha dato il giudizio (DA IMPLEMENTARE)
            
        }

        // calcoliamo il punteggio del singolo giudizio
        $punteggio_giudizio = (($giudizio['supporto'] / 3) * 0.4 + ($giudizio['utilita'] / 5) * 0.6) * $peso;
        $totale_punteggio += $punteggio_giudizio;
    }

    // calcolo reputazione finale (da 0 a 10)
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