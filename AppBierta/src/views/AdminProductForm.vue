<template>
  <ion-page>
    <ion-header>
      <ion-toolbar color="dark">
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/admin/products"></ion-back-button>
        </ion-buttons>
        <ion-title>{{ isEdit ? 'Editar Producto' : 'Nuevo Producto' }}</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <div v-if="fetching" class="ion-text-center">
        <ion-spinner></ion-spinner>
      </div>
      <ion-list v-else>
        <ion-item>
          <ion-label position="floating">Nombre de la Cerveza</ion-label>
          <ion-input v-model="prod.name"></ion-input>
        </ion-item>
        <ion-item>
          <ion-label position="floating">URL de la Imagen (Link externo)</ion-label>
          <ion-input type="url" v-model="prod.image_url" @ionInput="prod.image_url = ($event.target as any).value" placeholder="https://ejemplo.com/imagen.jpg"></ion-input>
        </ion-item>
        <ion-item v-if="prod.image_url">
          <img :src="prod.image_url" style="max-height: 100px; margin-top: 10px;" />
        </ion-item>

        <ion-item>
          <ion-label position="stacked">Categoría</ion-label>
          <ion-select v-model="prod.category_id" interface="action-sheet" placeholder="Selecciona una categoría">
            <ion-select-option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</ion-select-option>
          </ion-select>
          <ion-button slot="end" fill="clear" @click="promptManageCategory" v-if="prod.category_id"><ion-icon :icon="settingsOutline"></ion-icon></ion-button>
          <ion-button slot="end" fill="clear" @click="promptAddCategory">+ Nueva</ion-button>
        </ion-item>

        <ion-item>
          <ion-label position="floating">Precio de Venta (Bs)</ion-label>
          <ion-input type="number" v-model="prod.precio_venta"></ion-input>
        </ion-item>

        <ion-item>
          <ion-label>Es un Paquete/Combo</ion-label>
          <ion-checkbox slot="end" v-model="isPack" @ionChange="handlePackChange"></ion-checkbox>
        </ion-item>

        <template v-if="isPack">
          <ion-item>
            <ion-label position="stacked">Producto Base (Unidad)</ion-label>
            <ion-select v-model="prod.parent_id" interface="action-sheet" placeholder="Selecciona un producto base">
              <ion-select-option v-for="p in baseProducts" :key="p.id" :value="p.id">{{ p.name }}</ion-select-option>
            </ion-select>
          </ion-item>
          <ion-item>
            <ion-label position="floating">Unidades por Paquete (Multiplicador)</ion-label>
            <ion-input type="number" v-model="prod.package_multiplier"></ion-input>
          </ion-item>
        </template>

        <!-- Initial Stock only for new products (to keep simple) -->
        <template v-if="!isEdit && !isPack">
          <ion-item>
            <ion-label position="floating">Stock Inicial</ion-label>
            <ion-input type="number" v-model="prod.stock"></ion-input>
          </ion-item>

          <ion-item>
            <ion-label position="floating">Precio de Compra Inicial (Bs)</ion-label>
            <ion-input type="number" v-model="prod.precio_compra" placeholder="Opcional"></ion-input>
          </ion-item>
          
          <ion-item>
            <ion-label position="stacked">Proveedor Inicial</ion-label>
            <ion-select v-model="prod.supplier_id" interface="action-sheet" placeholder="Opcional">
              <ion-select-option v-for="sup in suppliers" :key="sup.id" :value="sup.id">{{ sup.name }}</ion-select-option>
            </ion-select>
            <ion-button slot="end" fill="clear" @click="promptManageSupplier" v-if="prod.supplier_id"><ion-icon :icon="settingsOutline"></ion-icon></ion-button>
            <ion-button slot="end" fill="clear" @click="promptAddSupplier">+ Nuevo</ion-button>
          </ion-item>

          <ion-item>
            <ion-label position="stacked">Fecha de Consumo Preferente</ion-label>
            <ion-input type="date" v-model="prod.expiry_date"></ion-input>
          </ion-item>
        </template>
        <ion-item>
          <ion-label position="floating">Puntos que otorga</ion-label>
          <ion-input type="number" v-model="prod.points_reward"></ion-input>
        </ion-item>
        <ion-item>
          <ion-label position="floating">Costo en Puntos (Canje)</ion-label>
          <ion-input type="number" v-model="prod.points_cost"></ion-input>
        </ion-item>
        <ion-item>
          <ion-label position="floating">Límite por usuario (Dejar vacío = sin límite)</ion-label>
          <ion-input type="number" v-model="prod.max_quota_per_user"></ion-input>
        </ion-item>
      </ion-list>

      <ion-button expand="block" color="success" class="ion-margin-top" @click="saveProduct" :disabled="loading || fetching">
        {{ loading ? 'Guardando...' : 'Guardar Producto' }}
      </ion-button>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonList, IonItem, IonLabel, IonInput, IonButton, IonButtons, IonBackButton, IonSpinner, IonSelect, IonSelectOption, toastController, alertController, actionSheetController, IonIcon, IonCheckbox } from '@ionic/vue';
import { settingsOutline } from 'ionicons/icons';
import axios from 'axios';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();
const loading = ref(false);
const fetching = ref(false);
const isEdit = ref(false);
const isPack = ref(false);

const categories = ref<any[]>([]);
const suppliers = ref<any[]>([]);
const allProducts = ref<any[]>([]);
const baseProducts = computed(() => allProducts.value.filter(p => !p.parent_id && p.id != prod.value.id));

const prod = ref({
  id: null,
  name: '',
  category_id: null as any,
  image_url: '',
  precio_compra: '',
  precio_venta: '',
  stock: '',
  supplier_id: null as any,
  expiry_date: '',
  points_reward: '',
  points_cost: '',
  max_quota_per_user: '',
  parent_id: null as any,
  package_multiplier: ''
});

const handlePackChange = () => {
  if (!isPack.value) {
    prod.value.parent_id = null;
    prod.value.package_multiplier = '';
  }
};

const loadData = async () => {
  try {
    const [catRes, supRes, prodRes] = await Promise.all([
      axios.get('/api/categories'),
      axios.get('/api/suppliers'),
      axios.get('/api/products')
    ]);
    categories.value = catRes.data;
    suppliers.value = supRes.data;
    allProducts.value = prodRes.data;
  } catch (error) { }
};

onMounted(async () => {
  await loadData();
  
  if (route.params.id) {
    isEdit.value = true;
    fetching.value = true;
    try {
      const found = allProducts.value.find((p: any) => p.id == route.params.id);
      if (found) {
        prod.value = { 
          ...found, 
          max_quota_per_user: found.max_quota_per_user || '',
          parent_id: found.parent_id || null,
          package_multiplier: found.package_multiplier || ''
        };
        if (found.parent_id) isPack.value = true;
      }
    } catch (e) { }
    fetching.value = false;
  }
});

const saveProduct = async () => {
  loading.value = true;
  try {
    const data = {
      ...prod.value,
      max_quota_per_user: prod.value.max_quota_per_user || null,
      image_url: prod.value.image_url || null
    };

    if (isEdit.value) {
      await axios.put(`/api/products/${prod.value.id}`, data);
    } else {
      await axios.post('/api/products', data);
    }
    
    const toast = await toastController.create({
      message: isEdit.value ? 'Producto actualizado' : 'Producto creado',
      duration: 3000,
      color: 'success'
    });
    toast.present();
    router.replace('/tabs/admin/products');
  } catch (error: any) {
    const toast = await toastController.create({
      message: 'Revisa que todos los campos obligatorios estén llenos',
      duration: 3000,
      color: 'danger'
    });
    toast.present();
  } finally {
    loading.value = false;
  }
};

const promptAddCategory = async () => {
  const alert = await alertController.create({
    header: 'Nueva Categoría',
    inputs: [{ name: 'name', type: 'text', placeholder: 'Ej. Fardos' }],
    buttons: [
      { text: 'Cancelar', role: 'cancel' },
      {
        text: 'Guardar',
        handler: async (data) => {
          if (data.name) {
            await axios.post('/api/categories', { name: data.name });
            await loadData();
          }
        }
      }
    ]
  });
  await alert.present();
};

const promptManageCategory = async () => {
  if (!prod.value.category_id) return;
  const category = categories.value.find(c => c.id === prod.value.category_id);
  
  const actionSheet = await actionSheetController.create({
    header: `Opciones para ${category.name}`,
    buttons: [
      {
        text: 'Editar',
        handler: async () => {
          const alert = await alertController.create({
            header: 'Editar Categoría',
            inputs: [{ name: 'name', type: 'text', value: category.name }],
            buttons: [
              { text: 'Cancelar', role: 'cancel' },
              {
                text: 'Guardar',
                handler: async (data) => {
                  if (data.name) {
                    await axios.put(`/api/categories/${category.id}`, { name: data.name });
                    await loadData();
                  }
                }
              }
            ]
          });
          await alert.present();
        }
      },
      {
        text: 'Eliminar',
        role: 'destructive',
        handler: async () => {
          await axios.delete(`/api/categories/${category.id}`);
          prod.value.category_id = null;
          await loadData();
        }
      },
      { text: 'Cancelar', role: 'cancel' }
    ]
  });
  await actionSheet.present();
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

const promptManageSupplier = async () => {
  if (!prod.value.supplier_id) return;
  const supplier = suppliers.value.find(s => s.id === prod.value.supplier_id);
  
  const actionSheet = await actionSheetController.create({
    header: `Opciones para ${supplier.name}`,
    buttons: [
      {
        text: 'Editar',
        handler: async () => {
          const alert = await alertController.create({
            header: 'Editar Proveedor',
            inputs: [
              { name: 'name', type: 'text', value: supplier.name },
              { name: 'contact_info', type: 'text', value: supplier.contact_info || '' },
              { name: 'nit', type: 'text', value: supplier.nit || '' }
            ],
            buttons: [
              { text: 'Cancelar', role: 'cancel' },
              {
                text: 'Guardar',
                handler: async (data) => {
                  if (data.name) {
                    await axios.put(`/api/suppliers/${supplier.id}`, data);
                    await loadData();
                  }
                }
              }
            ]
          });
          await alert.present();
        }
      },
      {
        text: 'Eliminar',
        role: 'destructive',
        handler: async () => {
          await axios.delete(`/api/suppliers/${supplier.id}`);
          prod.value.supplier_id = null;
          await loadData();
        }
      },
      { text: 'Cancelar', role: 'cancel' }
    ]
  });
  await actionSheet.present();
};
</script>
