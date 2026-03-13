/**
 * Alpine.js component for the admin user management page.
 *
 * Handles AJAX toggle of user status/role and delete confirmation.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';

Alpine.data('userManagement', () => ({
  toggleStatus(userId: number, action: string, form: HTMLFormElement) {
    const formData = new FormData(form);
    fetch(form.action, {
      method: 'POST',
      body: formData,
    })
      .then((r) => r.json())
      .then((data) => {
        if (data.success) {
          window.location.reload();
        } else {
          alert(data.error || 'Action failed');
        }
      })
      .catch(() => alert('Request failed'));
  },

  toggleRole(userId: number, action: string, form: HTMLFormElement) {
    const formData = new FormData(form);
    fetch(form.action, {
      method: 'POST',
      body: formData,
    })
      .then((r) => r.json())
      .then((data) => {
        if (data.success) {
          window.location.reload();
        } else {
          alert(data.error || 'Action failed');
        }
      })
      .catch(() => alert('Request failed'));
  },

  confirmDelete(event: Event) {
    if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
      event.preventDefault();
    }
  },
}));
