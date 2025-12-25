// public/js/notifications.js

class NotificationManager {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadNotifications();
        this.startPolling();
    }

    bindEvents() {
        // Mark notification as read when clicked
        $(document).on('click', '.notification-item', (e) => {
            const notificationId = $(e.currentTarget).data('id');
            this.markAsRead(notificationId);
        });

        // Clear all notifications
        $('#clear-all-notifications').on('click', () => {
            this.markAllAsRead();
        });

        // View all notifications
        $('#view-all-notifications').on('click', () => {
            // Redirect to notifications page or open modal
            console.log('View all notifications');
        });
    }

    loadNotifications() {
        $.get('/notifications', (response) => {
            this.updateNotificationDropdown(response.notifications);
            this.updateNotificationBadge(response.unread_count);
        }).fail(() => {
            console.error('Failed to load notifications');
        });
    }

    markAsRead(notificationId) {
        $.post(`/notifications/${notificationId}/read`, {
            _token: $('meta[name="csrf-token"]').attr('content')
        }).done(() => {
            $(`.notification-item[data-id="${notificationId}"]`)
                .removeClass('bg-light bg-opacity-25');
            this.updateUnreadCount();
        });
    }

    markAllAsRead() {
        $.post('/notifications/read-all', {
            _token: $('meta[name="csrf-token"]').attr('content')
        }).done(() => {
            $('.notification-item').removeClass('bg-light bg-opacity-25');
            this.updateNotificationBadge(0);
        });
    }

    updateUnreadCount() {
        $.get('/notifications/unread-count', (response) => {
            this.updateNotificationBadge(response.count);
        });
    }

    updateNotificationBadge(count) {
        const badge = $('#notification-badge');
        if (count > 0) {
            badge.text(count).removeClass('d-none');
        } else {
            badge.addClass('d-none');
        }
    }

    updateNotificationDropdown(notifications) {
        const container = $('#notifications-container');
        let html = '';

        if (notifications.length === 0) {
            html = `
                <div class="text-center p-4">
                    <iconify-icon icon="iconamoon:notification-off-duotone" class="fs-48 text-muted"></iconify-icon>
                    <p class="text-muted mt-2">No notifications yet</p>
                </div>
            `;
        } else {
            notifications.forEach(notification => {
                const data = notification.data;
                const isUnread = !notification.read_at;
                
                html += `
                    <a href="javascript:void(0);" 
                       class="dropdown-item py-3 border-bottom text-wrap notification-item ${isUnread ? 'bg-primary bg-opacity-10 border-start border-3' : ''}"
                       data-id="${notification.id}">
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                ${this.getNotificationAvatar(data)}
                            </div>
                            <div class="flex-grow-1">
                                <p class="mb-1 fw-semibold">${data.title}</p>
                                <p class="mb-0 text-wrap">${data.message}</p>
                                <small class="text-muted">${this.timeAgo(notification.created_at)}</small>
                            </div>
                        </div>
                    </a>
                `;
            });
        }

        container.html(html);
    }

    getNotificationAvatar(data) {
        if (data.user && data.user.avatar) {
            return `<img src="/storage/${data.user.avatar}" class="img-fluid me-2 avatar-sm rounded-circle" alt="avatar" />`;
        } else if (data.user) {
            const initial = data.user.name ? data.user.name.charAt(0).toUpperCase() : 'U';
            return `
                <div class="avatar-sm me-2">
                    <span class="avatar-title bg-soft-info text-info fs-20 rounded-circle">${initial}</span>
                </div>
            `;
        } else {
            const icon = data.icon || 'iconamoon:notification-duotone';
            return `
                <div class="avatar-sm me-2">
                    <span class="avatar-title bg-soft-primary text-primary fs-20 rounded-circle">
                        <iconify-icon icon="${icon}"></iconify-icon>
                    </span>
                </div>
            `;
        }
    }

    timeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);

        if (diffInSeconds < 60) return 'Just now';
        if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + ' minutes ago';
        if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + ' hours ago';
        return Math.floor(diffInSeconds / 86400) + ' days ago';
    }

    startPolling() {
        // Poll for new notifications every 30 seconds
        setInterval(() => {
            this.loadNotifications();
        }, 30000);
    }
}

// Initialize when document is ready
$(document).ready(() => {
    new NotificationManager();
});