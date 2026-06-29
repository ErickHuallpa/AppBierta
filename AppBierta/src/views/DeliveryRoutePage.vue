<template>
  <ion-page>
    <ion-header>
      <ion-toolbar color="primary">
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/delivery"></ion-back-button>
        </ion-buttons>
        <ion-title>Ruta #{{ order?.id }}</ion-title>
      </ion-toolbar>
    </ion-header>
    
    <ion-content v-if="loading" class="ion-padding ion-text-center">
      <ion-spinner></ion-spinner>
      <p>Cargando ruta...</p>
    </ion-content>
    
    <ion-content v-else-if="order" class="route-content">
      <div class="map-container-full">
        <div id="routeMap" style="width: 100%; height: 100%;"></div>
      </div>
      
      <!-- Overlay flotante con información -->
      <div class="route-overlay">
        <div class="route-info">
          <h2>{{ order.location?.name || order.location?.address }}</h2>
          <p v-if="order.location?.reference">{{ order.location?.reference }}</p>
          <p v-if="distance" style="color: var(--ion-color-primary); font-weight: bold;">
            <ion-icon :icon="navigateOutline"></ion-icon> A {{ distance }} km de ti
          </p>
          
          <div class="customer-info">
            <ion-icon :icon="personCircleOutline" style="font-size: 24px; margin-right: 8px;"></ion-icon>
            <span>{{ order.user?.persona?.nombre || order.user?.name || 'Cliente' }} {{ order.user?.persona?.apellidos || '' }}</span>
          </div>
          
          <div class="payment-info" v-if="order.payment_method === 'cash'">
            <ion-icon :icon="cashOutline" style="font-size: 24px; margin-right: 8px; color: #10dc60;"></ion-icon>
            <span><strong>Cobrar:</strong> Bs. {{ order.total_amount + order.delivery_cost }}</span>
          </div>
          <div class="payment-info" v-else>
            <ion-icon :icon="qrCodeOutline" style="font-size: 24px; margin-right: 8px; color: var(--ion-color-primary);"></ion-icon>
            <span><strong>Pagado por QR</strong></span>
          </div>
        </div>
      </div>
    </ion-content>

    <ion-footer class="ion-no-border" v-if="order">
      <ion-toolbar class="ion-padding">
        <ion-button expand="block" color="success" size="large" @click="markDelivered(order.id)">
          <ion-icon slot="start" :icon="checkmarkCircleOutline"></ion-icon>
          MARCAR COMO ENTREGADO
        </ion-button>
      </ion-toolbar>
    </ion-footer>
  </ion-page>
</template>

<script setup lang="ts">
import { IonPage, IonHeader, IonToolbar, IonButtons, IonBackButton, IonTitle, IonContent, IonFooter, IonButton, IonIcon, IonSpinner, toastController, onIonViewDidEnter, onIonViewWillLeave } from '@ionic/vue';
import { checkmarkCircleOutline, personCircleOutline, cashOutline, qrCodeOutline, navigateOutline } from 'ionicons/icons';
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import * as L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet-routing-machine';
import 'leaflet-routing-machine/dist/leaflet-routing-machine.css';

const route = useRoute();
const router = useRouter();
const order = ref<any>(null);
const loading = ref(true);
const distance = ref<string | null>(null);

let map: L.Map | null = null;
let userMarker: L.Marker | null = null;
let destMarker: L.Marker | null = null;
let routingControl: any = null;

const deg2rad = (deg: number) => deg * (Math.PI / 180);
const getDistanceFromLatLonInKm = (lat1: number, lon1: number, lat2: number, lon2: number) => {
  const R = 6371; 
  const dLat = deg2rad(lat2 - lat1);
  const dLon = deg2rad(lon2 - lon1);
  const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * 
            Math.sin(dLon/2) * Math.sin(dLon/2); 
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)); 
  return R * c; 
};

const fetchOrder = async () => {
  loading.value = true;
  try {
    const res = await axios.get(`/api/delivery/orders/${route.params.id}`);
    order.value = res.data;
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const initMap = () => {
  if (!order.value || !order.value.location) return;
  const destLat = parseFloat(order.value.location.latitude);
  const destLng = parseFloat(order.value.location.longitude);

  map = L.map('routeMap').setView([destLat, destLng], 14);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

  delete (L.Icon.Default.prototype as any)._getIconUrl;
  L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
    iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
  });

  // Marcador de destino
  destMarker = L.marker([destLat, destLng]).addTo(map).bindPopup('Destino');

  // Obtener ubicación actual
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition((pos) => {
      const userLat = pos.coords.latitude;
      const userLng = pos.coords.longitude;
      
      const dist = getDistanceFromLatLonInKm(userLat, userLng, destLat, destLng);
      distance.value = dist.toFixed(1);

      // Icono personalizado para el repartidor (azul)
      const userIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
      });

      userMarker = L.marker([userLat, userLng], {icon: userIcon}).addTo(map!).bindPopup('Tu ubicación');

      // Trazar ruta real por calles (OSRM)
      routingControl = (L as any).Routing.control({
        waypoints: [
          L.latLng(userLat, userLng),
          L.latLng(destLat, destLng)
        ],
        routeWhileDragging: false,
        addWaypoints: false,
        showAlternatives: false,
        fitSelectedRoutes: true,
        show: false, // Ocultar el panel de instrucciones para ahorrar espacio
        createMarker: function() { return null; } // No crear marcadores extra, ya tenemos los nuestros
      }).addTo(map!);
    });
  }
};

onIonViewDidEnter(async () => {
  await fetchOrder();
  setTimeout(() => {
    initMap();
  }, 100);
});

onIonViewWillLeave(() => {
  if (map) {
    map.remove();
    map = null;
  }
});

const markDelivered = async (id: number) => {
  try {
    await axios.post(`/api/delivery/orders/${id}/delivered`);
    const toast = await toastController.create({ message: 'Pedido entregado exitosamente', duration: 2000, color: 'success' });
    await toast.present();
    router.replace('/tabs/delivery');
  } catch(e) {}
};
</script>

<style scoped>
.route-content {
  --padding-top: 0;
  --padding-bottom: 0;
  --padding-start: 0;
  --padding-end: 0;
  position: relative;
}

.map-container-full {
  width: 100%;
  height: 100%;
  background: var(--ion-color-light);
}

.route-overlay {
  position: absolute;
  top: 10px;
  left: 10px;
  right: 10px;
  background: rgba(255, 255, 255, 0.95);
  border-radius: 12px;
  padding: 15px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  z-index: 1000; /* Leaflet usa z-index 400 por defecto */
}

@media (prefers-color-scheme: dark) {
  .route-overlay {
    background: rgba(30, 30, 30, 0.95);
    color: white;
  }
}

.route-info h2 {
  margin: 0 0 5px 0;
  font-size: 1.2rem;
  font-weight: bold;
}

.route-info p {
  margin: 0 0 10px 0;
  color: var(--ion-color-medium);
  font-size: 0.9rem;
}

.customer-info {
  display: flex;
  align-items: center;
  font-weight: 500;
  color: var(--ion-color-primary);
  border-top: 1px solid var(--ion-color-light);
  padding-top: 10px;
}

.payment-info {
  display: flex;
  align-items: center;
  font-weight: 500;
  margin-top: 10px;
  font-size: 1.1rem;
}
</style>
