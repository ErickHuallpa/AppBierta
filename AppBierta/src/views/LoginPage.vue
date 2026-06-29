<template>
  <ion-page>
    <ion-header>
      <ion-toolbar color="primary">
        <ion-title>Bienvenido</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <div v-if="loading" class="ion-text-center ion-margin-top">
        <ion-spinner></ion-spinner>
        <p>Verificando sistema...</p>
      </div>

      <div v-else>
        <div class="ion-text-center ion-margin-bottom">
          <h2>Distribuidora de Cervezas</h2>
          <p>Inicia sesión para continuar</p>
        </div>

        <ion-list>
          <ion-item>
            <ion-label position="floating">Correo Electrónico</ion-label>
            <ion-input type="email" v-model="email"></ion-input>
          </ion-item>

          <ion-item>
            <ion-label position="floating">Contraseña</ion-label>
            <ion-input type="password" v-model="password"></ion-input>
          </ion-item>
        </ion-list>

        <ion-button expand="block" color="primary" class="ion-margin-top" @click="login" :disabled="loggingIn">
          {{ loggingIn ? 'Ingresando...' : 'Iniciar Sesión' }}
        </ion-button>

        <div class="ion-text-center ion-margin-top">
          <p>¿No tienes cuenta?</p>
          <ion-button fill="clear" color="secondary" router-link="/register-client">
            Registrarse como Cliente
          </ion-button>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonList, IonItem, IonLabel, IonInput, IonButton, IonSpinner, toastController } from '@ionic/vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { authState } from '../store/auth';

const router = useRouter();
const loading = ref(true);
const loggingIn = ref(false);
const email = ref('');
const password = ref('');

onMounted(async () => {
  try {
    const res = await axios.get('/api/system/check');
    if (!res.data.adminExists) {
      router.push('/setup-admin');
    } else {
      loading.value = false;
    }
  } catch (e) {
    loading.value = false;
  }
});

const login = async () => {
  if (!email.value || !password.value) return;
  loggingIn.value = true;
  try {
    const res = await axios.post('/api/login', {
      email: email.value,
      password: password.value
    });
    
    authState.setAuth(res.data.access_token, res.data.user);
    
    // Redirect a Home sin importar el rol
    router.push('/home');
  } catch (error: any) {
    const toast = await toastController.create({
      message: error.response?.data?.message || 'Error al iniciar sesión',
      duration: 3000,
      color: 'danger'
    });
    toast.present();
  } finally {
    loggingIn.value = false;
  }
};
</script>
