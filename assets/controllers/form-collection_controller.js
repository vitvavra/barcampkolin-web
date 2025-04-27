// assets/controllers/form-collection_controller.js

import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["collectionContainer"]

    static values = {
        index: Number,
        prototype: String,
    }

    addCollectionElement(event) {
        const item = document.createElement('div');
        item.innerHTML = this.prototypeValue.replace(/__name__/g, this.indexValue);
        this.collectionContainerTarget.append(item);
        this.indexValue++;
    }

    deleteCollectionElement(event) {
        const tgt = event.target;
        tgt.closest('.collection-item').remove();
    }
}
