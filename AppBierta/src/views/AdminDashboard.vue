<template>
  <ion-page>
    <ion-header>
      <ion-toolbar color="dark">
        <ion-title>Panel de Administración</ion-title>
        <ion-buttons slot="end">
          <ion-button @click="logout">Salir</ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <div class="ion-text-center ion-margin-bottom">
        <h2>Hola, {{ authState.user?.email }}</h2>
        <ion-badge color="primary">{{ authState.user?.role?.toUpperCase() || 'ADMIN' }}</ion-badge>
      </div>

      <div class="menu-grid">
        <ion-card button router-link="/admin/products">
          <ion-card-header>
            <ion-card-title>
              <ion-icon :icon="cubeOutline" class="ion-margin-end"></ion-icon>
              Catálogo de Productos
            </ion-card-title>
          </ion-card-header>
        </ion-card>

        <ion-card button router-link="/admin/stock">
          <ion-card-header>
            <ion-card-title>
              <ion-icon :icon="addCircleOutline" class="ion-margin-end"></ion-icon>
              Ingreso de Stock (Lotes)
            </ion-card-title>
          </ion-card-header>
        </ion-card>

        <ion-card button router-link="/admin/batches">
          <ion-card-header>
            <ion-card-title>
              <ion-icon :icon="alertCircleOutline" class="ion-margin-end"></ion-icon>
              Control de Vencimientos
            </ion-card-title>
          </ion-card-header>
        </ion-card>

        <ion-card button router-link="/admin/payments">
          <ion-card-header>
            <ion-card-title>
              <ion-icon :icon="cashOutline" class="ion-margin-end"></ion-icon>
              Validar Pagos (QR)
            </ion-card-title>
          </ion-card-header>
        </ion-card>
      </div>

      <ion-card v-if="authState.isAdmin" class="ion-margin-top">
        <ion-card-header>
          <ion-card-title>Registrar Nuevo Empleado</ion-card-title>
        </ion-card-header>
        <ion-card-content>
          <ion-list>
            <ion-item>
              <ion-label position="floating">Nombre</ion-label>
              <ion-input v-model="emp.nombre"></ion-input>
            </ion-item>
            <ion-item>
              <ion-label position="floating">Apellidos</ion-label>
              <ion-input v-model="emp.apellidos"></ion-input>
            </ion-item>
            <ion-item>
              <ion-label position="floating">Email</ion-label>
              <ion-input type="email" v-model="emp.email"></ion-input>
            </ion-item>
            <ion-item>
              <ion-label position="floating">Contraseña temporal</ion-label>
              <ion-input type="password" v-model="emp.password"></ion-input>
            </ion-item>
          </ion-list>
          <ion-button expand="block" color="dark" class="ion-margin-top" @click="registerEmployee" :disabled="loading">
            {{ loading ? 'Guardando...' : 'Crear Empleado' }}
          </ion-button>
        </ion-card-content>
      </ion-card>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonList, IonItem, IonLabel, IonInput, IonButton, IonButtons, IonCard, IonCardHeader, IonCardTitle, IonCardContent, IonBadge, IonIcon, toastController } from '@ionic/vue';
import { cubeOutline, addCircleOutline, alertCircleOutline, cashOutline } from 'ionicons/icons';
import { authState } from '../store/auth';
import axios from 'axios';

const loading = ref(false);
const emp = ref({ nombre: '', apellidos: '', email: '', password: '' });

const logout = async () => {
  await axios.post('/api/logout');
  authState.logout();
};

const registerEmployee = async () => {
  loading.value = true;
  try {
    await axios.post('/api/register/employee', emp.value);
    const toast = await toastController.create({
      message: 'Empleado creado exitosamente',
      duration: 3000,
      color: 'success'
    });
    toast.present();
    emp.value = { nombre: '', apellidos: '', email: '', password: '' };
  } catch (error: any) {
    const toast = await toastController.create({
      message: error.response?.data?.error || 'Error al registrar',
      duration: 3000,
      color: 'danger'
    });
    toast.present();
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.menu-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 10px;
}
</style>
