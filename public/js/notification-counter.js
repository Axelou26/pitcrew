/**
 * Script pour gérer le compteur de notifications
 */
document.addEventListener('DOMContentLoaded', function() {
    let lastEtag = null;

    // Fonction pour mettre à jour le compteur de notifications
    function updateNotificationCounter() {
        fetch('/api/notifications/count', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'If-None-Match': lastEtag || ''
            }
        })
        .then(response => {
            // Stocke le nouvel ETag
            const etag = response.headers.get('ETag');
            if (etag) {
                lastEtag = etag;
            }

            // Si le contenu n'a pas changé (304), on ne fait rien
            if (response.status === 304) {
                return null;
            }

            return response.json();
        })
        .then(data => {
            if (data === null) return;

            const notificationBadge = document.getElementById('notification-badge');
            if (notificationBadge) {
                if (data.count > 0) {
                    notificationBadge.textContent = data.count;
                    notificationBadge.classList.remove('d-none');
                } else {
                    notificationBadge.classList.add('d-none');
                }
            }
        })
        .catch(error => console.error('Erreur lors de la récupération du nombre de notifications:', error));
    }

    // Appeler la fonction immédiatement au chargement
    updateNotificationCounter();

    // Mettre à jour le compteur toutes les 30 secondes
    setInterval(updateNotificationCounter, 30000);
}); 
}); 