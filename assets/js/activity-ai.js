document.addEventListener('DOMContentLoaded', () => {
    const button = document.querySelector('[data-activity-ai-generate]');

    if (!button) {
        return;
    }

    const form = button.closest('form');
    const feedbackContainer = document.querySelector('[data-activity-ai-feedback]');
    const csrfInput = document.querySelector('[data-activity-ai-csrf]');
    const titleInput = form?.querySelector('[name$="[titre]"]');
    const descriptionInput = form?.querySelector('[name$="[description]"]');
    const categoryInput = form?.querySelector('[name$="[category]"]');
    const ageMinInput = form?.querySelector('[name$="[ageMin]"]');
    const ageMaxInput = form?.querySelector('[name$="[ageMax]"]');
    const endpoint = button.dataset.generateUrl;
    const defaultLabel = button.innerHTML;

    if (!form || !feedbackContainer || !csrfInput || !titleInput || !descriptionInput || !categoryInput || !ageMinInput || !ageMaxInput || !endpoint) {
        return;
    }

    const renderAlert = (type, message) => {
        feedbackContainer.innerHTML = `<div class="alert alert-${type} rounded-4 border-0 shadow-sm mb-0" role="alert">${message}</div>`;
    };

    const getAgeValue = (input) => {
        const rawValue = input.value.trim();

        if (rawValue === '') {
            return null;
        }

        return Number.parseInt(rawValue, 10);
    };

    button.addEventListener('click', async () => {
        const title = titleInput.value.trim();
        const ageMin = getAgeValue(ageMinInput);
        const ageMax = getAgeValue(ageMaxInput);
        const selectedOption = categoryInput.options[categoryInput.selectedIndex];
        const category = categoryInput.value ? selectedOption.text.trim() : null;

        if (title === '') {
            renderAlert('danger', 'Veuillez saisir un titre avant de generer la description.');
            return;
        }

        if ((ageMin !== null && Number.isNaN(ageMin)) || (ageMax !== null && Number.isNaN(ageMax)) || (ageMin !== null && ageMax !== null && ageMax < ageMin)) {
            renderAlert('danger', 'Veuillez verifier les ages avant de generer la description.');
            return;
        }

        if (descriptionInput.value.trim() !== '') {
            const confirmed = window.confirm('Une description existe deja. Voulez-vous la remplacer par la description generee ?');

            if (!confirmed) {
                return;
            }
        }

        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Generation en cours...';
        feedbackContainer.innerHTML = '';

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfInput.value,
                },
                body: JSON.stringify({
                    title,
                    category,
                    ageMin,
                    ageMax,
                }),
            });

            const data = await response.json();

            if (!response.ok || !data.success || typeof data.description !== 'string') {
                renderAlert('danger', data.message || 'Impossible de generer la description. Veuillez reessayer ou saisir la description manuellement.');
                return;
            }

            descriptionInput.value = data.description;
            descriptionInput.dispatchEvent(new Event('input', { bubbles: true }));
            renderAlert('success', 'Description generee avec succes. Vous pouvez la modifier avant d enregistrer.');
        } catch (error) {
            renderAlert('danger', 'Impossible de generer la description. Veuillez reessayer ou saisir la description manuellement.');
        } finally {
            button.disabled = false;
            button.innerHTML = defaultLabel;
        }
    });
});
