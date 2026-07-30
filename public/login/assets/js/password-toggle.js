/**
 * Password Toggle - Afficher/Masquer le mot de passe
 * Fonctionne avec les éléments ayant la classe .form-password-toggle
 */
document.addEventListener('DOMContentLoaded', function() {
    // Sélectionner tous les toggles de mot de passe
    const toggleButtons = document.querySelectorAll('.form-password-toggle .input-group-text.cursor-pointer');
    
    toggleButtons.forEach(function(toggleButton) {
        toggleButton.addEventListener('click', function() {
            const inputGroup = this.closest('.input-group');
            const passwordInput = inputGroup.querySelector('input[type="password"], input[type="text"]');
            const icon = this.querySelector('i');
            
            if (passwordInput) {
                // Toggle le type de l'input
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Toggle l'icône
                if (type === 'text') {
                    icon.classList.remove('bx-hide');
                    icon.classList.add('bx-show');
                } else {
                    icon.classList.remove('bx-show');
                    icon.classList.add('bx-hide');
                }
            }
        });
    });
});
