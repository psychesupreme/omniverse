<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    tenant_id: {
        type: String,
        required: true,
    },
});

const mapInstance = ref(null);
const markers = ref({});

onMounted(() => {
    // Initialize the Leaflet map centered at Nairobi coordinates
    const map = L.map('map').setView([-1.2921, 36.8219], 10);
    mapInstance.value = map;

    // Add standard OpenStreetMap tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(map);

    // Subscribe to the tenant's private sync channel
    window.Echo.private('tenant.' + props.tenant_id + '.sync')
        .listen('.GeofenceAlertTriggered', (e) => {
            alert(`User ${e.user_id} triggered ${e.type} at ${e.geofence}`);
        })
        .listen('.SyncProcessed', (e) => {
            console.log('Sync processed event payload received:', e);
            if (e.model === 'TrackingLog' && Array.isArray(e.records)) {
                e.records.forEach((record) => {
                    const userId = record.user_id;
                    const location = record.location;
                    if (location && typeof location.latitude === 'number' && typeof location.longitude === 'number') {
                        const lat = location.latitude;
                        const lng = location.longitude;

                        // Check if marker for this worker/user already exists
                        if (markers.value[userId]) {
                            // Update marker coordinates dynamically
                            markers.value[userId].setLatLng([lat, lng]);
                        } else {
                            // Create a new Leaflet marker, bind tooltip/popup, and store it
                            const marker = L.marker([lat, lng])
                                .bindPopup(`Worker #${userId}`)
                                .addTo(mapInstance.value);
                            markers.value[userId] = marker;
                        }
                    }
                });
            }
        });
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="p-6 text-gray-900">
                        <div id="map" class="h-[600px] w-full rounded-lg shadow-inner z-0"></div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
