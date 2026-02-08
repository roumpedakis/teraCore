window.addEventListener("DOMContentLoaded", () => {
  const logoutButton = document.querySelector("#logout-button");
  if (logoutButton) {
    logoutButton.addEventListener("click", () => {
      window.location.href = "/admin/logout";
    });
  }

  if (window.jQuery) {
    const currentPath = window.location.pathname;
    window.jQuery('.nav-link').each(function () {
      if (this.getAttribute('href') === currentPath) {
        window.jQuery(this).addClass('is-active');
      }
    });

    const resetUserForm = () => {
      window.jQuery('#user-form-action').val('create');
      window.jQuery('#user-id').val('');
      window.jQuery('#user-username').val('');
      window.jQuery('#user-email').val('');
      window.jQuery('#user-first').val('');
      window.jQuery('#user-last').val('');
      window.jQuery('#user-pass').val('');
      window.jQuery('#user-active').prop('checked', true);
      window.jQuery('#user-modal-title').text('Νέος χρήστης');
    };

    window.jQuery('#user-modal').on('show.bs.modal', function (event) {
      const button = window.jQuery(event.relatedTarget);
      if (!button.length || !button.hasClass('user-edit')) {
        resetUserForm();
        return;
      }

      window.jQuery('#user-form-action').val('update');
      window.jQuery('#user-id').val(button.data('user-id'));
      window.jQuery('#user-username').val(button.data('username'));
      window.jQuery('#user-email').val(button.data('email'));
      window.jQuery('#user-first').val(button.data('first-name'));
      window.jQuery('#user-last').val(button.data('last-name'));
      window.jQuery('#user-pass').val('');
      window.jQuery('#user-active').prop('checked', button.data('is-active') === 1);
      window.jQuery('#user-modal-title').text('Επεξεργασία χρήστη');
    });

    window.jQuery('a.nav-link[href^="/admin/dashboard#"]').on('click', function (event) {
      if (window.location.pathname !== '/admin/dashboard') {
        return;
      }
      event.preventDefault();
      const target = this.getAttribute('href').split('#')[1];
      const $target = window.jQuery('#' + target);
      if ($target.length) {
        window.jQuery('html, body').animate({ scrollTop: $target.offset().top - 24 }, 350);
      }
    });
  }
});
