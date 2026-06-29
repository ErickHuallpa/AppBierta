<template>
  <ion-page>
    <ion-header>
      <ion-toolbar color="primary">
        <ion-buttons slot="start">
          <ion-back-button default-href="/admin/dashboard"></ion-back-button>
        </ion-buttons>
        <ion-title>Control de Vencimientos</ion-title>
      </ion-toolbar>
    </ion-header>
    <ion-content class="ion-padding">
      <ion-card v-if="batches.length === 0">
        <ion-card-content class="ion-text-center">
          <p>No hay lotes próximos a vencer en las siguientes 4 semanas.</p>
        </ion-card-content>
      </ion-card>

      <ion-card v-for="batch in batches" :key="batch.id" :color="batch.status === 'promotion' ? 'warning' : ''">
        <ion-card-header>
          <ion-card-title>{{ batch.product?.name || 'Producto' }} (Lote #{{ batch.id }})</ion-card-title>
          <ion-card-subtitle>
            Vence: {{ new Date(batch.expiry_date).toLocaleDateString() }} 
            <ion-badge color="danger" class="ion-margin-start">Próximo a Vencer</ion-badge>
          </ion-card-subtitle>
        </ion-card-header>
        <ion-card-content>
          <p><strong>Stock Restante:</strong> {{ batch.quantity_current }} unidades</p>
          <p><strong>Estado:</strong> {{ batch.status === 'promotion' ? 'En Promoción' : 'Normal' }}</p>
          <p v-if="batch.status === 'promotion'"><strong>Precio Rebajado:</strong> Bs. {{ batch.discount_price }}</p>
          
          <div class="ion-margin-top action-buttons">
            <ion-button v-if="batch.status !== 'promotion'" size="small" color="warning" @click="openDiscountModal(batch)">
              Poner en Promoción
            </ion-button>
            <ion-button size="small" color="danger" @click="markAsExpired(batch.id)">
              Dar de Baja (Caducado)
            </ion-button>
          </div>
        </ion-card-content>
      </ion-card>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { 
  IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton,
  IonCard, IonCardHeader, IonCardTitle, IonCardSubtitle, IonCardContent, IonBadge, IonButton,
  alertController, toastController
} from '@ionic/vue';
import axios from 'axios';

const batches = ref<any[]>([]);

const loadBatches = async () => {
  try {
    const res = await axios.get('/api/batches/expiring');
    batches.value = res.data;
  } catch (e) {
    console.error(e);
  }
};

onMounted(() => {
  loadBatches();
});

const markAsExpired = async (id: number) => {
  const alert = await alertController.create({
    header: 'Confirmar Baja',
    message: '¿Estás seguro de dar de baja este lote? El stock restante se restará del inventario.',
    buttons: [
      { text: 'Cancelar', role: 'cancel' },
      { 
        text: 'Dar de Baja', 
        handler: async () => {
          try {
            await axios.put(`/api/batches/${id}/status`, { status: 'expired' });
            loadBatches();
            const toast = await toastController.create({ message: 'Lote dado de baja', duration: 2000, color: 'success' });
            toast.present();
          } catch (e) {
            console.error(e);
          }
        }
      }
    ]
  });
  await alert.present();
};

const openDiscountModal = async (batch: any) => {
  const alert = await alertController.create({
    header: 'Aplicar Promoción',
    message: 'Ingresa el nuevo precio rebajado para este lote próximo a vencer:',
    inputs: [
      {
        name: 'price',
        type: 'number',
        placeholder: 'Ej: 10.50',
        min: 0
      }
    ],
    buttons: [
      { text: 'Cancelar', role: 'cancel' },
      { 
        text: 'Guardar', 
        handler: async (data) => {
          if (!data.price) return false;
          try {
            await axios.put(`/api/batches/${batch.id}/status`, { 
              status: 'promotion', 
              discount_price: data.price 
            });
            loadBatches();
            const toast = await toastController.create({ message: 'Promoción aplicada', duration: 2000, color: 'success' });
            toast.present();
          } catch (e) {
            console.error(e);
          }
        }
      }
    ]
  });
  await alert.present();
};
</script>

<style scoped>
.action-buttons {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}
</style>
