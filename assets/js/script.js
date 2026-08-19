/**
 * Keuangan App - Interactive Scripts
 */

document.addEventListener('DOMContentLoaded', function () {
    // Initial calculation if item table exists
    calculateTransactionTotal();
});

/**
 * Toggle Modal Helper
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

/**
 * Handle Business Selection Visibility based on Category
 */
function checkCategoryType() {
    const categorySelect = document.getElementById('kategori_id');
    const usahaContainer = document.getElementById('usaha_container');
    const usahaSelect = document.getElementById('usaha_id');

    if (!categorySelect || !usahaContainer) return;

    const selectedOption = categorySelect.options[categorySelect.selectedIndex];
    if (!selectedOption) return;

    const categoryName = selectedOption.getAttribute('data-nama') || selectedOption.text.toLowerCase();
    
    // Show business dropdown if category relates to business ('usaha')
    if (categoryName.toLowerCase().includes('usaha')) {
        usahaContainer.classList.remove('hidden');
    } else {
        usahaContainer.classList.add('hidden');
        if (usahaSelect) usahaSelect.value = '';
    }
}

/**
 * Dynamic Transaction Items (+ Tambah Item)
 */
function addItemRow() {
    const container = document.getElementById('itemRowsContainer');
    if (!container) return;

    const rowId = Date.now();
    const rowHTML = `
        <div class="item-row grid grid-cols-12 gap-2 items-center bg-surface-container-low p-2 rounded-xl border border-outline-variant transition-all animate-fade-in" id="row_${rowId}">
            <div class="col-span-5 sm:col-span-5">
                <input type="text" name="items[${rowId}][nama_item]" placeholder="Nama item / pengeluaran" required
                    class="w-full px-3 py-2 text-sm bg-white border border-outline-variant rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>
            <div class="col-span-2 sm:col-span-2">
                <input type="number" step="0.01" min="0.1" name="items[${rowId}][jumlah_qty]" value="1" placeholder="Qty" oninput="calculateRowSubtotal('${rowId}')" required
                    class="w-full px-2 py-2 text-sm bg-white border border-outline-variant rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary text-center">
            </div>
            <div class="col-span-4 sm:col-span-4">
                <input type="number" step="1" min="0" name="items[${rowId}][harga_satuan]" value="0" placeholder="Harga Satuan" oninput="calculateRowSubtotal('${rowId}')" required
                    class="w-full px-3 py-2 text-sm bg-white border border-outline-variant rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary text-right">
            </div>
            <div class="col-span-1 sm:col-span-1 text-center">
                <button type="button" onclick="removeItemRow('${rowId}')" class="p-1 text-secondary hover:bg-secondary-container/20 rounded-lg transition-colors" title="Hapus Item">
                    <span class="material-symbols-outlined text-lg">delete</span>
                </button>
            </div>
            <div class="col-span-12 flex justify-between px-2 pt-1 text-xs text-on-surface-variant font-medium">
                <span>Subtotal:</span>
                <span id="subtotal_display_${rowId}" class="font-semibold text-primary">Rp 0</span>
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', rowHTML);
}

function removeItemRow(rowId) {
    const row = document.getElementById(`row_${rowId}`);
    if (row) {
        row.remove();
        calculateTransactionTotal();
    }
}

function calculateRowSubtotal(rowId) {
    const row = document.getElementById(`row_${rowId}`);
    if (!row) return;

    const qtyInput = row.querySelector(`input[name="items[${rowId}][jumlah_qty]"]`);
    const priceInput = row.querySelector(`input[name="items[${rowId}][harga_satuan]"]`);
    const subtotalDisplay = document.getElementById(`subtotal_display_${rowId}`);

    const qty = parseFloat(qtyInput?.value) || 0;
    const price = parseFloat(priceInput?.value) || 0;
    const subtotal = qty * price;

    if (subtotalDisplay) {
        subtotalDisplay.innerText = 'Rp ' + subtotal.toLocaleString('id-ID');
    }

    calculateTransactionTotal();
}

function calculateTransactionTotal() {
    const container = document.getElementById('itemRowsContainer');
    const jumlahInput = document.getElementById('jumlah');
    const totalItemsDisplay = document.getElementById('totalItemsDisplay');

    if (!container || !jumlahInput) return;

    const rows = container.querySelectorAll('.item-row');
    if (rows.length === 0) {
        if (totalItemsDisplay) totalItemsDisplay.innerText = 'Rp 0';
        return;
    }

    let grandTotal = 0;
    rows.forEach(row => {
        const qtyInput = row.querySelector('input[name*="[jumlah_qty]"]');
        const priceInput = row.querySelector('input[name*="[harga_satuan]"]');
        const qty = parseFloat(qtyInput?.value) || 0;
        const price = parseFloat(priceInput?.value) || 0;
        grandTotal += (qty * price);
    });

    jumlahInput.value = grandTotal;
    if (totalItemsDisplay) {
        totalItemsDisplay.innerText = 'Rp ' + grandTotal.toLocaleString('id-ID');
    }
}
