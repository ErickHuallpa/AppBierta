<template>
  <ion-page>
    <ion-header>
      <ion-toolbar color="dark">
        <ion-buttons slot="start">
          <ion-back-button default-href="/admin"></ion-back-button>
        </ion-buttons>
        <ion-title>Nuevo Producto</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <ion-list>
        <ion-item>
          <ion-label position="floating">Nombre de la Cerveza</ion-label>
          <ion-input v-model="prod.name"></ion-input>
        </ion-item>
        <ion-item>
          <ion-label position="floating">Presentación (Lata/Botella)</ion-label>
          <ion-input v-model="prod.presentation"></ion-input>
        </ion-item>
        <ion-item>
          <ion-label position="floating">Precio de Compra (Bs)</ion-label>
          <ion-input type="number" v-model="prod.precio_compra"></ion-input>
        </ion-item>
        <ion-item>
          <ion-label position="floating">Precio de Venta (Bs)</ion-label>
          <ion-input type="number" v-model="prod.precio_venta"></ion-input>
        </ion-item>
        <ion-item>
          <ion-label position="floating">Stock Inicial</ion-label>
          <ion-input type="number" v-model="prod.stock"></ion-input>
        </ion-item>
        <ion-item>
          <ion-label position="floating">Puntos que otorga</ion-label>
          <ion-input type="number" v-model="prod.points_reward"></ion-input>
        </ion-item>
        <ion-item>
          <ion-label position="floating">Costo en Puntos (Canje)</ion-label>
          <ion-input type="number" v-model="prod.points_cost"></ion-input>
        </ion-item>
        <ion-item>
          <ion-label position="floating">Límite por usuario (Dejar vacío = sin límite)</ion-label>
          <ion-input type="number" v-model="prod.max_quota_per_user"></ion-input>
        </ion-item>
      </ion-list>

      <ion-button expand="block" color="success" class="ion-margin-top" @click="saveProduct" :disabled="loading">
        {{ loading ? 'Guardando...' : 'Guardar Producto' }}
      </ion-button>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonList, IonItem, IonLabel, IonInput, IonButton, IonButtons, IonBackButton, toastController } from '@ionic/vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const loading = ref(false);
const prod = ref({
  name: '',
  presentation: '',
  precio_compra: '',
  precio_venta: '',
  stock: '',
  points_reward: '',
  points_cost: '',
  max_quota_per_user: ''
});

const saveProduct = async () => {
  loading.value = true;
  try {
    await axios.post('/api/products', {
      ...prod.value,
      max_quota_per_user: prod.value.max_quota_per_user || null
    });
    
    const toast = await toastController.create({
      message: 'Producto registrado en el Catálogo',
      duration: 3000,
      color: 'success'
    });
    toast.present();
    router.push('/admin');
  } catch (error: any) {
    const toast = await toastController.create({
      message: 'Revisa que todos los campos estén llenos',
      duration: 3000,
      color: 'danger'
    });
    toast.present();
  } finally {
    loading.value = false;
  }
};
</script>
