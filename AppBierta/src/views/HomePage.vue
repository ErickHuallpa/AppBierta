<template>
  <ion-page>
    <ion-content class="home-content">
      <!-- Dark Header Section -->
      <div class="dark-header">
        <div class="header-top">
          <div class="location-selector" @click="goToLocations">
            <div class="loc-text">
              <span class="loc-value">{{ defaultLocation ? defaultLocation.address : 'Seleccionar ubicación' }}</span>
              <ion-icon :icon="chevronDownOutline" class="chevron-icon"></ion-icon>
            </div>
          </div>
          <div class="header-actions">
            <ion-button fill="clear" class="cart-btn" router-link="/tabs/cart">
              <ion-icon slot="icon-only" :icon="cartOutline"></ion-icon>
              <div v-if="cart.items.length > 0" class="cart-badge">{{ cart.totalItems }}</div>
            </ion-button>
          </div>
        </div>

        <div class="search-bar-container">
          <div class="search-input-wrapper">
            <input type="text" placeholder="productos, marcas o presentacion..." class="search-input" v-model="searchQuery" @keyup.enter="search" />
            <ion-icon :icon="searchOutline" class="search-icon"></ion-icon>
          </div>
        </div>
      </div>

      <!-- Top Banner -->
      <div class="top-banner-container">
        <img src="/assets/images/banner1.jpg" class="promotional-banner" alt="Banner 1" />
      </div>

      <!-- Hero Banner (Optional, makes it look more complete) -->
      <div class="hero-banner">
        <div class="hero-content">
          <h3>¡Envíos Rápidos!</h3>
          <p>Tu cerveza fría en minutos</p>
        </div>
      </div>

      <!-- Rewards Banner -->
      <div class="rewards-banner" @click="goToRewards">
        <div class="rewards-content">
          <ion-icon :icon="star" class="rewards-icon"></ion-icon>
          <div class="rewards-text">
            <h3>Mis Recompensas</h3>
            <p>Canjea tus puntos por productos gratis</p>
          </div>
          <ion-icon :icon="chevronForwardOutline" class="rewards-arrow"></ion-icon>
        </div>
      </div>

      <!-- Categories Grid -->
      <div class="categories-container">
        <ion-grid>
          <ion-row>
            <ion-col :size="index === 2 ? '12' : '6'" v-for="(cat, index) in categories" :key="cat.id">
              <div :class="['category-card', index === 2 ? 'category-card-wide' : '']" @click="goToCategory(cat)">
                <!-- Forcing icons as requested by user -->
                <ion-icon :icon="getCategoryIcon(cat.name)" class="cat-icon" :class="getCategoryColorClass(cat.name)"></ion-icon>
                <span class="cat-name">{{ cat.name }}</span>
              </div>
            </ion-col>
          </ion-row>
        </ion-grid>
      </div>

      <!-- Bottom Banner -->
      <div class="bottom-banner-container">
        <img src="/assets/images/banner2.jpg" class="promotional-banner" alt="Banner 2" />
      </div>

    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { IonPage, IonContent, IonButton, IonIcon, IonGrid, IonRow, IonCol } from '@ionic/vue';
import { cartOutline, chevronDownOutline, searchOutline, beerOutline, cubeOutline, waterOutline, archiveOutline, star, chevronForwardOutline } from 'ionicons/icons';
import { cartState } from '../store/cart';
import { useRouter } from 'vue-router';
import axios from 'axios';

const cart = cartState;
const router = useRouter();
const searchQuery = ref('');
const defaultLocation = ref<any>(null);
const categories = ref<any[]>([]);

onMounted(async () => {
  try {
    const locRes = await axios.get('/api/locations');
    if (locRes.data && locRes.data.length > 0) {
      defaultLocation.value = locRes.data.find((l: any) => l.is_default) || locRes.data[0];
    }
  } catch (error) {
    console.error('Error fetching locations', error);
  }

  try {
    const catRes = await axios.get('/api/categories');
    categories.value = catRes.data;
  } catch (error) {
    console.error('Error fetching categories', error);
  }
});

const getCategoryIcon = (name: string) => {
  const n = name.toLowerCase();
  if (n.includes('lata')) return cubeOutline; // Using cube as a proxy for can
  if (n.includes('botella')) return waterOutline; // Using water/flask as a proxy for bottle
  if (n.includes('paquete') || n.includes('fardo') || n.includes('caja')) return archiveOutline;
  return beerOutline;
};

const getCategoryColorClass = (name: string) => {
  const n = name.toLowerCase();
  if (n.includes('lata')) return 'icon-silver';
  if (n.includes('botella')) return 'icon-amber';
  if (n.includes('paquete')) return 'icon-red';
  return 'icon-default';
};

const goToLocations = () => {
  router.push('/tabs/locations');
};

const search = () => {
  if (searchQuery.value.trim()) {
    router.push({ path: '/category/all', query: { q: searchQuery.value } });
  }
};

const goToCategory = (cat: any) => {
  router.push(`/category/${cat.id}`);
};

const goToRewards = () => {
  router.push('/rewards');
};
</script>

<style scoped>
.home-content {
  --background: var(--ion-color-step-50, #f4f5f8);
}

.dark-header {
  background-color: #121212; /* Dark color as requested */
  padding: 15px 20px;
  padding-top: 20px;
  border-bottom-left-radius: 20px;
  border-bottom-right-radius: 20px;
  position: relative;
  z-index: 10;
}

.header-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.location-selector {
  color: #fff;
  display: flex;
  align-items: center;
  cursor: pointer;
}

.loc-text {
  display: flex;
  align-items: center;
  gap: 5px;
}

.loc-value {
  font-weight: 700;
  font-size: 1.1rem;
}

.chevron-icon {
  font-size: 1.2rem;
}

.cart-btn {
  color: #fff;
  --padding-end: 0;
  --padding-start: 0;
  position: relative;
}

.cart-badge {
  position: absolute;
  top: 0;
  right: -5px;
  background: #ff4757;
  color: #fff;
  border-radius: 50%;
  width: 18px;
  height: 18px;
  font-size: 0.7rem;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
}

.search-bar-container {
  width: 100%;
}

.search-input-wrapper {
  background: #fff;
  border-radius: 30px;
  display: flex;
  align-items: center;
  padding: 5px 15px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

.search-input {
  flex: 1;
  border: none;
  background: transparent;
  padding: 10px 5px;
  font-size: 0.95rem;
  color: #333;
  outline: none;
}

.search-icon {
  color: #ff4757; /* Accent color like Pedidos Ya */
  font-size: 1.4rem;
  background: #ffeeef;
  padding: 5px;
  border-radius: 50%;
}

.hero-banner {
  margin: 20px;
  background: linear-gradient(135deg, #ff4757 0%, #ff6b81 100%);
  border-radius: 15px;
  padding: 20px;
  color: #fff;
  box-shadow: 0 5px 15px rgba(255, 71, 87, 0.3);
}

.hero-content h3 {
  margin: 0;
  font-weight: 800;
  font-size: 1.3rem;
}

.hero-content p {
  margin: 5px 0 0 0;
  font-size: 0.9rem;
  opacity: 0.9;
}

.rewards-banner {
  margin: 0 20px 20px 20px;
  background: linear-gradient(135deg, #f1c40f 0%, #f39c12 100%);
  border-radius: 15px;
  padding: 15px;
  color: #fff;
  box-shadow: 0 5px 15px rgba(241, 196, 15, 0.3);
  cursor: pointer;
}

.rewards-content {
  display: flex;
  align-items: center;
}

.rewards-icon {
  font-size: 2.5rem;
  margin-right: 15px;
}

.rewards-text {
  flex: 1;
}

.rewards-text h3 {
  margin: 0;
  font-weight: 800;
  font-size: 1.1rem;
}

.rewards-text p {
  margin: 2px 0 0 0;
  font-size: 0.85rem;
  opacity: 0.95;
}

.rewards-arrow {
  font-size: 1.5rem;
}

.categories-container {
  padding: 10px;
}

.category-card {
  background: var(--ion-card-background, #fff);
  border-radius: 15px;
  padding: 20px 10px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(0,0,0,0.06);
  margin-bottom: 5px;
  cursor: pointer;
  height: 120px;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  border: 1px solid rgba(0,0,0,0.02);
}

.category-card:active {
  transform: scale(0.96);
}

.category-card-wide {
  height: 100px;
  flex-direction: row;
  justify-content: flex-start;
  padding: 15px 25px;
  gap: 20px;
  background: linear-gradient(135deg, #2b2b2b 0%, #1a1a1a 100%);
}

.category-card-wide .cat-name {
  color: #fff;
  font-size: 1.2rem;
}

.cat-image {
  width: 50px;
  height: 50px;
  object-fit: contain;
  margin-bottom: 10px;
}

.category-card-wide .cat-image {
  margin-bottom: 0;
  width: 60px;
  height: 60px;
}

.cat-icon {
  font-size: 3rem;
  margin-bottom: 10px;
}

.category-card-wide .cat-icon {
  margin-bottom: 0;
  font-size: 3.5rem;
}

.icon-silver {
  color: #a0a0a0;
}

.icon-amber {
  color: #ff9800;
}

.icon-red {
  color: #ff4757;
}

.icon-default {
  color: #555;
}

.top-banner-container,
.bottom-banner-container {
  padding: 10px 20px;
}

.promotional-banner {
  width: 100%;
  border-radius: 15px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  object-fit: cover;
  display: block;
}

.cat-name {
  font-weight: 700;
  color: var(--ion-text-color, #333);
  font-size: 0.95rem;
  text-transform: capitalize;
}
</style>
