import Alpine from 'alpinejs';

document.addEventListener('alpine:init', () => {
  Alpine.data('formsComponent', () => ({
    // Contact Form
    contactForm: {
      firstName: '',
      lastName: '',
      email: '',
      subject: '',
      message: ''
    },
    contactErrors: {},
    isSubmittingContact: false,

    // Registration Form
    regForm: {
      username: '',
      email: '',
      password: '',
      confirmPassword: '',
      agreeToTerms: false
    },
    regErrors: {},
    isSubmittingReg: false,
    passwordStrength: { score: 0, level: 'weak' },

    // File Upload
    files: [],
    isDragging: false,
    _uploadIntervals: [],

    // Wizard
    wizardStep: 1,
    wizardData: {
      personal: { firstName: '', lastName: '', email: '' },
      account:  { username: '', password: '' },
      address:  { street: '', city: '', zip: '' }
    },
    wizardErrors: { personal: {}, account: {}, address: {} },

    // Cleanup tracked intervals on component destroy
    destroy() {
      this._uploadIntervals.forEach(id => clearInterval(id));
      this._uploadIntervals = [];
    },

    // General method to validate a field for any form
    validate(formName, field) {
      let errors;
      let form;

      switch (formName) {
        case 'contact':
          errors = this.contactErrors;
          form   = this.contactForm;
          break;
        case 'registration':
          errors = this.regErrors;
          form   = this.regForm;
          break;
        default:
          return;
      }

      switch (field) {
        case 'firstName':
          errors.firstName = form.firstName.length < 2 ? 'First name must be at least 2 characters' : '';
          break;
        case 'lastName':
          errors.lastName = form.lastName.length < 2 ? 'Last name must be at least 2 characters' : '';
          break;
        case 'email': {
          const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
          errors.email = !emailRegex.test(form.email) ? 'Please enter a valid email address' : '';
          break;
        }
        case 'username':
          errors.username = form.username.length < 3 ? 'Username must be at least 3 characters' : '';
          break;
        case 'password':
          this.updatePasswordStrength();
          errors.password = this.passwordStrength.score < 3 ? 'Password is too weak' : '';
          break;
        case 'confirmPassword':
          errors.confirmPassword = form.password !== form.confirmPassword ? 'Passwords do not match' : '';
          break;
      }
    },

    updatePasswordStrength() {
      const password = this.regForm.password;
      let score = 0;
      if (password.length >= 8)         score++;
      if (/[a-z]/.test(password))       score++;
      if (/[A-Z]/.test(password))       score++;
      if (/[0-9]/.test(password))       score++;
      if (/[^A-Za-z0-9]/.test(password)) score++;

      const levels = ['weak', 'weak', 'fair', 'good', 'strong'];
      this.passwordStrength = { score, level: levels[Math.min(score, 4)] };
    },

    handleFiles(event) {
      const fileList = event.target?.files || event.dataTransfer?.files;
      if (!fileList) return;

      for (let i = 0; i < fileList.length; i++) {
        const file = fileList[i];
        const fileObj = {
          id: Date.now() + i,
          name: file.name,
          size: (file.size / 1024).toFixed(2) + ' KB',
          progress: 0,
          status: 'uploading'
        };
        this.files.push(fileObj);
        this.simulateUpload(fileObj);
      }
    },

    simulateUpload(fileObj) {
      const intervalId = setInterval(() => {
        fileObj.progress += 10;
        if (fileObj.progress >= 100) {
          fileObj.progress = 100;
          fileObj.status = 'complete';
          clearInterval(intervalId);
          this._uploadIntervals = this._uploadIntervals.filter(id => id !== intervalId);
        }
      }, 200);
      this._uploadIntervals.push(intervalId);
    },

    removeFile(fileId) {
      this.files = this.files.filter(f => f.id !== fileId);
    },

    // Wizard methods
    nextStep() { if (this.wizardStep < 3) this.wizardStep++; },
    prevStep() { if (this.wizardStep > 1) this.wizardStep--; },
    goToStep(step) { this.wizardStep = step; },

    async submitContactForm() {
      this.isSubmittingContact = true;
      await new Promise(res => setTimeout(res, 1500));
      this.isSubmittingContact = false;
      window.AdminApp?.notificationManager?.success(
        'Your message has been sent! We\'ll get back to you shortly.'
      );
      // Reset contact form
      this.contactForm = { firstName: '', lastName: '', email: '', subject: '', message: '' };
      this.contactErrors = {};
    },

    async submitRegForm() {
      this.isSubmittingReg = true;
      await new Promise(res => setTimeout(res, 1500));
      this.isSubmittingReg = false;
      window.AdminApp?.notificationManager?.success(
        'Account created successfully! Welcome aboard.'
      );
      // Reset registration form
      this.regForm = { username: '', email: '', password: '', confirmPassword: '', agreeToTerms: false };
      this.regErrors = {};
    }
  }));
});