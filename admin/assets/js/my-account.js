document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('.profile-grid');
  const editBtn = document.querySelector('.edit-btn');
  const saveBtn = document.querySelector('.save-btn');
  const cancelBtn = document.querySelector('.cancel-btn');
  const inputs = form ? form.querySelectorAll('.profile-input') : [];

  const avatarInput = document.querySelector('.avatar-input');
  const uploadBtn = document.querySelector('.upload-btn');
  const avatarImg = document.querySelector('.avatar-image');
  const avatarPlaceholder = document.querySelector('.avatar-placeholder');
  const initialAvatar = avatarImg && !avatarImg.hasAttribute('hidden') ? avatarImg.getAttribute('src') : '';
  let currentAvatarUrl = initialAvatar;

  const enableEditing = (enabled) => {
    if (!form) return;
    form.classList.toggle('editing', enabled);
    inputs.forEach((input) => {
      input.disabled = !enabled;
      if (!enabled) {
        input.blur();
      }
    });
    saveBtn.style.display = enabled ? 'inline-flex' : 'none';
    cancelBtn.style.display = enabled ? 'inline-flex' : 'none';
  };

  const storeInitialValues = () => {
    inputs.forEach((input) => {
      input.dataset.original = input.value;
    });
  };

  const restoreValues = () => {
    inputs.forEach((input) => {
      if (input.dataset.original !== undefined) {
        input.value = input.dataset.original;
      }
    });
    // revert avatar preview
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

  if (uploadBtn && avatarInput) {
    uploadBtn.addEventListener('click', () => avatarInput.click());
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

  if (editBtn) {
    storeInitialValues();
    enableEditing(false);
    editBtn.addEventListener('click', () => enableEditing(true));
  }

  if (cancelBtn) {
    cancelBtn.addEventListener('click', (e) => {
      e.preventDefault();
      restoreValues();
      enableEditing(false);
    });
  }

  if (saveBtn && form) {
    form.addEventListener('submit', (e) => {
      // Placeholder: prevent real submit until backend is wired
      e.preventDefault();
      storeInitialValues();
      enableEditing(false);
    });
  }
});
