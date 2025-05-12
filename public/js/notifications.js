/**
 * Gestion des notifications en temps réel
 */
const NotificationManager = {
    // Éléments DOM
    elements: {
        badge: null,
        dropdown: null,
        container: null,
        dropdownTrigger: null
    },

    // État
    state: {
        isLoading: false,
        lastCount: 0,
        updateInterval: 30000,
        lastUpdate: 0
    },

    // Initialisation
    init() {
        this.elements = {
            badge: document.getElementById('notification-badge'),
            dropdown: document.querySelector('.notification-dropdown'),
            container: document.getElementById('notifications-container'),
            dropdownTrigger: document.getElementById('notificationDropdown')
        };

        if (!this.elements.badge || !this.elements.container || !this.elements.dropdownTrigger) {
            return;
        }

        this.setupEventListeners();
        this.loadNotificationCount();
    },

    // Configuration des écouteurs d'événements
    setupEventListeners() {
        this.elements.dropdownTrigger.addEventListener('show.bs.dropdown', () => {
            this.loadNotifications();
        });

        // Mettre à jour périodiquement uniquement si l'onglet est actif
        setInterval(() => {
            if (!document.hidden && Date.now() - this.state.lastUpdate >= this.state.updateInterval) {
                this.loadNotificationCount();
            }
        }, this.state.updateInterval);

        // Mettre à jour lors du retour sur l'onglet
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && Date.now() - this.state.lastUpdate >= this.state.updateInterval) {
                this.loadNotificationCount();
            }
        });
    },

    // Charger le compteur de notifications non lues
    async loadNotificationCount() {
        if (this.state.isLoading) return;

        try {
            this.state.isLoading = true;
            const response = await fetch('/api/notifications/count', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache'
                }
            });

            if (!response.ok) throw new Error('Erreur réseau');

            const data = await response.json();
            this.updateBadge(data.count);
            this.state.lastCount = data.count;
            this.state.lastUpdate = Date.now();
        } catch (error) {
            console.error('Erreur lors du chargement des notifications:', error);
        } finally {
            this.state.isLoading = false;
        }
    },

    // Charger les notifications
    async loadNotifications() {
        if (this.state.isLoading) return;

        try {
            this.state.isLoading = true;
            const response = await fetch('/notifications/unread', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache'
                }
            });

            if (!response.ok) throw new Error('Erreur réseau');

            const html = await response.text();
            this.elements.container.innerHTML = html;
            
            // Mettre à jour le compteur après avoir chargé les notifications
            this.loadNotificationCount();
        } catch (error) {
            console.error('Erreur lors du chargement des notifications:', error);
        } finally {
            this.state.isLoading = false;
        }
    },

    // Mettre à jour le badge
    updateBadge(count) {
        if (count > 0) {
            this.elements.badge.textContent = count;
            this.elements.badge.classList.remove('d-none');
        } else {
            this.elements.badge.classList.add('d-none');
        }
    },

    // Fonction pour marquer une notification comme lue
    async markAsRead(notificationId) {
        try {
            const response = await fetch(`/notifications/${notificationId}/mark-as-read`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) throw new Error('Erreur réseau');

            const data = await response.json();
            if (data.success) {
                await this.loadNotificationCount();
                await this.loadNotifications();
            }
        } catch (error) {
            console.error('Erreur lors du marquage de la notification comme lue:', error);
        }
    },

    // Fonction pour marquer toutes les notifications comme lues
    async markAllAsRead() {
        try {
            const response = await fetch('/notifications/mark-all-as-read', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) throw new Error('Erreur réseau');

            const data = await response.json();
            if (data.success) {
                // Mettre à jour l'interface utilisateur
                const unreadItems = document.querySelectorAll('.notification-dropdown .dropdown-item.unread');
                unreadItems.forEach(item => {
                    item.classList.remove('unread');
                });
                
                // Afficher un message si toutes les notifications sont lues
                if (unreadItems.length > 0 && this.elements.dropdown) {
                    const dropdownContent = this.elements.dropdown.querySelector('.dropdown-header').nextElementSibling;
                    dropdownContent.innerHTML = `
                        <div class="dropdown-item text-center py-3">
                            <i class="bi bi-bell-slash text-muted"></i>
                            <p class="mb-0 text-muted">Aucune notification non lue</p>
                        </div>
                    `;
                }
            }
        } catch (error) {
            console.error('Erreur lors du marquage de toutes les notifications comme lues:', error);
        }
    },
    
    // Fonction pour supprimer une notification
    async deleteNotification(notificationId, element) {
        try {
            const response = await fetch(`/notifications/${notificationId}/delete`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) throw new Error('Erreur réseau');

            const data = await response.json();
            if (data.success) {
                // Supprimer l'élément de l'interface
                if (element && element.parentNode) {
                    element.parentNode.removeChild(element);
                }
            }
        } catch (error) {
            console.error('Erreur lors de la suppression de la notification:', error);
        }
    }
};

// Initialiser au chargement de la page
document.addEventListener('DOMContentLoaded', () => NotificationManager.init()); 