document.addEventListener('DOMContentLoaded', function () {
	const numbersList = document.getElementById('numbers-list');
	const addRowBtn = document.getElementById('addRowBtn');

	addRowBtn.addEventListener('click', function () {
		const row = document.createElement('div');
		row.className = 'row mb-2 align-items-center number-row';
		row.innerHTML = `
			<div class="col-md-3">
				<div class="input-group">
					<span class="input-group-text">+(243)</span>
					<input type="text" class="form-control" name="numero[]" placeholder="Numéro de téléphone" required>
				</div>
			</div>
			<div class="col-md-6">
				<input type="text" class="form-control" name="raison[]" placeholder="Raison de l'échec" required>
			</div>
			<div class="col-md-1">
				<button type="button" class="btn btn-link text-danger btn-remove-row" title="Supprimer">
					<i class="bx bx-trash"></i>
				</button>
			</div>
		`;
		numbersList.appendChild(row);
	});

	numbersList.addEventListener('click', function (e) {
		if (e.target.closest('.btn-remove-row')) {
			const row = e.target.closest('.number-row');
			if (row) row.remove();
		}
	});
});
