/**
 * Gestion des notifications en temps réel
 */
document.addEventListener('DOMContentLoaded', function() {
    // Éléments DOM
    const notificationBadge = document.getElementById('notification-badge');
    const notificationDropdown = document.querySelector('.notification-dropdown');
    
    // Fonction pour mettre à jour le compteur de notifications
    function updateNotificationCount() {
        fetch('/notifications/count')
            .then(response => response.json())
            .then(data => {
                if (data.count > 0) {
                    notificationBadge.textContent = data.count;
                    notificationBadge.classList.remove('d-none');
                } else {
                    notificationBadge.classList.add('d-none');
                }
            })
            .catch(error => console.error('Erreur lors de la récupération du nombre de notifications:', error));
    }
    
    // Fonction pour marquer une notification comme lue
    function markAsRead(notificationId) {
        fetch(`/notifications/${notificationId}/mark-as-read`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateNotificationCount();
                }
            })
            .catch(error => console.error('Erreur lors du marquage de la notification comme lue:', error));
    }
    
    // Fonction pour marquer toutes les notifications comme lues
    function markAllAsRead() {
        fetch('/notifications/mark-all-as-read', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateNotificationCount();
                    
                    // Mettre à jour l'interface utilisateur
                    const unreadItems = document.querySelectorAll('.notification-dropdown .dropdown-item.unread');
                    unreadItems.forEach(item => {
                        item.classList.remove('unread');
                    });
                    
                    // Afficher un message si toutes les notifications sont lues
                    if (unreadItems.length > 0 && notificationDropdown) {
                        const dropdownContent = notificationDropdown.querySelector('.dropdown-header').nextElementSibling;
                        dropdownContent.innerHTML = `
                            <div class="dropdown-item text-center py-3">
                                <i class="bi bi-bell-slash text-muted"></i>
                                <p class="mb-0 text-muted">Aucune notification non lue</p>
                            </div>
                        `;
                    }
                }
            })
            .catch(error => console.error('Erreur lors du marquage de toutes les notifications comme lues:', error));
    }
    
    // Fonction pour supprimer une notification
    function deleteNotification(notificationId, element) {
        fetch(`/notifications/${notificationId}/delete`, {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Supprimer l'élément de l'interface
                    if (element && element.parentNode) {
                        element.parentNode.removeChild(element);
                    }
                    updateNotificationCount();
                }
            })
            .catch(error => console.error('Erreur lors de la suppression de la notification:', error));
    }
    
    // Ajouter des écouteurs d'événements pour les boutons "Marquer comme lu"
    document.querySelectorAll('.mark-as-read').forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            const notificationId = this.dataset.id;
            markAsRead(notificationId);
            
            // Mettre à jour l'interface
            const notificationItem = this.closest('.notification-item');
            if (notificationItem) {
                notificationItem.classList.remove('unread');
                this.style.display = 'none';
            }
        });
    });
    
    // Ajouter un écouteur d'événement pour le bouton "Marquer tout comme lu"
    const markAllAsReadBtn = document.querySelector('.mark-all-as-read');
    if (markAllAsReadBtn) {
        markAllAsReadBtn.addEventListener('click', function(event) {
            event.preventDefault();
            markAllAsRead();
        });
    }
    
    // Ajouter des écouteurs d'événements pour les boutons "Supprimer"
    document.querySelectorAll('.delete-notification').forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            const notificationId = this.dataset.id;
            const notificationItem = this.closest('.notification-item');
            deleteNotification(notificationId, notificationItem);
        });
    });
    
    // Mettre à jour le compteur de notifications toutes les 30 secondes
    setInterval(updateNotificationCount, 30000);
    
    // Marquer les notifications comme lues lorsqu'on clique dessus dans le dropdown
    document.querySelectorAll('.notification-dropdown .dropdown-item').forEach(item => {
        if (!item.classList.contains('text-center')) {
            item.addEventListener('click', function() {
                const notificationId = this.dataset.id;
                if (notificationId) {
                    markAsRead(notificationId);
                }
            });
        }
    });

    // Sélectionner tous les éléments de notification dans le menu déroulant
    const notificationItems = document.querySelectorAll('.notification-item');

    // Ajouter un gestionnaire d'événements à chaque élément de notification
    notificationItems.forEach(item => {
        item.addEventListener('click', function(e) {
            // Ne pas marquer comme lu si l'utilisateur clique sur un bouton à l'intérieur de la notification
            if (e.target.closest('button')) {
                return;
            }
            
            // Récupérer l'URL pour marquer la notification comme lue
            const markAsReadUrl = this.dataset.markAsReadUrl;
            
            // Envoyer une requête AJAX pour marquer la notification comme lue
            if (markAsReadUrl && this.classList.contains('unread')) {
                // Empêcher la navigation immédiate pour permettre à la requête AJAX de se terminer
                e.preventDefault();
                
                fetch(markAsReadUrl, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Supprimer la classe 'unread'
                        this.classList.remove('unread');
                        
                        // Mettre à jour le compteur de notifications
                        const unreadCount = document.querySelectorAll('.notification-item.unread').length;
                        if (notificationBadge) {
                            if (unreadCount > 0) {
                                notificationBadge.textContent = unreadCount;
                            } else {
                                notificationBadge.classList.add('d-none');
                            }
                        }
                        
                        // Rediriger vers l'URL cible après un court délai
                        setTimeout(() => {
                            window.location.href = this.href;
                        }, 100);
                    }
                })
                .catch(error => {
                    console.error('Erreur lors du marquage de la notification comme lue:', error);
                    // En cas d'erreur, rediriger quand même
                    window.location.href = this.href;
                });
            }
        });
    });
}); 