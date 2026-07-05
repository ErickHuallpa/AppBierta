<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar style="--background: var(--ion-color-primary, #04644c); color: #ffffff;">
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/home" text="" color="light"></ion-back-button>
        </ion-buttons>
        <ion-title class="ion-text-center" style="font-weight: 600;">
          {{ pageTitle }}
        </ion-title>
        <ion-buttons slot="end">
          <ion-button router-link="/tabs/cart" color="light">
            <ion-icon slot="icon-only" :icon="cartOutline"></ion-icon>
            <ion-badge color="danger" v-if="cart.items.length > 0" class="cart-badge">{{ cart.totalItems }}</ion-badge>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding" style="--background: var(--ion-background-color, #f7f9fc);">
      <ion-refresher slot="fixed" @ionRefresh="handleRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

      <div v-if="loading" class="ion-text-center" style="margin-top: 50px;">
        <ion-spinner name="crescent"></ion-spinner>
      </div>

      <div v-else-if="products.length === 0" class="empty-state">
        <ion-icon :icon="searchOutline" class="empty-icon"></ion-icon>
        <p>No se encontraron productos.</p>
      </div>

      <div v-else class="products-list">
        <div class="product-item" v-for="product in products" :key="product.id" @click="openProductModal(product)">
          <img :src="product.image_url || `https://placehold.co/150x150/eeeeee/333333?text=${product.name}`" class="product-image" />
          
          <div class="product-info">
            <div class="product-title">
              {{ product.name }}
              <ion-badge color="danger" v-if="product.promotional_stock > 0" style="margin-left: 5px; font-size: 0.6em; vertical-align: middle;">🔥 20% OFF (Quedan {{ product.promotional_stock }})</ion-badge>
            </div>
            <div class="product-packages-preview">
              <div v-for="pkg in getPackagesFor(product.id)" :key="pkg.id" style="font-size: 0.85em; color: var(--ion-color-medium); margin-top: 2px;">
                {{ pkg.name.split(' ')[0] }}: 
                <span v-if="pkg.promotional_price">
                  <span style="text-decoration: line-through; color: #999;">Bs. {{ pkg.precio_venta }}</span> 
                  <strong style="color: var(--ion-color-danger)">Bs. {{ pkg.promotional_price.toFixed(2) }}</strong>
                </span>
                <strong v-else style="color: var(--ion-color-danger)">Bs. {{ pkg.precio_venta }}</strong>
              </div>
            </div>
          </div>

          <div class="add-btn-wrapper">
            <button class="add-button" @click.stop="openProductModal(product)">
              <ion-icon :icon="addOutline"></ion-icon>
            </button>
          </div>
        </div>
      </div>

      <!-- Modal Detalle de Producto -->
      <ion-modal :is-open="isModalOpen" @didDismiss="isModalOpen = false" :initial-breakpoint="0.65" :breakpoints="[0, 0.65, 1]">
        <ion-content class="ion-padding product-modal">
          <div v-if="selectedProduct" class="modal-inner">
            <div class="modal-image-container">
              <img :src="selectedProduct.image_url || `https://placehold.co/300x300/eeeeee/333333?text=${selectedProduct.name}`" />
            </div>
            <h2 class="modal-title">{{ selectedProduct.name }}</h2>
            <p class="modal-desc" v-if="selectedProduct.promotional_stock > 0">
              <ion-badge color="danger" style="font-size: 0.9em; padding: 5px 10px;">🔥 {{ selectedProduct.promotional_stock }} unidades en oferta de 20%</ion-badge>
            </p>
            <p class="modal-desc" v-else>{{ selectedProduct.description || 'Sin descripción disponible.' }}</p>
            
            <div v-if="availablePackages.length > 0">
              <ion-list lines="full">
                <ion-item v-for="pkg in availablePackages" :key="pkg.id">
                  <ion-label>
                    <h3 style="font-weight: 600;">{{ pkg.name }}</h3>
                    <p v-if="pkg.promotional_price" style="color: var(--ion-color-danger); font-weight: bold;">
                      <span style="text-decoration: line-through; color: var(--ion-color-medium); font-size: 0.85em; margin-right: 5px;">Bs. {{ pkg.precio_venta }}</span>
                      Bs. {{ pkg.promotional_price.toFixed(2) }}
                    </p>
                    <p v-else style="color: var(--ion-color-danger); font-weight: bold;">Bs. {{ pkg.precio_venta }}</p>
                    <div style="font-size: 0.85em; color: var(--ion-color-medium); margin-top: 5px;">Disponibles: <strong>{{ getMaxQtyFor(pkg) }}</strong> paquetes</div>
                  </ion-label>
                  <div slot="end" style="display: flex; align-items: center; gap: 5px; background: var(--ion-color-light); border-radius: 20px; padding: 2px;">
                    <ion-button fill="clear" color="dark" @click="pkg.qty > 0 ? pkg.qty-- : null" :disabled="pkg.qty === 0" style="--padding-start: 5px; --padding-end: 5px; height: 30px;">
                      <ion-icon :icon="removeOutline"></ion-icon>
                    </ion-button>
                    <span style="font-weight: bold; font-size: 1.1em; min-width: 20px; text-align: center;">{{ pkg.qty }}</span>
                    <ion-button fill="clear" color="dark" @click="pkg.qty < getMaxQtyFor(pkg) ? pkg.qty++ : null" :disabled="pkg.qty >= getMaxQtyFor(pkg)" style="--padding-start: 5px; --padding-end: 5px; height: 30px;">
                      <ion-icon :icon="addOutline"></ion-icon>
                    </ion-button>
                  </div>
                </ion-item>
              </ion-list>

              <ion-button expand="block" shape="round" color="danger" class="add-to-cart-btn" @click="confirmAddToCart" :disabled="totalSelectedQty === 0" style="margin-top: 20px;">
                <span v-if="totalSelectedQty > 0">Agregar {{ totalSelectedQty }} al Carrito - Bs. {{ totalSelectedPrice.toFixed(2) }}</span>
                <span v-else>Selecciona al menos 1</span>
              </ion-button>
            </div>
            <div v-else>
              <p style="text-align: center; color: var(--ion-color-danger); margin-top: 20px; font-weight: 600;">
                No hay presentaciones en paquete disponibles para este producto.
              </p>
            </div>
          </div>
        </ion-content>
      </ion-modal>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton, IonIcon, IonBadge, IonSpinner, IonModal, toastController, IonRefresher, IonRefresherContent, IonList, IonRadioGroup, IonRadio, IonItem, IonLabel } from '@ionic/vue';
import { cartOutline, searchOutline, addOutline, removeOutline } from 'ionicons/icons';
import axios from 'axios';
import { cartState } from '../store/cart';

const route = useRoute();
const cart = cartState;
const allProducts = ref<any[]>([]);
const products = ref<any[]>([]);
const loading = ref(true);
const categoryName = ref('Productos');

const isModalOpen = ref(false);
const selectedProduct = ref<any>(null);
const availablePackages = ref<any[]>([]);

const totalSelectedQty = computed(() => availablePackages.value.reduce((acc, p) => acc + p.qty, 0));
const totalSelectedPrice = computed(() => availablePackages.value.reduce((acc, p) => acc + (p.qty * (p.promotional_price || p.precio_venta)), 0));
const totalBaseUnitsSelected = computed(() => availablePackages.value.reduce((acc, pkg) => acc + (pkg.qty * (pkg.package_multiplier || 1)), 0));

const getMaxQtyFor = (pkg: any) => {
  if (!selectedProduct.value) return 0;
  const otherPackagesBaseUnits = totalBaseUnitsSelected.value - (pkg.qty * (pkg.package_multiplier || 1));
  const remainingBaseUnits = selectedProduct.value.real_stock - otherPackagesBaseUnits;
  return Math.floor(remainingBaseUnits / (pkg.package_multiplier || 1));
};

const getPackagesFor = (productId: number) => {
  return allProducts.value.filter((p: any) => p.parent_id === productId);
};

const openProductModal = (product: any) => {
  selectedProduct.value = product;
  availablePackages.value = allProducts.value.filter((p: any) => p.parent_id === product.id).map(p => ({...p, qty: 0}));
  isModalOpen.value = true;
};

const confirmAddToCart = async () => {
  const selected = availablePackages.value.filter(p => p.qty > 0);
  if (selected.length > 0) {
    for (const pkg of selected) {
      cart.addItems(pkg, pkg.qty);
    }
    isModalOpen.value = false;
    const t = await toastController.create({
      message: `Productos añadidos al carrito`,
      duration: 1500,
      color: 'dark',
      position: 'top'
    });
    t.present();
  }
};

const categoryId = route.params.id as string;
const queryParam = route.query.q as string;

const pageTitle = computed(() => {
  if (queryParam) return `Búsqueda: ${queryParam}`;
  if (categoryId === 'all') return 'Todos los Productos';
  return categoryName.value;
});

const fetchProducts = async () => {
  try {
    const res = await axios.get('/api/products');
    allProducts.value = res.data;
    
    // Only show base products in the catalog
    let baseProducts = allProducts.value.filter((p: any) => p.parent_id === null);
    
    // Simulate filtering (in a real app, backend handles this via query params)
    if (queryParam) {
      baseProducts = baseProducts.filter((p: any) => p.name.toLowerCase().includes(queryParam.toLowerCase()));
    } else if (categoryId !== 'all') {
      baseProducts = baseProducts.filter((p: any) => p.category_id == categoryId);
      if (baseProducts.length > 0) {
        categoryName.value = baseProducts[0].category?.name || 'Categoría';
      } else {
        // Fetch category name if empty
        const catRes = await axios.get('/api/categories');
        const cat = catRes.data.find((c: any) => c.id == categoryId);
        if (cat) categoryName.value = cat.name;
      }
    }
    
    products.value = baseProducts;
  } catch (error) {
    console.error(error);
  }
};

onMounted(async () => {
  loading.value = true;
  await fetchProducts();
  loading.value = false;
});

const handleRefresh = async (event: any) => {
  await fetchProducts();
  event.target.complete();
};

const addToCart = async (product: any) => {
  const existing = cart.items.find((i: any) => i.product_id === product.id && !i.is_reward);
  if (existing && (existing.quantity >= 5 || existing.quantity >= product.real_stock)) {
    const t = await toastController.create({
      message: 'Error: Se ha alcanzado la cuota máxima de 5 unidades por lote para este cliente.',
      duration: 3000,
      color: 'danger',
      position: 'top'
    });
    t.present();
    return;
  }
  cart.addItem(product);
  const t = await toastController.create({
    message: 'Añadido al carrito',
    duration: 1500,
    color: 'dark',
    position: 'top'
  });
  t.present();
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
  margin-top: 50px;
  color: var(--ion-color-step-400, #888);
}
.empty-icon {
  font-size: 60px;
  margin-bottom: 15px;
}

.cart-badge {
  position: absolute;
  top: 5px;
  right: 5px;
  font-size: 0.7rem;
  border-radius: 50%;
  padding: 3px 5px;
}

.products-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.product-item {
  display: flex;
  align-items: center;
  background: var(--app-card-bg, #ffffff);
  border-radius: 12px;
  margin-bottom: 15px;
  padding: 10px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
}

.product-image {
  width: 70px;
  height: 70px;
  border-radius: 10px;
  object-fit: cover;
  margin-right: 15px;
}

.product-info {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.product-title {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 5px;
  color: var(--ion-text-color, #000);
}

.product-price {
  font-size: 1.1rem;
  font-weight: bold;
  color: #ff4757;
}

.add-btn-wrapper {
  margin-left: 10px;
}

.add-button {
  background: var(--ion-color-primary, #04644c);
  color: white;
  border: none;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  display: flex;
  justify-content: center;
  align-items: center;
  font-size: 1.2rem;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(4, 100, 76, 0.3);
}

.add-button:active {
  transform: scale(0.9);
}

/* Modal Styles */
.modal-inner {
  text-align: center;
  padding: 20px 0;
}

.modal-image-container {
  width: 200px;
  height: 200px;
  margin: 0 auto 20px auto;
  border-radius: 12px;
  overflow: hidden;
  background: var(--app-card-bg, #ffffff);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
}

.modal-image-container img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.modal-title {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--ion-text-color, #333);
  margin-bottom: 10px;
}

.modal-desc {
  color: var(--ion-color-medium, #888888);
  margin-bottom: 20px;
  font-size: 0.95rem;
}

.modal-price {
  font-size: 1.4rem;
  font-weight: 800;
  color: #ff4757;
  margin-bottom: 25px;
}

.qty-selector {
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--ion-color-light, #f4f5f8);
  border-radius: 30px;
  padding: 5px 15px;
  margin-bottom: 25px;
}

.qty-btn {
  --padding-start: 10px;
  --padding-end: 10px;
  font-size: 1.2rem;
}

.qty-display {
  font-size: 1.2rem;
  font-weight: 700;
  min-width: 40px;
  text-align: center;
}
.add-to-cart-btn {
  width: 100%;
  --box-shadow: 0 4px 15px rgba(255, 71, 87, 0.3);
}
</style>
