import './bootstrap';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

function registerWizard(config = {}) {
  const draftKey = 'register-form-draft-v1';

  return {
    step: 1,
    maxStepReached: 1,
    savedMessageVisible: false,
    idProofNames: [],
    form: {
      name: '',
      email: '',
      employee_id: '',
      phone: '',
      department_id: '',
      position_id: '',
      password: '',
      password_confirmation: '',
    },
    errors: {},

    init() {
      const oldValues = config.old || {};
      const hasOldValues = Object.values(oldValues).some((value) => value);

      if (hasOldValues) {
        this.form = { ...this.form, ...oldValues };
      } else {
        this.restoreDraft();
      }

      const defaults = config.defaults || {};
      if (!this.form.position_id && defaults.position_id) {
        this.form.position_id = String(defaults.position_id);
      }

      this.validateAll();
      this.step = this.resolveInitialStep(config.errors || []);
      this.maxStepReached = this.step;

      this.syncDepartmentSelectFromState();

      this.$watch('form', () => {
        this.saveDraft();
      }, { deep: true });
    },

    syncDepartmentSelectFromState() {
      const desiredValue = this.form.department_id;
      if (!desiredValue) {
        return;
      }

      let attempts = 0;
      const maxAttempts = 10;

      const trySync = () => {
        attempts += 1;

        const selectElement = document.getElementById('department_id');
        if (selectElement) {
          selectElement.value = desiredValue;
          selectElement.dispatchEvent(new Event('change', { bubbles: true }));
          return;
        }

        if (attempts < maxAttempts) {
          window.setTimeout(trySync, 200);
        }
      };

      window.setTimeout(trySync, 200);
    },

    get progressPercent() {
      return this.step * 25;
    },

    stepButtonClass(targetStep) {
      if (targetStep === this.step) {
        return 'bg-cagsu-maroon text-white dark:bg-cagsu-yellow dark:text-gray-900';
      }

      if (targetStep <= this.maxStepReached) {
        return 'bg-cagsu-yellow/20 text-cagsu-maroon dark:bg-cagsu-yellow/30 dark:text-cagsu-yellow';
      }

      return 'bg-gray-100 text-gray-400 dark:bg-gray-900 dark:text-gray-600';
    },

    fieldMessage(field) {
      const message = this.errors[field];

      if (!message) {
        return { type: null, text: '' };
      }

      if (message === 'ok') {
        return { type: 'success', text: this.successText(field) };
      }

      return { type: 'error', text: message };
    },

    successText(field) {
      const successMessages = {
        name: 'Looks good.',
        email: 'Email looks good.',
        password: 'Password strength is acceptable.',
        password_confirmation: 'Passwords match.',
      };

      return successMessages[field] || '';
    },

    validateField(field) {
      const value = this.form[field] ?? '';

      if (field === 'name') {
        this.errors.name = value.length >= 2 ? 'ok' : 'Please enter your full name.';
      }

      if (field === 'email') {
        if (!value) {
          this.errors.email = 'Email is required.';
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
          this.errors.email = 'Please enter a valid email address.';
        } else {
          this.errors.email = 'ok';
        }
      }

      if (field === 'phone') {
        if (!value) {
          delete this.errors.phone;
        } else if (value.length < 7) {
          this.errors.phone = 'Phone number seems too short.';
        } else {
          delete this.errors.phone;
        }
      }

      if (field === 'department_id') {
        this.errors.department_id = value ? 'ok' : 'Please choose your department.';
      }

      if (field === 'position_id') {
        this.errors.position_id = value ? 'ok' : 'Please choose your position.';
      }

      if (field === 'password') {
        if (!value) {
          this.errors.password = 'Password is required.';
        } else if (value.length < 8) {
          this.errors.password = 'Password too short (minimum 8 characters).';
        } else {
          this.errors.password = 'ok';
        }

        this.validateField('password_confirmation');
      }

      if (field === 'password_confirmation') {
        if (!this.form.password_confirmation) {
          this.errors.password_confirmation = 'Please confirm your password.';
        } else if (this.form.password !== this.form.password_confirmation) {
          this.errors.password_confirmation = 'Passwords do not match.';
        } else {
          this.errors.password_confirmation = 'ok';
        }
      }
    },

    applyTitleCase(field) {
      const value = this.form[field];
      if (!value || typeof value !== 'string') {
        return;
      }

      const titleCased = this.toTitleCase(value);
      if (titleCased !== value) {
        this.form[field] = titleCased;
        this.validateField(field);
      }
    },

    toTitleCase(value) {
      return value
        .trim()
        .split(/\s+/)
        .map((word) => this.titleCaseWord(word))
        .join(' ');
    },

    titleCaseWord(word) {
      const segmentSeparators = ['-', "'"];

      let segments = [word];
      segmentSeparators.forEach((separator) => {
        segments = segments.flatMap((segment) => segment.split(separator).flatMap((part, index, all) => {
          if (all.length === 1) {
            return [segment];
          }

          const rejoined = [];
          for (let i = 0; i < all.length; i += 1) {
            rejoined.push(all[i]);
            if (i < all.length - 1) {
              rejoined.push(separator);
            }
          }
          return rejoined;
        }));
      });

      const rebuilt = [];
      for (let i = 0; i < segments.length; i += 1) {
        const segment = segments[i];
        if (segment === '-' || segment === '\'') {
          rebuilt.push(segment);
          continue;
        }

        rebuilt.push(this.capitalizeToken(segment));
      }

      return rebuilt.join('');
    },

    capitalizeToken(token) {
      const match = token.match(/^([A-Za-z])([A-Za-z]*)(\.)?$/);
      if (!match) {
        return token;
      }

      const first = match[1].toUpperCase();
      const rest = (match[2] || '').toLowerCase();
      const period = match[3] || '';

      return `${first}${rest}${period}`;
    },

    validateAll() {
      [
        'name',
        'email',
        'phone',
        'department_id',
        'position_id',
        'password',
        'password_confirmation',
      ].forEach((field) => this.validateField(field));
    },

    resolveInitialStep(serverErrorKeys) {
      if (!Array.isArray(serverErrorKeys) || serverErrorKeys.length === 0) {
        return 1;
      }

      if (serverErrorKeys.some((field) => ['name', 'email', 'employee_id', 'phone'].includes(field))) {
        return 1;
      }

      if (serverErrorKeys.some((field) => ['department_id', 'position_id'].includes(field))) {
        return 2;
      }

      if (serverErrorKeys.some((field) => ['password', 'password_confirmation', 'id_proof'].includes(field))) {
        return 3;
      }

      return 1;
    },

    validateStep(step) {
      if (step === 1) {
        this.validateField('name');
        this.validateField('email');

        return this.errors.name === 'ok' && this.errors.email === 'ok' && !this.errors.phone;
      }

      if (step === 2) {
        this.validateField('department_id');
        this.validateField('position_id');

        return this.errors.department_id === 'ok' && this.errors.position_id === 'ok';
      }

      if (step === 3) {
        this.validateField('password');
        this.validateField('password_confirmation');

        const hasFile = this.idProofNames.length || document.getElementById('id_proof')?.files?.length;

        return this.errors.password === 'ok' && this.errors.password_confirmation === 'ok' && hasFile;
      }

      return true;
    },

    nextStep() {
      if (!this.validateStep(this.step)) {
        return;
      }

      if (this.step < 4) {
        this.step += 1;
        this.maxStepReached = Math.max(this.maxStepReached, this.step);
      }
    },

    prevStep() {
      if (this.step > 1) {
        this.step -= 1;
      }
    },

    goToStep(targetStep) {
      if (targetStep < 1 || targetStep > 4) {
        return;
      }

      if (targetStep > this.maxStepReached + 1) {
        return;
      }

      if (targetStep > this.step && !this.validateStep(this.step)) {
        return;
      }

      this.step = targetStep;
      this.maxStepReached = Math.max(this.maxStepReached, this.step);
    },

    handleEnter(event) {
      if (event.target.type === 'textarea') {
        return;
      }

      if (this.step < 4) {
        this.nextStep();
      }
    },

    selectedText(selectId) {
      const selectElement = document.getElementById(selectId);
      if (!selectElement || !selectElement.selectedOptions?.length) {
        return '';
      }

      return selectElement.selectedOptions[0].textContent?.trim() || '';
    },

    handleIdProofChange(event) {
      const files = Array.from(event.target.files || []);
      this.idProofNames = files.map((file) => file.name);
    },

    idProofNamesLabel() {
      if (!this.idProofNames.length) {
        return 'Not selected yet';
      }

      if (this.idProofNames.length <= 2) {
        return this.idProofNames.join(', ');
      }

      return `${this.idProofNames.slice(0, 2).join(', ')} (+${this.idProofNames.length - 2} more)`;
    },

    saveDraft(showIndicator = true) {
      const payload = {
        name: this.form.name,
        email: this.form.email,
        employee_id: this.form.employee_id,
        phone: this.form.phone,
        department_id: this.form.department_id,
        position_id: this.form.position_id,
        password: this.form.password,
        password_confirmation: this.form.password_confirmation,
      };

      window.localStorage.setItem(draftKey, JSON.stringify(payload));

      if (showIndicator) {
        this.savedMessageVisible = true;
        window.clearTimeout(this.savedTimer);
        this.savedTimer = window.setTimeout(() => {
          this.savedMessageVisible = false;
        }, 1200);
      }
    },

    restoreDraft() {
      const draft = window.localStorage.getItem(draftKey);
      if (!draft) {
        return;
      }

      try {
        const parsed = JSON.parse(draft);
        this.form = { ...this.form, ...parsed };
      } catch (error) {
        window.localStorage.removeItem(draftKey);
      }
    },

    clearDraft(resetForm = false) {
      window.localStorage.removeItem(draftKey);
      this.savedMessageVisible = false;

      if (resetForm) {
        this.form = {
          name: '',
          email: '',
          employee_id: '',
          phone: '',
          department_id: '',
          position_id: '',
          password: '',
          password_confirmation: '',
        };
        this.errors = {};
        this.step = 1;
        this.maxStepReached = 1;
        this.idProofNames = [];
      }
    },

    prepareSubmit(event) {
      if (this.step !== 4) {
        event.preventDefault();
        this.nextStep();
        return;
      }

      this.validateAll();
      if (!this.validateStep(3)) {
        event.preventDefault();
        this.step = 3;
        return;
      }

      this.clearDraft(false);
    },
  };
}

// Alpine is provided by Livewire on app layout; on guest/landing we start it here so dropdowns and collapse work.
function registerWizardWithAlpine(alpineInstance) {
  if (!alpineInstance || typeof alpineInstance.data !== 'function') {
    return;
  }

  try {
    if (typeof alpineInstance.plugin === 'function') {
      alpineInstance.plugin(collapse);
    }
  } catch (error) {
    // ignore duplicate plugin registration
  }

  alpineInstance.data('registerWizard', registerWizard);
  window.registerWizard = registerWizard;
}

document.addEventListener('alpine:init', function () {
  registerWizardWithAlpine(window.Alpine);
});

document.addEventListener('DOMContentLoaded', function () {
  if (typeof window.Livewire === 'undefined') {
    window.Alpine = Alpine;
    registerWizardWithAlpine(window.Alpine);
    window.Alpine.start();
  } else if (window.Alpine) {
    registerWizardWithAlpine(window.Alpine);
  }
});
