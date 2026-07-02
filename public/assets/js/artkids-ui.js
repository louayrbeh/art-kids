const artkidsInitUi = () => {
    const revealTargets = document.querySelectorAll([
        '.admin-surface',
        '.parent-surface',
        '.admin-stat-card',
        '.parent-stat-card',
        '.art-auth-card',
        '.stats-panel',
        '.contact-banner',
        '.step-card',
        '.universe-card',
        '.stat-card',
        '.home-spotlight-card',
        '.home-benefit-card',
        '.home-testimonial',
        '.art-ai-panel',
    ].join(','));

    revealTargets.forEach((element, index) => {
        if (element.dataset.artRevealBound === '1') {
            return;
        }

        element.dataset.artRevealBound = '1';
        element.classList.add('art-reveal');
        element.style.setProperty('--art-delay', `${Math.min(index * 45, 360)}ms`);
    });

    document.querySelectorAll('.btn-primary, .btn-art-primary, .home-btn-primary').forEach((button) => {
        button.classList.add('glow-hover');
    });

    document.querySelectorAll('.art-upload-panel input[type="file"]').forEach((input) => {
        if (input.dataset.artUploadBound === '1') {
            return;
        }

        input.dataset.artUploadBound = '1';
        input.addEventListener('change', () => {
            const panel = input.closest('.art-upload-panel');
            const filenameHolder = panel ? panel.querySelector('[data-upload-filename]') : null;

            if (!filenameHolder) {
                return;
            }

            filenameHolder.textContent = input.files && input.files.length > 0
                ? input.files[0].name
                : 'Aucun fichier selectionne';
        });
    });
};

document.addEventListener('DOMContentLoaded', artkidsInitUi);
document.addEventListener('turbo:load', artkidsInitUi);
