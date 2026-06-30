<template>
  <ion-page>
    <ion-tabs>
      <ion-router-outlet></ion-router-outlet>
      
      <ion-tab-bar slot="bottom" class="custom-tab-bar">
        
        <!-- Cliente (Universal) -->
        <ion-tab-button tab="home" href="/tabs/home" class="custom-tab-btn">
          <ion-icon :icon="homeOutline" />
          <ion-label>Inicio</ion-label>
        </ion-tab-button>
        
        <ion-tab-button tab="cart" href="/tabs/cart" class="custom-tab-btn">
          <ion-icon :icon="cartOutline" />
          <ion-label>Carrito</ion-label>
        </ion-tab-button>

        <ion-tab-button tab="orders" href="/tabs/orders" class="custom-tab-btn">
          <ion-icon :icon="receiptOutline" />
          <ion-label>Mis Pedidos</ion-label>
        </ion-tab-button>

        <ion-tab-button tab="locations" href="/tabs/locations" class="custom-tab-btn">
          <ion-icon :icon="locationOutline" />
          <ion-label>Ubicaciones</ion-label>
          <span v-if="hasNoLocations" class="notification-dot"></span>
        </ion-tab-button>

        <!-- Todos (Perfil) -->
        <ion-tab-button tab="profile" href="/tabs/profile" class="custom-tab-btn">
          <ion-icon :icon="personOutline" />
          <ion-label>Mi perfil</ion-label>
        </ion-tab-button>
        
      </ion-tab-bar>
    </ion-tabs>
  </ion-page>
</template>

<script setup lang="ts">
import { IonPage, IonTabs, IonRouterOutlet, IonTabBar, IonTabButton, IonLabel, IonIcon, IonBadge, toastController } from '@ionic/vue';
import { homeOutline, cartOutline, locationOutline, bicycleOutline, cubeOutline, cashOutline, peopleOutline, personOutline, receiptOutline, gridOutline, pieChartOutline } from 'ionicons/icons';
import { authState } from '../store/auth';
import { onMounted, onUnmounted, ref } from 'vue';
import axios from 'axios';

let pollInterval: any = null;
const lastStatuses = ref<any>({});
const hasNoLocations = ref(false);

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

const checkLocations = async () => {
  try {
    const res = await axios.get('/api/locations');
    hasNoLocations.value = res.data.length === 0;
  } catch (e) { }
};

const checkOrderUpdates = async () => {
  if (!authState.isClient || !authState.token) return;
  try {
    await checkLocations();
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

    checkLocations();

    // Poll every 15 seconds
    pollInterval = setInterval(checkOrderUpdates, 15000);
    
    window.addEventListener('locationsUpdated', checkLocations);
  }
});

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval);
  window.removeEventListener('locationsUpdated', checkLocations);
});
</script>

<style scoped>
.custom-tab-bar {
  --background: var(--ion-background-color, #ffffff);
  --border: none;
  box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.05);
  border-top-left-radius: 20px;
  border-top-right-radius: 20px;
  overflow: visible;
  height: 65px;
  padding-bottom: 5px;
}

.custom-tab-btn {
  --color: var(--ion-color-medium, #888888);
  --color-selected: var(--ion-color-primary, #04644c);
}

.custom-tab-btn ion-icon {
  font-size: 1.5rem;
  margin-bottom: 4px;
}

.custom-tab-btn ion-label {
  font-weight: 600;
  font-size: 0.7rem;
}

.notification-dot {
  position: absolute;
  top: 8px;
  right: calc(50% - 16px);
  width: 10px;
  height: 10px;
  background-color: var(--ion-color-danger, #eb445a);
  border-radius: 50%;
  z-index: 10;
}
</style>
