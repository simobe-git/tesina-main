<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $titolo = $data['titolo'];
    $contenuto = $data['contenuto'];
    $autore = $data['autore'];
    $codice_gioco = $data['codice_gioco'];
    $data_pubblicazione = $data['data'];

    // carichiamo il file XML esistente o ne creiamo uno nuovo
    $xml_file = '../xml/domande.xml';
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true; // abilitiamo la formattazione

    // carichiamo il file XML se esiste
    if (file_exists($xml_file)) {
        $dom->load($xml_file);
    } else {
        // se il file non esiste, creiamo la struttura di base
        $root = $dom->createElement('domande');
        $dom->appendChild($root);
    }

    //controllo per evitare duplicati
    $duplicato = false;
    foreach ($dom->getElementsByTagName('domanda') as $domanda) {
        $codiceDomanda = $domanda->getElementsByTagName('codice_gioco')->item(0)->nodeValue;
        $titoloDomanda = $domanda->getElementsByTagName('titolo')->item(0)->nodeValue;
        if ($codiceDomanda == $codice_gioco && strtolower($titoloDomanda) == strtolower($titolo)){
            $duplicato = true;
            break;
        }
    }

    if ($duplicato) {
        echo json_encode(['success' => false, 'message' => 'Domanda già esistente']);
        exit;
    }
    
    // creazione della nuova domanda
    $domanda = $dom->createElement('domanda');
    $domanda->appendChild($dom->createElement('codice_gioco', htmlspecialchars($codice_gioco)));
    $domanda->appendChild($dom->createElement('titolo', htmlspecialchars($titolo)));
    $domanda->appendChild($dom->createElement('contenuto', htmlspecialchars($contenuto)));
    $domanda->appendChild($dom->createElement('autore', htmlspecialchars($autore)));
    $domanda->appendChild($dom->createElement('data', htmlspecialchars($data_pubblicazione)));
 
    // aggiungiamo la domanda al nodo radice
    $dom->documentElement->appendChild($domanda);

    // e salviamo file XML
    $dom->save($xml_file);
    echo json_encode(['success' => true]);
}
?>
