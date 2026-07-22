<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    products: {
        type: Object,
        required: true,
    },
});

const searchQuery = ref('');
const isModalOpen = ref(false);
const isEditing = ref(false);
const editingProductId = ref(null);

const form = useForm({
    sku: '',
    name: '',
    description: '',
    unit_price: '',
    stock_quantity: 0,
    is_active: true,
});

const filteredProducts = computed(() => {
    if (!searchQuery.value.trim()) return props.products.data || [];
    const query = searchQuery.value.toLowerCase();
    return (props.products.data || []).filter(p =>
        p.name.toLowerCase().includes(query) ||
        p.sku.toLowerCase().includes(query)
    );
});

function openCreateModal() {
    isEditing.value = false;
    editingProductId.value = null;
    form.reset();
    isModalOpen.value = true;
}

function openEditModal(product) {
    isEditing.value = true;
    editingProductId.value = product.id;
    form.sku = product.sku;
    form.name = product.name;
    form.description = product.description || '';
    form.unit_price = product.unit_price;
    form.stock_quantity = product.stock_quantity;
    form.is_active = product.is_active;
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    form.reset();
}

function submitForm() {
    if (isEditing.value) {
        form.put(`/api/v1/dispatch/products/${editingProductId.value}`, {
            onSuccess: () => {
                closeModal();
                router.reload();
            },
        });
    } else {
        form.post('/api/v1/dispatch/products', {
            onSuccess: () => {
                closeModal();
                router.reload();
            },
        });
    }
}

function deleteProduct(product) {
    if (confirm(`Are you sure you want to delete product "${product.name}"?`)) {
        router.delete(`/api/v1/dispatch/products/${product.id}`, {
            onSuccess: () => router.reload(),
        });
    }
}
</script>

<template>
    <Head title="Product Catalogue" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-800">
                    Product Catalogue & Inventory
                </h2>
                <button
                    @click="openCreateModal"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                >
                    + Add New Product
                </button>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Search & Filters -->
                <div class="flex items-center justify-between rounded-xl bg-white p-4 shadow-sm border border-gray-100">
                    <div class="w-72">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search by SKU or product name..."
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>
                </div>

                <!-- Products Table -->
                <div class="rounded-xl bg-white shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-600">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-500 border-b border-gray-100">
                                <tr>
                                    <th class="px-6 py-3 font-semibold">SKU</th>
                                    <th class="px-6 py-3 font-semibold">Product Name</th>
                                    <th class="px-6 py-3 font-semibold">Unit Price</th>
                                    <th class="px-6 py-3 font-semibold">Stock Quantity</th>
                                    <th class="px-6 py-3 font-semibold">Status</th>
                                    <th class="px-6 py-3 font-semibold text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="product in filteredProducts" :key="product.id" class="hover:bg-gray-50/50">
                                    <td class="px-6 py-4 font-mono font-semibold text-indigo-600">{{ product.sku }}</td>
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-gray-900">{{ product.name }}</div>
                                        <div v-if="product.description" class="text-xs text-gray-400 truncate max-w-xs">{{ product.description }}</div>
                                    </td>
                                    <td class="px-6 py-4 font-semibold text-gray-900">${{ parseFloat(product.unit_price).toFixed(2) }}</td>
                                    <td class="px-6 py-4">
                                        <span :class="[
                                            product.stock_quantity > 10 ? 'text-gray-900' : 'text-red-600 font-bold',
                                            'text-sm'
                                        ]">
                                            {{ product.stock_quantity }} units
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span :class="[
                                            product.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600',
                                            'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium'
                                        ]">
                                            {{ product.is_active ? 'Active' : 'Disabled' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right space-x-2">
                                        <button
                                            @click="openEditModal(product)"
                                            class="inline-flex items-center rounded-md bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-200"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            @click="deleteProduct(product)"
                                            class="inline-flex items-center rounded-md bg-red-50 px-2.5 py-1 text-xs font-medium text-red-600 hover:bg-red-100"
                                        >
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="filteredProducts.length === 0">
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">No products found in catalogue.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add/Edit Product Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm">
            <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
                <h3 class="text-lg font-bold text-gray-900">{{ isEditing ? 'Edit Product' : 'Add New Product' }}</h3>

                <form @submit.prevent="submitForm" class="mt-4 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">SKU Code</label>
                            <input
                                v-model="form.sku"
                                type="text"
                                required
                                placeholder="PROD-001"
                                class="mt-1 block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Product Name</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="Widget Item"
                                class="mt-1 block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            placeholder="Product specs or notes..."
                            class="mt-1 block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Unit Price ($)</label>
                            <input
                                v-model="form.unit_price"
                                type="number"
                                step="0.01"
                                required
                                placeholder="19.99"
                                class="mt-1 block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Stock Quantity</label>
                            <input
                                v-model="form.stock_quantity"
                                type="number"
                                required
                                placeholder="100"
                                class="mt-1 block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <div class="flex items-center space-x-2">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            id="is_active"
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <label for="is_active" class="text-sm font-medium text-gray-700">Active in Catalogue</label>
                    </div>

                    <div class="flex justify-end space-x-3 pt-3">
                        <button
                            type="button"
                            @click="closeModal"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50"
                        >
                            {{ isEditing ? 'Save Changes' : 'Create Product' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
