import * as bootstrap from 'bootstrap';
import Alpine from 'alpinejs';

import formSubmit from './components/form-submit';
import passwordField from './components/password-field';
import deleteUser from './components/delete-user';
import autoSubmit from './components/auto-submit';

window.bootstrap = bootstrap;
window.Alpine = Alpine;

Alpine.data('formSubmit', formSubmit);
Alpine.data('passwordField', passwordField);
Alpine.data('deleteUser', deleteUser);
Alpine.data('autoSubmit', autoSubmit);

Alpine.start();
