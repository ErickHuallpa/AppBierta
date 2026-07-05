<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar style="--background: #04644c; color: #ffffff;">
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/profile" color="light" text=""></ion-back-button>
        </ion-buttons>
        <ion-title class="ion-text-center" style="font-weight: 600;">Mis Pedidos</ion-title>
      </ion-toolbar>
    </ion-header>
    
    <ion-content style="--background: var(--ion-background-color, #f7f9fc);">
      <ion-refresher slot="fixed" @ionRefresh="handleRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

      <div v-if="loading" class="ion-text-center ion-padding" style="margin-top: 50px;">
        <ion-spinner name="crescent"></ion-spinner>
      </div>
      
      <div v-else-if="orders.length === 0" class="empty-state">
        <ion-icon :icon="receiptOutline" class="empty-icon"></ion-icon>
        <p>Aún no tienes pedidos.</p>
        <ion-button router-link="/tabs/home" fill="clear">Ver Catálogo</ion-button>
      </div>

      <div v-else class="orders-container">
        <div class="order-card" v-for="order in orders" :key="order.id" @click="openDetails(order)">
          <div class="order-header">
            <span class="order-id">Pedido #{{ order.id }}</span>
            <span class="order-date">{{ formatDate(order.created_at) }}</span>
          </div>
          <div class="order-preview" v-if="order.items && order.items.length > 0">
            <p v-for="item in order.items" :key="item.id" class="preview-text">
              {{ item.quantity }}x {{ item.product?.name || 'Producto eliminado' }}
            </p>
          </div>
          <div class="order-body">
            <div class="order-total-info">
              <div class="order-subtotal">Subtotal: Bs. {{ order.total_amount }}</div>
              <div v-if="order.delivery_cost > 0" class="order-delivery">+ Bs. {{ order.delivery_cost }} Delivery</div>
              <div class="order-total">Total: Bs. {{ parseFloat(order.total_amount) + parseFloat(order.delivery_cost) }}</div>
            </div>
            <div class="order-status">
              <ion-badge :color="getStatusColor(order.status)">
                {{ getStatusText(order.status) }}
              </ion-badge>
            </div>
          </div>
        </div>
      </div>

      <!-- Detalle Modal -->
      <ion-modal :is-open="selectedOrder !== null" @didDismiss="selectedOrder = null">
        <ion-header class="ion-no-border">
          <ion-toolbar style="--background: #04644c; color: #ffffff;">
            <ion-title>Detalle #{{ selectedOrder?.id }}</ion-title>
            <ion-buttons slot="end">
              <ion-button @click="selectedOrder = null" color="light">Cerrar</ion-button>
            </ion-buttons>
          </ion-toolbar>
        </ion-header>
        <ion-content class="ion-padding" style="--background: #f7f9fc;">
          <div v-if="selectedOrder" class="details-card">
            
            <div class="detail-section">
              <h3>Estado Actual</h3>
              <ion-badge :color="getStatusColor(selectedOrder.status)">
                {{ getStatusText(selectedOrder.status) }}
              </ion-badge>
              <p v-if="selectedOrder.status === 'in_transit'" style="color:var(--ion-color-medium); font-size:0.9rem;">
                Repartidor asignado: {{ selectedOrder.delivery_user?.email || 'Pendiente' }}
              </p>
            </div>

            <!-- Calificación de entrega -->
            <div class="detail-section" v-if="selectedOrder.status === 'delivered'">
              <h3>Calificación de Entrega</h3>
              <div v-if="selectedOrder.rating" style="display: flex; gap: 5px; color: #f1c40f;">
                <ion-icon v-for="i in 5" :key="i" :icon="i <= selectedOrder.rating ? star : starOutline" style="font-size: 1.5rem;"></ion-icon>
              </div>
              <div v-else>
                <p style="font-size: 0.9rem; margin-top:0;">¿Cómo fue tu entrega? ¡Califícanos!</p>
                <div style="display: flex; gap: 10px; color: #f1c40f; margin-bottom: 10px;">
                  <ion-icon v-for="i in 5" :key="i" :icon="tempRating >= i ? star : starOutline" @click="tempRating = i" style="font-size: 2rem; cursor: pointer;"></ion-icon>
                </div>
                <ion-button v-if="tempRating > 0" expand="block" @click="submitRating(selectedOrder.id)">
                  Enviar Calificación
                </ion-button>
              </div>
            </div>

            <div class="detail-section">
              <h3>Resumen de Pago</h3>
              <p>Subtotal: Bs. {{ selectedOrder.total_amount }}</p>
              <p>Envío: Bs. {{ selectedOrder.delivery_cost }}</p>
              <h2 style="color:#04644c; font-weight:bold; margin-top:5px;">Total: Bs. {{ selectedOrder.total_amount + selectedOrder.delivery_cost }}</h2>
              <p style="font-size: 0.85rem; color:var(--ion-color-medium)">Método: {{ selectedOrder.payment_method === 'cash' ? 'Efectivo' : 'Transferencia QR' }}</p>
            </div>

            <div class="detail-section" v-if="selectedOrder.nit || selectedOrder.razon_social">
              <h3>Datos de Facturación</h3>
              <p v-if="selectedOrder.razon_social"><strong>Razón Social:</strong> {{ selectedOrder.razon_social }}</p>
              <p v-if="selectedOrder.nit"><strong>NIT/CI:</strong> {{ selectedOrder.nit }}</p>
            </div>

            <div class="detail-section">
              <h3>Productos</h3>
              <ion-list lines="none" class="items-list">
                <ion-item v-for="item in selectedOrder.items" :key="item.id">
                  <ion-label>
                    <h3>{{ item.product?.name || 'Producto eliminado' }}</h3>
                    <p>{{ item.quantity }} x Bs. {{ item.price_at_time }}</p>
                  </ion-label>
                  <span slot="end" style="font-weight:bold;">Bs. {{ item.quantity * item.price_at_time }}</span>
                </ion-item>
              </ion-list>
            </div>

          </div>
        </ion-content>
      </ion-modal>

    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonSpinner, IonIcon, IonButton, IonBadge, IonModal, IonButtons, IonBackButton, IonList, IonItem, IonLabel, IonRefresher, IonRefresherContent, toastController } from '@ionic/vue';
import { receiptOutline, star, starOutline } from 'ionicons/icons';
import axios from 'axios';

const orders = ref<any[]>([]);
const loading = ref(true);
const selectedOrder = ref<any>(null);
const tempRating = ref(0);

onMounted(async () => {
  fetchOrders();
  window.addEventListener('ordersUpdated', fetchOrders);
});

onUnmounted(() => {
  window.removeEventListener('ordersUpdated', fetchOrders);
});

const fetchOrders = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/my-orders');
    orders.value = res.data;
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const handleRefresh = async (event: any) => {
  try {
    const res = await axios.get('/api/my-orders');
    orders.value = res.data;
  } catch (e) {
    console.error(e);
  } finally {
    event.target.complete();
  }
};

const openDetails = (order: any) => {
  selectedOrder.value = order;
  tempRating.value = 0;
};

const submitRating = async (orderId: number) => {
  try {
    await axios.post(`/api/orders/${orderId}/rate`, { rating: tempRating.value });
    const t = await toastController.create({ message: 'Gracias por tu calificación', duration: 2000, color: 'success' });
    await t.present();
    selectedOrder.value.rating = tempRating.value;
    // Update in list
    const o = orders.value.find(x => x.id === orderId);
    if (o) o.rating = tempRating.value;
  } catch (e: any) {
    const t = await toastController.create({ message: e.response?.data?.error || 'Error', duration: 2000, color: 'danger' });
    await t.present();
  }
};

const formatDate = (dateStr: string) => {
  const d = new Date(dateStr);
  return d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

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

const getStatusColor = (status: string) => {
  const map: any = {
    'pending_payment': 'warning',
    'pending_delivery_assignment': 'tertiary',
    'ready_for_pickup': 'primary',
    'in_transit': 'secondary',
    'delivered': 'success',
    'cancelled': 'danger'
  };
  return map[status] || 'medium';
};
</script>

<style scoped>
ion-content {
  --background: #f7f9fc;
}
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  margin-top: 60px;
  color: var(--ion-color-medium);
}

.empty-icon {
  font-size: 80px;
  margin-bottom: 20px;
}

.orders-container {
  padding: 15px;
}

.order-card {
  background: var(--app-card-bg, #ffffff);
  border-radius: 12px;
  padding: 15px;
  margin-bottom: 15px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
}

.order-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 10px;
  font-size: 0.9rem;
  color: var(--ion-color-medium);
}

.order-id {
  font-weight: 700;
  font-size: 1.05rem;
  color: var(--ion-text-color, #333);
}

.order-preview {
  margin-bottom: 15px;
  border-bottom: 1px solid var(--ion-color-step-100, #eee);
  padding-bottom: 10px;
}

.preview-text {
  margin: 2px 0;
  font-size: 0.9rem;
  color: var(--ion-color-medium);
}

.order-body {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.order-total-info {
  display: flex;
  flex-direction: column;
}

.order-subtotal, .order-delivery {
  font-size: 0.85rem;
  color: var(--ion-color-step-500, #666);
}

.order-total {
  font-weight: 700;
  font-size: 1.1rem;
  color: var(--ion-color-primary, #04644c);
  margin-top: 3px;
}

.details-card {
  background: var(--ion-card-background, #fff);
  border-radius: 12px;
  padding: 15px;
}

.detail-section {
  margin-bottom: 20px;
}

.detail-section h3 {
  font-size: 1rem;
  font-weight: 600;
  color: var(--ion-text-color, #333);
  margin-top: 0;
  margin-bottom: 10px;
}

.items-list ion-item {
  --padding-start: 0;
  --inner-padding-end: 0;
  --background: transparent;
  color: var(--ion-text-color, #000);
}
</style>
