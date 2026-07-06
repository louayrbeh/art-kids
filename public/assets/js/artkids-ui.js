const artkidsInitUi = () => {
    if (window.lucide) {
        window.lucide.createIcons();
    }

    const navbars = document.querySelectorAll('.art-appbar, .home-navbar, .parent-navbar, .admin-topbar');
    const syncNavbarState = () => {
        navbars.forEach((navbar) => {
            navbar.classList.toggle('scrolled', window.scrollY > 12);
        });
    };

    syncNavbarState();
    if (!document.documentElement.dataset.artScrollBound) {
        document.documentElement.dataset.artScrollBound = '1';
        window.addEventListener('scroll', syncNavbarState, { passive: true });
    }

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
        '.home-bento-card',
        '.home-studio-card',
        '.home-panel',
        '.home-metric-card',
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

    document.querySelectorAll('.art-flash').forEach((flash) => {
        if (flash.dataset.artFlashBound === '1') {
            return;
        }

        flash.dataset.artFlashBound = '1';
        window.setTimeout(() => {
            if (!flash.parentNode) {
                return;
            }

            const instance = window.bootstrap ? window.bootstrap.Alert.getOrCreateInstance(flash) : null;
            if (instance) {
                instance.close();
            }
        }, 5200);
    });
};

document.addEventListener('DOMContentLoaded', artkidsInitUi);
document.addEventListener('turbo:load', artkidsInitUi);
