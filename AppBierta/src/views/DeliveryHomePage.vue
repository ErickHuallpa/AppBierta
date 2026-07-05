<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar style="--background: #04644c; color: #ffffff;">
        <ion-title style="font-weight: 600;">Zona Delivery</ion-title>
      </ion-toolbar>
    </ion-header>
    <ion-content class="ion-padding" style="--background: var(--ion-background-color, #f7f9fc);">
      <ion-refresher slot="fixed" @ionRefresh="handleRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>
      <div class="ion-padding">
        <ion-segment v-model="segment">
          <ion-segment-button value="available">
            <ion-label>DISPONIBLES</ion-label>
          </ion-segment-button>
          <ion-segment-button value="mine">
            <ion-label>MIS ENTREGAS</ion-label>
          </ion-segment-button>
        </ion-segment>
      </div>

      <div v-if="segment === 'available'">
        <div v-if="loading" class="ion-text-center ion-padding" style="margin-top: 50px;">
          <ion-spinner name="crescent"></ion-spinner>
        </div>
        <div v-else>
          <ion-list class="items-list" v-if="availableOrders.length > 0">
            <ion-item v-for="order in availableOrders" :key="order.id" class="ion-margin-bottom order-item" button @click="openDetails(order)" lines="none">
              <ion-label>
                <h2 style="font-weight: 600;">Pedido #{{ order.id }}</h2>
                <p v-if="order.distance" style="color: var(--ion-color-primary); font-weight: 600;">
                  <ion-icon :icon="navigateOutline"></ion-icon> A {{ order.distance }} km de ti
                </p>
                <p>Método: {{ order.payment_method === 'cash' ? 'Efectivo' : 'Transferencia QR' }}</p>
                <p style="font-weight: bold; color: var(--ion-color-success);">Cobrar: Bs. {{ order.total_amount + order.delivery_cost }}</p>
              </ion-label>
              <ion-button slot="end" style="--background: #04644c;" @click.stop="acceptOrder(order.id)">Aceptar</ion-button>
            </ion-item>
          </ion-list>
          
          <div v-else class="ion-text-center ion-padding" style="color: var(--ion-color-medium); margin-top: 50px;">
            <ion-icon :icon="mapOutline" style="font-size: 64px;"></ion-icon>
            <p>No hay pedidos disponibles para asignar</p>
          </div>
        </div>
      </div>

      <div v-if="segment === 'mine'">
        <div v-if="loading" class="ion-text-center ion-padding" style="margin-top: 50px;">
          <ion-spinner name="crescent"></ion-spinner>
        </div>
        <div v-else>
          <ion-list class="items-list" v-if="myOrders.length > 0">
            <ion-item v-for="order in myOrders" :key="order.id" class="ion-margin-bottom order-item" lines="none">
              <ion-label>
                <h2 style="font-weight: 600;">Pedido #{{ order.id }}</h2>
                <p>Destino: {{ order.location?.name }} - {{ order.location?.address }}</p>
                <p v-if="order.status === 'delivered'" style="color: #2ed573; font-weight: bold; margin-top: 5px;">Entregado</p>
                <p v-else style="color: #ff9f43; font-weight: bold; margin-top: 5px;">En Ruta</p>
              </ion-label>
              <div slot="end" style="display:flex; gap: 8px;">
                <ion-button fill="outline" @click="openDetails(order)">
                  <ion-icon slot="icon-only" :icon="listOutline"></ion-icon>
                </ion-button>
                <ion-button color="primary" @click="openRoute(order)">
                  VER RUTA
                </ion-button>
              </div>
            </ion-item>
          </ion-list>
          
          <div v-else class="ion-text-center ion-padding" style="color: var(--ion-color-medium); margin-top: 50px;">
            <ion-icon :icon="checkmarkCircleOutline" style="font-size: 64px;"></ion-icon>
            <p>No tienes entregas pendientes</p>
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
    <ion-content class="ion-padding" style="--background: var(--ion-background-color, #f7f9fc);">
      <div v-if="selectedOrder" class="details-card">
            
            <div class="detail-section">
              <h3>Datos del Cliente</h3>
              <p><strong>Nombre:</strong> {{ selectedOrder.user?.persona?.nombre || selectedOrder.user?.name || 'Cliente' }} {{ selectedOrder.user?.persona?.apellidos || '' }}</p>
              <p><strong>Teléfono:</strong> {{ selectedOrder.user?.persona?.telefono || 'No registrado' }}</p>
              <p><strong>Dirección:</strong> {{ selectedOrder.location?.name }} - {{ selectedOrder.location?.address }}</p>
              <p v-if="selectedOrder.location?.reference"><strong>Ref:</strong> {{ selectedOrder.location?.reference }}</p>
            </div>

            <div class="detail-section">
              <h3>Resumen de Pago</h3>
              <p>Subtotal: Bs. {{ selectedOrder.total_amount }}</p>
              <p>Envío: Bs. {{ selectedOrder.delivery_cost }}</p>
              <h2 style="color:#ff4757; font-weight:bold; margin-top:5px;">Total: Bs. {{ selectedOrder.total_amount + selectedOrder.delivery_cost }}</h2>
              <p style="font-size: 0.85rem; color:var(--ion-color-medium)">Método: {{ selectedOrder.payment_method === 'cash' ? 'Efectivo' : 'Transferencia QR' }}</p>
            </div>

            <div class="detail-section">
              <h3>Productos a Entregar</h3>
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

            <div style="margin-top: 20px; display: flex; justify-content: center; gap: 10px;">
              <ion-button color="success" v-if="selectedOrder.status !== 'delivered'" @click="markDelivered(selectedOrder.id)">
                <ion-icon :icon="checkmarkCircleOutline" slot="start"></ion-icon> Marcar Entregado
              </ion-button>
              <ion-button color="tertiary" @click="generateReceipt(selectedOrder)">
                <ion-icon :icon="printOutline" slot="start"></ion-icon> Comprobante
              </ion-button>
            </div>

          </div>
        </ion-content>
      </ion-modal>

    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { IonPage, IonHeader, IonToolbar, IonButtons, IonTitle, IonContent, IonSegment, IonSegmentButton, IonLabel, IonList, IonItem, IonButton, onIonViewWillEnter, toastController, IonModal, IonIcon, IonRefresher, IonRefresherContent, IonSpinner } from '@ionic/vue';
import { checkmarkCircleOutline, mapOutline, listOutline, navigateOutline, printOutline } from 'ionicons/icons';
import { ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const segment = ref('available');
const availableOrders = ref<any[]>([]);
const myOrders = ref<any[]>([]);
const selectedOrder = ref<any>(null);
const loading = ref(true);

const deg2rad = (deg: number) => deg * (Math.PI / 180);
const getDistanceFromLatLonInKm = (lat1: number, lon1: number, lat2: number, lon2: number) => {
  const R = 6371; 
  const dLat = deg2rad(lat2 - lat1);
  const dLon = deg2rad(lon2 - lon1);
  const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * 
            Math.sin(dLon/2) * Math.sin(dLon/2); 
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)); 
  return R * c; 
};

const calculateDistances = (orders: any[]) => {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition((pos) => {
      const userLat = pos.coords.latitude;
      const userLng = pos.coords.longitude;
      
      orders.forEach(order => {
        if (order.location?.latitude && order.location?.longitude) {
          const destLat = parseFloat(order.location.latitude);
          const destLng = parseFloat(order.location.longitude);
          const dist = getDistanceFromLatLonInKm(userLat, userLng, destLat, destLng);
          order.distance = dist.toFixed(1);
        }
      });
    });
  }
};

const fetchAvailable = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/delivery/orders/available');
    availableOrders.value = res.data;
    calculateDistances(availableOrders.value);
  } catch(e) {
  } finally {
    loading.value = false;
  }
};

const fetchMine = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/delivery/my-orders');
    myOrders.value = res.data;
  } catch(e) {
  } finally {
    loading.value = false;
  }
};

const openDetails = async (order: any) => {
  selectedOrder.value = order;
};

const openRoute = async (order: any) => {
  router.push('/delivery/route/' + order.id);
};

const acceptOrder = async (id: number) => {
  try {
    await axios.post(`/api/delivery/orders/${id}/accept`);
    const toast = await toastController.create({ message: 'Pedido asignado a ti', duration: 2000 });
    await toast.present();
    fetchAvailable();
    fetchMine();
  } catch(e) {}
};

const markDelivered = async (id: number) => {
  try {
    await axios.post(`/api/delivery/orders/${id}/delivered`);
    const toast = await toastController.create({ message: 'Pedido entregado exitosamente', duration: 2000, color: 'success' });
    await toast.present();
    selectedOrder.value.status = 'delivered';
    fetchMine();
  } catch(e) {}
};

const generateReceipt = (order: any) => {
  const receiptHTML = `
    <html>
      <head>
        <title>Comprobante de Entrega - Pedido #${order.id}</title>
        <style>
          body { font-family: monospace; padding: 20px; }
          .center { text-align: center; }
          .bold { font-weight: bold; }
          .mt { margin-top: 20px; }
          table { width: 100%; border-collapse: collapse; margin-top: 10px; }
          th, td { text-align: left; border-bottom: 1px dashed #ccc; padding: 5px 0; }
        </style>
      </head>
      <body>
        <h2 class="center">COMPROBANTE DE ENTREGA</h2>
        <p class="center">Distribuidora de Cerveza</p>
        <div class="mt">
          <p><span class="bold">Pedido:</span> #${order.id}</p>
          <p><span class="bold">Fecha:</span> ${new Date(order.updated_at).toLocaleString()}</p>
          <p><span class="bold">Cliente:</span> ${order.user?.persona?.nombre || ''} ${order.user?.persona?.apellidos || ''}</p>
          <p><span class="bold">CI/NIT:</span> ${order.nit || order.user?.persona?.ci_nit || 'S/N'}</p>
          <p><span class="bold">Dirección:</span> ${order.location?.name} - ${order.location?.address}</p>
        </div>
        <table>
          <thead>
            <tr>
              <th>Cant</th>
              <th>Producto</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            ${order.items.map((i: any) => `
              <tr>
                <td>${i.quantity}</td>
                <td>${i.product?.name}</td>
                <td>Bs. ${i.quantity * i.price_at_time}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>
        <div class="mt">
          <p><span class="bold">Envío:</span> Bs. ${order.delivery_cost}</p>
          <h3 class="bold">Total Pagado: Bs. ${order.total_amount + order.delivery_cost}</h3>
          <p>Método: ${order.payment_method === 'cash' ? 'Efectivo' : 'Transferencia QR'}</p>
        </div>
        <div class="mt center" style="margin-top: 50px;">
          <p>_______________________</p>
          <p>Firma de Recepción</p>
        </div>
      </body>
    </html>
  `;
  const win = window.open('', '_blank');
  if(win) {
    win.document.write(receiptHTML);
    win.document.close();
    setTimeout(() => {
      win.print();
    }, 500);
  }
};

watch(segment, (newVal) => {
  if (newVal === 'available') fetchAvailable();
  if (newVal === 'mine') fetchMine();
});

onIonViewWillEnter(() => {
  fetchAvailable();
  fetchMine();
});

const handleRefresh = async (event: any) => {
  if (segment.value === 'available') {
    await fetchAvailable();
  } else {
    await fetchMine();
  }
  event.target.complete();
};
</script>

<style scoped>
.items-list {
  background: transparent;
}
.order-item {
  --border-radius: 12px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
  --padding-start: 15px;
  --inner-padding-end: 15px;
  --padding-top: 10px;
  --padding-bottom: 10px;
}
</style>
