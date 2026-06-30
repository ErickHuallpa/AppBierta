<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar style="--background: #04644c; color: #ffffff;">
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/profile" color="light" text=""></ion-back-button>
        </ion-buttons>
        <ion-title style="font-weight: 600;">Validar Pagos</ion-title>
      </ion-toolbar>
    </ion-header>
    <ion-content class="ion-padding" style="--background: #f7f9fc;">
      <ion-list>
        <ion-item v-for="order in pendingOrders" :key="order.id" class="payment-item" lines="none">
          <ion-label>
            <h2 style="font-weight: 600; color: #04644c;">Pedido #{{ order.id }} - {{ order.user?.email }}</h2>
            <p style="color: #666;">Monto: {{ order.total_amount }} Bs.</p>
            <p>Método: {{ order.payment_method === 'qr' ? 'QR' : 'Efectivo' }}</p>
            <p v-if="order.payment_method === 'qr' && order.payment_receipt">
              <ion-button fill="outline" size="small" @click="viewReceipt(order.payment_receipt)">Ver Comprobante</ion-button>
            </p>
          </ion-label>
          <div slot="end">
            <ion-button color="success" size="small" @click="approve(order.id)">Aprobar</ion-button>
            <ion-button color="danger" size="small" @click="reject(order.id)">Rechazar</ion-button>
          </div>
        </ion-item>
      </ion-list>
      <p v-if="pendingOrders.length === 0" class="ion-text-center">No hay pagos pendientes de validación.</p>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { IonPage, IonHeader, IonToolbar, IonButtons, IonBackButton, IonMenuButton, IonTitle, IonContent, IonList, IonItem, IonLabel, IonButton, onIonViewWillEnter, toastController } from '@ionic/vue';
import { ref } from 'vue';
import axios from 'axios';

const pendingOrders = ref<any[]>([]);

const fetchPending = async () => {
  try {
    const res = await axios.get('/api/admin/payments');
    pendingOrders.value = res.data;
  } catch(e) {}
};

const viewReceipt = (path: string) => {
  window.open(`http://127.0.0.1:8000/storage/${path}`, '_blank');
};

const approve = async (id: number) => {
  try {
    await axios.post(`/api/admin/payments/${id}/approve`);
    const toast = await toastController.create({ message: 'Pago aprobado', duration: 2000, color: 'success' });
    await toast.present();
    fetchPending();
  } catch(e) {}
};

const reject = async (id: number) => {
  try {
    await axios.post(`/api/admin/payments/${id}/reject`);
    const toast = await toastController.create({ message: 'Pago rechazado', duration: 2000, color: 'danger' });
    await toast.present();
    fetchPending();
  } catch(e) {}
};

onIonViewWillEnter(() => {
  fetchPending();
});
</script>

<style scoped>
.payment-item {
  --background: #ffffff;
  --border-radius: 12px;
  margin-bottom: 15px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
  --padding-start: 15px;
  --inner-padding-end: 15px;
  --padding-top: 10px;
  --padding-bottom: 10px;
}
</style>
