// Fix pour forcer l'affichage horizontal des formulaires
(function() {
    'use strict';
    
    function forceHorizontalDisplay() {
        // Force tous les éléments à s'afficher horizontalement
        const elements = document.querySelectorAll('label, .form-label, input, select, td, tr, table');
        
        elements.forEach(function(el) {
            el.style.writingMode = 'horizontal-tb';
            el.style.webkitWritingMode = 'horizontal-tb';
            el.style.msWritingMode = 'lr-tb';
            el.style.textOrientation = 'mixed';
        });
        
        // Force les labels à être en block
        const labels = document.querySelectorAll('label, .form-label');
        labels.forEach(function(label) {
            label.style.display = 'block';
            label.style.width = '100%';
        });
        
        // Force les inputs et selects
        const inputs = document.querySelectorAll('input, select');
        inputs.forEach(function(input) {
            input.style.display = 'block';
            input.style.width = '100%';
        });
        
        console.log('Formulaire forcé en mode horizontal');
    }
    
    // Exécute immédiatement
    forceHorizontalDisplay();
    
    // Exécute après le chargement du DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', forceHorizontalDisplay);
    }
    
    // Exécute après le chargement complet de la page
    window.addEventListener('load', forceHorizontalDisplay);
    
    // Exécute après un court délai pour être sûr
    setTimeout(forceHorizontalDisplay, 100);
    setTimeout(forceHorizontalDisplay, 500);
    setTimeout(forceHorizontalDisplay, 1000);
})();
