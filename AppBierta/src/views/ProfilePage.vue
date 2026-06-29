<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar style="--background: #121212; color: #fff;">
        <ion-title class="ion-text-center" style="font-weight: 600;">Mi Perfil</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content color="light">
      <div class="profile-header">
        <ion-icon :icon="personCircleOutline" class="avatar-icon"></ion-icon>
        <h2 class="profile-name">{{ authState.user?.persona?.nombre }} {{ authState.user?.persona?.apellidos }}</h2>
        <p class="profile-email">{{ authState.user?.email }}</p>
        <ion-badge color="tertiary" style="margin-top: 5px;">{{ getRoleName() }}</ion-badge>
      </div>

      <div class="profile-cards">
        <div class="card-box">
          <div class="section-title">Cuenta</div>
          <ion-item button :detail="true" lines="none" class="no-bg-item" @click="openEditProfile">
            <ion-icon :icon="createOutline" slot="start" color="medium"></ion-icon>
            <ion-label>Editar mis datos</ion-label>
          </ion-item>
          <ion-item button :detail="true" lines="none" class="no-bg-item" @click="isChangePasswordOpen = true">
            <ion-icon :icon="lockClosedOutline" slot="start" color="medium"></ion-icon>
            <ion-label>Cambiar contraseña</ion-label>
          </ion-item>
        </div>
        
        <!-- Tools Section -->
        <div class="card-box" v-if="authState.isAdmin || authState.isEmployee" style="margin-top:20px;">
          <div class="section-title">Panel de Administración</div>

          <ion-item button :detail="true" lines="none" class="no-bg-item" @click="router.push('/admin/pos')" style="--background: #e8f5e9;">
            <ion-icon :icon="cartOutline" slot="start" color="success"></ion-icon>
            <ion-label style="font-weight: 700; color: #2e7d32;">Punto de Venta (POS)</ion-label>
          </ion-item>
          
          <ion-item button :detail="true" lines="none" class="no-bg-item" @click="router.push('/tabs/admin/products')">
            <ion-icon :icon="cubeOutline" slot="start" color="primary"></ion-icon>
            <ion-label>Gestión de Productos</ion-label>
          </ion-item>
          
          <ion-item button :detail="true" lines="none" class="no-bg-item" @click="router.push('/tabs/admin/payments')">
            <ion-icon :icon="cashOutline" slot="start" color="success"></ion-icon>
            <ion-label>Aprobación de Pagos</ion-label>
          </ion-item>
          
          <ion-item button :detail="true" lines="none" class="no-bg-item" @click="router.push('/tabs/admin/staff')" v-if="authState.isAdmin">
            <ion-icon :icon="peopleOutline" slot="start" color="tertiary"></ion-icon>
            <ion-label>Gestión de Personal</ion-label>
          </ion-item>
          
          <ion-item button :detail="true" lines="none" class="no-bg-item" @click="router.push('/tabs/admin/reports')" v-if="authState.isAdmin">
            <ion-icon :icon="pieChartOutline" slot="start" color="warning"></ion-icon>
            <ion-label>Reportes Avanzados</ion-label>
          </ion-item>

          <ion-item lines="none" class="no-bg-item" v-if="authState.isAdmin">
            <ion-icon :icon="timeOutline" slot="start" color="danger"></ion-icon>
            <ion-label>Bloquear Ventas (16:00)</ion-label>
            <ion-toggle slot="end" :checked="enforceTimeLimit" @ionChange="toggleTimeLimitSettings"></ion-toggle>
          </ion-item>
        </div>

        <div class="card-box" v-if="authState.isDelivery" style="margin-top:20px;">
          <div class="section-title">Panel de Repartidor</div>
          <ion-item button :detail="true" lines="none" class="no-bg-item" @click="router.push('/tabs/delivery')">
            <ion-icon :icon="bicycleOutline" slot="start" color="primary"></ion-icon>
            <ion-label>Mis Entregas y Pedidos</ion-label>
          </ion-item>
        </div>
        
        <div class="card-box" style="margin-top:20px; padding: 5px 15px;">
          <ion-item button lines="none" class="no-bg-item" @click="logout" style="--color: #ff4757;">
            <ion-icon :icon="logOutOutline" slot="start" color="danger"></ion-icon>
            <ion-label style="font-weight: 600;">Cerrar Sesión</ion-label>
          </ion-item>
        </div>
      </div>

      <!-- Modal Editar Perfil -->
      <ion-modal :is-open="isEditProfileOpen" @didDismiss="isEditProfileOpen = false">
        <ion-header class="ion-no-border">
          <ion-toolbar>
            <ion-title>Editar Datos</ion-title>
            <ion-buttons slot="end">
              <ion-button @click="isEditProfileOpen = false">Cerrar</ion-button>
            </ion-buttons>
          </ion-toolbar>
        </ion-header>
        <ion-content class="ion-padding" color="light">
          <div class="card-box">
            <ion-item lines="none" class="input-item">
              <ion-input label="Nombre" label-placement="floating" v-model="profileForm.nombre"></ion-input>
            </ion-item>
            <ion-item lines="none" class="input-item" style="margin-top:15px;">
              <ion-input label="Apellidos" label-placement="floating" v-model="profileForm.apellidos"></ion-input>
            </ion-item>
            <ion-item lines="none" class="input-item" style="margin-top:15px;">
              <ion-input label="CI/NIT" label-placement="floating" v-model="profileForm.ci_nit"></ion-input>
            </ion-item>
            <ion-item lines="none" class="input-item" style="margin-top:15px;">
              <ion-input label="Razón Social (Para Factura)" label-placement="floating" v-model="profileForm.razon_social"></ion-input>
            </ion-item>
            <ion-item lines="none" class="input-item" style="margin-top:15px;">
              <ion-input label="Email" label-placement="floating" type="email" v-model="profileForm.email"></ion-input>
            </ion-item>
          </div>
          <ion-button expand="block" shape="round" color="dark" class="action-btn" @click="updateProfile" :disabled="isLoading">
            <ion-spinner name="crescent" v-if="isLoading"></ion-spinner>
            <span v-else>Guardar Cambios</span>
          </ion-button>
        </ion-content>
      </ion-modal>

      <!-- Modal Cambiar Contraseña -->
      <ion-modal :is-open="isChangePasswordOpen" @didDismiss="isChangePasswordOpen = false">
        <ion-header class="ion-no-border">
          <ion-toolbar>
            <ion-title>Cambiar Contraseña</ion-title>
            <ion-buttons slot="end">
              <ion-button @click="isChangePasswordOpen = false">Cerrar</ion-button>
            </ion-buttons>
          </ion-toolbar>
        </ion-header>
        <ion-content class="ion-padding" color="light">
          <div class="card-box">
            <ion-item lines="none" class="input-item">
              <ion-input label="Contraseña Actual" label-placement="floating" type="password" v-model="passwordForm.current_password"></ion-input>
            </ion-item>
            <ion-item lines="none" class="input-item" style="margin-top:15px;">
              <ion-input label="Nueva Contraseña" label-placement="floating" type="password" v-model="passwordForm.new_password"></ion-input>
            </ion-item>
            <ion-item lines="none" class="input-item" style="margin-top:15px;">
              <ion-input label="Confirmar Contraseña" label-placement="floating" type="password" v-model="passwordForm.new_password_confirmation"></ion-input>
            </ion-item>
          </div>
          <ion-button expand="block" shape="round" color="dark" class="action-btn" @click="updatePassword" :disabled="isLoading">
            <ion-spinner name="crescent" v-if="isLoading"></ion-spinner>
            <span v-else>Actualizar Contraseña</span>
          </ion-button>
        </ion-content>
      </ion-modal>

    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonIcon, IonBadge, IonItem, IonLabel, IonModal, IonButtons, IonButton, IonInput, IonSpinner, IonSelect, IonSelectOption, toastController, IonToggle } from '@ionic/vue';
import { personCircleOutline, logOutOutline, createOutline, lockClosedOutline, cubeOutline, cashOutline, peopleOutline, pieChartOutline, bicycleOutline, timeOutline, cartOutline } from 'ionicons/icons';
import { authState } from '../store/auth';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();

const isEditProfileOpen = ref(false);
const isChangePasswordOpen = ref(false);
const isLoading = ref(false);
const enforceTimeLimit = ref(true);

const fetchTimeLimit = async () => {
  if (authState.isAdmin) {
    try {
      const res = await axios.get('/api/settings/time-limit');
      enforceTimeLimit.value = res.data.enforce_time_limit;
    } catch (e) {}
  }
};

const fetchUserProfile = async () => {
  try {
    const res = await axios.get('/api/user');
    authState.user = res.data;
    localStorage.setItem('user', JSON.stringify(res.data));
  } catch (e) {
    console.error('Error al obtener perfil:', e);
  }
};

const toggleTimeLimitSettings = async () => {
  try {
    const res = await axios.post('/api/settings/time-limit/toggle');
    enforceTimeLimit.value = res.data.enforce_time_limit;
    showToast('Límite de horario modificado', 'success');
  } catch (e) {
    showToast('Error al modificar límite', 'danger');
  }
};

const profileForm = ref({
  nombre: '',
  apellidos: '',
  ci_nit: '',
  razon_social: '',
  email: '',
  delivery_route_day: 1
});

const passwordForm = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
});

const openEditProfile = () => {
  if (authState.user) {
    profileForm.value = {
      nombre: authState.user.persona?.nombre || '',
      apellidos: authState.user.persona?.apellidos || '',
      ci_nit: authState.user.persona?.ci_nit || '',
      razon_social: authState.user.persona?.razon_social || '',
      email: authState.user.email || ''
    };
  }
  isEditProfileOpen.value = true;
};

const getRoleName = () => {
  if (authState.isAdmin) return 'Administrador';
  if (authState.isEmployee) return 'Personal Interno';
  if (authState.isDelivery) return 'Repartidor';
  return 'Cliente';
};

const showToast = async (msg: string, color: string) => {
  const t = await toastController.create({ message: msg, duration: 3000, color });
  await t.present();
};

const updateProfile = async () => {
  isLoading.value = true;
  try {
    const res = await axios.put('/api/profile', profileForm.value);
    authState.user = res.data.user; // Update local state
    await showToast('Perfil actualizado con éxito', 'success');
    isEditProfileOpen.value = false;
  } catch (error: any) {
    showToast(error.response?.data?.message || 'Error al actualizar perfil', 'danger');
  } finally {
    isLoading.value = false;
  }
};

const updatePassword = async () => {
  if (passwordForm.value.new_password !== passwordForm.value.new_password_confirmation) {
    return showToast('Las contraseñas no coinciden', 'warning');
  }

  isLoading.value = true;
  try {
    await axios.put('/api/password', passwordForm.value);
    await showToast('Contraseña actualizada con éxito', 'success');
    passwordForm.value = { current_password: '', new_password: '', new_password_confirmation: '' };
    isChangePasswordOpen.value = false;
  } catch (error: any) {
    showToast(error.response?.data?.error || 'Error al actualizar contraseña', 'danger');
  } finally {
    isLoading.value = false;
  }
};

const logout = async () => {
  try {
    await axios.post('/api/logout');
  } catch (e) {
    // Ignore
  }
  authState.logout();
};

onMounted(() => {
  fetchTimeLimit();
  fetchUserProfile();
});
</script>

<style scoped>
ion-content {
  --background: var(--ion-color-step-50, #f4f5f8);
}

.profile-header {
  background: #121212;
  color: #fff;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 20px 20px 40px;
  border-bottom-left-radius: 20px;
  border-bottom-right-radius: 20px;
}

.avatar-icon {
  font-size: 90px;
  color: #ccc;
  margin-bottom: 10px;
}

.profile-name {
  font-weight: 700;
  font-size: 1.4rem;
  margin: 0;
  text-align: center;
}

.profile-email {
  color: #aaa;
  margin: 5px 0 0 0;
  font-size: 0.95rem;
}

.profile-cards {
  padding: 20px 15px;
  margin-top: -30px;
}

.card-box {
  background: var(--ion-card-background, #fff);
  border-radius: 12px;
  padding: 10px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}

.section-title {
  font-weight: 600;
  color: var(--ion-text-color, #333);
  margin: 10px 15px;
  font-size: 0.95rem;
}

.no-bg-item {
  --background: transparent;
}

.input-item {
  border: 1px solid var(--ion-color-step-200, #e0e0e0);
  border-radius: 8px;
  --padding-start: 10px;
  --background: transparent;
}

.action-btn {
  margin: 20px 15px;
  height: 50px;
  font-weight: 600;
}
</style>
