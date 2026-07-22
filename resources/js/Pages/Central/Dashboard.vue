<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            totalTenants: 0,
            activeWorkers: 0,
            mrr: 0,
        }),
    },
    recentTenants: {
        type: Array,
        default: () => [],
    },
});

const websocketStatus = ref('Connecting...');
const isWebsocketOnline = ref(true);

onMounted(() => {
    // Check Reverb WebSocket Telemetry Connection
    try {
        if (window.Echo) {
            websocketStatus.value = 'Active (Reverb :8085)';
            isWebsocketOnline.value = true;
        } else {
            websocketStatus.value = 'Offline / Standby';
            isWebsocketOnline.value = false;
        }
    } catch (e) {
        websocketStatus.value = 'Offline';
        isWebsocketOnline.value = false;
    }
});
</script>

<template>
    <Head title="Super-Admin Control Plane" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-800">
                    SaaS Super-Admin Dashboard
                </h2>
                <div class="flex items-center space-x-3">
                    <Link
                        :href="route('central.tenants.index')"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                    >
                        Manage Tenants
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Summary Stat Cards Grid -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Total Tenants Card -->
                    <div class="overflow-hidden rounded-xl bg-white p-6 shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Tenants</p>
                                <p class="mt-2 text-3xl font-bold text-gray-900">{{ stats.totalTenants }}</p>
                            </div>
                            <div class="rounded-lg bg-indigo-50 p-3 text-indigo-600">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 0h4" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Active Field Workers Card -->
                    <div class="overflow-hidden rounded-xl bg-white p-6 shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Active Field Workers</p>
                                <p class="mt-2 text-3xl font-bold text-gray-900">{{ stats.activeWorkers }}</p>
                            </div>
                            <div class="rounded-lg bg-green-50 p-3 text-green-600">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Recurring Revenue Card -->
                    <div class="overflow-hidden rounded-xl bg-white p-6 shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total MRR</p>
                                <p class="mt-2 text-3xl font-bold text-gray-900">${{ stats.mrr }} <span class="text-xs text-gray-500 font-normal">/ mo</span></p>
                            </div>
                            <div class="rounded-lg bg-emerald-50 p-3 text-emerald-600">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Telemetry WebSocket Health Status -->
                    <div class="overflow-hidden rounded-xl bg-white p-6 shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Telemetry Server</p>
                                <div class="mt-2 flex items-center space-x-2">
                                    <span :class="[isWebsocketOnline ? 'bg-green-500' : 'bg-red-500', 'h-3 w-3 rounded-full animate-pulse']"></span>
                                    <p class="text-base font-semibold text-gray-900">{{ websocketStatus }}</p>
                                </div>
                            </div>
                            <div class="rounded-lg bg-blue-50 p-3 text-blue-600">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Registrations Table -->
                <div class="rounded-xl bg-white shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Recent Tenant Registrations</h3>
                        <Link :href="route('central.tenants.index')" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">View All Tenants →</Link>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-600">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-500 border-b border-gray-100">
                                <tr>
                                    <th class="px-6 py-3 font-semibold">Tenant Slug / ID</th>
                                    <th class="px-6 py-3 font-semibold">Subdomain</th>
                                    <th class="px-6 py-3 font-semibold">Plan Tier</th>
                                    <th class="px-6 py-3 font-semibold">Status</th>
                                    <th class="px-6 py-3 font-semibold">Created Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="tenant in recentTenants" :key="tenant.id" class="hover:bg-gray-50/50">
                                    <td class="px-6 py-4 font-semibold text-gray-900">{{ tenant.id }}</td>
                                    <td class="px-6 py-4 font-mono text-indigo-600">
                                        <a :href="`http://${tenant.domains?.[0]?.domain || tenant.id + '.localhost'}:8888`" target="_blank" class="hover:underline">
                                            {{ tenant.domains?.[0]?.domain || tenant.id + '.localhost' }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700">
                                            {{ tenant.subscription_plan?.name || 'Basic' }}
                                        </span>
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
                                </tr>
                                <tr v-if="recentTenants.length === 0">
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">No tenant registrations found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
