import './bootstrap';
import Swal from 'sweetalert2';
import TomSelect from 'tom-select/dist/js/tom-select.complete.min.js';

window.Swal = Swal;
window.TomSelect = TomSelect;

// Tracking map for TomSelect instances by DOM element
window.tsInstances = window.tsInstances || new WeakMap();

/**
 * Inisialisasi Searchable Dropdown (TomSelect) secara aman & presisi
 * @param {HTMLElement|Document} container
 */
window.initSearchableSelects = function(container = document) {
    if (!container) return;
    const selector = 'select.searchable-select, select[data-searchable="true"]';
    const elements = container.querySelectorAll ? container.querySelectorAll(selector) : [];

    elements.forEach((selectEl) => {
        if (window.tsInstances.has(selectEl)) {
            return; // Sudah diinisialisasi
        }

        // Tentukan teks placeholder
        const firstOption = selectEl.querySelector('option[value=""], option:not([value])');
        const placeholderText = selectEl.getAttribute('placeholder') || 
                                (firstOption ? firstOption.textContent : 'Cari / Pilih...');

        try {
            const instance = new TomSelect(selectEl, {
                create: false,
                maxOptions: 1000,
                placeholder: placeholderText,
                allowEmptyOption: true,
                dropdownParent: 'body',
                plugins: selectEl.multiple ? ['remove_button'] : [],
                render: {
                    no_results: function(data, escape) {
                        return '<div class="no-results p-2 text-muted small"><i class="bi bi-search me-1"></i> Tidak ditemukan "' + escape(data.input) + '"</div>';
                    }
                },
                onInitialize: function() {
                    if (selectEl.classList.contains('form-select-sm')) {
                        this.wrapper.classList.add('ts-sm');
                    }
                },
                onChange: function(value) {
                    // Memicu native change event agar handler inline (seperti onchange="this.form.submit()") tetap bekerja sempurna
                    selectEl.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });

            window.tsInstances.set(selectEl, instance);
        } catch (err) {
            console.warn('TomSelect init warning:', err, selectEl);
        }
    });
};

/**
 * Set nilai Searchable Dropdown secara tertutup / tanpa rekursif event
 */
window.setSearchableSelectValue = function(selectEl, value) {
    if (typeof selectEl === 'string') {
        selectEl = document.getElementById(selectEl) || document.querySelector(selectEl);
    }
    if (!selectEl) return;

    const instance = window.tsInstances.get(selectEl);
    if (instance) {
        instance.setValue(value, true);
    } else {
        selectEl.value = value;
    }
};

/**
 * Sinkronisasi opsi dropdown ketika isi option berubah secara dinamis (AJAX/DOM)
 */
window.syncSearchableSelectOptions = function(selectEl) {
    if (typeof selectEl === 'string') {
        selectEl = document.getElementById(selectEl) || document.querySelector(selectEl);
    }
    if (!selectEl) return;

    const instance = window.tsInstances.get(selectEl);
    if (instance) {
        const currentValue = selectEl.value;
        instance.clearOptions();
        
        Array.from(selectEl.options).forEach(opt => {
            instance.addOption({
                value: opt.value,
                text: opt.text,
                disabled: opt.disabled
            });
        });
        
        instance.setValue(currentValue, true);
        instance.refreshOptions(false);
    }
};

// Inisialisasi otomatis saat DOM siap
document.addEventListener('DOMContentLoaded', () => {
    window.initSearchableSelects();
});

// Inisialisasi otomatis jika ada modal Bootstrap yang dibuka
document.addEventListener('shown.bs.modal', (e) => {
    window.initSearchableSelects(e.target);
});
