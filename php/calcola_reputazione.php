<?php
function calcolaReputazione($username) {
    // caricamento delle valutazioni dalle recensioni
    $xml_file_recensioni = '../xml/valuta_recensioni.xml';
    $valutazioni_recensioni = [];
    if (file_exists($xml_file_recensioni)) {
        $xml_recensioni = simplexml_load_file($xml_file_recensioni);
        foreach ($xml_recensioni->valutazione as $valutazione) {
            if ((string)$valutazione->username === $username) {
                $valutazioni_recensioni[] = (int)$valutazione->stelle; // Aggiungi le stelle
            }
        }
    }

    // caricamento delle valutazioni dalle discussioni
    $xml_file_discussioni = '../xml/valuta_discussioni.xml';
    $valutazioni_discussioni = [];
    if (file_exists($xml_file_discussioni)) {
        $xml_discussioni = simplexml_load_file($xml_file_discussioni);
        foreach ($xml_discussioni->valutazione as $valutazione) {
            if ((string)$valutazione->autore === $username) {
                $valutazioni_discussioni[] = (int)$valutazione->stelle; // Aggiungi le stelle
            }
        }
    }

    // calcolo della media delle valutazioni
    $tutte_le_valutazioni = array_merge($valutazioni_recensioni, $valutazioni_discussioni);
    $num_valutazioni = count($tutte_le_valutazioni);
    $totale_stelle = array_sum($tutte_le_valutazioni);

    // calcolo della reputazione finale (da 1 a 10)
    $reputazione = $num_valutazioni > 0 ? min(10, ($totale_stelle / $num_valutazioni) * 2) : 0;

    return round($reputazione, 2);
}

// funzione per ottenere la reputazione di un utente
function getReputazioneUtente($username) {
    return calcolaReputazione($username);
}