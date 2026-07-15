<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet-draw';
import 'leaflet-draw/dist/leaflet.draw.css';

const props = defineProps({
    geofences: {
        type: Array,
        default: () => [],
    },
});

// Map references
let map = null;
let drawnItems = null;

// Modal & creation state
const showCreateModal = ref(false);
const newGeofenceName = ref('');
const newGeofenceDescription = ref('');
const tempDrawnLayer = ref(null);
const tempCoordinates = ref([]);
const isSubmitting = ref(false);
const formErrors = ref({});

// Map initialization
onMounted(() => {
    // Initialize Leaflet map
    map = L.map('geofence-map').setView([-1.286389, 36.817223], 13); // Default Nairobi Coordinates

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(map);

    drawnItems = new L.FeatureGroup();
    map.addLayer(drawnItems);

    const bounds = [];

    // Render existing geofences as static polygons on the map
    props.geofences.forEach((geofence) => {
        if (geofence.area && geofence.area.length > 0) {
            const latlngs = geofence.area.map(coord => [coord.lat, coord.lng]);
            const polygon = L.polygon(latlngs, {
                color: '#6366f1', // indigo
                fillColor: '#818cf8',
                fillOpacity: 0.35,
                weight: 2
            })
            .addTo(map)
            .bindPopup(`
                <div class="p-1">
                    <h3 class="font-bold text-gray-900 text-sm">${geofence.name}</h3>
                    <p class="text-xs text-gray-500 mt-1">${geofence.description || 'No description'}</p>
                </div>
            `);

            bounds.push(latlngs);
        }
    });

    if (bounds.length > 0) {
        map.fitBounds(bounds);
    }

    // Configure and add Leaflet Draw controls
    const drawControl = new L.Control.Draw({
        draw: {
            polygon: {
                allowIntersection: false,
                drawError: {
                    color: '#e11d48', // rose 600
                    message: '<strong>Intersection not allowed!<strong>'
                },
                shapeOptions: {
                    color: '#10b981', // emerald 500
                    fillColor: '#34d399',
                    fillOpacity: 0.4,
                    weight: 3
                }
            },
            polyline: false,
            rectangle: false,
            circle: false,
            marker: false,
            circlemarker: false
        },
        edit: false // Disable direct editing toolbar for simplicity
    });
    
    map.addControl(drawControl);

    // Listen to polygon creation event
    map.on(L.Draw.Event.CREATED, (e) => {
        const layer = e.layer;
        tempDrawnLayer.value = layer;

        // Extract coordinates from drawn polygon ring
        const latlngs = layer.getLatLngs()[0];
        tempCoordinates.value = latlngs.map(latlng => ({
            lat: latlng.lat,
            lng: latlng.lng
        }));

        // Reset inputs and open naming modal
        newGeofenceName.value = '';
        newGeofenceDescription.value = '';
        formErrors.value = {};
        showCreateModal.value = true;
    });
});

// Save geofence to API
const saveGeofence = () => {
    if (!newGeofenceName.value.trim()) {
        formErrors.value = { name: ['The geofence name is required.'] };
        return;
    }

    isSubmitting.value = true;
    formErrors.value = {};

    axios.post('/api/v1/dispatch/geofences', {
        name: newGeofenceName.value,
        description: newGeofenceDescription.value,
        coordinates: tempCoordinates.value
    })
    .then((response) => {
        // Add layer permanently to drawnItems layer group
        if (tempDrawnLayer.value) {
            tempDrawnLayer.value.bindPopup(`
                <div class="p-1">
                    <h3 class="font-bold text-gray-900 text-sm">${newGeofenceName.value}</h3>
                    <p class="text-xs text-gray-500 mt-1">${newGeofenceDescription.value || 'No description'}</p>
                </div>
            `);
            drawnItems.addLayer(tempDrawnLayer.value);
        }

        closeModal();
        
        // Reload Inertia props
        router.reload();
    })
    .catch((error) => {
        if (error.response && error.response.data && error.response.data.errors) {
            formErrors.value = error.response.data.errors;
        } else {
            console.error('Failed to create geofence:', error);
            alert('Failed to save geofence. Make sure it is a valid closed polygon.');
        }
    })
    .finally(() => {
        isSubmitting.value = false;
    });
};

// Cancel & Close Modal
const closeModal = () => {
    if (tempDrawnLayer.value && !isSubmitting.value) {
        tempDrawnLayer.value.remove(); // Remove drawn layer from map preview
    }
    tempDrawnLayer.value = null;
    tempCoordinates.value = [];
    showCreateModal.value = false;
};

// Delete Geofence
const deleteGeofence = (geofenceId) => {
    if (confirm('Are you sure you want to delete this geofence?')) {
        axios.delete(`/api/v1/dispatch/geofences/${geofenceId}`)
            .then(() => {
                router.reload();
            })
            .catch((error) => {
                console.error('Failed to delete geofence:', error);
                alert('Could not delete the geofence. Please try again.');
            });
    }
};

// Fly map to polygon bounding box on sidebar selection
const focusGeofence = (geofence) => {
    if (geofence.area && geofence.area.length > 0) {
        const latlngs = geofence.area.map(coord => [coord.lat, coord.lng]);
        const polygon = L.polygon(latlngs);
        map.fitBounds(polygon.getBounds(), { padding: [30, 30], maxZoom: 16 });
    }
};
</script>

<template>
    <Head title="Geofence Management" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold leading-tight text-gray-800">
                    Geofence Management
                </h2>
                <span class="text-sm text-gray-500 font-medium">
                    Use the polygon tool on the map to define a geofence.
                </span>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Two column layout -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-4 h-[calc(100vh-220px)] min-h-[600px]">
                    
                    <!-- Sidebar list: 1/4 width -->
                    <div class="lg:col-span-1 rounded-xl border border-gray-200 bg-white p-4 shadow-sm flex flex-col h-full overflow-hidden">
                        <div class="mb-4">
                            <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                                </svg>
                                Geofences
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5">Active monitoring zones</p>
                        </div>

                        <!-- Scrollable list -->
                        <div class="flex-1 overflow-y-auto pr-1">
                            <ul role="list" class="divide-y divide-gray-100">
                                <li 
                                    v-for="geofence in geofences" 
                                    :key="geofence.id" 
                                    class="py-3 flex flex-col justify-between hover:bg-gray-50 rounded-lg p-2 transition-colors cursor-pointer group"
                                    @click="focusGeofence(geofence)"
                                >
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 group-hover:text-indigo-600 truncate">{{ geofence.name }}</p>
                                        <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ geofence.description || 'No description' }}</p>
                                    </div>
                                    <div class="mt-2 flex items-center justify-between border-t border-gray-50 pt-2">
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 capitalize">
                                            Active
                                        </span>
                                        <button 
                                            @click.stop="deleteGeofence(geofence.id)"
                                            class="text-xs font-semibold text-red-600 hover:text-red-800 transition-colors flex items-center gap-0.5"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            Delete
                                        </button>
                                    </div>
                                </li>
                                <li v-if="geofences.length === 0" class="py-12 text-center text-sm text-gray-500">
                                    No geofences created yet. Click the polygon icon on the map to draw one!
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Main Leaflet Map area: 3/4 width -->
                    <div class="lg:col-span-3 h-full">
                        <div class="rounded-xl border border-gray-200 bg-white p-2 shadow-sm h-full flex flex-col overflow-hidden">
                            <div id="geofence-map" class="flex-1 rounded-lg w-full h-full"></div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Custom Creation Dialog Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Overlay background -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="closeModal"></div>

                <!-- Center elements trick -->
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <!-- Modal box -->
                <div class="relative inline-block transform overflow-hidden rounded-xl bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                    <div class="bg-white px-6 pt-6 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg font-bold leading-6 text-gray-900" id="modal-title">
                                    Save Geofence Area
                                </h3>
                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700">Geofence Name</label>
                                        <input 
                                            v-model="newGeofenceName" 
                                            type="text" 
                                            placeholder="e.g. Warehouse Zone A" 
                                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                        />
                                        <span v-if="formErrors.name" class="text-xs text-red-600 mt-1 block">{{ formErrors.name[0] }}</span>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700">Description</label>
                                        <textarea 
                                            v-model="newGeofenceDescription" 
                                            rows="3"
                                            placeholder="Specify boundary highlights or guidelines..."
                                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                        ></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button 
                            type="button" 
                            :disabled="isSubmitting"
                            @click="saveGeofence"
                            class="inline-flex w-full justify-center rounded-lg border border-transparent bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:ml-3 sm:w-auto transition-colors disabled:opacity-50"
                        >
                            {{ isSubmitting ? 'Saving...' : 'Save Geofence' }}
                        </button>
                        <button 
                            type="button" 
                            :disabled="isSubmitting"
                            @click="closeModal"
                            class="mt-3 inline-flex w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:mt-0 sm:w-auto transition-colors"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
