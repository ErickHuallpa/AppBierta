<template>
  <ion-page>
    <ion-content class="login-content" :fullscreen="true">
      <!-- Background Blobs -->
      <div class="blob blob-top"></div>
      <div class="blob blob-bottom-right"></div>
      <div class="blob blob-bottom-left"></div>

      <div class="login-container">
        <div v-if="loading" class="ion-text-center">
          <ion-spinner color="primary"></ion-spinner>
          <p style="color: #333">Verificando sistema...</p>
        </div>

        <div v-else class="login-form-wrapper">
          <div class="header-texts">
            <video src="/assets/images/videologo.mp4" autoplay loop muted playsinline class="login-logo"></video>
            <h1 class="login-title">SED</h1>
            <p class="login-subtitle">Inicia sesión para continuar</p>
          </div>

          <div class="form-card">
            <div class="inputs-group">
              <div class="input-row">
                <ion-icon :icon="personOutline" class="input-icon"></ion-icon>
                <input type="email" placeholder="Correo Electrónico" v-model="email" />
              </div>
              <div class="input-divider"></div>
              <div class="input-row">
                <ion-icon :icon="lockClosedOutline" class="input-icon"></ion-icon>
                <input type="password" placeholder="Contraseña" v-model="password" />
              </div>
            </div>
            
            <button class="submit-btn" @click="login" :disabled="loggingIn">
              <ion-spinner name="crescent" v-if="loggingIn" color="light"></ion-spinner>
              <ion-icon :icon="arrowForwardOutline" v-else></ion-icon>
            </button>
          </div>

          <div class="register-link">
            <button class="link-btn" @click="router.push('/register-client')">Registro</button>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { IonPage, IonContent, IonSpinner, toastController, onIonViewDidEnter, IonIcon } from '@ionic/vue';
import { personOutline, lockClosedOutline, arrowForwardOutline } from 'ionicons/icons';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { authState } from '../store/auth';

const router = useRouter();
const loading = ref(true);
const loggingIn = ref(false);
const email = ref('');
const password = ref('');

onIonViewDidEnter(() => {
  email.value = '';
  password.value = '';
});

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
    email.value = '';
    password.value = '';
    window.location.href = '/tabs/home';
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

<style scoped>
.login-content {
  --background: #ffffff;
}

.blob {
  position: absolute;
  border-radius: 50%;
  z-index: 0;
}

.blob-top {
  top: -15vh;
  left: -20vw;
  width: 70vw;
  height: 70vw;
  background-color: #04644c;
}

.blob-bottom-right {
  bottom: -15vh;
  right: -20vw;
  width: 60vw;
  height: 60vw;
  background-color: #000000;
}

.blob-bottom-left {
  bottom: -10vh;
  left: -30vw;
  width: 50vw;
  height: 50vw;
  background-color: #000000;
  opacity: 0.8;
}

.login-container {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  min-height: 100vh;
  padding: 30px;
}

.login-form-wrapper {
  margin-top: 20px;
}

.header-texts {
  text-align: center;
  margin-bottom: 40px;
}

.login-logo {
  max-width: 120px;
  max-height: 120px;
  margin-bottom: 10px;
  border-radius: 20px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.login-title {
  font-size: 2.5rem;
  font-weight: 700;
  color: #000;
  margin: 0;
}

.login-subtitle {
  color: #666;
  margin-top: 10px;
  font-size: 1rem;
}

.form-card {
  position: relative;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
}

.inputs-group {
  background: #fff;
  border-radius: 0 40px 40px 0;
  box-shadow: 0 10px 30px rgba(0,0,0,0.1);
  width: 90%; 
  padding: 10px 40px 10px 0;
  margin-left: -30px; 
  padding-left: 50px;
}

.input-row {
  display: flex;
  align-items: center;
  padding: 15px 0;
}

.input-icon {
  font-size: 1.2rem;
  color: #555;
  margin-right: 15px;
}

.input-row input {
  border: none;
  outline: none;
  width: 100%;
  font-size: 1rem;
  color: #333;
  background: transparent;
}

.input-row input::placeholder {
  color: #999;
}

.input-divider {
  height: 1px;
  background: #f0f0f0;
  width: 100%;
}

.submit-btn {
  position: absolute;
  right: 0;
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background-color: #04644c;
  color: #fff;
  border: none;
  box-shadow: 0 5px 15px rgba(4, 100, 76, 0.4);
  display: flex;
  justify-content: center;
  align-items: center;
  font-size: 1.5rem;
  z-index: 2;
  cursor: pointer;
}

.submit-btn:disabled {
  opacity: 0.7;
}

.register-link {
  margin-top: 60px;
  text-align: left;
}

.link-btn {
  background: #fff;
  border: none;
  color: #04644c;
  font-size: 1rem;
  font-weight: bold;
  padding: 12px 30px;
  border-radius: 30px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  cursor: pointer;
}
</style>
