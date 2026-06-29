<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/locations"></ion-back-button>
        </ion-buttons>
        <ion-title>Nueva Ubicación</ion-title>
      </ion-toolbar>
    </ion-header>
    <ion-content class="ion-padding">
      <ion-item>
        <ion-label position="stacked">Nombre de la Ubicación</ion-label>
        <ion-input v-model="name" placeholder="Ej. Casa, Trabajo"></ion-input>
      </ion-item>

      <ion-item>
        <ion-label position="stacked">Dirección Escrita</ion-label>
        <ion-input v-model="address" placeholder="Ej. Av. Principal 123"></ion-input>
      </ion-item>

      <div style="margin-top: 16px;">
        <p>Selecciona en el mapa:</p>
        <div id="map" style="height: 300px; width: 100%; border: 1px solid #ccc;"></div>
      </div>

      <ion-button expand="block" style="margin-top: 20px;" @click="saveLocation" :disabled="!name || !address || !lat || saving">
        {{ saving ? 'Guardando...' : 'Guardar Ubicación' }}
      </ion-button>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { IonPage, IonHeader, IonToolbar, IonButtons, IonBackButton, IonTitle, IonContent, IonItem, IonLabel, IonInput, IonButton, onIonViewDidEnter, onIonViewWillLeave } from '@ionic/vue';
import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

let map: any = null;
let marker: any = null;

const name = ref('');
const address = ref('');
const lat = ref<number | null>(null);
const lng = ref<number | null>(null);
const saving = ref(false);
const router = useRouter();
const route = useRoute();
const isEditing = ref(false);
const editId = ref<number | null>(null);

onIonViewDidEnter(async () => {
  // Configuración inicial del mapa
  map = L.map('map').setView([-19.03332, -65.26274], 13);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

  map.on('click', (e: L.LeafletMouseEvent) => {
    if (marker) {
      marker.setLatLng(e.latlng);
    } else {
      marker = L.marker(e.latlng).addTo(map!);
    }
    lat.value = e.latlng.lat;
    lng.value = e.latlng.lng;
  });

  // Fix para el icono de Leaflet
  delete (L.Icon.Default.prototype as any)._getIconUrl;
  L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
    iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
  });

  if (route.query.id) {
    isEditing.value = true;
    editId.value = parseInt(route.query.id as string);
    try {
      const res = await axios.get('/api/locations');
      const loc = res.data.find((l: any) => l.id === editId.value);
      if (loc) {
        name.value = loc.name;
        address.value = loc.address;
        lat.value = parseFloat(loc.latitude);
        lng.value = parseFloat(loc.longitude);
        const latlng = new L.LatLng(lat.value, lng.value);
        marker = L.marker(latlng).addTo(map!);
        map.setView(latlng, 15);
      }
    } catch (e) {}
  }
});

onIonViewWillLeave(() => {
  if (map) {
    map.remove();
    map = null;
  }
  name.value = '';
  address.value = '';
  lat.value = null;
  lng.value = null;
  isEditing.value = false;
  editId.value = null;
});

const saveLocation = async () => {
  if (!name.value || !address.value || lat.value === null || lng.value === null || saving.value) return;
  
  saving.value = true;
  try {
    if (isEditing.value && editId.value) {
      await axios.put(`/api/locations/${editId.value}`, {
        name: name.value,
        address: address.value,
        latitude: lat.value,
        longitude: lng.value
      });
    } else {
      await axios.post('/api/locations', {
        name: name.value,
        address: address.value,
        latitude: lat.value,
        longitude: lng.value
      });
    }
    router.replace('/tabs/locations');
  } catch (e) {
    console.error(e);
  } finally {
    saving.value = false;
  }
};
</script>
