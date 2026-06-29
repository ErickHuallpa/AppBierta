<template>
  <ion-page>
    <ion-tabs>
      <ion-router-outlet></ion-router-outlet>
      
      <ion-tab-bar slot="bottom">
        
        <!-- Cliente (Universal) -->
        <ion-tab-button tab="home" href="/tabs/home">
          <ion-icon :icon="homeOutline" />
          <ion-label>Inicio</ion-label>
        </ion-tab-button>
        
        <ion-tab-button tab="cart" href="/tabs/cart">
          <ion-icon :icon="cartOutline" />
          <ion-label>Carrito</ion-label>
        </ion-tab-button>

        <ion-tab-button tab="orders" href="/tabs/orders">
          <ion-icon :icon="receiptOutline" />
          <ion-label>Mis Pedidos</ion-label>
        </ion-tab-button>

        <ion-tab-button tab="locations" href="/tabs/locations">
          <ion-icon :icon="locationOutline" />
          <ion-label>Ubicaciones</ion-label>
        </ion-tab-button>

        <!-- Todos (Perfil) -->
        <ion-tab-button tab="profile" href="/tabs/profile">
          <ion-icon :icon="personOutline" />
          <ion-label>Mi perfil</ion-label>
        </ion-tab-button>
        
      </ion-tab-bar>
    </ion-tabs>
  </ion-page>
</template>

<script setup lang="ts">
import { IonPage, IonTabs, IonRouterOutlet, IonTabBar, IonTabButton, IonLabel, IonIcon, toastController } from '@ionic/vue';
import { homeOutline, cartOutline, locationOutline, bicycleOutline, cubeOutline, cashOutline, peopleOutline, personOutline, receiptOutline, gridOutline, pieChartOutline } from 'ionicons/icons';
import { authState } from '../store/auth';
import { onMounted, onUnmounted, ref } from 'vue';
import axios from 'axios';

let pollInterval: any = null;
const lastStatuses = ref<any>({});

const getStatusText = (status: string) => {
  const map: any = {
    'pending_payment': 'Pendiente de Pago',
    'pending_delivery_assignment': 'Buscando Repartidor',
    'ready_for_pickup': 'Listo para Recojo',
    'in_transit': 'En Camino',
    'delivered': 'Entregado',
    'cancelled': 'Cancelado'
  };
  return map[status] || status;
};

const checkOrderUpdates = async () => {
  if (!authState.isClient || !authState.token) return;
  try {
    const res = await axios.get('/api/my-orders');
    const orders = res.data;
    
    for (const order of orders) {
      const prevStatus = lastStatuses.value[order.id];
      if (prevStatus && prevStatus !== order.status) {
        // Status changed!
        const msg = `Tu pedido #${order.id} ahora está: ${getStatusText(order.status)}`;
        const t = await toastController.create({
          message: msg,
          duration: 4000,
          color: 'primary',
          position: 'top',
          icon: bicycleOutline
        });
        await t.present();
      }
      lastStatuses.value[order.id] = order.status;
    }
  } catch (e) {
    // ignore
  }
};

onMounted(() => {
  if (authState.isClient) {
    // Initial fetch to populate lastStatuses without alerting
    axios.get('/api/my-orders').then(res => {
      res.data.forEach((o: any) => lastStatuses.value[o.id] = o.status);
    }).catch(e => {});

    // Poll every 15 seconds
    pollInterval = setInterval(checkOrderUpdates, 15000);
  }
});

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval);
});
</script>
