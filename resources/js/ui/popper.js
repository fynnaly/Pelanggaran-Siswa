export function positionPopper(trigger, wrapper, content, options = {}) {
    if (!trigger || !wrapper) return;
    const side = options.side || 'bottom';
    const align = options.align || 'start';
    const sideOffset = Number(options.sideOffset) || 8;
    const alignOffset = Number(options.alignOffset) || 0;

    const tRect = trigger.getBoundingClientRect();
    const cRect = (content || wrapper).getBoundingClientRect();

    let top = 0;
    let left = 0;

    if (side === 'bottom') {
        top = tRect.bottom + sideOffset;
    } else if (side === 'top') {
        top = tRect.top - cRect.height - sideOffset;
    } else if (side === 'right') {
        left = tRect.right + sideOffset;
    } else if (side === 'left') {
        left = tRect.left - cRect.width - sideOffset;
    }

    if (side === 'bottom' || side === 'top') {
        if (align === 'start') {
            left = tRect.left + alignOffset;
        } else if (align === 'end') {
            left = tRect.right - cRect.width - alignOffset;
        } else {
            left = tRect.left + (tRect.width - cRect.width) / 2 + alignOffset;
        }
    } else {
        if (align === 'start') {
            top = tRect.top + alignOffset;
        } else if (align === 'end') {
            top = tRect.bottom - cRect.height - alignOffset;
        } else {
            top = tRect.top + (tRect.height - cRect.height) / 2 + alignOffset;
        }
    }

    const maxLeft = Math.max(8, window.innerWidth - (cRect.width || 200) - 8);
    const maxTop = Math.max(8, window.innerHeight - (cRect.height || 100) - 8);
    left = Math.max(8, Math.min(left, maxLeft));
    top = Math.max(8, Math.min(top, maxTop));

    wrapper.style.position = 'fixed';
    wrapper.style.top = `${top}px`;
    wrapper.style.left = `${left}px`;
    wrapper.style.transform = 'none';
    wrapper.style.zIndex = '99999';
}
