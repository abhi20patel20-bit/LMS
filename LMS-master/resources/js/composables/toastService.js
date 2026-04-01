/**
 * Global toast notification helper
 * Requires a toast instance (e.g., from PrimeVue or another toast lib)
 */
export const showToast = (toast, severity, summary, detail = 'Message Content', life = 3000) => {
  toast.add({
    severity,
    summary,
    detail,
    life
  });
};

// Predefined shortcuts
export const toastSuccess = (toast, summary = 'Success', detail = 'Operation completed') => {
  showToast(toast, 'success', summary, detail);
};

export const toastInfo = (toast, summary = 'Info', detail = 'Information message') => {
  showToast(toast, 'info', summary, detail);
};

export const toastWarn = (toast, summary = 'Warning', detail = 'Be careful') => {
  showToast(toast, 'warn', summary, detail);
};

export const toastError = (toast, summary = 'Error', detail = 'Something went wrong') => {
  showToast(toast, 'error', summary, detail);
};

export const toastSecondary = (toast, summary = 'Note', detail = 'Secondary info') => {
  showToast(toast, 'secondary', summary, detail);
};

export const toastContrast = (toast, summary = 'Notice', detail = 'High contrast alert') => {
  showToast(toast, 'contrast', summary, detail);
};
