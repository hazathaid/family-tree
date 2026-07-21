import './bootstrap';
import 'bootstrap';

document.addEventListener('shown.bs.offcanvas', (event) => {
    event.target.querySelector('a, button')?.focus();
});
