<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar style="--background: var(--ion-background-color, #121212);">
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/home" text=""></ion-back-button>
        </ion-buttons>
        <ion-title class="ion-text-center" style="font-weight: 600;">Carrito</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content class="cart-content" color="light">
      <div v-if="cart.items.length === 0" class="empty-state">
        <ion-icon :icon="cartOutline" class="empty-icon"></ion-icon>
        <p>Tu carrito está vacío</p>
        <ion-button router-link="/tabs/home" shape="round" color="dark">Explorar Catálogo</ion-button>
      </div>

      <div v-else class="cart-container">
        <!-- Lista de Productos -->
        <div class="card-box" v-for="item in cart.items" :key="item.product_id">
          <div class="product-header">
            <div>
              <span class="product-name">{{ item.name }}</span>
              <span v-if="item.is_reward" class="reward-badge">Recompensa</span>
            </div>
            <ion-icon :icon="trashOutline" class="delete-icon" @click="cart.removeProduct(item.product_id, item.is_reward)"></ion-icon>
          </div>
          <div class="product-body">
            <img :src="item.image_url" style="width: 60px; height: 60px; object-fit: cover; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.2);" />
            <div class="product-controls-wrapper">
              <div class="qty-control">
                <button class="qty-btn" @click="cart.removeItem(item.product_id, item.is_reward)">
                  <ion-icon :icon="removeOutline"></ion-icon>
                </button>
                <span class="qty-value">{{ item.quantity }}</span>
                <button class="qty-btn add-btn" @click="cart.addItem({id: item.product_id, name: item.name, precio_venta: item.price, points_cost: item.points_cost, points_reward: item.points_reward}, item.is_reward)">
                  <ion-icon :icon="addOutline"></ion-icon>
                </button>
              </div>
            </div>
            <div class="product-price">
              <span v-if="item.is_reward" style="color: #f1c40f;">{{ item.points_cost * item.quantity }} pts</span>
              <span v-else>Bs. {{ (item.price * item.quantity).toFixed(2) }}</span>
            </div>
          </div>
        </div>

        <!-- Recomendaciones -->
        <div class="section-title">Agrega alguna recomendación para el local</div>
        <div class="card-box">
          <ion-textarea 
            v-model="cart.checkout.notes"
            placeholder="" 
            auto-grow 
            :rows="3" 
            class="recommendation-input">
          </ion-textarea>
        </div>

        <div class="card-box" style="margin-top: 15px;">
          <div class="section-subtitle">Selecciona tu tipo de compra</div>
          <ion-radio-group v-model="cart.checkout.order_type">
            <ion-item lines="none" class="no-bg-item radio-item">
              <ion-radio slot="start" value="delivery" color="danger"></ion-radio>
              <ion-label>Delivery</ion-label>
            </ion-item>
            <ion-item lines="none" class="no-bg-item radio-item">
              <ion-radio slot="start" value="pickup" color="danger"></ion-radio>
              <ion-label>Recoger del comercio</ion-label>
            </ion-item>
          </ion-radio-group>
        </div>

        <!-- Forma de Pago -->
        <div class="card-box" style="padding: 0; margin-top: 15px;">
          <ion-item lines="none" class="no-bg-item">
            <ion-label>Forma de pago</ion-label>
            <ion-select v-model="cart.checkout.payment_method" justify="end" interface="popover" class="payment-select">
              <ion-select-option value="cash">Efectivo</ion-select-option>
              <ion-select-option value="qr">QR</ion-select-option>
              <ion-select-option value="points">Mis Puntos</ion-select-option>
            </ion-select>
          </ion-item>
        </div>

        <!-- Aviso de Horario -->
        <div v-if="isPastCutoff" class="card-box time-warning-box">
          <ion-icon :icon="timeOutline" class="warning-icon"></ion-icon>
          <div class="warning-text">
            <strong>Fuera de Horario</strong><br>
            Los pedidos solo se reciben hasta las 16:00 hrs.
          </div>
        </div>

        <!-- Resumen -->
        <div class="card-box summary-box">
          <div class="summary-row">
            <span class="summary-label">Total en Efectivo</span>
            <span class="summary-val">Bs. {{ cart.totalPrice.toFixed(2) }}</span>
          </div>
          <div class="summary-row" v-if="cart.totalPointsCost > 0" style="margin-top: 10px;">
            <span class="summary-label">Total en Puntos</span>
            <span class="summary-val" style="color: #f1c40f;">{{ cart.totalPointsCost }} pts</span>
          </div>
          <div class="summary-row" v-if="cart.checkout.order_type === 'delivery'" style="margin-top: 10px;">
            <span class="summary-label">Costo estimado delivery</span>
            <span class="summary-val" style="font-size: 0.8rem; font-weight: normal;">(Se calculará al final)</span>
          </div>
        </div>
      </div>
    </ion-content>

    <!-- Bottom Action Bar -->
    <div class="floating-bottom-bar" v-if="cart.items.length > 0">
      <button class="checkout-button" @click="goToCheckout" :disabled="isPastCutoff">
        <div class="cart-badge">{{ cart.totalItems }}</div>
        <span class="btn-text">Continuar</span>
        <span class="btn-price">
          <span v-if="cart.totalPrice === 0 && cart.totalPointsCost > 0">Pagar con Puntos</span>
          <span v-else>Bs. {{ cart.totalPrice.toFixed(2) }}</span>
        </span>
      </button>
    </div>
  </ion-page>
</template>

<script setup lang="ts">
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton, IonIcon, IonTextarea, IonRadioGroup, IonRadio, IonItem, IonLabel, IonSelect, IonSelectOption } from '@ionic/vue';
import { cartOutline, trashOutline, addOutline, removeOutline, timeOutline } from 'ionicons/icons';
import { cartState } from '../store/cart';
import { useRouter } from 'vue-router';
import { computed, ref, onMounted } from 'vue';
import axios from 'axios';

const cart = cartState;
const router = useRouter();

const enforceTimeLimit = ref(true);

onMounted(async () => {
  try {
    const res = await axios.get('/api/settings/time-limit');
    enforceTimeLimit.value = res.data.enforce_time_limit;
  } catch (e) {
    console.error('Failed to fetch time limit settings', e);
  }
});

const isPastCutoff = computed(() => {
  const hour = new Date().getHours();
  return enforceTimeLimit.value && hour >= 16;
});

const goToCheckout = () => {
  if (isPastCutoff.value) return;
  router.push('/checkout');
};
</script>

<style scoped>
ion-content {
  --background: var(--ion-background-color, #f4f5f8);
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: var(--ion-color-step-400, #888);
}
.empty-icon {
  font-size: 80px;
  margin-bottom: 20px;
}

.cart-container {
  padding: 15px;
  padding-bottom: 100px; /* space for bottom bar */
}

.card-box {
  background: var(--ion-card-background, #ffffff);
  border-radius: 12px;
  padding: 15px;
  margin-bottom: 15px;
  box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.product-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.product-name {
  font-weight: 600;
  font-size: 1.05rem;
  color: var(--ion-text-color, #333);
}

.delete-icon {
  color: var(--ion-color-step-400, #999);
  font-size: 1.2rem;
  cursor: pointer;
}

.reward-badge {
  background: #f1c40f;
  color: #fff;
  font-size: 0.7rem;
  padding: 2px 8px;
  border-radius: 10px;
  margin-left: 10px;
  font-weight: 700;
  vertical-align: middle;
}

.product-body {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.qty-control {
  display: flex;
  align-items: center;
  background: var(--ion-background-color, #fff);
  border: 1px solid var(--ion-color-step-200, #e0e0e0);
  border-radius: 20px;
  padding: 2px 5px;
}

.qty-btn {
  background: transparent;
  border: none;
  font-size: 1.2rem;
  color: var(--ion-text-color, #555);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 5px 10px;
}

.add-btn {
  color: #ff4757;
}

.qty-value {
  font-weight: 600;
  min-width: 20px;
  text-align: center;
  color: var(--ion-text-color);
}

.product-price {
  font-weight: 700;
  font-size: 1.05rem;
  color: var(--ion-text-color, #333);
}

.time-warning-box {
  display: flex;
  align-items: center;
  background-color: #fff3f3;
  border-left: 4px solid #ff4757;
  padding: 10px 15px;
}

.warning-icon {
  font-size: 1.5rem;
  color: #ff4757;
  margin-right: 15px;
}

.warning-text {
  font-size: 0.9rem;
  color: #d63031;
}

.section-title {
  font-weight: 600;
  color: var(--ion-text-color, #333);
  margin-bottom: 10px;
  font-size: 0.95rem;
  margin-top: 5px;
}

.section-subtitle {
  font-weight: 600;
  font-size: 0.9rem;
  margin-bottom: 5px;
  color: var(--ion-text-color, #333);
}

.recommendation-input {
  --background: transparent;
  --padding-start: 0;
  font-size: 0.95rem;
  --color: var(--ion-text-color, #555);
}

.no-bg-item {
  --background: transparent;
  --padding-start: 15px;
  --inner-padding-end: 15px;
}

.radio-item {
  --padding-start: 0;
}
.radio-item ion-radio {
  margin-right: 10px;
}

.payment-select {
  color: #ff4757;
  font-weight: 600;
}

.summary-box {
  margin-top: 15px;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.summary-label {
  color: var(--ion-color-step-600, #666);
  font-size: 0.95rem;
}

.summary-val {
  font-weight: 600;
  color: var(--ion-text-color, #888);
  font-size: 0.95rem;
}

/* Floating Bottom Bar */
.floating-bottom-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  width: 100%;
  padding: 15px;
  background: var(--ion-background-color, #f4f5f8);
  z-index: 1000;
}

.checkout-button {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
  background: #1a1e29;
  color: #fff;
  border: none;
  border-radius: 30px;
  padding: 12px 20px;
  font-size: 1.1rem;
  font-weight: 600;
  box-shadow: 0 4px 10px rgba(0,0,0,0.15);
}

.cart-badge {
  background: #ff4757;
  color: #fff;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
}

.btn-price {
  color: #ffcc00; /* Yellowish color as in dinki */
}
</style>
