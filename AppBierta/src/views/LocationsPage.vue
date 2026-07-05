<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar style="--background: #04644c; color: #ffffff;">
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/profile" color="light" text=""></ion-back-button>
        </ion-buttons>
        <ion-title style="font-weight: 600;">Mis Ubicaciones</ion-title>
      </ion-toolbar>
    </ion-header>
    <ion-content class="ion-padding" style="--background: var(--ion-background-color, #f7f9fc);">
      <ion-refresher slot="fixed" @ionRefresh="handleRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>
      <div v-if="loading" class="ion-text-center ion-padding" style="margin-top: 50px;">
        <ion-spinner name="crescent"></ion-spinner>
      </div>

      <ion-list v-else-if="locations.length > 0" style="background: transparent;">
        <ion-item v-for="loc in locations" :key="loc.id" class="location-item">
          <div class="location-content">
            <ion-label>
              <h2 style="font-weight: 600;">{{ loc.name }} <ion-badge v-if="loc.is_default" color="success">Principal</ion-badge></h2>
              <p style="color: #666;">{{ loc.address }}</p>
            </ion-label>
            <div class="location-actions">
              <ion-button fill="clear" size="small" style="--color: #04644c;" @click="editLocation(loc.id)">
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
      
      <div v-else class="ion-text-center ion-padding">
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
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonList, IonItem, IonLabel, IonBadge, IonFab, IonFabButton, IonIcon, IonButton, IonButtons, IonBackButton, onIonViewWillEnter, IonRefresher, IonRefresherContent, alertController, IonSpinner } from '@ionic/vue';
import { add, createOutline, starOutline, trashOutline } from 'ionicons/icons';
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const locations = ref<any[]>([]);
const loading = ref(true);
const router = useRouter();

const fetchLocations = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/locations');
    locations.value = res.data;
    window.dispatchEvent(new Event('locationsUpdated'));
  } catch (error) {
    console.error(error);
  } finally {
    loading.value = false;
  }
};

const handleRefresh = async (event: any) => {
  await fetchLocations();
  event.target.complete();
};

const editLocation = (id: number) => {
  router.push(`/locations/add?id=${id}`);
};

const deleteLocation = async (id: number) => {
  const alert = await alertController.create({
    header: 'Confirmar',
    message: '¿Estás seguro de que quieres eliminar esta ubicación?',
    buttons: [
      { text: 'Cancelar', role: 'cancel' },
      { text: 'Eliminar', role: 'confirm' }
    ]
  });
  await alert.present();
  const { role } = await alert.onDidDismiss();
  if (role !== 'confirm') return;

  try {
    await axios.delete(`/api/locations/${id}`);
    fetchLocations();
  } catch (e) {
    console.error(e);
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
  border-top: 1px solid var(--ion-color-step-100, #eee);
  margin-top: 10px;
  padding-top: 5px;
}
.location-item {
  --background: var(--app-card-bg, #ffffff);
  --border-radius: 12px;
  margin-bottom: 15px;
  --padding-start: 15px;
  --inner-padding-end: 15px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
}
</style>
