import {ApplicationController} from 'stimulus-use'

export default class extends ApplicationController {
    static values = {
        isExpanded: Boolean
    };

    connect() {
        this.isExpandedValue = false;
        this.element.style.maxHeight = '25rem'

        if (this.element.scrollHeight > this.element.clientHeight
            || this.element.scrollWidth > this.element.clientWidth) {

            this.moreBtn = this.createMoreBtn();
            this.more();
        } else {
            this.element.style.maxHeight = null;
        }
    }

    createMoreBtn() {
        let moreBtn = document.createElement('div')
        moreBtn.innerHTML = 'pokaż więcej';
        moreBtn.classList.add('kbin-more', 'text-center', 'font-weight-bold');

        this.element.parentNode.insertBefore(moreBtn, this.element.nextSibling);

        return moreBtn;
    }

    more() {
        this.moreBtn.addEventListener('click', e => {
            if (e.target.previousSibling.style.maxHeight) {
                e.target.previousSibling.style.maxHeight = null;
                e.target.innerHTML = 'ukryj';
                this.isExpandedValue = true;
            } else {
                e.target.previousSibling.style.maxHeight = '25rem';
                e.target.innerHTML = 'pokaż więcej';
                e.target.previousSibling.scrollIntoView();
                this.isExpandedValue = false;
            }
        })
    }

    expand() {
        if (!this.isExpandedValue) {
            this.moreBtn.click();
        }
    }
}