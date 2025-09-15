document.addEventListener('DOMContentLoaded', function() {
    const checkButton = document.getElementById('check-for-update');
    const updateInfoDiv = document.getElementById('update-info');

    if (checkButton) {
        checkButton.addEventListener('click', function() {
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Buscando...';

            fetch('ajax/check_update.php', {
                method: 'POST',
            })
            .then(response => response.text())
            .then(html => {
                updateInfoDiv.innerHTML = html;
                updateInfoDiv.style.display = 'block';
            })
            .catch(error => {
                console.error('Error al buscar actualizaciones:', error);
                updateInfoDiv.innerHTML = '<div class="alert alert-danger">Error al conectar con el servidor de actualizaciones.</div>';
                updateInfoDiv.style.display = 'block';
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-cloud-download-alt me-2"></i> Buscar Actualizaciones';
            });
        });
    }
});
