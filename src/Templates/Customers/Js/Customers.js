class AdminCustomerOverview {
    constructor(root = document) {
        this.root = root;
        this.searchTimer = null;
        this.searchDelay = 400;

        this.onInput = this.onInput.bind(this);
        this.onChange = this.onChange.bind(this);
        this.onClick = this.onClick.bind(this);
        this.bindEvents();
    }

    bindEvents() {
        this.root.addEventListener('input', this.onInput);
        this.root.addEventListener('change', this.onChange);
        this.root.addEventListener('click', this.onClick);
    }

    onInput(event) {
        const field = this.closestElement(event.target, '[data-admin-customer-filters] input[name="q"]');
        if (!field) {
            return;
        }
        window.clearTimeout(this.searchTimer);
        this.searchTimer = window.setTimeout(() => this.submit(this.getForm(field), true), this.searchDelay);
    }

    onChange(event) {
        const field = this.closestElement(event.target, '[data-admin-customer-filters] select');
        if (field) {
            this.submit(this.getForm(field), true);
        }
    }

    onClick(event) {
        const pageButton = this.closestElement(event.target, '[data-admin-customer-page]');
        if (pageButton) {
            event.preventDefault();
            if (!pageButton.disabled) {
                const form = this.getForm(pageButton);
                this.setValue(form, 'page', pageButton.dataset.adminCustomerPage);
                this.submit(form, false);
            }
            return;
        }

        const sortButton = this.closestElement(event.target, '[data-admin-customer-filters] [data-fg-table-sort]');
        if (sortButton) {
            event.preventDefault();
            const form = this.getForm(sortButton);
            const sortField = form ? form.elements.namedItem('sort') : null;
            const directionField = form ? form.elements.namedItem('direction') : null;
            const nextSort = sortButton.dataset.fgTableSort || '';
            const currentSort = sortField ? sortField.value : '';
            const currentDirection = directionField ? directionField.value : 'asc';
            this.setValue(form, 'sort', nextSort);
            this.setValue(form, 'direction', currentSort === nextSort && currentDirection === 'asc' ? 'desc' : 'asc');
            this.submit(form, true);
            return;
        }

        const resetButton = this.closestElement(event.target, '[data-admin-customer-reset]');
        if (!resetButton) {
            return;
        }
        event.preventDefault();
        const form = this.getForm(resetButton);
        this.setValue(form, 'q', '');
        this.setValue(form, 'status', '');
        this.setValue(form, 'sort', 'display_name');
        this.setValue(form, 'direction', 'asc');
        this.submit(form, true);
    }

    submit(form, resetPage) {
        if (!form || !form.isConnected) {
            return;
        }
        window.clearTimeout(this.searchTimer);
        if (resetPage) {
            this.setValue(form, 'page', '1');
        }
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
            return;
        }
        form.dispatchEvent(new Event('submit', {bubbles: true, cancelable: true}));
    }

    setValue(form, name, value) {
        const field = form ? form.elements.namedItem(name) : null;
        if (field) {
            field.value = value == null ? '' : String(value);
        }
    }

    getForm(element) {
        return element ? element.closest('[data-admin-customer-filters]') : null;
    }

    closestElement(target, selector) {
        return target instanceof Element ? target.closest(selector) : null;
    }
}

if (!window.adminCustomerOverview) {
    window.adminCustomerOverview = new AdminCustomerOverview();
}
