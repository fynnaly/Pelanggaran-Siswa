

import Alpine from 'alpinejs';
import { uiDropdown } from './ui/dropdown.js';
import { uiSelect } from './ui/select.js';
import { uiCombobox } from './ui/combobox.js';
import { uiDialog } from './ui/dialog.js';

window.Alpine = Alpine;
window.uiDropdown = uiDropdown;
window.uiSelect = uiSelect;
window.uiCombobox = uiCombobox;
window.uiDialog = uiDialog;

Alpine.data('uiDropdown', uiDropdown);
Alpine.data('uiSelect', uiSelect);
Alpine.data('uiCombobox', uiCombobox);
Alpine.data('uiDialog', uiDialog);

Alpine.start();
