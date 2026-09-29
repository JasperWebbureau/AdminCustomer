class AdminCustomerEditor {
    constructor(root = document) {
        this.root = root;
        this.nextIndex = 0;
        this.onClick = this.onClick.bind(this);
        this.onChange = this.onChange.bind(this);
        this.root.addEventListener('click', this.onClick);
        this.root.addEventListener('change', this.onChange);
    }

    onClick(event) {
        const addButton = this.closest(event.target, '[data-admin-customer-add]');
        if (addButton) {
            event.preventDefault();
            this.addEntry(addButton.dataset.adminCustomerAdd, addButton);
            return;
        }

        const removeButton = this.closest(event.target, '[data-admin-customer-remove]');
        if (!removeButton) {
            return;
        }
        event.preventDefault();
        const card = removeButton.closest('[data-admin-customer-contact-card], [data-admin-customer-address-card]');
        if (card) {
            card.remove();
            this.ensurePrimaries();
        }
    }

    onChange(event) {
        const contactPrimary = this.closest(event.target, '[data-admin-customer-contact-primary]');
        if (contactPrimary) {
            if (contactPrimary.checked) {
                this.uncheckOthers('[data-admin-customer-contact-primary]', contactPrimary);
            } else {
                this.ensurePrimaries();
            }
            return;
        }

        const addressPrimary = this.closest(event.target, '[data-admin-customer-address-primary]');
        if (addressPrimary) {
            if (addressPrimary.checked) {
                this.uncheckAddressType(addressPrimary);
            } else {
                this.ensureAddressPrimaries();
            }
            return;
        }

        const addressType = this.closest(event.target, '[data-admin-customer-address-type]');
        if (addressType) {
            const card = addressType.closest('[data-admin-customer-address-card]');
            const primary = card ? card.querySelector('[data-admin-customer-address-primary]') : null;
            if (primary && primary.checked) {
                this.uncheckAddressType(primary);
            }
            this.ensureAddressPrimaries();
        }
    }

    addEntry(type, button) {
        const form = button.closest('[data-admin-customer-form]');
        const template = form ? form.querySelector(`[data-admin-customer-template="${type}"]`) : null;
        const target = form ? form.querySelector(`[data-admin-customer-${type === 'contact' ? 'contacts' : 'addresses'}]`) : null;
        if (!template || !target) {
            return;
        }
        const key = `new_${Date.now()}_${this.nextIndex++}`;
        target.insertAdjacentHTML('beforeend', template.innerHTML.replace(/__INDEX__/g, key));
        this.ensurePrimaries();
        const added = target.lastElementChild;
        const firstInput = added ? added.querySelector('input:not([type="hidden"]), select') : null;
        if (firstInput) {
            firstInput.focus();
        }
    }

    ensurePrimaries() {
        const contacts = Array.from(this.root.querySelectorAll('[data-admin-customer-contact-primary]'));
        if (contacts.length && !contacts.some((input) => input.checked)) {
            contacts[0].checked = true;
        }
        this.ensureAddressPrimaries();
    }

    ensureAddressPrimaries() {
        const groups = new Map();
        this.root.querySelectorAll('[data-admin-customer-address-card]').forEach((card) => {
            const type = card.querySelector('[data-admin-customer-address-type]');
            const primary = card.querySelector('[data-admin-customer-address-primary]');
            if (!type || !primary) {
                return;
            }
            if (!groups.has(type.value)) {
                groups.set(type.value, []);
            }
            groups.get(type.value).push(primary);
        });
        groups.forEach((inputs) => {
            if (!inputs.some((input) => input.checked)) {
                inputs[0].checked = true;
            }
        });
    }

    uncheckOthers(selector, current) {
        this.root.querySelectorAll(selector).forEach((input) => {
            if (input !== current) {
                input.checked = false;
            }
        });
    }

    uncheckAddressType(current) {
        const card = current.closest('[data-admin-customer-address-card]');
        const type = card ? card.querySelector('[data-admin-customer-address-type]') : null;
        if (!type) {
            return;
        }
        this.root.querySelectorAll('[data-admin-customer-address-card]').forEach((otherCard) => {
            const otherType = otherCard.querySelector('[data-admin-customer-address-type]');
            const otherPrimary = otherCard.querySelector('[data-admin-customer-address-primary]');
            if (otherCard !== card && otherType && otherPrimary && otherType.value === type.value) {
                otherPrimary.checked = false;
            }
        });
    }

    closest(target, selector) {
        return target instanceof Element ? target.closest(selector) : null;
    }
}

if (!window.adminCustomerEditor) {
    window.adminCustomerEditor = new AdminCustomerEditor();
}
