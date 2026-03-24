/**
 * Axiomeer — Application JavaScript
 */
import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

/**
 * Domain Selector — persist selection in localStorage
 */
document.addEventListener('DOMContentLoaded', function () {
    const domainLabel = document.querySelector('.axiomeer-domain-label');
    const domainItems = document.querySelectorAll('.axiomeer-domain-item');
    const saved = localStorage.getItem('axiomeer-domain') || 'legal';

    if (domainLabel) {
        domainLabel.textContent = saved.charAt(0).toUpperCase() + saved.slice(1);
    }

    domainItems.forEach(function (item) {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            const domain = this.getAttribute('data-domain');
            localStorage.setItem('axiomeer-domain', domain);
            if (domainLabel) {
                domainLabel.textContent = domain.charAt(0).toUpperCase() + domain.slice(1);
            }
        });
    });
});
