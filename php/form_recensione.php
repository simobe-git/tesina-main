<div class="form-recensione">
    <h2>Scrivi una recensione</h2>
    <form action="processa_recensione.php" method="POST">
        <input type="hidden" name="codice_gioco" value="<?php echo $codice_gioco; ?>">
        <textarea name="testo_recensione" required 
                  placeholder="Scrivi qui la tua recensione..."></textarea>
        <button type="submit">Pubblica recensione</button>
    </form>
</div>

<style>
.form-recensione {
    max-width: 600px;
    margin: 20px auto;
    padding: 15px;
    background: #f5f5f5;
    border-radius: 8px;
}

.form-recensione textarea {
    width: 100%;
    min-height: 150px;
    padding: 10px;
    margin: 10px 0;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.form-recensione button {
    background: #007bff;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}
</style>
