import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    connect() {
        console.log('Kanban connecté');
    }

    selectionner(event) {
        const carte = event.currentTarget;

        const numero = carte.dataset.numero;

        console.log('OF sélectionné :', numero);
    }

    dragStart(event) {
        const carte = event.currentTarget;

        const numero = carte.dataset.numero;

        console.log('Début déplacement :', numero);

        event.dataTransfer.effectAllowed = 'move';

        event.dataTransfer.setData(
            'text/plain',
            numero
        );

        carte.classList.add('opacity-50');
    }

    dragEnd(event) {
        const carte = event.currentTarget;

        carte.classList.remove('opacity-50');

        console.log('Fin déplacement');
    }

    dragOver(event) {
        event.preventDefault();

        event.currentTarget.classList.add(
            'ring-2',
            'ring-blue-400'
        );
    }

    dragLeave(event) {
        event.currentTarget.classList.remove(
            'ring-2',
            'ring-blue-400'
        );
    }

    async drop(event) {
        event.preventDefault();

        const colonne = event.currentTarget;

        colonne.classList.remove(
            'ring-2',
            'ring-blue-400'
        );

        const numero = event.dataTransfer.getData(
            'text/plain'
        );

        const statut = colonne.dataset.statut;

        console.log(
            'OF déplacé :',
            numero,
            '→',
            statut
        );

        if (!numero || !statut) {
            console.error(
                'Numéro ou statut manquant.'
            );

            return;
        }

        const carte = this.element.querySelector(
            `[data-numero="${numero}"]`
        );

        if (!carte) {
            console.error(
                'Carte OF introuvable :',
                numero
            );

            return;
        }

        const id = carte.dataset.id;

        if (!id) {
            console.error(
                'ID de l’OF manquant.'
            );

            return;
        }

        const token = carte.dataset.csrfToken;

        if (!token) {
            console.error(
                'Token CSRF manquant.'
            );

            return;
        }

        try {

            const formData = new FormData();

            formData.append(
                '_token',
                token
            );

            formData.append(
                'statut',
                statut
            );

            const response = await fetch(
                `/ordre/fabrication/${id}/kanban/statut`,
                {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            );

            const data = await response.json();

            if (!response.ok || !data.success) {

                throw new Error(
                    data.message
                    || 'Erreur lors du déplacement.'
                );
            }

            console.log(
                'Statut enregistré :',
                data.statut
            );

            window.location.reload();

        } catch (error) {

            console.error(
                'Erreur Kanban :',
                error
            );

            alert(
                error.message
                || 'Impossible de déplacer cet OF.'
            );
        }
    }
}