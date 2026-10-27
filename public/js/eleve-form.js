
document.addEventListener('DOMContentLoaded', () => {
    const ecoleSelect = document.getElementById('eleve_ecole');
    const sectionSelect = document.getElementById('eleve_section');
    const classeSelect = document.getElementById('eleve_classe');
    const optionSelect = document.getElementById('eleve_option');

    // Vérifier que les quatre champs existent sur la page.
    if (!ecoleSelect || !sectionSelect || !classeSelect || !optionSelect) {
        return;
    }

    /**
     * Réinitialise une liste déroulante.
     */
    function resetSelect(select, placeholder) {
        select.replaceChildren(new Option(placeholder, ''));
        select.disabled = true;
    }

    /**
     * Charge les éléments d'une liste depuis une route AJAX.
     * Le JSON attendu est : [{ id: 1, nom: "..." }]
     */
    async function loadSelect(select, url, placeholder) {
        resetSelect(select, 'Chargement...');

        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (!response.ok) {
                throw new Error(`Erreur HTTP ${response.status}`);
            }

            const items = await response.json();

            resetSelect(select, placeholder);

            items.forEach(item => {
                select.add(new Option(item.nom, item.id));
            });

            select.disabled = false;
        } catch (error) {
            console.error('Erreur de chargement :', error);
            resetSelect(select, 'Erreur de chargement');
        }
    }

    // Quand l'école change : charger les sections.
    ecoleSelect.addEventListener('change', async () => {
        resetSelect(sectionSelect, 'Choisir une section');
        resetSelect(classeSelect, 'Choisir une classe');
        resetSelect(optionSelect, 'Choisir une option');

        if (!ecoleSelect.value) return;

        await loadSelect(
            sectionSelect,
            `/eleve/ajax/sections/${ecoleSelect.value}`,
            'Choisir une section'
        );
    });

    // Quand la section change : charger les classes.
    sectionSelect.addEventListener('change', async () => {
        resetSelect(classeSelect, 'Choisir une classe');
        resetSelect(optionSelect, 'Choisir une option');

        if (!sectionSelect.value) return;

        await loadSelect(
            classeSelect,
            `/eleve/ajax/classes/${sectionSelect.value}`,
            'Choisir une classe'
        );
    });

    // Quand la classe change : charger les options.
    classeSelect.addEventListener('change', async () => {
        resetSelect(optionSelect, 'Choisir une option');

        if (!classeSelect.value) return;

        await loadSelect(
            optionSelect,
            `/eleve/ajax/options/${classeSelect.value}`,
            'Choisir une option'
        );
    });
});
