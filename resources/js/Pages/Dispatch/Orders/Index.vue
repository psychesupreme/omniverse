<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    orders: {
        type: Object,
        required: true,
    },
});

const selectedStatus = ref('all');
const selectedOrder = ref(null);
const isDetailModalOpen = ref(false);

const filteredOrders = computed(() => {
    if (selectedStatus.value === 'all') return props.orders.data || [];
    return (props.orders.data || []).filter(o => o.status === selectedStatus.value);
});

function openDetailModal(order) {
    selectedOrder.value = order;
    isDetailModalOpen.value = true;
}

function closeDetailModal() {
    isDetailModalOpen.value = false;
    selectedOrder.value = null;
}

function updateOrderStatus(order, newStatus) {
    if (confirm(`Change order #${order.order_number} status to ${newStatus}?`)) {
        router.patch(`/api/v1/dispatch/orders/${order.id}/status`, {
            status: newStatus,
        }, {
            onSuccess: () => {
                closeDetailModal();
                router.reload();
            },
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head title="Sales Orders" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-800">
                    Sales Orders & Fulfillment
                </h2>
                <span class="text-sm font-medium text-gray-500">Total Orders: {{ orders.total }}</span>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Status Filter Tabs -->
                <div class="flex items-center space-x-2 rounded-xl bg-white p-2 shadow-sm border border-gray-100 overflow-x-auto">
                    <button
                        v-for="status in ['all', 'pending', 'approved', 'delivered', 'cancelled']"
                        :key="status"
                        @click="selectedStatus = status"
                        :class="[
                            selectedStatus === status ? 'bg-indigo-600 text-white font-semibold shadow-xs' : 'text-gray-600 hover:bg-gray-100',
                            'rounded-lg px-4 py-2 text-xs uppercase tracking-wider transition-all capitalize'
                        ]"
                    >
                        {{ status }}
                    </button>
                </div>

                <!-- Orders Table -->
                <div class="rounded-xl bg-white shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-600">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-500 border-b border-gray-100">
                                <tr>
                                    <th class="px-6 py-3 font-semibold">Order #</th>
                                    <th class="px-6 py-3 font-semibold">Customer / Outlet</th>
                                    <th class="px-6 py-3 font-semibold">Field Agent</th>
                                    <th class="px-6 py-3 font-semibold">Total Amount</th>
                                    <th class="px-6 py-3 font-semibold">Status</th>
                                    <th class="px-6 py-3 font-semibold">Placed Date</th>
                                    <th class="px-6 py-3 font-semibold text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="order in filteredOrders" :key="order.id" class="hover:bg-gray-50/50">
                                    <td class="px-6 py-4 font-mono font-semibold text-indigo-600">#{{ order.order_number }}</td>
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-gray-900">{{ order.outlet?.name || 'Direct Sales' }}</div>
                                    </td>
                                    <td class="px-6 py-4 font-medium text-gray-700">
                                        {{ order.user?.name || `Worker #${order.user_id}` }}
                                    </td>
                                    <td class="px-6 py-4 font-bold text-gray-900">${{ parseFloat(order.total_amount).toFixed(2) }}</td>
                                    <td class="px-6 py-4">
                                        <span :class="[
                                            order.status === 'delivered' ? 'bg-green-50 text-green-700' :
                                            order.status === 'approved' ? 'bg-blue-50 text-blue-700' :
                                            order.status === 'pending' ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700',
                                            'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize'
                                        ]">
                                            {{ order.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-500">
                                        {{ new Date(order.placed_at || order.created_at).toLocaleString() }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button
                                            @click="openDetailModal(order)"
                                            class="inline-flex items-center rounded-md bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-100"
                                        >
                                            View Details
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="filteredOrders.length === 0">
                                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">No sales orders found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Detail & Line Items Breakdown Modal -->
        <div v-if="isDetailModalOpen && selectedOrder" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm">
            <div class="w-full max-w-2xl rounded-xl bg-white p-6 shadow-xl">
                <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Order #{{ selectedOrder.order_number }}</h3>
                        <p class="text-xs text-gray-500">Outlet: {{ selectedOrder.outlet?.name || 'Direct Sales' }} | Placed by: {{ selectedOrder.user?.name || 'Agent' }}</p>
                    </div>
                    <button @click="closeDetailModal" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>

                <div class="mt-4 space-y-4">
                    <!-- Line Items Table -->
                    <div class="max-h-60 overflow-y-auto rounded-lg border border-gray-100">
                        <table class="w-full text-left text-xs text-gray-600">
                            <thead class="bg-gray-50 text-gray-500">
                                <tr>
                                    <th class="px-4 py-2 font-semibold">Item Product</th>
                                    <th class="px-4 py-2 font-semibold">Unit Price</th>
                                    <th class="px-4 py-2 font-semibold">Qty</th>
                                    <th class="px-4 py-2 font-semibold text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="item in selectedOrder.items" :key="item.id">
                                    <td class="px-4 py-2.5 font-medium text-gray-900">{{ item.product?.name || 'Product' }}</td>
                                    <td class="px-4 py-2.5">${{ parseFloat(item.unit_price).toFixed(2) }}</td>
                                    <td class="px-4 py-2.5">{{ item.quantity }}</td>
                                    <td class="px-4 py-2.5 text-right font-bold text-gray-900">${{ parseFloat(item.subtotal).toFixed(2) }}</td>
                                </tr>
                                <tr v-if="!selectedOrder.items || selectedOrder.items.length === 0">
                                    <td colspan="4" class="px-4 py-4 text-center text-gray-400">No line items recorded.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg border border-gray-100">
                        <span class="text-sm font-semibold text-gray-700">Total Order Amount:</span>
                        <span class="text-lg font-bold text-indigo-600">${{ parseFloat(selectedOrder.total_amount).toFixed(2) }}</span>
                    </div>

                    <!-- Workflow Actions -->
                    <div class="flex justify-end space-x-2 pt-2 border-t border-gray-100">
                        <button
                            v-if="selectedOrder.status === 'pending'"
                            @click="updateOrderStatus(selectedOrder, 'approved')"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-500"
                        >
                            Approve Order
                        </button>
                        <button
                            v-if="selectedOrder.status === 'approved'"
                            @click="updateOrderStatus(selectedOrder, 'delivered')"
                            class="rounded-lg bg-green-600 px-4 py-2 text-xs font-semibold text-white hover:bg-green-500"
                        >
                            Mark Delivered
                        </button>
                        <button
                            v-if="['pending', 'approved'].includes(selectedOrder.status)"
                            @click="updateOrderStatus(selectedOrder, 'cancelled')"
                            class="rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white hover:bg-red-500"
                        >
                            Cancel Order
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
