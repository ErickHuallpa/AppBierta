<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>

        <ion-title>Mis Ubicaciones</ion-title>
      </ion-toolbar>
    </ion-header>
    <ion-content class="ion-padding">
      <ion-list>
        <ion-item v-for="loc in locations" :key="loc.id">
          <div class="location-content">
            <ion-label>
              <h2>{{ loc.name }} <ion-badge v-if="loc.is_default" color="success">Principal</ion-badge></h2>
              <p>{{ loc.address }}</p>
            </ion-label>
            <div class="location-actions">
              <ion-button fill="clear" size="small" color="primary" @click="editLocation(loc.id)">
                <ion-icon :icon="createOutline" slot="icon-only"></ion-icon>
              </ion-button>
              <ion-button fill="clear" size="small" color="warning" @click="setDefault(loc.id)" v-if="!loc.is_default">
                <ion-icon :icon="starOutline" slot="icon-only"></ion-icon>
              </ion-button>
              <ion-button fill="clear" size="small" color="danger" @click="deleteLocation(loc.id)">
                <ion-icon :icon="trashOutline" slot="icon-only"></ion-icon>
              </ion-button>
            </div>
          </div>
        </ion-item>
      </ion-list>
      
      <div v-if="locations.length === 0" class="ion-text-center ion-padding">
        <p>No tienes ubicaciones guardadas.</p>
      </div>

      <ion-fab vertical="bottom" horizontal="end" slot="fixed">
        <ion-fab-button router-link="/locations/add">
          <ion-icon :icon="add"></ion-icon>
        </ion-fab-button>
      </ion-fab>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonList, IonItem, IonLabel, IonBadge, IonFab, IonFabButton, IonIcon, IonButton, onIonViewWillEnter } from '@ionic/vue';
import { add, createOutline, starOutline, trashOutline } from 'ionicons/icons';
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const locations = ref<any[]>([]);
const router = useRouter();

const fetchLocations = async () => {
  try {
    const res = await axios.get('/api/locations');
    locations.value = res.data;
  } catch (error) {
    console.error(error);
  }
};

const editLocation = (id: number) => {
  router.push(`/locations/add?id=${id}`);
};

const deleteLocation = async (id: number) => {
  if (confirm('¿Estás seguro de que quieres eliminar esta ubicación?')) {
    try {
      await axios.delete(`/api/locations/${id}`);
      fetchLocations();
    } catch (e) {
      console.error(e);
    }
  }
};

const setDefault = async (id: number) => {
  try {
    await axios.post(`/api/locations/${id}/set-default`);
    fetchLocations();
  } catch (e) {
    console.error(e);
  }
};

onIonViewWillEnter(() => {
  fetchLocations();
});
</script>

<style scoped>
.location-content {
  width: 100%;
  display: flex;
  flex-direction: column;
}
.location-actions {
  display: flex;
  justify-content: flex-end;
  border-top: 1px solid var(--ion-color-light);
  margin-top: 8px;
}
</style>
