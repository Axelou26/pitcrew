document.addEventListener('DOMContentLoaded', function() {
    const notificationContainer = document.getElementById('notifications-container');
    const notificationBadge = document.getElementById('notification-badge');
    
    if (!notificationContainer || !notificationBadge) return;

    function updateNotifications() {
        fetch('/notifications/unread', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.notifications && data.notifications.length > 0) {
                notificationBadge.textContent = data.notifications.length;
                notificationBadge.classList.remove('d-none');
                
                // Mettre à jour le contenu des notifications
                notificationContainer.innerHTML = data.notifications.map(notification => `
                    <a href="${notification.url}" class="dropdown-item notification-item ${notification.isRead ? '' : 'unread'}"
                       data-mark-as-read-url="/notifications/${notification.id}/mark-as-read">
                        <div class="d-flex align-items-center">
                            <div class="notification-icon ${notification.type}">
                                <i class="bi ${notification.icon}"></i>
                            </div>
                            <div class="ms-3">
                                <div class="notification-title">${notification.title}</div>
                                <div class="notification-message">${notification.message}</div>
                                <small class="notification-time">${notification.createdAt}</small>
                            </div>
                        </div>
                    </a>
                `).join('');
            } else {
                notificationBadge.classList.add('d-none');
                notificationContainer.innerHTML = '<p class="text-center py-3">Aucune notification</p>';
            }
        })
        .catch(error => {
            console.error('Erreur lors de la récupération des notifications:', error);
            notificationContainer.innerHTML = '<p class="text-center py-3">Erreur lors du chargement</p>';
        });
    }

    // Mettre à jour les notifications toutes les 30 secondes
    updateNotifications();
    setInterval(updateNotifications, 30000);
}); 