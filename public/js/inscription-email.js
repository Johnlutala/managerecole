document.addEventListener('DOMContentLoaded', function () {
    const emailInput = document.getElementById('inscription_email');
    const feedback = document.getElementById('email-feedback');

    if (!emailInput || !feedback) {
        console.error('Champ email ou zone de message introuvable.');
        return;
    }

    const checkUrl = emailInput.dataset.checkUrl;
    let controller = null;

    function afficherMessage(message, type) {
        feedback.textContent = message;

        feedback.className = 'small mt-1';

        emailInput.classList.remove('is-valid', 'is-invalid');

        if (type === 'success') {
            feedback.classList.add('text-success');
            emailInput.classList.add('is-valid');
            emailInput.style.color = 'green';
        } else if (type === 'error') {
            feedback.classList.add('text-danger');
            emailInput.classList.add('is-invalid');
            emailInput.style.color = 'red';
        } else {
            feedback.classList.add('text-muted');
            emailInput.style.color = '';
        }
    }

    async function verifierEmail() {
        const email = emailInput.value.trim();

        // Annuler une éventuelle vérification précédente.
        if (controller) {
            controller.abort();
            controller = null;
        }

        emailInput.classList.remove('is-valid', 'is-invalid');
        emailInput.style.color = '';
        feedback.textContent = '';

        // Ne rien vérifier si le champ est vide.
        if (email === '') {
            return;
        }

        // Vérifier d'abord le format de l'adresse.
        if (!emailInput.checkValidity()) {
            afficherMessage(
                'Veuillez saisir une adresse e-mail valide.',
                'error'
            );
            return;
        }

        if (!checkUrl) {
            afficherMessage(
                'La route de vérification est introuvable.',
                'error'
            );
            return;
        }

        controller = new AbortController();
        const currentController = controller;

        afficherMessage('Vérification en cours...', 'loading');

        try {
            const url = new URL(checkUrl, window.location.origin);
            url.searchParams.set('email', email);

            const response = await fetch(url.toString(), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                },
                signal: currentController.signal
            });

            if (!response.ok) {
                throw new Error(`Erreur HTTP ${response.status}`);
            }

            const data = await response.json();

            // Ignorer la réponse si le contenu du champ a changé.
            if (emailInput.value.trim() !== email) {
                return;
            }

            if (data.available === true) {
                afficherMessage(
                    'Cette adresse e-mail est disponible.',
                    'success'
                );
            } else if (data.available === false) {
                afficherMessage(
                    data.message || 'Cette adresse e-mail est déjà utilisée.',
                    'error'
                );
            } else {
                throw new Error('Réponse inattendue du serveur.');
            }

        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            console.error('Erreur de vérification email :', error);

            afficherMessage(
                'Impossible de vérifier cette adresse. Réessayez.',
                'error'
            );
        } finally {
            if (controller === currentController) {
                controller = null;
            }
        }
    }

    // Vérification dès que l'utilisateur quitte le champ.
    emailInput.addEventListener('blur', verifierEmail);

    // Effacer le résultat précédent dès que l'adresse change.
    emailInput.addEventListener('input', function () {
        if (controller) {
            controller.abort();
            controller = null;
        }

        emailInput.classList.remove('is-valid', 'is-invalid');
        emailInput.style.color = '';
        feedback.textContent = '';
    });
});