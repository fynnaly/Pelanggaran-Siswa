import { positionPopper } from './popper.js';

export function uiCombobox(config = {}) {
    return {
        id: 'ui-combobox-' + Math.random().toString(36).substring(2, 9),
        value: config.value ?? null,
        search: '',
        open: false,
        optionsMap: {},
        highlightedEl: null,

        get state() {
            return this.open ? 'open' : 'closed';
        },

        get label() {
            return this.optionsMap[this.value] ?? (this.value ? String(this.value) : null);
        },

        init() {
            const onOutsideClick = (e) => {
                if (!this.open) return;
                const trigger = this.$refs.trigger || this.$refs.input;
                const wrapper = this.$refs.wrapper;
                const content = this.$refs.content;

                if (trigger && (trigger === e.target || trigger.contains(e.target))) return;
                if (wrapper && (wrapper === e.target || wrapper.contains(e.target))) return;
                if (content && (content === e.target || content.contains(e.target))) return;

                this.close();
            };

            const onWindowScrollOrResize = () => {
                if (this.open) this.position();
            };

            document.addEventListener('click', onOutsideClick, true);
            window.addEventListener('resize', onWindowScrollOrResize, { passive: true });
            window.addEventListener('scroll', onWindowScrollOrResize, { passive: true, capture: true });

            this.$watch('open', (val) => {
                if (val) {
                    this.$nextTick(() => {
                        this.position();
                    });
                }
            });
        },

        register(val, text) {
            this.optionsMap[val] = text;
        },

        isSelected(val) {
            return this.value === val;
        },

        pick(val) {
            this.value = val;
            this.open = false;
            this.$dispatch('change', { value: this.value });
        },

        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.$nextTick(() => this.position());
            }
        },

        openCombobox() {
            this.open = true;
            this.$nextTick(() => this.position());
        },

        close() {
            this.open = false;
        },

        highlight(el) {
            if (this.highlightedEl && this.highlightedEl !== el) {
                this.highlightedEl.removeAttribute('data-highlighted');
            }
            this.highlightedEl = el;
            if (el) {
                el.setAttribute('data-highlighted', '');
            }
        },

        onTriggerKeydown(e) {
            if (e.key === 'ArrowDown' || e.key === 'Enter') {
                e.preventDefault();
                this.openCombobox();
            } else if (e.key === 'Escape') {
                this.close();
            }
        },

        onContentKeydown(e) {
            if (e.key === 'Escape') {
                e.preventDefault();
                this.close();
            }
        },

        position() {
            const trigger = this.$refs.input || this.$refs.trigger;
            const wrapper = this.$refs.wrapper;
            const content = this.$refs.content;
            if (!trigger || !wrapper) return;

            let opts = {};
            if (content && content.dataset.options) {
                try {
                    opts = JSON.parse(content.dataset.options);
                } catch (e) {}
            }
            positionPopper(trigger, wrapper, content, opts);
        }
    };
}
