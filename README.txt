Questo file contiene le informazioni necessarie per una migliore conoscenza dell'applicazione web sviluppata dagli studenti:
- Simone Belli, matricola 2024391
- Samuele Di Mario, matricola 2009541

Il progetto è stato caricato sul profilo github di entrambi i membri del gruppo, a cui si può accedere seguendo i seguenti indirizzi:
- https://github.com/simobe-git/tesina-main
- https://github.com/ICON39/tesina-main

Traccia:
Gestione di un negozio virtuale che vende giochi da tavolo. Nel sistem è presente un catalogo di giochi da tavolo, descritto con testo, immagini e prezzo, che tutti possono consultare.
Oltre queste semplici informazioni, se ne possono visualizzare altre visitando i dettagli dei giochi con l'apposito pulsante.
Il catalogo può essere visionato in diversi modi, in base ad alcuni filtri che ordinano i giochi da tavolo in base al costo (crescente e decrescente), al nome del gioco o all'anno di uscita.

Ogni gioco da tavolo è caratterizzato dalle seguenti caratteristiche:

- Titolo: nome del gioco.
- Descrizione: breve testo che riassume lo scopo e le meccaniche di gioco.
- Categoria: genere del gioco (party game, cooperativo, ecc.).
- Numero di giocatori: range di giocatori supportati.
- Età consigliata: indicazione dell'età minima adatta al gioco.
- Durata media: stima media della durata di una partita.
- Produttore/Editore: casa produttrice o editrice del gioco.
- Autore: creatore del gioco.
- Immagini: foto della scatola, del tabellone o dei componenti.
- Prezzo: costo del gioco.
- Disponibilità: indica se il gioco è disponibile per l'acquisto.
- Meccaniche di gioco, che possono essere lancio di dadi; movimento di pedine o tessere e loro posizionamento; gestione delle risorse a disposizione, cooperazione, eventuali aste o bluff.
- Ambientazione: fantasy, storica, fantascientifica, distopica, realistica.

Un gioco può prevedere uno sconto o un bonus.
Uno sconto è una percentuale di riduzione del prezzo (in crediti) del gioco e viene applicato in base a criteri: 
-  clienti che hanno speso X crediti finora
-  clienti che hanno speso Y crediti da una certa data
-  clienti che hanno acquistato un determinato gioco
-  clienti con una certa reputazione
-  clienti registrati da X mesi o anni
-  sconto indiscriminato.
N.B. Gli sconti sono descritti meglio nel 1° stato di avanzamento.

Un bonus invece è un numero di crediti che viene assegnato al cliente che acquista il gioco.

Esiste una pagina di FAQ, gestita dall'admin.
Una FAQ può essere costruita dall'admin o provenire da una domanda postata nei forum: in questo caso, la domanda e una delle risposte selezionate dall'admin compongono la FAQ.

Un cliente acquisisce crediti facendo una richiesta all'admin attraverso una funzionalità del sistema.
Si assume che la richiesta sia associata a un pagamento in denaro che non abbiamo gestito qui.
Si assume inoltre che l'admin verifichi il pagamento, ma anche questa operazione non è implementata: l'admin ha un bottone per accettare o rifiutare la richiesta.

Il sistema permette di scrivere recensioni sui giochi da tavolo, fare domande e dare risposte.

I clienti possono giudicare i contributi suddetti, assegnando loro un valore di:
- "Supporto" ("Accordo", da 1 a 3).
- "Utilità" ("È stato utile da leggere", da 1 a 5).
In base ai giudizi ricevuti sui propri contributi, un cliente ha una reputazione.
Ai fini del calcolo della reputazione pesano i risultati sia dei contributi relativi ad acquisti effettivamente fatti dal cliente, sia quelli relativi agli altri giochi: ma i primi pesano di più.
È possibile calcolare la reputazione usando pesi diversi per i giudizi dati da utenti diversi:

Il gestore pesa più dei clienti.
Un cliente pesa in base alla propria reputazione.
Quando si visualizza la reputazione di un utente, vengono mostrate sia quella calcolata senza pesi, sia quella calcolata con i pesi.

Tipologie di utenti

Visitatore
- Accede al catalogo.
- Accede a link informativi (chi siamo, dove siamo, contatti, novità...).
- Visiona le FAQ.
- Può registrarsi.

Cliente
- Come per il visitatore.
- Accede al profilo per vedere: lo storico degli acquisti, gli acquisti in corso, i propri crediti, la propria reputazione, i propri dati anagrafici e può gestire username e password.
- Acquista giochi (con un meccanismo di carrello della spesa).
- Scrive recensioni, domande e risposte sui giochi.
- Giudica le recensioni, domande e risposte di altri clienti (può giudicare ogni contributo una sola volta).
- Chiede l'assegnazione di crediti (specificando la quantità). I crediti verranno aggiunti al profilo quando l'admin accetterà la richiesta.

Gestore
- Aggiunge/elimina/modifica giochi nel catalogo.
- Definisce gli sconti e i bonus.
- Applica uno o più sconti e/o bonus a un gioco.
- Consulta il profilo dei clienti (reputazione, storico degli acquisti).
- Modera e giudica i contributi dei clienti.
- Risponde alle domande.

Amministratore
- Vede/modifica i dati anagrafici, username e password degli utenti.
- Disattiva (banna) e riattiva utenti.
- Accetta richieste di crediti.
- Eleva una domanda (e la risposta migliore, o quella scelta dall'admin) nelle FAQ.
