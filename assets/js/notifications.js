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
        updateInterval: 60000,
        lastUpdate: 0,
        errorCount: 0,
        maxErrors: 3
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

            // Gérer les différents codes de statut
            if (response.status === 401) {
                // Utilisateur non authentifié, masquer le badge
                this.updateBadge(0);
                this.state.errorCount = 0;
                return;
            }

            if (!response.ok) {
                this.state.errorCount++;
                throw new Error(`Erreur HTTP: ${response.status}`);
            }

            // Vérifier le type de contenu
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                this.state.errorCount++;
                console.error('Réponse non-JSON reçue:', contentType);
                throw new Error('Réponse non-JSON reçue');
            }

            const data = await response.json();
            this.updateBadge(data.count);
            this.state.lastCount = data.count;
            this.state.lastUpdate = Date.now();
            this.state.errorCount = 0; // Réinitialiser le compteur d'erreurs en cas de succès
        } catch (error) {
            console.error('Erreur lors du chargement des notifications:', error);
            
            // Si trop d'erreurs consécutives, arrêter les mises à jour automatiques
            if (this.state.errorCount >= this.state.maxErrors) {
                console.warn('Trop d\'erreurs consécutives, arrêt des mises à jour automatiques');
                return;
            }
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

            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }

            const html = await response.text();
            this.elements.container.innerHTML = html;
            
            // Mettre à jour le compteur après avoir chargé les notifications
            this.loadNotificationCount();
        } catch (error) {
            console.error('Erreur lors du chargement des notifications:', error);
            // Afficher un message d'erreur dans le conteneur
            this.elements.container.innerHTML = `
                <div class="dropdown-item text-center py-3">
                    <i class="bi bi-exclamation-triangle text-warning"></i>
                    <p class="mb-0 text-muted">Erreur lors du chargement des notifications</p>
                </div>
            `;
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
        if (!notificationId) {
            console.error('Erreur: notificationId indéfini ou vide pour markAsRead.');
            return;
        }
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
        if (!notificationId) {
            console.error('Erreur: notificationId indéfini ou vide pour deleteNotification.');
            return;
        }
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