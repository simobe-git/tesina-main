function applicaFiltri() {
    const ordinamento = document.getElementById('ordinamento').value;
    const direzione = document.getElementById('direzione').value;
    const genere = document.getElementById('genere').value;
    const editore = document.getElementById('editore').value;

    let url = new URL(window.location.href);
    url.searchParams.set('ordinamento', ordinamento);
    url.searchParams.set('direzione', direzione);
    
    if (genere) {
        url.searchParams.set('genere', genere);
    } else {
        url.searchParams.delete('genere');
    }
    
    if (editore) {
        url.searchParams.set('editore', editore);
    } else {
        url.searchParams.delete('editore');
    }

    window.location.href = url.toString();
}