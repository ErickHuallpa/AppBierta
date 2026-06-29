<template>
  <ion-page>
    <ion-header>
      <ion-toolbar color="dark">
        <ion-title>Configuración Inicial</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <div class="ion-text-center ion-margin-bottom">
        <h2>Crear Administrador</h2>
        <p>Parece que es la primera vez que usas el sistema. Crea la cuenta de Administrador principal.</p>
      </div>

      <ion-list>
        <ion-item>
          <ion-label position="floating">Nombre</ion-label>
          <ion-input v-model="form.nombre"></ion-input>
        </ion-item>
        <ion-item>
          <ion-label position="floating">Apellidos</ion-label>
          <ion-input v-model="form.apellidos"></ion-input>
        </ion-item>
        <ion-item>
          <ion-label position="floating">Correo Electrónico</ion-label>
          <ion-input type="email" v-model="form.email"></ion-input>
        </ion-item>
        <ion-item>
          <ion-label position="floating">Contraseña</ion-label>
          <ion-input type="password" v-model="form.password"></ion-input>
        </ion-item>
      </ion-list>

      <ion-button expand="block" color="dark" class="ion-margin-top" @click="registerAdmin" :disabled="loading">
        {{ loading ? 'Creando...' : 'Crear Administrador' }}
      </ion-button>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonList, IonItem, IonLabel, IonInput, IonButton, toastController } from '@ionic/vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { authState } from '../store/auth';

const router = useRouter();
const loading = ref(false);
const form = ref({ nombre: '', apellidos: '', email: '', password: '' });

const registerAdmin = async () => {
  if (!form.value.nombre || !form.value.email || !form.value.password) return;
  loading.value = true;
  try {
    const res = await axios.post('/api/register/admin', form.value);
    authState.setAuth(res.data.access_token, res.data.user);
    router.push('/admin');
  } catch (error: any) {
    const toast = await toastController.create({
      message: error.response?.data?.error || 'Error al crear administrador',
      duration: 3000,
      color: 'danger'
    });
    toast.present();
  } finally {
    loading.value = false;
  }
};
</script>
