document.addEventListener('DOMContentLoaded', function () {
    const ecoleSelect = document.getElementById('cours_ecole');
    const professeurSelect = document.getElementById('cours_professeur');

    if (ecoleSelect && professeurSelect) {
        ecoleSelect.addEventListener('change', function () {
            if (ecoleSelect.value) {
                professeurSelect.removeAttribute('disabled');
            } else {
                professeurSelect.setAttribute('disabled', 'disabled');
            }
        });
    }
});
