<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar style="--background: #04644c; color: #fff;">
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/home" text="" color="light"></ion-back-button>
        </ion-buttons>
        <ion-title class="ion-text-center" style="font-weight: 600;">Mis Recompensas</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content color="light">
      <ion-refresher slot="fixed" @ionRefresh="handleRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

      <div class="points-header">
        <ion-icon :icon="star" class="points-icon"></ion-icon>
        <h2 class="points-title">Tus Puntos</h2>
        <p class="points-value">{{ currentPoints }} pts</p>
        <p class="points-subtitle">Sigue comprando para ganar más</p>
      </div>

      <div class="rewards-container">
        <h3 class="section-title">Canjea tus puntos</h3>
        
        <div v-if="loading" class="ion-text-center ion-padding">
          <ion-spinner name="crescent"></ion-spinner>
        </div>

        <div v-else-if="rewardProducts.length === 0" class="ion-text-center ion-padding">
          <p>No hay productos disponibles para canjear en este momento.</p>
        </div>

        <ion-list v-else lines="none" style="background: transparent;">
          <ion-item v-for="prod in rewardProducts" :key="prod.id" class="reward-item">
            <div class="reward-card">
              <div class="reward-header">
                <img :src="prod.image_url" alt="producto" class="reward-img" v-if="prod.image_url" />
                <div class="reward-info">
                  <h4>{{ prod.name }}</h4>
                  <div class="stats-row">
                    <p class="cost">Costo: <strong>{{ prod.points_cost }} pts</strong></p>
                  </div>
                </div>
              </div>
              
              <div class="progress-section">
                <ion-progress-bar 
                  :value="Math.min(currentPoints / prod.points_cost, 1)" 
                  :color="currentPoints >= prod.points_cost ? 'success' : 'warning'"
                  style="height: 10px; border-radius: 5px;">
                </ion-progress-bar>
                <p class="progress-text">
                  <span v-if="currentPoints >= prod.points_cost" style="color: var(--ion-color-success);">¡Listo para canjear!</span>
                  <span v-else>Te faltan {{ prod.points_cost - currentPoints }} pts</span>
                </p>
              </div>

              <ion-button 
                expand="block" 
                :disabled="currentPoints < prod.points_cost"
                @click="redeemProduct(prod)"
                :color="currentPoints >= prod.points_cost ? 'primary' : 'medium'"
                class="redeem-btn"
              >
                <ion-icon :icon="cartOutline" slot="start"></ion-icon>
                Añadir Gratis al Carrito
              </ion-button>
            </div>
          </ion-item>
        </ion-list>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonIcon, IonProgressBar, IonButton, IonList, IonItem, IonSpinner, IonRefresher, IonRefresherContent, toastController, alertController } from '@ionic/vue';
import { star, cartOutline } from 'ionicons/icons';
import { authState } from '../store/auth';
import { cartState } from '../store/cart';
import axios from 'axios';

const products = ref<any[]>([]);
const loading = ref(true);

const currentPoints = computed(() => {
  // Puntos actuales del usuario menos los que ya están comprometidos en el carrito
  const userPoints = authState.user?.loyalty_points || 0;
  return userPoints - cartState.totalPointsCost;
});

const rewardProducts = computed(() => {
  return products.value.filter(p => p.parent_id !== null && p.points_cost && p.points_cost > 0);
});

const loadData = async () => {
  loading.value = true;
  try {
    // 1. Refresh User Points
    const userRes = await axios.get('/api/user');
    authState.setAuth(authState.token as string, userRes.data);

    // 2. Fetch All Products
    const prodRes = await axios.get('/api/products');
    products.value = prodRes.data;
  } catch (error) {
    console.error('Error cargando recompensas', error);
  } finally {
    loading.value = false;
  }
};

const handleRefresh = async (event: any) => {
  await loadData();
  event.target.complete();
};

const redeemProduct = async (prod: any) => {
  if (currentPoints.value >= prod.points_cost) {
    const alert = await alertController.create({
      header: 'Confirmar Canje',
      message: `¿Estás seguro de que deseas usar ${prod.points_cost} puntos para obtener "${prod.name}" gratis?`,
      buttons: [
        {
          text: 'Cancelar',
          role: 'cancel'
        },
        {
          text: 'Sí, Canjear',
          handler: async () => {
            cartState.addItem(prod, true); // true = isReward
            const t = await toastController.create({
              message: `${prod.name} añadido al carrito como recompensa.`,
              duration: 2000,
              color: 'success'
            });
            await t.present();
          }
        }
      ]
    });
    await alert.present();
  }
};

onMounted(() => {
  loadData();
});
</script>

<style scoped>
.points-header {
  background: #04644c;
  color: #fff;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 20px 20px 40px;
  border-bottom-left-radius: 30px;
  border-bottom-right-radius: 30px;
  box-shadow: 0 10px 20px rgba(4, 100, 76, 0.2);
}

.points-icon {
  font-size: 60px;
  color: #f1c40f;
  margin-bottom: 10px;
}

.points-title {
  font-weight: 600;
  font-size: 1.2rem;
  margin: 0;
}

.points-value {
  font-weight: 800;
  font-size: 2.5rem;
  color: #f1c40f;
  margin: 5px 0;
}

.points-subtitle {
  color: #aaa;
  margin: 0;
  font-size: 0.9rem;
}

.rewards-container {
  padding: 10px 15px;
  margin-top: -20px;
}

.section-title {
  font-weight: 700;
  color: var(--ion-text-color, #333);
  margin-bottom: 15px;
  padding-left: 5px;
}

.reward-item {
  --background: transparent;
  --padding-start: 0;
  --inner-padding-end: 0;
  margin-bottom: 15px;
}

.reward-card {
  background: var(--ion-item-background, #fff);
  border-radius: 12px;
  width: 100%;
  padding: 15px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}

.reward-header {
  display: flex;
  align-items: center;
  gap: 15px;
}

.reward-img {
  width: 60px;
  height: 60px;
  object-fit: cover;
  border-radius: 8px;
}

.reward-info {
  flex: 1;
}

.reward-info h4 {
  margin: 0 0 5px 0;
  font-weight: 700;
  color: var(--ion-text-color, #222);
}

.stats-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 5px;
}

.cost {
  margin: 0;
  color: var(--ion-color-step-600, #666);
  font-size: 0.95rem;
}

.balance {
  margin: 0;
  color: #f39c12;
  font-weight: 600;
  font-size: 0.95rem;
}

.progress-section {
  margin: 15px 0;
}

.progress-text {
  font-size: 0.85rem;
  color: #888;
  margin: 5px 0 0 0;
  text-align: right;
  font-weight: 600;
}

.redeem-btn {
  margin-top: 10px;
  height: 45px;
  font-weight: 600;
}
</style>
