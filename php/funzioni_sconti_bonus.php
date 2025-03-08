<?php
/*
Riepilogo degli sconti:
    - Ultimi 3 mesi:
        - 5% per 50+ crediti spesi
        10% per 100+ crediti spesi
    - Ultimi 6 mesi:
        - 8% per 100+ crediti spesi
        - 15% per 200+ crediti spesi
    - Ultimo anno:
        - 12% per 200+ crediti spesi
        - 20% per 500+ crediti spesi
    - Ultimi 3 anni:
        - 15% per 500+ crediti spesi
        - 25% per 1000+ crediti spesi
*/ 
require_once('funzioni_reputazioni.php');

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

    // assicuriamoci di nuovo che entrambi gli operandi siano numeri
    $sconto_massimo = floatval($sconto_massimo);
    $importo_sconto = ($prezzo_originale * $sconto_massimo) / 100;
    
    return [
        'percentuale' => $sconto_massimo,
        'importo' => $importo_sconto,
        'prezzo_finale' => $prezzo_originale - $importo_sconto,
        'motivo' => $motivo_sconto ?: 'Nessuno sconto applicabile'
    ];
}

// funzioni utili (di supporto)
function getCreditiSpesiTotali($username) {
    global $connessione;

    // query per ottenere il totale dei crediti spesi dall'utente
    $query = "SELECT SUM(prezzo_attuale) as totale FROM acquisti WHERE username = ?";
    $stmt = $connessione->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['totale'] ?? 0;
}

function getCreditiSpesiPeriodo($username, $mesi) {
    global $connessione;
    
    // prendiamo i dati dal file XML degli acquisti
    $totale_crediti = 0;
    $data_limite = date('Y-m-d', strtotime("-$mesi months"));
    
    $xml_file = '../xml/acquisti.xml';
    if (file_exists($xml_file)) {
        $xml = simplexml_load_file($xml_file);
        foreach ($xml->acquisto as $acquisto) {
            if ((string)$acquisto->username === $username) {
                $data_acquisto = (string)$acquisto->data;
                if ($data_acquisto >= $data_limite) {
                    $totale_crediti += (float)$acquisto->prezzo;
                }
            }
        }
    }
    
    return $totale_crediti;
}

function getAnzianitaMesi($username) {
    global $connessione;
    $query = "SELECT TIMESTAMPDIFF(MONTH, data_registrazione, NOW()) as mesi 
              FROM utenti WHERE username = ?";
    $stmt = $connessione->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['mesi'] ?? 0;
}

// funzione per ottenere i bonus disponibili
function getBonusDisponibili($codice_gioco) {
    global $connessione;
    $bonus = [];
    
    $query = "SELECT * FROM bonus 
              WHERE codice_gioco = ? 
              AND data_inizio <= CURRENT_DATE 
              AND data_fine >= CURRENT_DATE";

    if ($stmt = $connessione->prepare($query)) {

        $stmt->bind_param("i", $codice_gioco);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $bonus[] = $row;
        }

        $stmt->close();
    } else {
        die("Errore nella preparazione della query: " . $connessione->error);
    }

    return $bonus;
}

// funzione per applicare il bonus all'utente
function applicaBonus($username, $codice_gioco) {
    $bonus = getBonusDisponibili($codice_gioco);
    if (!empty($bonus)) {

        // IMPLEMENTARE LA LOGICA PER AGGIUNGERE I CREDITI ALL'UTENTE

        // per ora restituiamo solo l'ammontare del bonus
        return $bonus[0]['ammontare'];
    }
    return 0;
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
}

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
function mostraDettagliSconti($username) {
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
}
?>
