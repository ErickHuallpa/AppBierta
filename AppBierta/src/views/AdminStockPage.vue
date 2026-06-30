<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar style="--background: #04644c; color: #ffffff;">
        <ion-buttons slot="start">
          <ion-back-button default-href="/admin/dashboard" color="light"></ion-back-button>
        </ion-buttons>
        <ion-title style="font-weight: 600;">Ingreso de Stock</ion-title>
      </ion-toolbar>
    </ion-header>
    <ion-content class="ion-padding" style="--background: #f7f9fc;">
      <ion-card class="pro-card">
        <ion-card-header>
          <ion-card-title>Registrar Nuevo Lote</ion-card-title>
        </ion-card-header>
        <ion-card-content>
          <form @submit.prevent="submitBatch">
            <ion-item class="pro-input" lines="none">
              <ion-label position="stacked">Producto</ion-label>
              <ion-select v-model="form.product_id" placeholder="Selecciona un producto">
                <ion-select-option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</ion-select-option>
              </ion-select>
            </ion-item>

            <ion-item class="pro-input" lines="none">
              <ion-label position="stacked">Proveedor (Opcional)</ion-label>
              <ion-select v-model="form.supplier_id" placeholder="Selecciona un proveedor">
                <ion-select-option :value="null">Ninguno</ion-select-option>
                <ion-select-option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</ion-select-option>
              </ion-select>
              <ion-button slot="end" fill="clear" @click="promptAddSupplier">+ Nuevo</ion-button>
            </ion-item>

            <ion-item class="pro-input" lines="none">
              <ion-label position="stacked">Cantidad (Unidades)</ion-label>
              <ion-input type="number" v-model="form.quantity" required min="1"></ion-input>
            </ion-item>

            <ion-item class="pro-input" lines="none">
              <ion-label position="stacked">Precio de Compra Unitario (Bs)</ion-label>
              <ion-input type="number" step="0.01" v-model="form.purchase_price" required min="0"></ion-input>
            </ion-item>

            <ion-item class="pro-input" lines="none">
              <ion-label position="stacked">Fecha de Consumo Preferente</ion-label>
              <ion-input type="date" v-model="form.expiry_date"></ion-input>
            </ion-item>

            <ion-button expand="block" type="submit" class="ion-margin-top" style="--background: #000; height: 50px; font-weight: bold;" :disabled="loading">
              {{ loading ? 'Guardando...' : 'Registrar Ingreso' }}
            </ion-button>
          </form>
        </ion-card-content>
      </ion-card>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { 
  IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton,
  IonCard, IonCardHeader, IonCardTitle, IonCardContent, IonItem, IonLabel, IonInput, IonSelect, IonSelectOption, IonButton,
  toastController, alertController
} from '@ionic/vue';
import axios from 'axios';

const form = ref({
  product_id: null,
  supplier_id: null,
  quantity: '',
  purchase_price: '',
  expiry_date: ''
});

const products = ref<any[]>([]);
const suppliers = ref<any[]>([]);
const loading = ref(false);

const loadData = async () => {
  try {
    const [prodRes, supRes] = await Promise.all([
      axios.get('/api/products'),
      axios.get('/api/suppliers')
    ]);
    products.value = prodRes.data;
    suppliers.value = supRes.data;
  } catch (e) {
    console.error(e);
  }
};

onMounted(() => {
  loadData();
});

const submitBatch = async () => {
  if (!form.value.product_id || !form.value.quantity || !form.value.purchase_price) return;
  loading.value = true;
  try {
    await axios.post('/api/batches', form.value);
    const toast = await toastController.create({
      message: 'Stock ingresado exitosamente',
      duration: 2000,
      color: 'success'
    });
    await toast.present();
    form.value = { product_id: null, supplier_id: null, quantity: '', purchase_price: '', expiry_date: '' };
  } catch (e: any) {
    const toast = await toastController.create({
      message: e.response?.data?.error || 'Error al guardar',
      duration: 2000,
      color: 'danger'
    });
    await toast.present();
  } finally {
    loading.value = false;
  }
};

const promptAddSupplier = async () => {
  const alert = await alertController.create({
    header: 'Nuevo Proveedor',
    inputs: [
      { name: 'name', type: 'text', placeholder: 'Nombre' },
      { name: 'contact_info', type: 'text', placeholder: 'Contacto' },
      { name: 'nit', type: 'text', placeholder: 'NIT (opcional)' }
    ],
    buttons: [
      { text: 'Cancelar', role: 'cancel' },
      {
        text: 'Guardar',
        handler: async (data) => {
          if (data.name) {
            await axios.post('/api/suppliers', data);
            await loadData();
          }
        }
      }
    ]
  });
  await alert.present();
};
</script>

<style scoped>
.pro-card {
  border-radius: 12px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
  background: #ffffff;
}
.pro-input {
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  margin-bottom: 15px;
  --padding-start: 10px;
}
</style>
