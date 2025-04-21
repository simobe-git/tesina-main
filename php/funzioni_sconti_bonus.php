<?php
require_once('funzioni_reputazioni.php');
require_once('calcola_reputazione.php');

// funzione per verificare se una data è nel range valido
function dataValida($data_inizio, $data_fine) {
    $oggi = new DateTime();
    $inizio = new DateTime($data_inizio);
    $fine = new DateTime($data_fine);
    return $oggi >= $inizio && $oggi <= $fine;
}

// funzione per calcolare lo sconto applicabile
function calcolaSconto($username, $prezzo_originale) {
    // assicuriamoci che il prezzo sia un numero
    $prezzo_originale = floatval($prezzo_originale);
    
    $xml_file = '../xml/sconti_bonus.xml';
    if (!file_exists($xml_file)) {
        return ['percentuale' => 0, 'importo' => 0, 'prezzo_finale' => $prezzo_originale];
    }

    $storico_acquisti = getStoricoAcquisti($username);
    $sconto_massimo = 0;
    $motivo_sconto = '';

    // caricamento del file XML e verifica che sia valido
    $xml = simplexml_load_file($xml_file);
    if ($xml === false || !isset($xml->sconti) || !isset($xml->sconti->sconto)) {
        return [
            'percentuale' => 0,
            'importo' => 0,
            'prezzo_finale' => $prezzo_originale,
            'motivo' => 'Nessuno sconto disponibile'
        ];
    }

    if (!empty($storico_acquisti)) {
        foreach ($xml->sconti->sconto as $sconto) {
            if (!dataValida($sconto->data_inizio, $sconto->data_fine)) {
                continue;
            }

            // verifichiamo che esistano i livelli prima di iterare
            if (!isset($sconto->livelli) || !isset($sconto->livelli->livello)) {
                continue;
            }

            $periodo_mesi = (int)$sconto->periodo_mesi;
            $spesa_periodo = calcolaSpesaPeriodo($storico_acquisti, $periodo_mesi);

            foreach ($sconto->livelli->livello as $livello) {
                $requisito = (float)$livello->requisito_crediti;
                $percentuale = (float)$livello->percentuale;

                if ($spesa_periodo >= $requisito && $percentuale > $sconto_massimo) {
                    $sconto_massimo = $percentuale;
                    $motivo_sconto = (string)$livello->descrizione;
                }
            }
        }
    }

    if ($sconto_massimo > 0) {
        $importo_sconto = ($prezzo_originale * $sconto_massimo) / 100;
    } else {
        $importo_sconto = 0;
    }

    return [
        'percentuale' => $sconto_massimo,
        'importo' => $importo_sconto,
        'prezzo_finale' => $prezzo_originale - $importo_sconto,
        'motivo' => $motivo_sconto ?: 'Nessuno sconto applicabile'
    ];
} 

// prendiamo i crediti totali spesi da un utente per applicare (eventualmente) la prima tipologia di sconti
function getCreditiSpesiTotali($username) {
    $totaleCrediti = 0;
    $xml_file = '../xml/acquisti.xml'; 
    if (file_exists($xml_file)) {
        $xml = simplexml_load_file($xml_file);
        foreach ($xml->acquisto as $acquisto) {
            if ((string)$acquisto->username === $username) {
                $totaleCrediti += (float)$acquisto->prezzo_pagato; // sommiamo i crediti spesi
            }
        }
    }

    return $totaleCrediti; // restituisce il totale dei crediti spesi
}

// funzione per ottenere i crediti spesi in un determinato periodo
function getCreditiSpesiPeriodo($username, $mesi) {
    $totaleCrediti = 0;
    $xml_file = '../xml/acquisti.xml'; 
    $oggi = new DateTime();
    $dataLimite = (clone $oggi)->modify("-$mesi months"); // calcola la data limite

    if (file_exists($xml_file)) {
        $xml = simplexml_load_file($xml_file); 
        foreach ($xml->acquisto as $acquisto) {
            if ((string)$acquisto->username === $username) {
                $dataAcquisto = new DateTime((string)$acquisto->data);
                if ($dataAcquisto >= $dataLimite) {
                    $totaleCrediti += (float)$acquisto->prezzo_pagato; // sommiamo i crediti spesi
                }
            }
        }
    }

    return $totaleCrediti; // restituiamo il totale dei crediti spesi nel periodo
}

/*function getAnzianitaMesi($username) {
    global $connessione;
    $query = "SELECT TIMESTAMPDIFF(MONTH, data_registrazione, NOW()) as mesi 
              FROM utenti WHERE username = ?";
    $stmt = $connessione->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['mesi'] ?? 0;
}


// funzione per ottenere gli acquisti dell'utente
function getAcquistiUtente($username) {
    $acquisti = [];
    $xml_file = '../xml/acquisti.xml';
    if (file_exists($xml_file)) {
        $xml = simplexml_load_file($xml_file);
        foreach ($xml->acquisto as $acquisto) {
            if ((string)$acquisto->username === $username) {
                $acquisti[] = (int)$acquisto->codice_gioco;
            }
        }
    }
    return $acquisti;
}*/

function getStoricoAcquisti($username) {
    $acquisti = [];
    $xml_file = '../xml/acquisti.xml';
    
    if (file_exists($xml_file)) {
        $xml = simplexml_load_file($xml_file);
        foreach ($xml->acquisto as $acquisto) {
            if ((string)$acquisto->username === $username) {
                $acquisti[] = [
                    'data' => new DateTime((string)$acquisto->data),
                    'prezzo' => (float)$acquisto->prezzo,
                    'codice_gioco' => (int)$acquisto->codice_gioco
                ];
            }
        }
    }
    
    return $acquisti;
}

function calcolaSpesaPeriodo($storico_acquisti, $mesi) {
    $oggi = new DateTime();
    $data_limite = (new DateTime())->sub(new DateInterval("P{$mesi}M"));
    $totale = 0;

    foreach ($storico_acquisti as $acquisto) {
        if ($acquisto['data'] >= $data_limite && $acquisto['data'] <= $oggi) {
            $totale += $acquisto['prezzo'];
        }
    }

    return $totale;
}

// esempio di utilizzo tramite questa funzione  (AD ORA NON E' STATA ANCORA UTILIZZATA)
/* function mostraDettagliSconti($username) {
    $storico = getStoricoAcquisti($username);
    if (empty($storico)) {
        return "Non hai ancora effettuato acquisti.";
    }

    $oggi = new DateTime();
    $dettagli = [];
    
    // calcola spese per ogni periodo
    $periodi = [3, 6, 12, 36];
    foreach ($periodi as $mesi) {
        $spesa = calcolaSpesaPeriodo($storico, $mesi);
        $dettagli[] = "Ultimi $mesi mesi: $spesa crediti spesi";
    }

    return implode("\n", $dettagli);
} */

// funzione per caricare gli sconti dal file XML
function caricaScontiDaXml() {
    $sconti = [];
    $xml = simplexml_load_file('../xml/sconti_bonus.xml');

    if ($xml === false) {
        error_log("Errore nel caricamento del file XML.");
        return $sconti; // restituiamo un array vuoto in caso di errore
    }

    foreach ($xml->sconti->sconto as $sconto) {
        $tipo = (string)$sconto->tipo;

        // inizializziamo l'array per il tipo se non esiste
        if (!isset($sconti[$tipo])) {
            $sconti[$tipo] = [];
        }

        // carichiamo i requisiti e le percentuali
        if (isset($sconto->livelli) && isset($sconto->livelli->livello)) {
            foreach ($sconto->livelli->livello as $livello) {
                $sconti[$tipo][] = [
                    'requisito_mesi' => (int)$livello->requisito_mesi,
                    'percentuale' => (float)$livello->percentuale,
                    'descrizione' => (string)$livello->descrizione
                ];
            }
        }
    }

    return $sconti;
}

// tipologia 1 di sconti
// funzione per calcolare lo sconto in base ai crediti spesi fino a questo momento
function calcolaScontoCreditiSpesi($username) {
    $sconti = caricaScontiDaXml(); // Carica gli sconti dal file XML
    $sconto = 0;

    // otteniamo i crediti spesi totali
    $creditiSpesi = getCreditiSpesiTotali($username); // Funzione che restituisce i crediti spesi

    if ($creditiSpesi >= 500) {
        $sconto = 15; // sconto del 15% per chi ha speso 500 crediti
    } elseif ($creditiSpesi >= 200) {
        $sconto = 10; // sconto del 10% per chi ha speso 200 crediti
    } elseif ($creditiSpesi >= 100) {
        $sconto = 5; // sconto del 5% per chi ha speso 100 crediti
    }

    return $sconto; 
}

// tipologia 2 di sconti
// funzione per calcolare lo sconto in base ai crediti spesi in un determinato periodo
function calcolaScontoPeriodo($username) {
    $sconti = caricaScontiDaXml(); 
    $sconto = 0;

    // controlliamo i crediti spesi negli ultimi 3 mesi
    $spesaUltimi3Mesi = getCreditiSpesiPeriodo($username, 3);
    if ($spesaUltimi3Mesi >= 100) {
        $sconto = max($sconto, 10); // sconto del 10% per 100 crediti
    } elseif ($spesaUltimi3Mesi >= 50) {
        $sconto = max($sconto, 5); // sconto del 5% per 50 crediti
    }

    // controlliamo i crediti spesi negli ultimi 6 mesi
    $spesaUltimi6Mesi = getCreditiSpesiPeriodo($username, 6);
    if ($spesaUltimi6Mesi >= 200) {
        $sconto = max($sconto, 15); // sconto del 15% per 200 crediti
    } elseif ($spesaUltimi6Mesi >= 100) {
        $sconto = max($sconto, 8); // sconto dell'8% per 100 crediti
    }

    // controlliamo i crediti spesi negli ultimi 12 mesi
    $spesaUltimi12Mesi = getCreditiSpesiPeriodo($username, 12);
    if ($spesaUltimi12Mesi >= 1000) {
        $sconto = max($sconto, 20); // sconto del 20% per 1000 crediti
    } elseif ($spesaUltimi12Mesi >= 500) {
        $sconto = max($sconto, 15); // sconto del 15% per 500 crediti
    } elseif ($spesaUltimi12Mesi >= 200) {
        $sconto = max($sconto, 12); // sconto del 12% per 200 crediti
    }

    return $sconto; // restituiamo la percentuale di sconto
}

// tipologia 3 di sconti
// funzione per calcolare lo sconto in base alla reputazione
function calcolaScontoReputazione($username) {
    $sconti = caricaScontiDaXml();
    $reputazione = calcolaReputazione($username); 
    $sconto = 0;

    // controlliamo la reputazione
    if ($reputazione >= 9) {
        $sconto = 15; // sconto del 15% per reputazione tra 9 e 10
    } elseif ($reputazione >= 7) {
        $sconto = 10; // sconto del 10% per reputazione tra 7 e 8.99
    } elseif ($reputazione >= 6) {
        $sconto = 5; // sconto del 5% per reputazione tra 6 e 6.99
    }

    return $sconto;
}

// tipologia 4 di sconti
// funzione per calcolare lo sconto in base all'anzianità
function calcolaScontoAnzianita($username) {
    global $connessione; // assicuriamoci di avere accesso alla connessione al database
    $sconti = caricaScontiDaXml(); 

    // query per ottenere la data di registrazione dell'utente
    $query = "SELECT TIMESTAMPDIFF(MONTH, data_registrazione, NOW()) as mesi 
              FROM utenti WHERE username = ?";
    $stmt = $connessione->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $mesi = $result->fetch_assoc()['mesi'] ?? 0;

    // calcolo dello sconto in base all'anzianità
    if ($mesi >= 24) {
        return 20; // sconto del 20% per registrazione di almeno 24 mesi
    } elseif ($mesi >= 12) {
        return 8; // sconto dell'8% per registrazione di almeno 12 mesi
    }

    return 0;
}
