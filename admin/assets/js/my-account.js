document.addEventListener('DOMContentLoaded', () => {
  const avatarInput = document.querySelector('.avatar-input');
  const avatarImg = document.querySelector('.avatar-image');
  const avatarPlaceholder = document.querySelector('.avatar-placeholder');
  const initialAvatar = avatarImg && !avatarImg.hasAttribute('hidden') ? avatarImg.getAttribute('src') : '';
  let currentAvatarUrl = initialAvatar;

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

  profileDisplays.forEach((node) => {
    profileState[node.dataset.profileDisplay] = node.textContent.trim();
  });

  securityDisplays.forEach((node) => {
    securityState[node.dataset.securityDisplay] = node.textContent.trim();
  });

  const restoreAvatar = () => {
    if (avatarImg) {
      if (initialAvatar) {
        avatarImg.src = initialAvatar;
        avatarImg.hidden = false;
      } else {
        avatarImg.hidden = true;
      }
    }
    if (avatarPlaceholder) {
      avatarPlaceholder.style.display = initialAvatar ? 'none' : 'grid';
    }
    if (currentAvatarUrl && currentAvatarUrl.startsWith('blob:')) {
      URL.revokeObjectURL(currentAvatarUrl);
    }
    currentAvatarUrl = initialAvatar;
  };

  const setProfileFeedback = (message, type = '') => {
    if (!profileFeedback) return;
    profileFeedback.textContent = message;
    profileFeedback.classList.toggle('success', type === 'success');
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

  const updateProfileDisplay = (key, value) => {
    const target = document.querySelector(`[data-profile-display="${key}"]`);
    if (target) {
      target.textContent = value;
    }
    profileState[key] = value;
  };

  const setSecurityFeedback = (message, type = '') => {
    if (!securityFeedback) return;
    securityFeedback.textContent = message;
    securityFeedback.classList.toggle('success', type === 'success');
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
      if (target) {
        target.type = 'password';
      }
      button.setAttribute('aria-pressed', 'false');
      button.setAttribute('aria-label', 'Show password');
      const icon = button.querySelector('i');
      if (icon) {
        icon.className = 'fa-regular fa-eye';
      }
    });
    setSecurityFeedback('');
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

  const updateSecurityDisplay = (key, value) => {
    const target = document.querySelector(`[data-security-display="${key}"]`);
    if (target) {
      target.textContent = value;
    }
    securityState[key] = value;
  };

  if (avatarInput) {
    avatarInput.addEventListener('change', (e) => {
      const file = e.target.files && e.target.files[0];
      if (!file) return;
      const url = URL.createObjectURL(file);
      currentAvatarUrl = url;
      if (avatarImg) {
        avatarImg.src = url;
        avatarImg.hidden = false;
      }
      if (avatarPlaceholder) {
        avatarPlaceholder.style.display = 'none';
      }
    });
  }

  profileOpenButtons.forEach((button) => {
    button.addEventListener('click', openProfileModal);
  });

  if (profileCloseButton) {
    profileCloseButton.addEventListener('click', closeProfileModal);
  }

  if (profileCancelButton) {
    profileCancelButton.addEventListener('click', closeProfileModal);
  }

  if (profileModal) {
    profileModal.addEventListener('click', (event) => {
      if (event.target === profileModal) {
        closeProfileModal();
      }
    });
  }

  if (profileForm) {
    profileForm.addEventListener('submit', (event) => {
      event.preventDefault();

      const nextState = {};
      profileInputs.forEach((input) => {
        nextState[input.name] = input.value.trim();
      });

      if (!nextState.full_name || !nextState.phone || !nextState.email) {
        setProfileFeedback('Full name, phone, and email are required.');
        return;
      }

      Object.entries(nextState).forEach(([key, value]) => {
        updateProfileDisplay(key, value);
      });

      setProfileFeedback('Profile information updated.', 'success');
      window.setTimeout(() => {
        closeProfileModal();
      }, 500);
    });
  }

  securityOpenButtons.forEach((button) => {
    button.addEventListener('click', openSecurityModal);
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

  if (securityCloseButton) {
    securityCloseButton.addEventListener('click', closeSecurityModal);
  }

  if (securityCancelButton) {
    securityCancelButton.addEventListener('click', closeSecurityModal);
  }

  if (securityModal) {
    securityModal.addEventListener('click', (event) => {
      if (event.target === securityModal) {
        closeSecurityModal();
      }
    });
  }

  if (securityForm) {
    securityForm.addEventListener('submit', (event) => {
      event.preventDefault();

      const recoveryEmail = securityEmailInput ? securityEmailInput.value.trim() : '';
      const recoveryPhone = securityPhoneInput ? securityPhoneInput.value.trim() : '';
      const currentPassword = currentPasswordInput ? currentPasswordInput.value.trim() : '';
      const newPassword = newPasswordInput ? newPasswordInput.value.trim() : '';
      const confirmPassword = confirmPasswordInput ? confirmPasswordInput.value.trim() : '';

      if (!recoveryEmail || !recoveryPhone) {
        setSecurityFeedback('Recovery email and phone are required.');
        return;
      }

      const isPasswordUpdate = currentPassword || newPassword || confirmPassword;
      if (isPasswordUpdate) {
        if (!currentPassword || !newPassword || !confirmPassword) {
          setSecurityFeedback('Fill in all password fields to update your password.');
          return;
        }

        if (newPassword !== confirmPassword) {
          setSecurityFeedback('New password and confirm password do not match.');
          return;
        }
      }

      updateSecurityDisplay('recovery_email', recoveryEmail);
      updateSecurityDisplay('recovery_phone', recoveryPhone);

      if (isPasswordUpdate) {
        const now = new Date();
        const dateText = now.toLocaleDateString('en-GB').replace(/\//g, '.');
        const timeText = now.toLocaleTimeString('en-GB', { hour12: false });
        updateSecurityDisplay('password_mask', '************');
        updateSecurityDisplay('last_changed_date', dateText);
        updateSecurityDisplay('last_changed_time', timeText);
      }

      setSecurityFeedback('Security information updated.', 'success');
      window.setTimeout(() => {
        closeSecurityModal();
      }, 500);
    });
  }

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && profileModal && profileModal.classList.contains('open')) {
      closeProfileModal();
      return;
    }

    if (event.key === 'Escape' && securityModal && securityModal.classList.contains('open')) {
      closeSecurityModal();
    }
  });
});
