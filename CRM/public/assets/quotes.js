(() => {
    const lines = document.getElementById('lines');
    const template = document.getElementById('line-template');
    const total = document.getElementById('estimated');
    const addButton = document.getElementById('add-line');

    if (!lines || addButton.disabled) {
        return;
    }

    const submitButton = document.querySelector('#quote-form button[type="submit"]');
    const maxLines = Number(lines.dataset.maxLines);
    const maxCents = Number(lines.dataset.maxCents);
    const format = new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    });

    function update() {
        let sumCents = 0;

        lines.querySelectorAll('.quote-line').forEach(row => {
            const option = row.querySelector('select').selectedOptions[0];
            const quantity = row.querySelector('input');
            const priceCents = Number((option?.dataset.price || '0.00').replace('.', ''));

            if (quantity.validity.valid) {
                sumCents += priceCents * quantity.valueAsNumber;
            }
        });

        total.textContent = sumCents > maxCents ? 'Fuera de rango' : format.format(sumCents / 100);
        submitButton.disabled = sumCents > maxCents;
        addButton.disabled = lines.children.length >= maxLines;
    }

    function add() {
        const line = template.content.cloneNode(true);
        lines.append(line);
        update();
    }

    addButton.addEventListener('click', add);
    lines.addEventListener('input', update);
    lines.addEventListener('change', update);

    lines.addEventListener('click', event => {
        if (event.target.classList.contains('remove') && lines.children.length > 1) {
            event.target.closest('.quote-line').remove();
            update();
        }
    });

    add();
})();
