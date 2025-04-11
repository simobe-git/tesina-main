<?php

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
                'reputazione' => $reputazione,
                'numero_giudizi' => 0 
            ];
        }
        
        // ordinamento per reputazione decrescente
        usort($recensori, function($a, $b) {
            return $b['reputazione'] <=> $a['reputazione'];
        });
        
        // restituiamo solo il numero richiesto di recensori
        return array_slice($recensori, 0, $limite);
    }
    
    return [];
}
?>
