<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $codice_gioco = $data['codice_gioco'];
    $contenuto = $data['contenuto'];
    $autore = $data['autore'];
    $data_pubblicazione = $data['data'];

    // caricamento del file XML esistente o creazione di uno nuovo
    $xml_file = '../xml/domande.xml';
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;

    // caricamento del file XML se esiste
    if (file_exists($xml_file)) {
        $dom->load($xml_file);
    } else {
        // se il file non esiste, creiamo la struttura di base
        $root = $dom->createElement('domande');
        $dom->appendChild($root);
    }

    // calcoliamo il prossimo ID per la risposta da inserire nel file xml
    $maxId = 0;
    foreach ($dom->getElementsByTagName('risposta') as $risposta) {
        $currentId = (int)$risposta->getAttribute('id');
        if ($currentId > $maxId) {
            $maxId = $currentId;
        }
    }
    $nextId = $maxId + 1; // incrementiamo l'ID

    // troviamo la domanda corrispondente al codice del gioco
    $domandaTrovata = false; // Aggiungi questa variabile per controllare se la domanda è stata trovata
    foreach ($dom->getElementsByTagName('domanda') as $domanda) {
        if ($domanda->getElementsByTagName('codice_gioco')->item(0)->nodeValue == $codice_gioco) {
            // creazione della nuova risposta
            $risposta = $dom->createElement('risposta');
            $risposta->setAttribute('id', $nextId); // Assegna l'ID incrementale
            $risposta->appendChild($dom->createElement('contenuto', htmlspecialchars($contenuto)));
            $risposta->appendChild($dom->createElement('autore', htmlspecialchars($autore)));
            $risposta->appendChild($dom->createElement('data', htmlspecialchars($data_pubblicazione)));

            // Aggiungi la risposta alla domanda
            $domanda->appendChild($risposta);
            $domandaTrovata = true; // Imposta a true se la domanda è stata trovata
            break; 
        }
    }

    // salvataggio dell'XML
    $dom->save($xml_file);
    echo json_encode(['success' => true]);

    echo "<h1 style=\"margin-top:200px;\">Codice Gioco ricevuto: " . $codice_gioco . "<br></h1>";
}
?>
