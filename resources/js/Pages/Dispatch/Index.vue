<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, reactive, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    tenant_id: {
        type: String,
        required: true,
    },
    outlets: {
        type: Array,
        default: () => [],
    },
    workers: {
        type: Array,
        default: () => [],
    },
    tasks: {
        type: Array,
        default: () => [],
    },
});

// Notifications (Toasts) State
const notifications = ref([]);

const addNotification = (message, type) => {
    const id = Date.now().toString(36) + Math.random().toString(36).substr(2, 5);
    notifications.value.push({ id, message, type });
    setTimeout(() => {
        notifications.value = notifications.value.filter(n => n.id !== id);
    }, 5000);
};

// Task dispatch form data
const form = reactive({
    outlet_id: '',
    assigned_user_id: '',
    title: '',
    scheduled_for: '',
});

const errors = ref({});
const isSubmitting = ref(false);

// Map and markers references
let map = null;
let routeLayer = null;
const workerMarkers = {};

// Selected Worker & Route Optimization State
const selectedWorker = ref(null);
const isOptimizingRoute = ref(false);
const activeRouteSummary = ref(null);

// Custom premium SVG Leaflet markers
const outletIcon = L.divIcon({
    html: `
        <div class="flex items-center justify-center w-8 h-8 rounded-full bg-blue-600 border-2 border-white shadow-lg transition-transform duration-200 hover:scale-110">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
        </div>
    `,
    className: 'custom-div-icon',
    iconSize: [32, 32],
    iconAnchor: [16, 16],
});

const workerIcon = L.divIcon({
    html: `
        <div class="relative flex items-center justify-center w-8 h-8 rounded-full bg-emerald-500 border-2 border-white shadow-lg transition-transform duration-200 hover:scale-110">
            <div class="absolute -inset-1 rounded-full bg-emerald-500 animate-ping opacity-60"></div>
            <svg class="w-4 h-4 text-white relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
        </div>
    `,
    className: 'custom-div-icon',
    iconSize: [32, 32],
    iconAnchor: [16, 16],
});

// Format dates nicely
const formatDate = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

function selectWorker(worker) {
    selectedWorker.value = worker;
    if (workerMarkers[worker.id]) {
        map.panTo(workerMarkers[worker.id].getLatLng());
        workerMarkers[worker.id].openPopup();
    }
}

function clearActiveRoute() {
    if (routeLayer) {
        map.removeLayer(routeLayer);
        routeLayer = null;
    }
    activeRouteSummary.value = null;
}

function optimizeWorkerRoute() {
    if (!selectedWorker.value) return;

    const worker = selectedWorker.value;
    let startLat = worker.location?.latitude;
    let startLng = worker.location?.longitude;

    if (workerMarkers[worker.id]) {
        const pos = workerMarkers[worker.id].getLatLng();
        startLat = pos.lat;
        startLng = pos.lng;
    }

    if (!startLat || !startLng) {
        addNotification(`No location telemetry available for ${worker.name}`, 'exit');
        return;
    }

    isOptimizingRoute.value = true;

    axios.post('/api/v1/dispatch/routes/optimize', {
        worker_id: worker.id,
        start_latitude: startLat,
        start_longitude: startLng,
    })
    .then((response) => {
        const data = response.data;
        clearActiveRoute();

        if (data.geojson && data.geojson.features && data.geojson.features.length > 0) {
            routeLayer = L.geoJSON(data.geojson, {
                style: {
                    color: '#4f46e5', // Indigo-600
                    weight: 5,
                    opacity: 0.85,
                    dashArray: '8, 8',
                },
            }).addTo(map);

            map.fitBounds(routeLayer.getBounds(), { padding: [40, 40] });

            activeRouteSummary.value = {
                workerName: worker.name,
                totalStops: data.tasks ? data.tasks.length : 0,
                totalDistanceKm: data.total_distance_km,
                estimatedTimeMins: data.estimated_time_mins,
            };

            addNotification(`Optimal route calculated for ${worker.name}!`, 'entry');
        } else {
            addNotification(`No pending tasks found for ${worker.name}`, 'exit');
        }
    })
    .catch((error) => {
        console.error('Route optimization error:', error);
        addNotification('Failed to optimize route', 'exit');
    })
    .finally(() => {
        isOptimizingRoute.value = false;
    });
}

// Dispatch a task
const submitTask = () => {
    isSubmitting.value = true;
    errors.value = {};

    axios.post('/api/v1/dispatch/tasks', {
        outlet_id: form.outlet_id,
        assigned_user_id: form.assigned_user_id,
        title: form.title,
        scheduled_for: form.scheduled_for,
    })
    .then(() => {
        form.outlet_id = '';
        form.assigned_user_id = '';
        form.title = '';
        form.scheduled_for = '';
        
        // Reload tasks data
        router.reload({ only: ['tasks'] });
    })
    .catch((error) => {
        if (error.response && error.response.data && error.response.data.errors) {
            errors.value = error.response.data.errors;
        } else {
            console.error('Task dispatch failed:', error);
        }
    })
    .finally(() => {
        isSubmitting.value = false;
    });
};

onMounted(() => {
    // 1. Initialize the Leaflet map
    map = L.map('dispatch-map').setView([-1.286389, 36.817223], 13); // Default to Nairobi Coordinates

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(map);

    const bounds = [];

    // 2. Loop through outlets and place blue markers
    props.outlets.forEach((outlet) => {
        if (outlet.location && outlet.location.latitude && outlet.location.longitude) {
            const marker = L.marker([outlet.location.latitude, outlet.location.longitude], { icon: outletIcon })
                .addTo(map)
                .bindPopup(`
                    <div class="p-1">
                        <h3 class="font-bold text-gray-900 text-sm">${outlet.name}</h3>
                        <p class="text-xs text-gray-500 mt-1">Status: ${outlet.status}</p>
                    </div>
                `);
            bounds.push([outlet.location.latitude, outlet.location.longitude]);
        }
    });

    // 3. Loop through workers and place distinct green markers
    props.workers.forEach((worker) => {
        if (worker.location && worker.location.latitude && worker.location.longitude) {
            const marker = L.marker([worker.location.latitude, worker.location.longitude], { icon: workerIcon })
                .addTo(map)
                .bindPopup(`
                    <div class="p-1">
                        <h3 class="font-bold text-gray-900 text-sm">${worker.name}</h3>
                        <p class="text-xs text-gray-500 mt-1">${worker.email}</p>
                    </div>
                `);
            
            marker.on('click', () => selectWorker(worker));
            workerMarkers[worker.id] = marker;
            bounds.push([worker.location.latitude, worker.location.longitude]);
        }
    });

    // Fit map view bounds if elements exist
    if (bounds.length > 0) {
        map.fitBounds(bounds, { padding: [50, 50] });
    }

    // 4. Connect to Laravel Reverb via Echo for real-time telemetry updates and geofencing alerts
    if (window.Echo) {
        window.Echo.private(`tenant.${props.tenant_id}.telemetry`)
            .listen('.WorkerLocationUpdated', (e) => {
                const workerId = e.user_id;
                const newLat = e.latitude;
                const newLng = e.longitude;

                if (workerMarkers[workerId]) {
                    workerMarkers[workerId].setLatLng([newLat, newLng]);
                } else {
                    const marker = L.marker([newLat, newLng], { icon: workerIcon })
                        .addTo(map)
                        .bindPopup(`
                            <div class="p-1">
                                <h3 class="font-bold text-gray-900 text-sm">${e.user_name || 'Worker'}</h3>
                                <p class="text-xs text-emerald-500 font-semibold mt-1">Active Now</p>
                            </div>
                        `);
                    workerMarkers[workerId] = marker;
                }
            });

        window.Echo.private(`tenant.${props.tenant_id}.dispatch`)
            .listen('.WorkerEnteredGeofence', (e) => {
                addNotification(`${e.worker_name} entered ${e.geofence_name}`, 'entry');
            })
            .listen('.WorkerExitedGeofence', (e) => {
                addNotification(`${e.worker_name} exited ${e.geofence_name}`, 'exit');
            });
    }
});

onUnmounted(() => {
    if (window.Echo) {
        window.Echo.leave(`tenant.${props.tenant_id}.telemetry`);
        window.Echo.leave(`tenant.${props.tenant_id}.dispatch`);
    }
});
</script>

<template>
    <Head title="Dispatcher Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold leading-tight text-gray-800">
                Real-Time Operations & Route Optimization
            </h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Outer 2-Column layout -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    
                    <!-- Left column: Dispatch Form & Workers Drawer -->
                    <div class="space-y-6 lg:col-span-1">
                        
                        <!-- Field Workers Drawer Panel -->
                        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                            <h3 class="text-lg font-bold text-gray-900 mb-3 flex items-center justify-between">
                                <span class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Active Workers
                                </span>
                                <span class="text-xs bg-emerald-100 text-emerald-700 font-bold px-2 py-0.5 rounded-full">{{ workers.length }}</span>
                            </h3>

                            <div class="space-y-2 max-h-[180px] overflow-y-auto pr-1">
                                <div
                                    v-for="worker in workers"
                                    :key="worker.id"
                                    @click="selectWorker(worker)"
                                    :class="[
                                        selectedWorker?.id === worker.id ? 'border-indigo-500 bg-indigo-50/50 shadow-xs' : 'border-gray-100 hover:bg-gray-50',
                                        'p-3 rounded-lg border cursor-pointer transition-all flex items-center justify-between'
                                    ]"
                                >
                                    <div>
                                        <p class="text-sm font-bold text-gray-900">{{ worker.name }}</p>
                                        <p class="text-xs text-gray-500">{{ worker.email }}</p>
                                    </div>
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                </div>
                            </div>

                            <!-- Selected Worker Detail Drawer & Route Optimization Button -->
                            <div v-if="selectedWorker" class="mt-4 pt-4 border-t border-gray-100 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold text-gray-500 uppercase">Selected Worker</span>
                                    <span class="text-xs font-bold text-indigo-600">{{ selectedWorker.name }}</span>
                                </div>
                                <button
                                    @click="optimizeWorkerRoute"
                                    :disabled="isOptimizingRoute"
                                    class="w-full inline-flex justify-center items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white shadow hover:bg-indigo-700 focus:outline-none transition-colors disabled:opacity-50"
                                >
                                    <svg v-if="isOptimizingRoute" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    ⚡ Optimize Worker Route
                                </button>
                            </div>
                        </div>

                        <!-- Dispatch form -->
                        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Dispatch New Task
                            </h3>

                            <form @submit.prevent="submitTask" class="space-y-4">
                                <!-- Title -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700">Task Title</label>
                                    <input 
                                        v-model="form.title" 
                                        type="text" 
                                        placeholder="e.g. Stock Check, Delivery" 
                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                    />
                                    <span v-if="errors.title" class="text-xs text-red-600 mt-1 block">{{ errors.title[0] }}</span>
                                </div>

                                <!-- Outlet select -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700">Target Outlet</label>
                                    <select 
                                        v-model="form.outlet_id" 
                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                    >
                                        <option value="" disabled selected>Select an outlet</option>
                                        <option v-for="outlet in outlets" :key="outlet.id" :value="outlet.id">
                                            {{ outlet.name }} ({{ outlet.status }})
                                        </option>
                                    </select>
                                    <span v-if="errors.outlet_id" class="text-xs text-red-600 mt-1 block">{{ errors.outlet_id[0] }}</span>
                                </div>

                                <!-- Worker select -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700">Assign Worker</label>
                                    <select 
                                        v-model="form.assigned_user_id" 
                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                    >
                                        <option value="" disabled selected>Select a field worker</option>
                                        <option v-for="worker in workers" :key="worker.id" :value="worker.id">
                                            {{ worker.name }}
                                        </option>
                                    </select>
                                    <span v-if="errors.assigned_user_id" class="text-xs text-red-600 mt-1 block">{{ errors.assigned_user_id[0] }}</span>
                                </div>

                                <!-- Scheduled date -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700">Schedule For</label>
                                    <input 
                                        v-model="form.scheduled_for" 
                                        type="datetime-local" 
                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                    />
                                    <span v-if="errors.scheduled_for" class="text-xs text-red-600 mt-1 block">{{ errors.scheduled_for[0] }}</span>
                                </div>

                                <!-- Submit button -->
                                <button 
                                    type="submit" 
                                    :disabled="isSubmitting"
                                    class="w-full inline-flex justify-center items-center px-4 py-2.5 bg-blue-600 border border-transparent rounded-lg font-semibold text-sm text-white shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors disabled:opacity-50"
                                >
                                    <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ isSubmitting ? 'Dispatching...' : 'Dispatch Task' }}
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Right column: Leaflet map -->
                    <div class="lg:col-span-2 relative">
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm h-full flex flex-col min-h-[600px] relative">
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                                    </svg>
                                    Real-Time Operations Map
                                </h3>
                                <div class="flex items-center gap-4 text-xs font-semibold">
                                    <span class="flex items-center gap-1.5 text-gray-600">
                                        <span class="w-2.5 h-2.5 bg-blue-600 rounded-full inline-block"></span> Outlets
                                    </span>
                                    <span class="flex items-center gap-1.5 text-gray-600">
                                        <span class="w-2.5 h-2.5 bg-emerald-500 rounded-full inline-block"></span> Active Workers
                                    </span>
                                </div>
                            </div>
                            
                            <div id="dispatch-map" class="flex-1 rounded-lg shadow-inner overflow-hidden border border-gray-100 relative z-0"></div>

                            <!-- Floating Route Summary Overlay Card -->
                            <div v-if="activeRouteSummary" class="absolute top-16 right-8 z-10 w-72 rounded-xl bg-white/95 backdrop-blur-md p-4 shadow-xl border border-indigo-100 space-y-2">
                                <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-indigo-600">Optimized Route</h4>
                                    <button @click="clearActiveRoute" class="text-xs font-bold text-gray-400 hover:text-gray-600">✕ Clear</button>
                                </div>
                                <p class="text-sm font-bold text-gray-900">{{ activeRouteSummary.workerName }}</p>
                                <div class="grid grid-cols-3 gap-2 text-center pt-1">
                                    <div class="rounded-lg bg-indigo-50 p-2">
                                        <p class="text-[10px] text-indigo-500 font-bold uppercase">Stops</p>
                                        <p class="text-sm font-extrabold text-indigo-700">{{ activeRouteSummary.totalStops }}</p>
                                    </div>
                                    <div class="rounded-lg bg-emerald-50 p-2">
                                        <p class="text-[10px] text-emerald-500 font-bold uppercase">Dist.</p>
                                        <p class="text-sm font-extrabold text-emerald-700">{{ activeRouteSummary.totalDistanceKm }} km</p>
                                    </div>
                                    <div class="rounded-lg bg-amber-50 p-2">
                                        <p class="text-[10px] text-amber-500 font-bold uppercase">Time</p>
                                        <p class="text-sm font-extrabold text-amber-700">{{ activeRouteSummary.estimatedTimeMins }}m</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Notification Toasts Container -->
        <div class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 max-w-sm w-full">
            <TransitionGroup
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div 
                    v-for="toast in notifications" 
                    :key="toast.id"
                    class="p-4 rounded-xl shadow-lg border flex items-center gap-3 bg-white transition-all duration-300 hover:shadow-xl"
                    :class="{
                        'bg-emerald-50 border-emerald-200 text-emerald-800': toast.type === 'entry',
                        'bg-amber-50 border-amber-200 text-amber-800': toast.type === 'exit'
                    }"
                >
                    <!-- SVG Icons for alerts -->
                    <svg v-if="toast.type === 'entry'" class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                    <svg v-if="toast.type === 'exit'" class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 003 3h6a3 3 0 003-3V7a3 3 0 00-3-3h-6a3 3 0 00-3 3v1"></path>
                    </svg>
                    <span class="text-sm font-semibold">{{ toast.message }}</span>
                </div>
            </TransitionGroup>
        </div>
    </AuthenticatedLayout>
</template>
