<template>
  <ion-page>
    <ion-header>
      <ion-toolbar color="dark">
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/profile"></ion-back-button>
        </ion-buttons>
        <ion-title>Punto de Venta (POS)</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding" color="light">
      <ion-grid style="height: 100%;">
        <ion-row style="height: 100%;">
          
          <!-- Lado Izquierdo: Catálogo de Productos (70%) -->
          <ion-col size="12" size-md="8" class="catalog-col">
            <ion-searchbar 
              placeholder="Buscar producto por nombre..." 
              v-model="searchQuery"
              @ionInput="filterProducts"
            ></ion-searchbar>
            
            <div v-if="loadingProducts" class="ion-text-center ion-margin-top">
              <ion-spinner></ion-spinner>
            </div>

            <ion-grid v-else>
              <ion-row>
                <ion-col size="12" size-sm="6" size-md="4" size-xl="3" v-for="prod in filteredProducts" :key="prod.id">
                  <ion-card class="product-card">
                    <img :src="prod.image_url || 'https://via.placeholder.com/150'" class="product-image" />
                    <ion-card-header class="ion-no-padding ion-padding-horizontal ion-padding-top">
                      <ion-card-title class="product-title">{{ prod.name }}</ion-card-title>
                      <ion-card-subtitle>Stock: {{ prod.stock }}</ion-card-subtitle>
                    </ion-card-header>
                    <ion-card-content>
                      <div class="price-row">
                        <span class="price-tag">{{ prod.precio_venta }} Bs</span>
                        <ion-button size="small" color="primary" @click="addToCart(prod)" :disabled="prod.stock <= 0">
                          <ion-icon :icon="addOutline"></ion-icon>
                        </ion-button>
                      </div>
                    </ion-card-content>
                  </ion-card>
                </ion-col>
              </ion-row>
            </ion-grid>
          </ion-col>

          <!-- Lado Derecho: Carrito y Venta (30%) -->
          <ion-col size="12" size-md="4" class="cart-col">
            <ion-card class="cart-card">
              <ion-card-header color="primary">
                <ion-card-title>Detalle de Venta</ion-card-title>
              </ion-card-header>

              <ion-card-content class="ion-no-padding cart-content">
                
                <!-- Buscador de Cliente -->
                <div class="client-section ion-padding">
                  <ion-label style="font-weight: 600; display: block; margin-bottom: 5px;">Cliente Asignado:</ion-label>
                  
                  <div v-if="selectedClient" class="selected-client-box">
                    <div>
                      <strong>{{ selectedClient.persona.nombre }} {{ selectedClient.persona.apellidos }}</strong>
                      <div style="font-size: 0.8rem; color: #666;">CI/NIT: {{ selectedClient.persona.ci_nit || 'S/N' }}</div>
                    </div>
                    <ion-button fill="clear" color="danger" @click="selectedClient = null">
                      <ion-icon slot="icon-only" :icon="closeCircleOutline"></ion-icon>
                    </ion-button>
                  </div>
                  
                  <div v-else>
                    <ion-searchbar 
                      placeholder="Buscar por Nombre, CI o Apellido" 
                      v-model="clientSearchQuery" 
                      @ionInput="searchClients"
                      debounce="500"
                      class="ion-no-padding"
                    ></ion-searchbar>
                    
                    <ion-list v-if="clientSearchResults.length > 0" class="client-search-results">
                      <ion-item button v-for="c in clientSearchResults" :key="c.id" @click="selectClient(c)">
                        <ion-label>
                          <h2>{{ c.persona.nombre }} {{ c.persona.apellidos }}</h2>
                          <p>CI: {{ c.persona.ci_nit }} | {{ c.email }}</p>
                        </ion-label>
                      </ion-item>
                    </ion-list>
                    
                    <ion-button expand="block" fill="outline" size="small" class="ion-margin-top" @click="isNewClientModalOpen = true">
                      <ion-icon slot="start" :icon="personAddOutline"></ion-icon>
                      Registrar Nuevo Cliente
                    </ion-button>
                  </div>
                </div>

                <hr style="background: #eee; margin: 0;"/>

                <!-- Lista de Productos en Carrito -->
                <div class="cart-items-wrapper">
                  <div v-if="cart.length === 0" class="ion-text-center ion-padding" style="color: #999;">
                    El carrito está vacío
                  </div>
                  <ion-list v-else lines="full">
                    <ion-item v-for="item in cart" :key="item.product.id">
                      <ion-label class="ion-text-wrap">
                        <h2>{{ item.product.name }}</h2>
                        <p>{{ item.product.precio_venta }} Bs. c/u</p>
                      </ion-label>
                      <div slot="end" class="qty-controls">
                        <ion-button fill="clear" size="small" @click="decreaseQty(item)">-</ion-button>
                        <span>{{ item.quantity }}</span>
                        <ion-button fill="clear" size="small" @click="increaseQty(item)">+</ion-button>
                      </div>
                      <div slot="end" class="item-total">
                        {{ item.product.precio_venta * item.quantity }} Bs
                      </div>
                    </ion-item>
                  </ion-list>
                </div>
              </ion-card-content>

              <!-- Footer del Carrito -->
              <div class="cart-footer">
                <div class="total-row">
                  <span>Total:</span>
                  <span class="total-amount">{{ cartTotal }} Bs</span>
                </div>
                
                <ion-item lines="none" class="payment-method-item">
                  <ion-label>Método de Pago:</ion-label>
                  <ion-select v-model="paymentMethod" interface="popover">
                    <ion-select-option value="cash">Efectivo</ion-select-option>
                    <ion-select-option value="qr">Transferencia QR</ion-select-option>
                  </ion-select>
                </ion-item>

                <ion-button expand="block" color="success" class="process-btn" @click="handleProcessSale" :disabled="cart.length === 0 || !selectedClient || processing">
                  <ion-spinner name="crescent" v-if="processing"></ion-spinner>
                  <span v-else>Procesar Venta</span>
                </ion-button>
              </div>
            </ion-card>
          </ion-col>
        </ion-row>
      </ion-grid>

      <!-- Modal para Registrar Nuevo Cliente -->
      <ion-modal :is-open="isNewClientModalOpen" @didDismiss="isNewClientModalOpen = false">
        <ion-header>
          <ion-toolbar color="primary">
            <ion-title>Registrar Cliente</ion-title>
            <ion-buttons slot="end">
              <ion-button @click="isNewClientModalOpen = false">Cerrar</ion-button>
            </ion-buttons>
          </ion-toolbar>
        </ion-header>
        <ion-content class="ion-padding">
          <ion-list>
            <ion-item>
              <ion-label position="floating">Nombre</ion-label>
              <ion-input :value="newClientForm.nombre" maxlength="50" required @ionInput="newClientForm.nombre = $event.target.value = ($event.target.value || '').toString().replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '')"></ion-input>
            </ion-item>
            <ion-item>
              <ion-label position="floating">Apellidos</ion-label>
              <ion-input :value="newClientForm.apellidos" maxlength="50" required @ionInput="newClientForm.apellidos = $event.target.value = ($event.target.value || '').toString().replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '')"></ion-input>
            </ion-item>
            <ion-item>
              <ion-label position="floating">CI / NIT</ion-label>
              <ion-input :value="newClientForm.ci_nit" maxlength="20" @ionInput="newClientForm.ci_nit = $event.target.value = ($event.target.value || '').toString().replace(/[^0-9a-zA-Z\-\s]/g, '')"></ion-input>
            </ion-item>
            <ion-item>
              <ion-label position="floating">Correo Electrónico</ion-label>
              <ion-input type="email" v-model="newClientForm.email"></ion-input>
            </ion-item>
            <ion-item>
              <ion-label position="floating">Contraseña (Para su cuenta app)</ion-label>
              <ion-input type="password" v-model="newClientForm.password"></ion-input>
            </ion-item>
          </ion-list>
          <ion-button expand="block" class="ion-margin-top" @click="registerClient" :disabled="registering">
            {{ registering ? 'Registrando...' : 'Guardar y Asignar' }}
          </ion-button>
        </ion-content>
      </ion-modal>

      <!-- Modal de Pago QR -->
      <ion-modal :is-open="isQrModalOpen" @didDismiss="isQrModalOpen = false" class="qr-modal">
        <div class="qr-modal-content">
          <h2 class="ion-text-center">Pago con QR</h2>
          <p class="ion-text-center" style="color: var(--ion-color-medium);">Por favor, pida al cliente que escanee el siguiente código para transferir <strong>{{ cartTotal }} Bs.</strong></p>
          
          <div class="qr-image-container">
            <img src="/assets/qr.png" alt="Código QR" />
          </div>

          <div class="qr-actions">
            <ion-button expand="block" color="success" @click="confirmQrPayment" :disabled="processing">
              <ion-icon slot="start" :icon="checkmarkCircleOutline"></ion-icon>
              Confirmar Pago Recibido
            </ion-button>
            <ion-button expand="block" fill="clear" color="danger" @click="cancelQrPayment" :disabled="processing">
              Cancelar Venta
            </ion-button>
          </div>
        </div>
      </ion-modal>

    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonGrid, IonRow, IonCol, IonCard, IonCardHeader, IonCardTitle, IonCardSubtitle, IonCardContent, IonSearchbar, IonSpinner, IonButton, IonIcon, IonList, IonItem, IonLabel, IonSelect, IonSelectOption, IonModal, IonInput, IonButtons, IonBackButton, toastController } from '@ionic/vue';
import { addOutline, personAddOutline, closeCircleOutline, checkmarkCircleOutline } from 'ionicons/icons';
import axios from 'axios';

// Estado de Productos
const products = ref<any[]>([]);
const filteredProducts = ref<any[]>([]);
const searchQuery = ref('');
const loadingProducts = ref(true);

// Estado de Carrito
const cart = ref<any[]>([]);
const paymentMethod = ref('cash');
const processing = ref(false);

// Estado de Clientes
const clientSearchQuery = ref('');
const clientSearchResults = ref<any[]>([]);
const selectedClient = ref<any>(null);
const isNewClientModalOpen = ref(false);
const registering = ref(false);

const newClientForm = ref({
  nombre: '',
  apellidos: '',
  ci_nit: '',
  email: '',
  password: ''
});

// Estado de Modal QR
const isQrModalOpen = ref(false);

// Cargar Productos
const loadProducts = async () => {
  loadingProducts.value = true;
  try {
    const res = await axios.get('/api/products');
    products.value = res.data;
    filteredProducts.value = res.data;
  } catch (e) {
    console.error(e);
  }
  loadingProducts.value = false;
};

onMounted(() => {
  loadProducts();
});

const filterProducts = () => {
  const q = searchQuery.value.toLowerCase();
  if (!q) {
    filteredProducts.value = products.value;
  } else {
    filteredProducts.value = products.value.filter(p => p.name.toLowerCase().includes(q));
  }
};

// Carrito Lógica
const addToCart = (product: any) => {
  const existing = cart.value.find(i => i.product.id === product.id);
  if (existing) {
    if (existing.quantity < product.stock) {
      existing.quantity++;
    }
  } else {
    cart.value.push({ product, quantity: 1 });
  }
};

const increaseQty = (item: any) => {
  if (item.quantity < item.product.stock) item.quantity++;
};

const decreaseQty = (item: any) => {
  if (item.quantity > 1) {
    item.quantity--;
  } else {
    cart.value = cart.value.filter(i => i.product.id !== item.product.id);
  }
};

const cartTotal = computed(() => {
  return cart.value.reduce((total, item) => total + (item.product.precio_venta * item.quantity), 0);
});

// Búsqueda de Clientes
const searchClients = async () => {
  if (clientSearchQuery.value.length < 2) {
    clientSearchResults.value = [];
    return;
  }
  try {
    const res = await axios.get(`/api/pos/clients?q=${clientSearchQuery.value}`);
    clientSearchResults.value = res.data;
  } catch (e) {
    console.error(e);
  }
};

const selectClient = (client: any) => {
  selectedClient.value = client;
  clientSearchQuery.value = '';
  clientSearchResults.value = [];
};

// Registro de Cliente
const registerClient = async () => {
  if (!newClientForm.value.nombre || !newClientForm.value.apellidos || !newClientForm.value.email || !newClientForm.value.password) {
    const toast = await toastController.create({
      message: 'Faltan completar campos obligatorios',
      duration: 3000,
      color: 'warning'
    });
    toast.present();
    return;
  }
  registering.value = true;
  try {
    const res = await axios.post('/api/register/client', newClientForm.value);
    selectedClient.value = res.data.user; // El backend devuelve el nuevo usuario y token
    isNewClientModalOpen.value = false;
    newClientForm.value = { nombre: '', apellidos: '', ci_nit: '', email: '', password: '' };
    
    const toast = await toastController.create({
      message: 'Cliente registrado y asignado exitosamente.',
      duration: 2500,
      color: 'success'
    });
    toast.present();
  } catch (e: any) {
    let errorMessage = 'Error al registrar cliente.';
    if (e.response?.data?.errors) {
      const errors = e.response.data.errors;
      errorMessage = Object.values(errors)[0]?.[0] as string || errorMessage;
    } else if (e.response?.data?.message) {
      errorMessage = e.response.data.message;
    }

    const toast = await toastController.create({
      message: errorMessage,
      duration: 4000,
      color: 'danger'
    });
    toast.present();
  }
  registering.value = false;
};

// Procesar Venta
const handleProcessSale = () => {
  if (paymentMethod.value === 'qr') {
    isQrModalOpen.value = true;
  } else {
    submitSale();
  }
};

const cancelQrPayment = () => {
  isQrModalOpen.value = false;
};

const confirmQrPayment = () => {
  submitSale();
};

const submitSale = async () => {
  processing.value = true;
  
  const payload = {
    is_pos: true,
    client_id: selectedClient.value.id,
    order_type: 'pickup', // POS es venta directa (para llevar en el momento)
    payment_method: paymentMethod.value,
    items: cart.value.map(i => ({ product_id: i.product.id, quantity: i.quantity }))
  };

  try {
    await axios.post('/api/orders', payload); 
    
    const toast = await toastController.create({
      message: 'Venta completada con éxito.',
      duration: 3000,
      color: 'success'
    });
    toast.present();
    
    // Limpiar POS
    cart.value = [];
    selectedClient.value = null;
    isQrModalOpen.value = false;
    loadProducts(); // Actualizar stock visual
    
  } catch (e: any) {
    const toast = await toastController.create({
      message: e.response?.data?.error || 'Error al procesar la venta.',
      duration: 4000,
      color: 'danger'
    });
    toast.present();
  }
  processing.value = false;
};

</script>

<style scoped>
.catalog-col {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.product-card {
  margin: 5px;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.product-image {
  height: 120px;
  object-fit: contain;
  width: 100%;
  padding: 10px;
  background: #fff;
}

.product-title {
  font-size: 1.1rem;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.price-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 10px;
}

.price-tag {
  font-size: 1.2rem;
  font-weight: bold;
  color: var(--ion-color-success);
}

.cart-col {
  height: 100%;
}

.cart-card {
  height: 100%;
  display: flex;
  flex-direction: column;
  margin: 0;
}

.cart-content {
  flex-grow: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.client-section {
  background: var(--ion-color-light);
}

.selected-client-box {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #fff;
  color: #333;
  border: 1px solid var(--ion-color-primary);
  border-radius: 8px;
  padding: 10px;
}

.client-search-results {
  max-height: 150px;
  overflow-y: auto;
  border: 1px solid #ddd;
  border-radius: 8px;
  margin-top: 5px;
}

.cart-items-wrapper {
  flex-grow: 1;
  overflow-y: auto;
}

.qty-controls {
  display: flex;
  align-items: center;
  background: #f4f5f8;
  color: #333;
  border-radius: 20px;
}

.qty-controls span {
  width: 20px;
  text-align: center;
  font-weight: bold;
}

.item-total {
  font-weight: bold;
  min-width: 60px;
  text-align: right;
  color: var(--ion-color-primary);
}

.cart-footer {
  padding: 15px;
  background: #f4f5f8;
  color: #333;
  border-top: 1px solid #ddd;
}

.total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
  font-size: 1.2rem;
  font-weight: 600;
}

.total-amount {
  font-size: 1.5rem;
  color: var(--ion-color-success);
}

.payment-method-item {
  border-radius: 8px;
  margin-bottom: 15px;
  border: 1px solid #ccc;
  --background: #fff;
  --color: #333;
}

.process-btn {
  height: 50px;
  font-weight: bold;
}

.qr-modal {
  --width: 400px;
  --height: 500px;
  --border-radius: 12px;
}
.qr-modal-content {
  padding: 20px;
  display: flex;
  flex-direction: column;
  height: 100%;
}
.qr-image-container {
  flex-grow: 1;
  display: flex;
  justify-content: center;
  align-items: center;
  margin: 20px 0;
}
.qr-image-container img {
  max-width: 250px;
  max-height: 250px;
  border: 2px solid #ccc;
  border-radius: 10px;
  padding: 10px;
}
.qr-actions {
  margin-top: auto;
}
</style>
