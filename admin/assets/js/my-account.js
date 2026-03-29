document.addEventListener('DOMContentLoaded', () => {
  const myAccountUrl = window.appPath ? window.appPath('/admin/my-account.php') : '/admin/my-account.php';
  const avatarInput = document.querySelector('.avatar-input');
  const avatarImg = document.querySelector('.avatar-image');
  const avatarPlaceholder = document.querySelector('.avatar-placeholder');
  const navbarAvatarImg = document.querySelector('.admin-user .avatar img');
  const navbarAvatarFallback = document.querySelector('.admin-user .avatar-fallback');
  const navbarName = document.querySelector('.admin-user .name');
  const navbarRole = document.querySelector('.admin-user .role');

  const profileModal = document.querySelector('#profileModal');
  const profileForm = document.querySelector('#profileForm');
  const profileOpenButtons = document.querySelectorAll('.profile-edit-btn');
  const profileCloseButton = profileModal ? profileModal.querySelector('.modal-close') : null;
  const profileCancelButton = document.querySelector('.profile-cancel-btn');
  const profileFeedback = document.querySelector('#profileFeedback');
  const profileDisplays = document.querySelectorAll('[data-profile-display]');
  const profileInputs = profileForm ? profileForm.querySelectorAll('input[name]') : [];
  const profileState = {};

  const securityModal = document.querySelector('#securityModal');
  const securityForm = document.querySelector('#securityForm');
  const securityOpenButtons = document.querySelectorAll('.security-edit-btn');
  const securityCloseButton = securityModal ? securityModal.querySelector('.modal-close') : null;
  const securityCancelButton = document.querySelector('.security-cancel-btn');
  const securityFeedback = document.querySelector('#securityFeedback');
  const securityDisplays = document.querySelectorAll('[data-security-display]');
  const securityEmailInput = document.querySelector('#securityRecoveryEmail');
  const securityPhoneInput = document.querySelector('#securityRecoveryPhone');
  const currentPasswordInput = document.querySelector('#securityCurrentPassword');
  const newPasswordInput = document.querySelector('#securityNewPassword');
  const confirmPasswordInput = document.querySelector('#securityConfirmPassword');
  const passwordToggleButtons = document.querySelectorAll('[data-password-toggle]');
  const securityState = {};

  const forgotPasswordModal = document.querySelector('#forgotPasswordModal');
  const forgotPasswordForm = document.querySelector('#forgotPasswordForm');
  const forgotPasswordOpenButtons = document.querySelectorAll('.forgot-password-btn');
  const forgotPasswordCloseButton = forgotPasswordModal ? forgotPasswordModal.querySelector('.modal-close') : null;
  const forgotPasswordCancelButton = document.querySelector('.forgot-password-cancel-btn');
  const forgotPasswordFeedback = document.querySelector('#forgotPasswordFeedback');
  const forgotPasswordEmailInput = document.querySelector('#adminForgotPasswordEmail');
  const forgotPasswordUrl = window.appPath ? window.appPath('/admin/forgot-password.php') : '/admin/forgot-password.php';

  const initialAvatar = avatarImg && !avatarImg.hasAttribute('hidden') ? avatarImg.getAttribute('src') : '';
  let currentAvatarUrl = initialAvatar;

  profileDisplays.forEach((node) => {
    profileState[node.dataset.profileDisplay] = node.textContent.trim();
  });

  securityDisplays.forEach((node) => {
    securityState[node.dataset.securityDisplay] = node.textContent.trim();
  });

  const setNavbarIdentity = (profile) => {
    if (!profile) return;
    if (navbarName && profile.full_name) {
      navbarName.textContent = profile.full_name;
    }
    if (navbarRole && profile.role) {
      navbarRole.textContent = profile.role;
    }
    if (profile.avatar !== undefined) {
      if (navbarAvatarImg) {
        if (profile.avatar) {
          navbarAvatarImg.src = profile.avatar;
          navbarAvatarImg.hidden = false;
        } else {
          navbarAvatarImg.hidden = true;
        }
      }
      if (navbarAvatarFallback) {
        navbarAvatarFallback.style.display = profile.avatar ? 'none' : 'grid';
      }
    }
  };

  const setPageAvatar = (avatar) => {
    if (avatarImg) {
      if (avatar) {
        avatarImg.src = avatar;
        avatarImg.hidden = false;
      } else {
        avatarImg.hidden = true;
      }
    }
    if (avatarPlaceholder) {
      avatarPlaceholder.style.display = avatar ? 'none' : 'grid';
    }
    currentAvatarUrl = avatar || '';
  };

  const setProfileFeedback = (message, type = '') => {
    if (!profileFeedback) return;
    profileFeedback.textContent = message;
    profileFeedback.classList.toggle('success', type === 'success');
  };

  const setSecurityFeedback = (message, type = '') => {
    if (!securityFeedback) return;
    securityFeedback.textContent = message;
    securityFeedback.classList.toggle('success', type === 'success');
  };

  const setForgotPasswordFeedback = (message, type = '') => {
    if (!forgotPasswordFeedback) return;
    forgotPasswordFeedback.textContent = message;
    forgotPasswordFeedback.classList.toggle('success', type === 'success');
  };

  const syncProfileForm = () => {
    profileInputs.forEach((input) => {
      const key = input.name;
      if (profileState[key] !== undefined) {
        input.value = profileState[key];
      }
    });
    setProfileFeedback('');
  };

  const syncSecurityForm = () => {
    if (securityEmailInput) securityEmailInput.value = securityState.recovery_email || '';
    if (securityPhoneInput) securityPhoneInput.value = securityState.recovery_phone || '';
    if (currentPasswordInput) currentPasswordInput.value = '';
    if (newPasswordInput) newPasswordInput.value = '';
    if (confirmPasswordInput) confirmPasswordInput.value = '';
    passwordToggleButtons.forEach((button) => {
      const targetId = button.dataset.passwordToggle;
      const target = targetId ? document.getElementById(targetId) : null;
      if (target) target.type = 'password';
      button.setAttribute('aria-pressed', 'false');
      button.setAttribute('aria-label', 'Show password');
      const icon = button.querySelector('i');
      if (icon) icon.className = 'fa-regular fa-eye';
    });
    setSecurityFeedback('');
  };

  const syncForgotPasswordForm = () => {
    if (forgotPasswordEmailInput) {
      forgotPasswordEmailInput.value = securityState.recovery_email || profileState.email || '';
    }
    setForgotPasswordFeedback('');
  };

  const openProfileModal = () => {
    if (!profileModal) return;
    syncProfileForm();
    profileModal.classList.add('open');
    profileModal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  };

  const closeProfileModal = () => {
    if (!profileModal) return;
    profileModal.classList.remove('open');
    profileModal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    syncProfileForm();
  };

  const openSecurityModal = () => {
    if (!securityModal) return;
    syncSecurityForm();
    securityModal.classList.add('open');
    securityModal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  };

  const closeSecurityModal = () => {
    if (!securityModal) return;
    securityModal.classList.remove('open');
    securityModal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    syncSecurityForm();
  };

  const openForgotPasswordModal = () => {
    if (!forgotPasswordModal) return;
    syncForgotPasswordForm();
    forgotPasswordModal.classList.add('open');
    forgotPasswordModal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  };

  const closeForgotPasswordModal = () => {
    if (!forgotPasswordModal) return;
    forgotPasswordModal.classList.remove('open');
    forgotPasswordModal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    syncForgotPasswordForm();
  };

  const updateProfileDisplay = (key, value) => {
    const target = document.querySelector(`[data-profile-display="${key}"]`);
    if (target) {
      target.textContent = value;
    }
    profileState[key] = value;
  };

  const updateSecurityDisplay = (key, value) => {
    const target = document.querySelector(`[data-security-display="${key}"]`);
    if (target) {
      target.textContent = value;
    }
    securityState[key] = value;
  };

  const jsonRequest = async (formData) => {
    const response = await fetch(myAccountUrl, {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    const payload = await response.json().catch(() => ({
      success: false,
      message: 'Unexpected server response.',
    }));

    if (!response.ok || !payload.success) {
      throw new Error(payload.message || 'Request failed.');
    }

    return payload;
  };

  if (avatarInput) {
    avatarInput.addEventListener('change', async (event) => {
      const file = event.target.files && event.target.files[0];
      if (!file) return;

      const previewUrl = URL.createObjectURL(file);
      setPageAvatar(previewUrl);

      const formData = new FormData();
      formData.append('account_action', 'avatar_upload');
      formData.append('avatar', file);

      try {
        const payload = await jsonRequest(formData);
        setPageAvatar(payload.profile.avatar || '');
        setNavbarIdentity(payload.profile);
        if (window.Swal) {
          Swal.fire({
            icon: 'success',
            text: payload.message || 'Profile picture updated.',
            timer: 1800,
            showConfirmButton: false,
          });
        }
      } catch (error) {
        setPageAvatar(initialAvatar);
        if (window.Swal) {
          Swal.fire({
            icon: 'error',
            text: error.message,
          });
        }
      } finally {
        avatarInput.value = '';
      }
    });
  }

  profileOpenButtons.forEach((button) => button.addEventListener('click', openProfileModal));
  securityOpenButtons.forEach((button) => button.addEventListener('click', openSecurityModal));
  forgotPasswordOpenButtons.forEach((button) => button.addEventListener('click', openForgotPasswordModal));
  profileCloseButton?.addEventListener('click', closeProfileModal);
  securityCloseButton?.addEventListener('click', closeSecurityModal);
  forgotPasswordCloseButton?.addEventListener('click', closeForgotPasswordModal);
  profileCancelButton?.addEventListener('click', closeProfileModal);
  securityCancelButton?.addEventListener('click', closeSecurityModal);
  forgotPasswordCancelButton?.addEventListener('click', closeForgotPasswordModal);

  profileModal?.addEventListener('click', (event) => {
    if (event.target === profileModal) closeProfileModal();
  });

  securityModal?.addEventListener('click', (event) => {
    if (event.target === securityModal) closeSecurityModal();
  });

  forgotPasswordModal?.addEventListener('click', (event) => {
    if (event.target === forgotPasswordModal) closeForgotPasswordModal();
  });

  passwordToggleButtons.forEach((button) => {
    button.addEventListener('click', () => {
      const targetId = button.dataset.passwordToggle;
      const target = targetId ? document.getElementById(targetId) : null;
      if (!target) return;

      const isVisible = target.type === 'text';
      target.type = isVisible ? 'password' : 'text';
      button.setAttribute('aria-pressed', String(!isVisible));
      button.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');

      const icon = button.querySelector('i');
      if (icon) {
        icon.className = isVisible ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash';
      }
    });
  });

  profileForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    setProfileFeedback('');

    const formData = new FormData(profileForm);

    try {
      const payload = await jsonRequest(formData);
      const profile = payload.profile || {};
      updateProfileDisplay('full_name', profile.full_name || '');
      updateProfileDisplay('phone', profile.phone || '');
      updateProfileDisplay('email', profile.email || '');
      updateProfileDisplay('address', profile.address || '');
      updateProfileDisplay('township', profile.township || '');
      updateProfileDisplay('city', profile.city || '');
      setNavbarIdentity(profile);
      setProfileFeedback(payload.message || 'Profile information updated.', 'success');
      window.setTimeout(() => {
        closeProfileModal();
      }, 450);
    } catch (error) {
      setProfileFeedback(error.message);
    }
  });

  securityForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    setSecurityFeedback('');

    const formData = new FormData(securityForm);

    try {
      const payload = await jsonRequest(formData);
      const profile = payload.profile || {};
      updateSecurityDisplay('recovery_email', profile.recovery_email || '');
      updateSecurityDisplay('recovery_phone', profile.recovery_phone || '');
      updateSecurityDisplay('last_changed_date', profile.last_password_changed_at ? new Date(profile.last_password_changed_at).toLocaleDateString('en-GB').replace(/\//g, '.') : (securityState.last_changed_date || '-'));
      updateSecurityDisplay('last_changed_time', profile.last_password_changed_at ? new Date(profile.last_password_changed_at).toLocaleTimeString('en-GB', { hour12: false }) : (securityState.last_changed_time || '-'));
      setSecurityFeedback(payload.message || 'Recovery information updated.', 'success');
      window.setTimeout(() => {
        closeSecurityModal();
      }, 450);
    } catch (error) {
      setSecurityFeedback(error.message);
    }
  });

  forgotPasswordForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    setForgotPasswordFeedback('');

    const email = forgotPasswordEmailInput?.value.trim() || '';
    if (!email) {
      setForgotPasswordFeedback('Email is required.');
      return;
    }

    const formData = new FormData(forgotPasswordForm);

    try {
      const response = await fetch(forgotPasswordUrl, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
        },
      });

      const payload = await response.json().catch(() => ({
        success: false,
        message: 'Unexpected server response.',
      }));

      if (!response.ok || !payload.success) {
        throw new Error(payload.message || 'Unable to send reset link right now.');
      }

      closeForgotPasswordModal();
      if (window.Swal) {
        await Swal.fire({
          icon: 'success',
          title: 'Check your email',
          text: payload.message || 'If an admin account matches that email, a reset link has been sent.',
          confirmButtonColor: '#56b356',
        });
      }
    } catch (error) {
      setForgotPasswordFeedback(error.message || 'Unable to send reset link right now.');
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && profileModal?.classList.contains('open')) {
      closeProfileModal();
      return;
    }
    if (event.key === 'Escape' && securityModal?.classList.contains('open')) {
      closeSecurityModal();
      return;
    }
    if (event.key === 'Escape' && forgotPasswordModal?.classList.contains('open')) {
      closeForgotPasswordModal();
    }
  });

  if (window.adminMyAccountFlash && window.Swal && window.adminMyAccountFlash.message) {
    Swal.fire({
      icon: window.adminMyAccountFlash.type === 'error' ? 'error' : 'success',
      text: window.adminMyAccountFlash.message,
      timer: 2200,
      showConfirmButton: false,
    });
  }
});
