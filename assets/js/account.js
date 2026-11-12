
async function resetAccountPassword(url, csrfToken) {
    // Confirmation
    const confirmation = await Swal.fire({
        title: 'Réinitialiser le mot de passe ?',
        text: 'Le mot de passe actuel sera remplacé par un nouveau.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Oui, réinitialiser',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d'
    });

    if (!confirmation.isConfirmed) {
        return;
    }

    Swal.fire({
        title: 'Réinitialisation...',
        text: 'Veuillez patienter.',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                _token: csrfToken
            })
        });

        const data = await response.json();

        Swal.close();

        if (data.success) {
            await Swal.fire({
                icon: data.mailSent ? 'success' : 'warning',
                title: data.mailSent
                    ? 'Réinitialisation réussie'
                    : 'Envoi du courriel échoué',
                text: data.message,
                confirmButtonText: 'Fermer',
                confirmButtonColor: '#198754'
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: data.message || 'Une erreur est survenue.'
            });
        }

    } catch (error) {
        Swal.close();

        console.error('Erreur de réinitialisation :', error);

        Swal.fire({
            icon: 'error',
            title: 'Erreur de communication',
            text: 'Impossible de contacter le serveur. Veuillez réessayer.'
        });
    }
}
