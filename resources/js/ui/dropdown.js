import { positionPopper } from './popper.js';

export function uiDropdown(config = {}) {
    return {
        id: 'ui-dropdown-' + Math.random().toString(36).substring(2, 9),
        open: !!config.defaultOpen,
        sub: !!config.sub,
        highlightedEl: null,

        get state() {
            return this.open ? 'open' : 'closed';
        },

        init() {
            const onOutsideClick = (e) => {
                if (!this.open) return;
                const trigger = this.$refs.trigger || this.$refs.subTrigger;
                const wrapper = this.$refs.wrapper;
                const content = this.$refs.content || this.$refs.subContent;

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

        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.$nextTick(() => this.position());
            }
        },

        openDropdown() {
            this.open = true;
            this.$nextTick(() => this.position());
        },

        close() {
            this.open = false;
        },

        closeAll() {
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
            if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.openDropdown();
            } else if (e.key === 'Escape') {
                this.close();
            }
        },

        onContentKeydown(e) {
            if (e.key === 'Escape') {
                e.preventDefault();
                this.close();
                const trigger = this.$refs.trigger || this.$refs.subTrigger;
                if (trigger) trigger.focus();
            }
        },

        position() {
            const trigger = this.$refs.trigger || this.$refs.subTrigger;
            const wrapper = this.$refs.wrapper;
            const content = this.$refs.content || this.$refs.subContent;
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
