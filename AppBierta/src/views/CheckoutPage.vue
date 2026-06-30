<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar style="--background: #04644c; --color: #ffffff;">
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/cart" text="" color="light"></ion-back-button>
        </ion-buttons>
        <ion-title class="ion-text-center" style="font-weight: 600;">Checkout</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content color="light" class="ion-padding">
      
      <!-- Ubicación -->
      <div class="card-box" v-if="cart.checkout.order_type === 'delivery'">
        <div class="section-title">Ubicación de entrega</div>
        <ion-item lines="none" class="no-bg-item">
          <ion-select v-model="cart.checkout.location_id" placeholder="Seleccione una ubicación" interface="action-sheet" style="width:100%;">
            <ion-select-option v-for="loc in locations" :key="loc.id" :value="loc.id">
              {{ loc.name }} - {{ loc.address }}
            </ion-select-option>
          </ion-select>
        </ion-item>
        <p v-if="locations.length === 0" style="color:#ff4757; font-size: 0.9rem; margin-top:0;">
          No tienes ubicaciones. <router-link to="/locations/add">Agrega una aquí.</router-link>
        </p>
      </div>

      <!-- Datos de Facturación -->
      <div class="card-box">
        <div class="section-title">Datos de Facturación (Opcional)</div>
        <ion-item lines="none" class="no-bg-item input-item">
          <ion-input label="NIT / CI" label-placement="floating" v-model="cart.checkout.nit" type="text" placeholder="Ej. 1234567"></ion-input>
        </ion-item>
        <ion-item lines="none" class="no-bg-item input-item" style="margin-top: 10px;">
          <ion-input label="Razón Social" label-placement="floating" v-model="cart.checkout.razon_social" type="text" placeholder="Ej. Juan Perez"></ion-input>
        </ion-item>
      </div>

      <!-- Pago QR -->
      <div class="card-box" v-if="cart.checkout.payment_method === 'qr'">
        <div class="section-title">Pago con QR</div>
        <p style="font-size: 0.9rem; color: #666;">
          Realiza la transferencia por <b>Bs. {{ finalTotal.toFixed(2) }}</b> a la siguiente cuenta y sube el comprobante.
        </p>
        <div style="text-align:center; margin: 15px 0;">
          <img src="/assets/qr.png" alt="QR" style="width: 200px; height: 200px; object-fit: contain; border-radius: 10px;" />
        </div>
        <ion-item lines="none" class="no-bg-item">
          <input type="file" @change="onFileSelected" accept="image/*" />
        </ion-item>
      </div>

      <!-- Pago con Puntos -->
      <div class="card-box" v-if="cart.checkout.payment_method === 'points'">
        <div class="section-title">Pago con Puntos</div>
        <p style="font-size: 0.9rem; color: #666;">
          Esta compra se realizará canjeando tus puntos acumulados. Se descontarán los puntos correspondientes de tu cuenta al enviar el pedido.
        </p>
      </div>

      <div style="height: 100px;"></div>
    </ion-content>

    <!-- Bottom Action Bar -->
    <div class="floating-bottom-bar">
      <button class="checkout-button" @click="confirmOrder" :disabled="isSubmitting">
        <span class="btn-text" style="width: 100%; text-align: center;">
          <ion-spinner name="crescent" v-if="isSubmitting" style="margin-right:10px; vertical-align:middle;"></ion-spinner>
          {{ isSubmitting ? 'Procesando...' : 'Enviar Pedido' }}
        </span>
      </button>
    </div>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonItem, IonSelect, IonSelectOption, IonInput, IonSpinner, alertController, toastController } from '@ionic/vue';
import { cartState } from '../store/cart';
import { useRouter } from 'vue-router';
import axios from 'axios';

const cart = cartState;
const router = useRouter();
const locations = ref<any[]>([]);
const isSubmitting = ref(false);
const qrReceipt = ref<File | null>(null);

const finalTotal = computed(() => {
  return cart.totalPrice; // El backend sumará el envío si corresponde
});

onMounted(async () => {
  if (cart.items.length === 0) {
    router.replace('/tabs/cart');
    return;
  }
  try {
    const res = await axios.get('/api/locations');
    locations.value = res.data;
    if (locations.value.length > 0 && !cart.checkout.location_id) {
      const defaultLoc = locations.value.find((l: any) => l.is_default) || locations.value[0];
      cart.checkout.location_id = defaultLoc.id;
    }
  } catch (error) {
    console.error(error);
  }

  // Pre-fill billing info from user profile
  const user = (await import('../store/auth')).authState.user;
  if (user?.persona) {
    if (!cart.checkout.nit) cart.checkout.nit = user.persona.ci_nit || '';
    if (!cart.checkout.razon_social) cart.checkout.razon_social = user.persona.razon_social || '';
  }
});

const onFileSelected = (event: any) => {
  const file = event.target.files[0];
  if (file) {
    qrReceipt.value = file;
  }
};

const showToast = async (msg: string, color: string) => {
  const t = await toastController.create({ message: msg, duration: 3000, color });
  await t.present();
};

const confirmOrder = async () => {
  if (cart.checkout.order_type === 'delivery' && !cart.checkout.location_id) {
    return showToast('Por favor seleccione una ubicación de entrega.', 'warning');
  }

  if (cart.checkout.payment_method === 'qr' && !qrReceipt.value) {
    return showToast('Por favor suba el comprobante de pago.', 'warning');
  }

  isSubmitting.value = true;
  
  const formData = new FormData();
  formData.append('order_type', cart.checkout.order_type);
  formData.append('payment_method', cart.checkout.payment_method);
  formData.append('notes', cart.checkout.notes || '');
  formData.append('nit', cart.checkout.nit || '');
  formData.append('razon_social', cart.checkout.razon_social || '');

  if (cart.checkout.order_type === 'delivery' && cart.checkout.location_id) {
    formData.append('location_id', cart.checkout.location_id.toString());
  }

  if (cart.checkout.payment_method === 'qr' && qrReceipt.value) {
    formData.append('payment_receipt', qrReceipt.value);
  }

  cart.items.forEach((item, index) => {
    formData.append(`items[${index}][product_id]`, item.product_id.toString());
    formData.append(`items[${index}][quantity]`, item.quantity.toString());
    if (item.is_reward) {
      formData.append(`items[${index}][is_reward]`, '1');
    }
  });

  try {
    const res = await axios.post('/api/orders/confirm', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });

    if (res.data.requires_confirmation) {
      isSubmitting.value = false;
      const alert = await alertController.create({
        header: 'Costo Adicional',
        message: res.data.message,
        buttons: [
          { text: 'Cancelar', role: 'cancel' },
          { 
            text: 'Aceptar Recargo', 
            handler: async () => {
              formData.append('accept_delivery_cost', '1');
              isSubmitting.value = true;
              try {
                await axios.post('/api/orders/confirm', formData, {
                  headers: { 'Content-Type': 'multipart/form-data' }
                });
                orderSuccess();
              } catch (e: any) {
                isSubmitting.value = false;
                showToast(e.response?.data?.error || 'Error al confirmar', 'danger');
              }
            }
          }
        ]
      });
      await alert.present();
      return;
    }

    orderSuccess();
  } catch (error: any) {
    isSubmitting.value = false;
    showToast(error.response?.data?.error || 'Ocurrió un error en la confirmación.', 'danger');
  }
};

const orderSuccess = async () => {
  isSubmitting.value = false;
  await showToast('¡Pedido enviado exitosamente!', 'success');
  cart.clearCart();
  router.replace('/tabs/home');
};
</script>

<style scoped>
ion-content {
  --background: var(--ion-background-color, #f7f9fc);
}

.card-box {
  background: var(--ion-card-background, #ffffff);
  border-radius: 12px;
  padding: 15px;
  margin-bottom: 15px;
  box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.section-title {
  font-weight: 600;
  color: var(--ion-text-color, #333);
  margin-bottom: 10px;
  font-size: 0.95rem;
}

.no-bg-item {
  --background: transparent;
  --padding-start: 0;
  --inner-padding-end: 0;
}

.input-item {
  border: 1px solid var(--ion-color-step-200, #e0e0e0);
  border-radius: 8px;
  --padding-start: 10px;
}

/* Floating Bottom Bar */
.floating-bottom-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  width: 100%;
  padding: 15px;
  background: var(--ion-background-color, #f7f9fc);
  z-index: 1000;
}

.checkout-button {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
  background: #000000;
  color: #fff;
  border: none;
  border-radius: 30px;
  padding: 14px 20px;
  font-size: 1.1rem;
  font-weight: 600;
  box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.checkout-button:disabled {
  opacity: 0.7;
}
</style>
