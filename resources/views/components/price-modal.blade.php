<div id="priceModal" class="fixed inset-0 bg-black bg-opacity-40 hidden z-50 flex items-center justify-center" aria-hidden="true">
    <div id="priceModalPanel" role="dialog" aria-modal="true" aria-labelledby="priceModalItemName" aria-describedby="priceModalError" tabindex="-1" class="bg-white rounded-lg shadow-xl w-96 p-4">
        <div class="flex justify-between items-start">
            <h3 class="text-lg font-semibold" id="priceModalItemName">Edit Price</h3>
            <button type="button" class="text-gray-400 hover:text-gray-600 priceModalCancel" aria-label="Close">✕</button>
        </div>
        <div class="mt-3">
            <label class="block text-sm text-gray-700">Unit Price (₱)</label>
            <input id="priceModalInput" type="number" step="0.01" min="0" class="mt-1 block w-full border-gray-300 rounded-md p-2" aria-label="Unit price" />
            <p id="priceModalError" class="text-xs text-red-600 mt-2 hidden"></p>
        </div>
        <div class="mt-4 flex justify-end space-x-2">
            <button type="button" class="px-3 py-2 bg-gray-100 rounded-md hover:bg-gray-200 priceModalCancel">Cancel</button>
            <button type="button" id="priceModalSave" class="px-3 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Add Item</button>
        </div>
    </div>
</div>
