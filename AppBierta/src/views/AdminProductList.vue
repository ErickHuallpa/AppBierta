<template>
  <ion-page>
    <ion-header>
      <ion-toolbar color="dark">

        <ion-title>Gestión de Productos</ion-title>
        <ion-buttons slot="end">
          <ion-button router-link="/admin/products/form">
            <ion-icon slot="icon-only" :icon="addOutline"></ion-icon>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <div style="display: flex; gap: 10px; margin-bottom: 15px;">
        <ion-button expand="block" router-link="/admin/stock" style="flex:1" color="tertiary">
          <ion-icon :icon="cubeOutline" slot="start"></ion-icon>
          Ingresar Stock
        </ion-button>
        <ion-button expand="block" router-link="/admin/batches" style="flex:1" color="warning">
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
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonButton, IonIcon, IonGrid, IonRow, IonCol, IonCard, IonCardHeader, IonCardTitle, IonCardSubtitle, IonCardContent, IonSpinner, toastController, onIonViewWillEnter } from '@ionic/vue';
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

const editProduct = (id: number) => {
  router.push(`/admin/products/form/${id}`);
};

const deleteProduct = async (id: number) => {
  if (!confirm('¿Estás seguro de borrar este producto?')) return;
  try {
    await axios.delete(`/api/products/${id}`);
    loadProducts();
    const toast = await toastController.create({
      message: 'Producto eliminado',
      duration: 2000,
      color: 'success'
    });
    toast.present();
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
