/**
 * Script pour gérer le compteur de notifications
 */
const NotificationCounter = {
    lastEtag: null,
    notificationBadge: null,

    init() {
        this.notificationBadge = document.getElementById('notification-badge');
        this.updateCounter();
        // Mettre à jour le compteur toutes les 30 secondes
        setInterval(() => this.updateCounter(), 30000);
    },

    updateCounter() {
        fetch('/api/notifications/count', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'If-None-Match': this.lastEtag || ''
            }
        })
        .then(response => {
            // Stocke le nouvel ETag
            const etag = response.headers.get('ETag');
            if (etag) {
                this.lastEtag = etag;
            }

            // Si le contenu n'a pas changé (304), on ne fait rien
            if (response.status === 304) {
                return null;
            }

            return response.json();
        })
        .then(data => {
            if (data === null) return;

            if (this.notificationBadge) {
                if (data.count > 0) {
                    this.notificationBadge.textContent = data.count;
                    this.notificationBadge.classList.remove('d-none');
                } else {
                    this.notificationBadge.classList.add('d-none');
                }
            }

            // Émettre un événement personnalisé pour informer d'autres parties de l'application
            document.dispatchEvent(new CustomEvent('notificationCountUpdated', { detail: data.count }));
        })
        .catch(error => console.error('Erreur lors de la récupération du nombre de notifications:', error));
    }
};

// Initialiser le compteur au chargement de la page
document.addEventListener('DOMContentLoaded', () => NotificationCounter.init()); 