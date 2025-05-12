/**
 * Script pour gérer le compteur de notifications
 */
const NotificationCounter = {
    lastEtag: null,
    notificationBadge: null,
    updateInterval: 30000, // 30 secondes
    retryDelay: 5000, // 5 secondes
    maxRetries: 3,
    currentRetries: 0,
    isUpdating: false,

    init() {
        this.notificationBadge = document.getElementById('notification-badge');
        if (!this.notificationBadge) {
            console.error('Badge de notification non trouvé');
            return;
        }

        this.updateCounter();
        this.startAutoUpdate();
    },

    startAutoUpdate() {
        setInterval(() => {
            if (!document.hidden) {
                this.updateCounter();
            }
        }, this.updateInterval);

        // Mettre à jour lors du retour sur l'onglet
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.updateCounter();
            }
        });
    },

    async updateCounter() {
        if (this.isUpdating) return;
        this.isUpdating = true;

        try {
            const response = await fetch('/api/notifications/count', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'If-None-Match': this.lastEtag || ''
                }
            });

            // Gérer les différents codes de statut
            switch (response.status) {
                case 200:
                    const data = await response.json();
                    this.updateBadge(data.count);
                    this.lastEtag = response.headers.get('ETag');
                    this.currentRetries = 0;
                    break;

                case 304:
                    // Contenu non modifié, rien à faire
                    this.currentRetries = 0;
                    break;

                case 401:
                    // Utilisateur non authentifié
                    console.warn('Session expirée ou utilisateur non authentifié');
                    break;

                default:
                    throw new Error(`Erreur serveur: ${response.status}`);
            }
        } catch (error) {
            console.error('Erreur lors de la mise à jour du compteur:', error);
            
            // Réessayer si le nombre maximum de tentatives n'est pas atteint
            if (this.currentRetries < this.maxRetries) {
                this.currentRetries++;
                setTimeout(() => this.updateCounter(), this.retryDelay);
            } else {
                this.showError();
            }
        } finally {
            this.isUpdating = false;
        }
    },

    updateBadge(count) {
        if (!this.notificationBadge) return;

        if (count > 0) {
            this.notificationBadge.textContent = count > 99 ? '99+' : count;
            this.notificationBadge.classList.remove('d-none');
            
            // Ajouter une animation subtile
            this.notificationBadge.classList.add('notification-update');
            setTimeout(() => {
                this.notificationBadge.classList.remove('notification-update');
            }, 300);
        } else {
            this.notificationBadge.classList.add('d-none');
        }

        // Émettre un événement pour informer d'autres parties de l'application
        document.dispatchEvent(new CustomEvent('notificationCountUpdated', {
            detail: { count, timestamp: new Date().getTime() }
        }));
    },

    showError() {
        if (this.notificationBadge) {
            this.notificationBadge.classList.add('notification-error');
            setTimeout(() => {
                this.notificationBadge.classList.remove('notification-error');
            }, 3000);
        }
    }
};

// Styles pour les animations
const style = document.createElement('style');
style.textContent = `
    .notification-badge {
        transition: transform 0.3s ease-in-out;
    }
    .notification-update {
        animation: notification-pulse 0.3s ease-in-out;
    }
    .notification-error {
        animation: notification-shake 0.5s ease-in-out;
    }
    @keyframes notification-pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }
    @keyframes notification-shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
`;
document.head.appendChild(style);

// Initialiser le compteur au chargement de la page
document.addEventListener('DOMContentLoaded', () => NotificationCounter.init()); 