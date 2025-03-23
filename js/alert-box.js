class AlertBox {
    constructor(alertBox, showButton) {
        this.alertBox = jQuery(alertBox);
        this.closeButton = this.alertBox.find('.close');
        this.showButton = jQuery(showButton);
        this.init();
    }

    init() {
        this.alertBox.hide(); // Hide the alert box by default
        this.closeButton.click(() => this.hide());
        this.showButton.click((event) => {
            event.preventDefault();
            // Verify nonce before showing the alert
            if (this.verifyNonce()) {
                this.show();
            } else {
                console.error('Nonce verification failed.');
            }
        });
    }

    show() {
        this.alertBox.fadeIn();
    }

    hide() {
        this.alertBox.fadeOut();
    }

    toggle() {
        this.alertBox.toggle();
    }

    verifyNonce() {
        // Add nonce verification logic
        const nonce = alertBox.nonce;
        return nonce && nonce.length > 0;
    }
}

jQuery(document).ready(function () {
    // Initialize multiple alert boxes with corresponding buttons using data attributes
    jQuery('[data-alert-id]').each(function () {
        const alertId = jQuery(this).data('alert-id');
        const alertBoxSelector = `[data-alert-id="${alertId}"]`;
        const showButtonSelector = `[data-show-alert="${alertId}"]`;
        new AlertBox(alertBoxSelector, showButtonSelector);
    });
});