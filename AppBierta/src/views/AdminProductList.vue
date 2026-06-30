<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar style="--background: #04644c; color: #ffffff;">
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/profile" color="light" text=""></ion-back-button>
        </ion-buttons>
        <ion-title style="font-weight: 600;">Gestión de Productos</ion-title>
        <ion-buttons slot="end">
          <ion-button router-link="/admin/products/form" color="light">
            <ion-icon slot="icon-only" :icon="addOutline"></ion-icon>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding" style="--background: #f7f9fc;">
      <ion-refresher slot="fixed" @ionRefresh="handleRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>
      <div style="display: flex; gap: 10px; margin-bottom: 15px;">
        <ion-button expand="block" router-link="/admin/stock" style="flex:1; --background: #04644c; color: #fff;">
          <ion-icon :icon="cubeOutline" slot="start"></ion-icon>
          Ingresar Stock
        </ion-button>
        <ion-button expand="block" router-link="/admin/batches" style="flex:1; --background: #000; color: #fff;">
          <ion-icon :icon="calendarOutline" slot="start"></ion-icon>
          Vencimientos
        </ion-button>
      </div>
      <div v-if="loading" class="ion-text-center">
        <ion-spinner></ion-spinner>
      </div>

      <ion-grid v-else>
        <ion-row>
          <ion-col size="12" size-md="6" size-lg="4" v-for="product in products" :key="product.id">
            <ion-card>
              <div class="image-container" style="height: 150px; overflow: hidden;">
                <img :src="product.image_url || `https://placehold.co/600x400/eeeeee/333333?text=${product.name}`" style="width: 100%; height: 100%; object-fit: cover;"/>
              </div>
              <ion-card-header>
                <ion-card-title>{{ product.name }}</ion-card-title>
                <ion-card-subtitle>Stock: {{ product.real_stock }} | Bs. {{ product.precio_venta }}</ion-card-subtitle>
              </ion-card-header>
              <ion-card-content class="ion-text-right">
                <ion-button fill="clear" color="primary" @click="editProduct(product.id)">Editar</ion-button>
                <ion-button fill="clear" color="danger" @click="deleteProduct(product.id)">Borrar</ion-button>
              </ion-card-content>
            </ion-card>
          </ion-col>
        </ion-row>
      </ion-grid>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton, IonIcon, IonGrid, IonRow, IonCol, IonCard, IonCardHeader, IonCardTitle, IonCardSubtitle, IonCardContent, IonSpinner, toastController, alertController, onIonViewWillEnter, IonRefresher, IonRefresherContent } from '@ionic/vue';
import { addOutline, cubeOutline, calendarOutline } from 'ionicons/icons';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const products = ref<any[]>([]);
const loading = ref(true);

const loadProducts = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/products');
    products.value = res.data;
  } catch (e) { }
  loading.value = false;
};

onIonViewWillEnter(() => {
  loadProducts();
});

const handleRefresh = async (event: any) => {
  await loadProducts();
  event.target.complete();
};

const editProduct = (id: number) => {
  router.push(`/admin/products/form/${id}`);
};

const deleteProduct = async (id: number) => {
  const alert = await alertController.create({
    header: 'Confirmar',
    message: '¿Estás seguro de borrar este producto?',
    buttons: [
      { text: 'Cancelar', role: 'cancel' },
      { text: 'Aceptar', role: 'confirm' }
    ]
  });
  await alert.present();
  const { role } = await alert.onDidDismiss();
  if (role !== 'confirm') return;

  try {
    await axios.delete(`/api/products/${id}`);
    loadProducts();
    const toast = await toastController.create({
      message: 'Producto eliminado',
      duration: 2000,
      color: 'success'
    });
    await toast.present();
  } catch (error) {
    const toast = await toastController.create({
      message: 'Error al eliminar',
      duration: 2000,
      color: 'danger'
    });
    toast.present();
  }
};
</script>

<style scoped>
ion-card {
  border-radius: 12px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
  background: #ffffff;
}
</style>
