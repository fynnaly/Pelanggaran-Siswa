export function uiDialog(config = {}) {
    return {
        id: 'ui-dialog-' + Math.random().toString(36).substring(2, 9),
        titleId: 'ui-dialog-title-' + Math.random().toString(36).substring(2, 9),
        descriptionId: 'ui-dialog-desc-' + Math.random().toString(36).substring(2, 9),
        open: !!config.defaultOpen,

        get state() {
            return this.open ? 'open' : 'closed';
        },

        init() {
            this.$watch('open', (val) => {
                if (val) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            });
        },

        openDialog() {
            this.open = true;
        },

        close() {
            this.open = false;
        },

        toggle() {
            this.open = !this.open;
        }
    };
}
