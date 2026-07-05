<template>
  <ion-page>
    <ion-content class="home-content">
      <ion-refresher slot="fixed" @ionRefresh="handleRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>
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
        <img src="/assets/images/bannerlogo.png" class="promotional-banner" alt="Banner 1" />
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
import { IonPage, IonContent, IonButton, IonIcon, IonGrid, IonRow, IonCol, IonRefresher, IonRefresherContent } from '@ionic/vue';
import { cartOutline, chevronDownOutline, searchOutline, beerOutline, cubeOutline, waterOutline, archiveOutline, star, chevronForwardOutline } from 'ionicons/icons';
import { cartState } from '../store/cart';
import { useRouter } from 'vue-router';
import axios from 'axios';

const cart = cartState;
const router = useRouter();
const searchQuery = ref('');
const defaultLocation = ref<any>(null);
const categories = ref<any[]>([]);

const fetchLocations = async () => {
  try {
    const locRes = await axios.get('/api/locations');
    if (locRes.data && locRes.data.length > 0) {
      defaultLocation.value = locRes.data.find((l: any) => l.is_default) || locRes.data[0];
    }
  } catch (error) {
    console.error('Error fetching locations', error);
  }
};

const fetchCategories = async () => {
  try {
    const catRes = await axios.get('/api/categories');
    categories.value = catRes.data.filter((c: any) => c.name.toLowerCase() !== 'paquetes');
  } catch (error) {
    console.error('Error fetching categories', error);
  }
};

onMounted(async () => {
  await fetchLocations();
  await fetchCategories();
});

const handleRefresh = async (event: any) => {
  await fetchLocations();
  await fetchCategories();
  event.target.complete();
};

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
  --background: var(--ion-background-color, #f7f9fc);
}

.dark-header {
  background: #04644c;
  padding: 50px 20px 20px 20px; /* Extra top padding for mobile status bar area */
  border-bottom-left-radius: 30px;
  border-bottom-right-radius: 30px;
  box-shadow: 0 10px 20px rgba(4, 100, 76, 0.2);
}

.header-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.location-selector {
  color: #fff;
  cursor: pointer;
}

.loc-text {
  display: flex;
  align-items: center;
  font-size: 1rem;
  font-weight: 600;
}

.loc-value {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 200px;
}

.chevron-icon {
  margin-left: 5px;
  font-size: 1.2rem;
}

.cart-btn {
  --color: #fff;
  position: relative;
  overflow: visible;
}

.cart-btn ion-icon {
  font-size: 1.8rem;
}

.cart-badge {
  position: absolute;
  top: 5px;
  right: 5px;
  background: #000;
  color: #fff;
  font-size: 0.7rem;
  font-weight: bold;
  border-radius: 10px;
  padding: 2px 6px;
  border: 2px solid #04644c;
}

.search-bar-container {
  margin-top: 10px;
}

.search-input-wrapper {
  display: flex;
  align-items: center;
  background: var(--app-card-bg, #ffffff);
  border-radius: 25px;
  padding: 5px 15px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

.search-input {
  border: none;
  outline: none;
  flex: 1;
  padding: 10px;
  font-size: 0.95rem;
  background: transparent;
  color: var(--ion-text-color, #333);
}

.search-icon {
  color: #04644c;
  font-size: 1.4rem;
  background: rgba(4, 100, 76, 0.1);
  padding: 5px;
  border-radius: 50%;
}

.hero-banner {
  margin: 20px;
  background: linear-gradient(135deg, #000000 0%, #333333 100%);
  border-radius: 20px;
  padding: 20px;
  color: #fff;
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

.hero-content h3 {
  margin: 0;
  font-weight: 800;
  font-size: 1.3rem;
  color: #fff;
}

.hero-content p {
  margin: 5px 0 0 0;
  font-size: 0.9rem;
  opacity: 0.9;
}

.rewards-banner {
  margin: 0 20px 20px 20px;
  background: linear-gradient(135deg, #04644c 0%, #1d745e 100%);
  border-radius: 20px;
  padding: 15px;
  color: #fff;
  box-shadow: 0 8px 20px rgba(4, 100, 76, 0.2);
  cursor: pointer;
}

.rewards-content {
  display: flex;
  align-items: center;
}

.rewards-icon {
  font-size: 2.5rem;
  margin-right: 15px;
  color: #fff;
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
  background: var(--app-card-bg, #ffffff);
  border-radius: 20px;
  padding: 20px 10px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  box-shadow: 0 6px 15px rgba(0, 0, 0, 0.05);
  margin-bottom: 5px;
  cursor: pointer;
  height: 120px;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  border: 1px solid var(--ion-color-step-100, rgba(0,0,0,0.02));
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
  background: var(--ion-color-secondary, #000000);
}

.category-card-wide .cat-name {
  color: var(--ion-color-secondary-contrast, #fff);
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
  color: var(--ion-color-secondary-contrast, #fff);
}

.icon-silver {
  color: var(--ion-text-color, #888);
}

.icon-amber {
  color: var(--ion-color-primary, #04644c);
}

.icon-red {
  color: var(--ion-color-secondary-contrast, #fff); /* changed from black for contrast */
}

.icon-default {
  color: #04644c;
}

.top-banner-container,
.bottom-banner-container {
  padding: 10px 20px;
}

.promotional-banner {
  width: 100%;
  border-radius: 20px;
  box-shadow: 0 6px 15px rgba(0,0,0,0.1);
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
