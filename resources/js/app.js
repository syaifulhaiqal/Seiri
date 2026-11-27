import * as bootstrap from 'bootstrap';
import Alpine from 'alpinejs';

import formSubmit from './components/form-submit';
import passwordField from './components/password-field';
import deleteUser from './components/delete-user';
import autoSubmit from './components/auto-submit';
import journalLines from './components/journal-lines';
import invoiceLines from './components/invoice-lines';
import itemLines from './components/item-lines';
import themeToggle from './components/theme-toggle';
import remoteClock from './components/remote-clock';
import qrKiosk from './components/qr-kiosk';
import copyText from './components/copy-text';
import navGroup from './components/nav-group';

window.bootstrap = bootstrap;
window.Alpine = Alpine;

Alpine.data('formSubmit', formSubmit);
Alpine.data('passwordField', passwordField);
Alpine.data('deleteUser', deleteUser);
// Same modal behaviour, named for any record (see components/delete-modal.blade.php).
Alpine.data('confirmDelete', deleteUser);
Alpine.data('autoSubmit', autoSubmit);
Alpine.data('journalLines', journalLines);
Alpine.data('invoiceLines', invoiceLines);
Alpine.data('itemLines', itemLines);
Alpine.data('themeToggle', themeToggle);
Alpine.data('remoteClock', remoteClock);
Alpine.data('qrKiosk', qrKiosk);
Alpine.data('copyText', copyText);
Alpine.data('navGroup', navGroup);

Alpine.start();
