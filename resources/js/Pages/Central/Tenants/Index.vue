<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    tenants: {
        type: Object,
        required: true,
    },
    plans: {
        type: Array,
        default: () => [],
    },
});

const searchQuery = ref('');
const selectedTenant = ref(null);
const isPlanModalOpen = ref(false);

const planForm = useForm({
    subscription_plan_id: '',
});

const filteredTenants = computed(() => {
    if (!searchQuery.value.trim()) return props.tenants.data || [];
    const query = searchQuery.value.toLowerCase();
    return (props.tenants.data || []).filter(t => 
        t.id.toLowerCase().includes(query) ||
        (t.name && t.name.toLowerCase().includes(query)) ||
        (t.domains?.[0]?.domain && t.domains[0].domain.toLowerCase().includes(query))
    );
});

function toggleStatus(tenant) {
    const newStatus = tenant.status === 'suspended' ? 'active' : 'suspended';
    if (confirm(`Are you sure you want to change status of ${tenant.id} to ${newStatus}?`)) {
        router.patch(route('central.tenants.update-status', tenant.id), {
            status: newStatus,
        }, {
            preserveScroll: true,
        });
    }
}

function openPlanModal(tenant) {
    selectedTenant.value = tenant;
    planForm.subscription_plan_id = tenant.subscription_plan_id || (props.plans[0]?.id ?? '');
    isPlanModalOpen.value = true;
}

function closePlanModal() {
    isPlanModalOpen.value = false;
    selectedTenant.value = null;
}

function submitPlanUpdate() {
    if (!selectedTenant.value) return;
    planForm.patch(route('central.tenants.update-plan', selectedTenant.value.id), {
        onSuccess: () => closePlanModal(),
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Tenant Management" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-800">
                    Tenant Management
                </h2>
                <span class="text-sm font-medium text-gray-500">Total Registered: {{ tenants.total }}</span>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Search & Filters Bar -->
                <div class="flex items-center justify-between rounded-xl bg-white p-4 shadow-sm border border-gray-100">
                    <div class="w-72">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Filter tenants by name, slug, or domain..."
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>
                </div>

                <!-- Tenants Data Table -->
                <div class="rounded-xl bg-white shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-600">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-500 border-b border-gray-100">
                                <tr>
                                    <th class="px-6 py-3 font-semibold">Tenant ID / Name</th>
                                    <th class="px-6 py-3 font-semibold">Domain URL</th>
                                    <th class="px-6 py-3 font-semibold">Plan Tier</th>
                                    <th class="px-6 py-3 font-semibold">Status</th>
                                    <th class="px-6 py-3 font-semibold">Created Date</th>
                                    <th class="px-6 py-3 font-semibold text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="tenant in filteredTenants" :key="tenant.id" class="hover:bg-gray-50/50">
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-gray-900">{{ tenant.name || tenant.id }}</div>
                                        <div class="text-xs text-gray-400 font-mono">{{ tenant.id }}</div>
                                    </td>
                                    <td class="px-6 py-4 font-mono text-indigo-600">
                                        {{ tenant.domains?.[0]?.domain || tenant.id + '.localhost' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <button
                                            @click="openPlanModal(tenant)"
                                            class="inline-flex items-center space-x-1 rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700 hover:bg-blue-100"
                                        >
                                            <span>{{ tenant.subscription_plan?.name || 'Unassigned' }}</span>
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span :class="[
                                            tenant.status === 'active' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700',
                                            'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize'
                                        ]">
                                            {{ tenant.status || 'active' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-500">
                                        {{ new Date(tenant.created_at).toLocaleDateString() }}
                                    </td>
                                    <td class="px-6 py-4 text-right space-x-2">
                                        <a
                                            :href="`http://${tenant.domains?.[0]?.domain || tenant.id + '.localhost'}:8888`"
                                            target="_blank"
                                            class="inline-flex items-center rounded-md bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-200"
                                        >
                                            Portal ↗
                                        </a>

                                        <button
                                            @click="toggleStatus(tenant)"
                                            :class="[
                                                tenant.status === 'suspended' ? 'bg-green-600 hover:bg-green-500' : 'bg-amber-600 hover:bg-amber-500',
                                                'inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium text-white'
                                            ]"
                                        >
                                            {{ tenant.status === 'suspended' ? 'Activate' : 'Suspend' }}
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="filteredTenants.length === 0">
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">No matching tenants found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Update Subscription Plan Modal -->
        <div v-if="isPlanModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h3 class="text-lg font-bold text-gray-900">Change Subscription Tier</h3>
                <p class="mt-1 text-sm text-gray-500">Select a new plan tier for tenant <strong class="text-gray-800">{{ selectedTenant?.id }}</strong>.</p>

                <form @submit.prevent="submitPlanUpdate" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Subscription Plan</label>
                        <select
                            v-model="planForm.subscription_plan_id"
                            class="mt-1 block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                                {{ plan.name }} (${{ plan.price_monthly }}/mo)
                            </option>
                        </select>
                    </div>

                    <div class="flex justify-end space-x-3 pt-3">
                        <button
                            type="button"
                            @click="closePlanModal"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="planForm.processing"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50"
                        >
                            Save Plan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
