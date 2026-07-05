<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar class="header-toolbar">
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/admin/dashboard" color="light" text=""></ion-back-button>
        </ion-buttons>
        <ion-title style="font-weight: 600;">Entregar Pedidos</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding content-bg">
      <ion-refresher slot="fixed" @ionRefresh="handleRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

      <div v-if="loading" class="ion-text-center ion-margin-top">
        <ion-spinner></ion-spinner>
      </div>

      <ion-list v-else>
        <ion-item v-for="order in pickupOrders" :key="order.id" class="order-item" lines="none">
          <ion-label>
            <h2 class="order-title">Pedido #{{ order.id }}</h2>
            <p class="order-client">Cliente: {{ order.user?.persona?.nombre }} {{ order.user?.persona?.apellidos }}</p>
            <p class="order-total">Total: Bs. {{ order.total_amount }} <span v-if="order.payment_method === 'points'">(Puntos: {{ order.points_cost }})</span></p>
            
            <div class="products-box">
              <p class="products-title">Productos:</p>
              <ul class="products-list">
                <li v-for="item in order.items" :key="item.id">
                  {{ item.quantity }}x {{ item.product?.name }}
                </li>
              </ul>
            </div>
          </ion-label>
          <div slot="end" style="display: flex; flex-direction: column; gap: 10px; justify-content: center;">
            <ion-button color="success" shape="round" @click="markDelivered(order.id)">
              Entregar
            </ion-button>
          </div>
        </ion-item>
      </ion-list>

      <div v-if="!loading && pickupOrders.length === 0" class="ion-text-center empty-msg">
        <p>No hay pedidos pendientes para recoger.</p>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { IonPage, IonHeader, IonToolbar, IonButtons, IonBackButton, IonTitle, IonContent, IonList, IonItem, IonLabel, IonButton, IonSpinner, IonRefresher, IonRefresherContent, toastController, onIonViewWillEnter } from '@ionic/vue';
import { ref } from 'vue';
import axios from 'axios';

const pickupOrders = ref<any[]>([]);
const loading = ref(false);

const fetchOrders = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/admin/pickup-orders');
    pickupOrders.value = res.data;
  } catch(e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const markDelivered = async (id: number) => {
  try {
    const res = await axios.post(`/api/admin/orders/${id}/deliver`);
    const toast = await toastController.create({
      message: 'Pedido entregado al cliente',
      duration: 2000,
      color: 'success'
    });
    toast.present();
    fetchOrders();
  } catch(e) {
    const toast = await toastController.create({
      message: 'Error al entregar pedido',
      duration: 2000,
      color: 'danger'
    });
    toast.present();
  }
};

const handleRefresh = async (event: any) => {
  await fetchOrders();
  event.target.complete();
};

onIonViewWillEnter(() => {
  fetchOrders();
});
</script>

<style scoped>
.header-toolbar {
  --background: #04644c;
  color: #ffffff;
}
.content-bg {
  --background: #f7f9fc;
}
.order-item {
  --background: #ffffff;
  --border-radius: 12px;
  margin-bottom: 15px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
  --padding-start: 15px;
  --inner-padding-end: 15px;
  --padding-top: 10px;
  --padding-bottom: 10px;
  align-items: flex-start;
}
.order-title {
  font-weight: 600;
  color: #04644c;
}
.order-client {
  font-weight: 500;
  color: #333;
}
.order-total {
  color: #666;
}
.products-box {
  margin-top: 10px;
  background: #f0f0f0;
  padding: 10px;
  border-radius: 8px;
}
.products-title {
  font-size: 0.9em;
  font-weight: bold;
  margin-bottom: 5px;
  color: #333;
}
.products-list {
  margin: 0;
  padding-left: 20px;
  font-size: 0.85em;
  color: #555;
}
.empty-msg {
  margin-top: 50px;
  color: #666;
}

body.dark .content-bg,
:host-context(body.dark) .content-bg {
  --background: #121212;
}
body.dark .order-item,
:host-context(body.dark) .order-item {
  --background: #1e2023;
  box-shadow: 0 4px 10px rgba(0,0,0,0.3);
}
body.dark .order-title,
:host-context(body.dark) .order-title {
  color: #4caf50;
}
body.dark .order-client,
:host-context(body.dark) .order-client {
  color: #e0e0e0;
}
body.dark .order-total,
:host-context(body.dark) .order-total {
  color: #aaa;
}
body.dark .products-box,
:host-context(body.dark) .products-box {
  background: #2a2d32;
}
body.dark .products-title,
:host-context(body.dark) .products-title {
  color: #e0e0e0;
}
body.dark .products-list,
:host-context(body.dark) .products-list {
  color: #ccc;
}
body.dark .empty-msg,
:host-context(body.dark) .empty-msg {
  color: #aaa;
}

</style>
