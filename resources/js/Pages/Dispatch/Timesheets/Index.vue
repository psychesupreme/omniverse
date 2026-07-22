<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    timesheets: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    },
});

const selectedFilter = ref('all');
const selectedTimesheet = ref(null);
const isOverrideModalOpen = ref(false);

const form = useForm({
    clock_in: '',
    clock_out: '',
    status: 'completed',
});

const filteredTimesheets = computed(() => {
    if (selectedFilter.value === 'all') return props.timesheets.data || [];
    if (selectedFilter.value === 'active') return (props.timesheets.data || []).filter(t => !t.clock_out && !t.clock_out_time);
    if (selectedFilter.value === 'completed') return (props.timesheets.data || []).filter(t => t.clock_out || t.clock_out_time);
    return props.timesheets.data || [];
});

function openOverrideModal(timesheet) {
    selectedTimesheet.value = timesheet;
    const inTime = timesheet.clock_in || timesheet.clock_in_time;
    const outTime = timesheet.clock_out || timesheet.clock_out_time;

    form.clock_in = inTime ? new Date(inTime).toISOString().slice(0, 16) : '';
    form.clock_out = outTime ? new Date(outTime).toISOString().slice(0, 16) : new Date().toISOString().slice(0, 16);
    form.status = timesheet.status || 'completed';
    isOverrideModalOpen.value = true;
}

function closeOverrideModal() {
    isOverrideModalOpen.value = false;
    selectedTimesheet.value = null;
    form.reset();
}

function submitOverride() {
    if (!selectedTimesheet.value) return;

    form.post(`/api/v1/dispatch/timesheets/${selectedTimesheet.value.id}/override`, {
        onSuccess: () => {
            closeOverrideModal();
            router.reload();
        },
    });
}
</script>

<template>
    <Head title="Worker Attendance & Timesheets" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-800">
                    Field Worker Attendance & Timesheets
                </h2>
                <span class="text-sm font-medium text-gray-500">
                    Total Records: {{ timesheets.total }}
                </span>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Summary Stat Cards -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <div class="rounded-xl bg-white p-6 shadow-sm border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Active Shifts Today</p>
                            <p class="mt-2 text-3xl font-extrabold text-indigo-600">{{ stats.activeShiftsToday }}</p>
                        </div>
                        <div class="rounded-xl bg-indigo-50 p-3 text-indigo-600">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>

                    <div class="rounded-xl bg-white p-6 shadow-sm border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total Hours Tracked</p>
                            <p class="mt-2 text-3xl font-extrabold text-emerald-600">{{ stats.totalHoursTracked }} hrs</p>
                        </div>
                        <div class="rounded-xl bg-emerald-50 p-3 text-emerald-600">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                    </div>

                    <div class="rounded-xl bg-white p-6 shadow-sm border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Automated Ratio</p>
                            <p class="mt-2 text-3xl font-extrabold text-blue-600">{{ stats.automatedRatio }}%</p>
                        </div>
                        <div class="rounded-xl bg-blue-50 p-3 text-blue-600">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Filter Controls -->
                <div class="flex items-center space-x-2 rounded-xl bg-white p-2 shadow-sm border border-gray-100">
                    <button
                        v-for="filter in ['all', 'active', 'completed']"
                        :key="filter"
                        @click="selectedFilter = filter"
                        :class="[
                            selectedFilter === filter ? 'bg-indigo-600 text-white font-semibold shadow-xs' : 'text-gray-600 hover:bg-gray-100',
                            'rounded-lg px-4 py-2 text-xs uppercase tracking-wider transition-all capitalize'
                        ]"
                    >
                        {{ filter }} Shifts
                    </button>
                </div>

                <!-- Timesheets Table -->
                <div class="rounded-xl bg-white shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-600">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-500 border-b border-gray-100">
                                <tr>
                                    <th class="px-6 py-3 font-semibold">Field Worker</th>
                                    <th class="px-6 py-3 font-semibold">Geofence Site</th>
                                    <th class="px-6 py-3 font-semibold">Clock In</th>
                                    <th class="px-6 py-3 font-semibold">Clock Out</th>
                                    <th class="px-6 py-3 font-semibold">Shift Duration</th>
                                    <th class="px-6 py-3 font-semibold">Mode</th>
                                    <th class="px-6 py-3 font-semibold">Status</th>
                                    <th class="px-6 py-3 font-semibold text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="sheet in filteredTimesheets" :key="sheet.id" class="hover:bg-gray-50/50">
                                    <td class="px-6 py-4 font-semibold text-gray-900">
                                        {{ sheet.user?.name || `Worker #${sheet.user_id}` }}
                                    </td>
                                    <td class="px-6 py-4 font-medium text-gray-700">
                                        {{ sheet.geofence?.name || 'Default Zone' }}
                                    </td>
                                    <td class="px-6 py-4 text-xs text-gray-600">
                                        {{ (sheet.clock_in || sheet.clock_in_time) ? new Date(sheet.clock_in || sheet.clock_in_time).toLocaleString() : '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-xs text-gray-600">
                                        {{ (sheet.clock_out || sheet.clock_out_time) ? new Date(sheet.clock_out || sheet.clock_out_time).toLocaleString() : 'On Shift' }}
                                    </td>
                                    <td class="px-6 py-4 font-bold text-gray-900">
                                        {{ sheet.shift_duration_minutes ?? sheet.total_minutes ?? 0 }} mins
                                    </td>
                                    <td class="px-6 py-4">
                                        <span :class="[
                                            sheet.is_automated ? 'bg-purple-50 text-purple-700' : 'bg-amber-50 text-amber-700',
                                            'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium'
                                        ]">
                                            {{ sheet.is_automated ? 'Automated' : 'Manual' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span :class="[
                                            (sheet.clock_out || sheet.clock_out_time) ? 'bg-green-50 text-green-700' : 'bg-blue-50 text-blue-700 font-bold animate-pulse',
                                            'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium'
                                        ]">
                                            {{ (sheet.clock_out || sheet.clock_out_time) ? 'Completed' : 'Active Shift' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button
                                            @click="openOverrideModal(sheet)"
                                            class="inline-flex items-center rounded-md bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-200"
                                        >
                                            Override
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="filteredTimesheets.length === 0">
                                    <td colspan="8" class="px-6 py-8 text-center text-gray-500">No attendance records found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Manual Override Modal -->
        <div v-if="isOverrideModalOpen && selectedTimesheet" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h3 class="text-lg font-bold text-gray-900">Manual Shift Override</h3>
                <p class="text-xs text-gray-500 mt-1">Worker: {{ selectedTimesheet.user?.name }} | Site: {{ selectedTimesheet.geofence?.name || 'Zone' }}</p>

                <form @submit.prevent="submitOverride" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Clock-In Timestamp</label>
                        <input
                            v-model="form.clock_in"
                            type="datetime-local"
                            required
                            class="mt-1 block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Clock-Out Timestamp</label>
                        <input
                            v-model="form.clock_out"
                            type="datetime-local"
                            class="mt-1 block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Shift Status</label>
                        <select
                            v-model="form.status"
                            class="mt-1 block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="active">Active (On Shift)</option>
                            <option value="completed">Completed</option>
                            <option value="rejected">Rejected / Invalid</option>
                        </select>
                    </div>

                    <div class="flex justify-end space-x-3 pt-3">
                        <button
                            type="button"
                            @click="closeOverrideModal"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50"
                        >
                            Save Override
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
