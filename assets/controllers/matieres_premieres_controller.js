import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
static targets = [
'produit',
'option',
'message'
];


connect() {
    this.mettreAJour();
}

produitChange() {
    this.mettreAJour();
}

mettreAJour() {
    const produitId = this.produitTarget.value;

    let nombreVisibles = 0;

    this.optionTargets.forEach((option) => {
        const checkbox = option.querySelector(
            'input[type="checkbox"]'
        );

        if (!checkbox) {
            option.classList.add('hidden');
            return;
        }

        const productIds = checkbox.dataset.productIds
            ? checkbox.dataset.productIds
                .split(',')
                .filter(Boolean)
            : [];

        const visible =
            produitId !== ''
            && productIds.includes(produitId);

        option.classList.toggle('hidden', !visible);

        if (!visible) {
            checkbox.checked = false;
        }

        if (visible) {
            nombreVisibles++;
        }
    });

    if (produitId === '') {
        this.messageTarget.textContent =
            'Sélectionnez d’abord un produit pour afficher les matières premières disponibles.';

        this.messageTarget.classList.remove('hidden');

        return;
    }

    if (nombreVisibles === 0) {
        this.messageTarget.textContent =
            'Aucune matière première n’est associée à ce produit.';

        this.messageTarget.classList.remove('hidden');

        return;
    }

    this.messageTarget.classList.add('hidden');
}


}
