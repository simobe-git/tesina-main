// funzione per modificare un'offerta
function modificaOfferta(codice) {

    // recuperiamo i dati dell'offerta tramite AJAX
    fetch(`get_offerta.php?codice=${codice}`)
        .then(response => response.json())
        .then(data => {
            // popoliamo il form con i dati
            document.getElementById('codiceGioco').value = data.codice;
            document.getElementById('nome').value = data.nome;
            document.getElementById('prezzo_originale').value = data.prezzo_originale;
            document.getElementById('prezzo_attuale').value = data.prezzo_attuale;
            document.getElementById('genere').value = data.genere;
            document.getElementById('data_rilascio').value = data.data_rilascio;
            document.getElementById('nome_editore').value = data.nome_editore;
            document.getElementById('descrizione').value = data.descrizione;
            document.getElementById('immagine').value = data.immagine;

            // cambia l'azione del form a 'modifica'
            document.querySelector('input[name="azione"]').value = 'modifica';
            
            // per scorrere fino al form
            document.getElementById('offertaForm').scrollIntoView({ behavior: 'smooth' });
        })
        .catch(error => console.error('Errore:', error));
}

// funzione per eliminare un'offerta
function eliminaOfferta(codice) {
    if (confirm('Sei sicuro di voler eliminare questa offerta?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="azione" value="elimina">
            <input type="hidden" name="codice" value="${codice}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// funzione per resettare il form
function resetForm() {
    document.getElementById('offertaForm').reset();
    document.querySelector('input[name="azione"]').value = 'aggiungi';
    document.getElementById('codiceGioco').value = '';
}

// evento Listener per il form
document.getElementById('offertaForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // e validazione dei campi
    const prezzo_originale = parseFloat(document.getElementById('prezzo_originale').value);
    const prezzo_attuale = parseFloat(document.getElementById('prezzo_attuale').value);
    
    if (prezzo_attuale > prezzo_originale) {
        alert('Il prezzo attuale non può essere maggiore del prezzo originale');
        return;
    }
    
    // se è andato tutto ok, invia il form
    this.submit();
});