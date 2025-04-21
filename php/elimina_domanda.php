<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $contenutoDomanda = $data['contenuto'];

    // caricamento file XML
    $xmlFile = '../xml/domande.xml';
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;

    if (file_exists($xmlFile)) {
        $dom->load($xmlFile);
        $domande = $dom->getElementsByTagName('domanda');

        foreach ($domande as $domanda) {
            // controllo se il contenuto della domanda corrisponde a quello da eliminare
            if ($domanda->getElementsByTagName('contenuto')->item(0)->nodeValue === $contenutoDomanda) {
                // controlliamo se l'autore della domanda è l'utente loggato
                if ($domanda->getElementsByTagName('autore')->item(0)->nodeValue === $_SESSION['username']) {
                    // verifichiamo che la domanda non ha risposte
                    // poichè vogliamo dare la possibilità di eliminare solo le domande che non hanno ricevuto risposte
                    if ($domanda->getElementsByTagName('risposta')->length === 0) {
                        // elimina domanda
                        $domanda->parentNode->removeChild($domanda);
                        $dom->save($xmlFile);
                        echo json_encode(['success' => true]);
                        exit;
                    } else {
                        echo json_encode(['success' => false, 'message' => 'La domanda ha già ricevuto risposte.']);
                        exit;
                    }
                }
            }
        }
    }
    echo json_encode(['success' => false, 'message' => 'Domanda non trovata.']);
}
?>
