<?php
// funzione per calcolare la reputazione di un utente
function calcolaReputazione($username) {
    $xml_recensioni = 'xml/recensioni.xml';
    $reputazione = 0;
    $numero_giudizi = 0;
    
    if (file_exists($xml_recensioni)) {
        $xml = simplexml_load_file($xml_recensioni);
        
        foreach ($xml->recensione as $recensione) {
            // dobbiamo considere solo le recensioni dell'utente specificato
            if ((string)$recensione->username === $username) {
                foreach ($recensione->giudizi->giudizio as $giudizio) {
                    // calcola il punteggio per ogni giudizio
                    // supporto (1-3) ha peso 0.4, Utilità (1-5) ha peso 0.6
                    $punteggio_supporto = ((float)$giudizio->supporto / 3) * 0.4;
                    $punteggio_utilita = ((float)$giudizio->utilita / 5) * 0.6;
                    
                    $reputazione += $punteggio_supporto + $punteggio_utilita;
                    $numero_giudizi++;
                }
            }
        }
    }
    
    // se l'utente ha ricevuto giudizi, calcola la media
    if ($numero_giudizi > 0) {
        $reputazione = $reputazione / $numero_giudizi;
        // conversione in scala 1-5
        $reputazione = $reputazione * 5;
    }
    
    return [
        'punteggio' => round($reputazione, 2),
        'numero_giudizi' => $numero_giudizi
    ];
}

// funzione per ottenere i migliori recensori
function getMiglioriRecensori($limite = 5) {
    $xml_recensioni = 'xml/recensioni.xml';
    $recensori = [];
    
    if (file_exists($xml_recensioni)) {
        $xml = simplexml_load_file($xml_recensioni);
        
        // raccoglimento di tutti gli username unici che hanno scritto recensioni
        $usernames = [];
        foreach ($xml->recensione as $recensione) {
            $username = (string)$recensione->username;
            if (!in_array($username, $usernames)) {
                $usernames[] = $username;
            }
        }
        
        // calcolo della reputazione per ogni utente
        foreach ($usernames as $username) {
            $reputazione = calcolaReputazione($username);
            $recensori[] = [
                'username' => $username,
                'reputazione' => $reputazione['punteggio'],
                'numero_giudizi' => $reputazione['numero_giudizi']
            ];
        }
        
        // ordina per reputazione decrescente
        usort($recensori, function($a, $b) {
            return $b['reputazione'] <=> $a['reputazione'];
        });
        
        // restituisci solo il numero richiesto di recensori
        return array_slice($recensori, 0, $limite);
    }
    
    return [];
}
?>
