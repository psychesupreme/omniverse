<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconUrl: markerIcon,
    iconRetinaUrl: markerIcon2x,
    shadowUrl: markerShadow,
});

const props = defineProps({
    tenant_id: {
        type: String,
        required: true,
    },
    users: {
        type: Array,
        default: () => [],
    },
    geofences: {
        type: Array,
        default: () => [],
    },
    outlets: {
        type: Array,
        default: () => [],
    },
});

const mapInstance = ref(null);
const markers = ref({});

onMounted(() => {
    // Build user name lookup
    const userNames = {};
    props.users.forEach(u => {
        userNames[u.id] = u.name;
    });

    // Initialize the Leaflet map centered at Nairobi coordinates
    const map = L.map('map').setView([-1.2921, 36.8219], 12); // slightly zoomed in
    mapInstance.value = map;

    // Add standard OpenStreetMap tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(map);

    // Render Geofences (Polygons)
    props.geofences.forEach(gf => {
        if (Array.isArray(gf.boundary) && gf.boundary.length > 0) {
            const points = gf.boundary.map(p => [p.lat, p.lng]);
            L.polygon(points, {
                color: 'red',
                fillOpacity: 0.2,
            })
            .bindTooltip(gf.name)
            .addTo(map);
        }
    });

    // Render Outlets (Circle Markers with Custom Icon)
    props.outlets.forEach(ot => {
        if (ot.location && typeof ot.location.latitude === 'number' && typeof ot.location.longitude === 'number') {
            const outletIcon = L.divIcon({
                className: 'custom-outlet-marker',
                html: '<div style="background-color: #10b981; width: 16px; height: 16px; border-radius: 50%; border: 2px solid #ffffff; box-shadow: 0 0 4px rgba(0,0,0,0.5);"></div>',
                iconSize: [16, 16],
                iconAnchor: [8, 8]
            });
            L.marker([ot.location.latitude, ot.location.longitude], { icon: outletIcon })
                .bindPopup(ot.name)
                .addTo(map);
        }
    });

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
                        const name = userNames[userId] || `Worker #${userId}`;

                        // Check if marker for this worker/user already exists
                        if (markers.value[userId]) {
                            // Update marker coordinates dynamically
                            markers.value[userId].setLatLng([lat, lng]);
                            markers.value[userId].getPopup().setContent(name);
                            markers.value[userId].getTooltip().setContent(name);
                        } else {
                            // Create a new Leaflet marker, bind tooltip/popup, and store it
                            const marker = L.marker([lat, lng])
                                .bindPopup(name)
                                .bindTooltip(name, { permanent: true, direction: 'top' })
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
