/**
 * Script pour gérer le compteur de notifications
 */
document.addEventListener('DOMContentLoaded', function() {
    // Fonction pour mettre à jour le compteur de notifications
    function updateNotificationCounter() {
        fetch('/api/notifications/count', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
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

    // Mettre à jour le compteur toutes les 60 secondes
    setInterval(updateNotificationCounter, 60000);
}); 