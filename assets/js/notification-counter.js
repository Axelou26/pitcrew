document.addEventListener('DOMContentLoaded', function() {
    const notificationBadge = document.getElementById('notification-badge');
    if (!notificationBadge) return;

    function updateNotificationCount() {
        fetch('/notifications/count', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.count > 0) {
                notificationBadge.textContent = data.count;
                notificationBadge.classList.remove('d-none');
                
                // Ajouter une animation de pulse si c'est une nouvelle notification
                if (data.hasNew) {
                    notificationBadge.classList.add('pulse');
                    setTimeout(() => {
                        notificationBadge.classList.remove('pulse');
                    }, 1000);
                }
            } else {
                notificationBadge.classList.add('d-none');
            }
        })
        .catch(error => console.error('Erreur lors de la mise à jour du compteur:', error));
    }

    // Mettre à jour le compteur toutes les 30 secondes
    updateNotificationCount();
    setInterval(updateNotificationCount, 30000);
}); 