<template>
  <ion-page>
    <ion-content class="login-content" :fullscreen="true">
      <!-- Background Blobs -->
      <div class="blob blob-top-right"></div>
      <div class="blob blob-bottom-left"></div>
      <div class="blob blob-bottom-right"></div>

      <div class="login-container">
        <!-- Back button top left -->
        <ion-button fill="clear" class="back-btn" @click="router.push('/login')">
          <ion-icon slot="icon-only" :icon="arrowBackOutline"></ion-icon>
        </ion-button>

        <div class="login-form-wrapper">
          <div class="header-texts">
            <h1 class="login-title" style="margin-bottom: 20px;">Registro</h1>
          </div>

          <div class="form-card">
            <div class="inputs-group">
              <div class="input-row">
                <ion-icon :icon="personOutline" class="input-icon"></ion-icon>
                <input type="text" placeholder="Nombre" v-model="form.nombre" @input="sanitizeNombre" />
              </div>
              <div class="input-divider"></div>
              
              <div class="input-row">
                <ion-icon :icon="personOutline" class="input-icon"></ion-icon>
                <input type="text" placeholder="Apellidos" v-model="form.apellidos" @input="sanitizeApellidos" />
              </div>
              <div class="input-divider"></div>
              
              <div class="input-row">
                <ion-icon :icon="cardOutline" class="input-icon"></ion-icon>
                <input type="text" placeholder="CI / NIT" v-model="form.ci_nit" @input="sanitizeCiNit" />
              </div>
              <div class="input-divider"></div>
              
              <div class="input-row">
                <ion-icon :icon="callOutline" class="input-icon"></ion-icon>
                <input type="tel" placeholder="Teléfono / Celular" v-model="form.telefono" />
              </div>
              <div class="input-divider"></div>
              
              <div class="input-row">
                <ion-icon :icon="businessOutline" class="input-icon"></ion-icon>
                <input type="text" placeholder="Razón Social (Factura)" v-model="form.razon_social" />
              </div>
              <div class="input-divider"></div>

              <div class="input-row">
                <ion-icon :icon="mailOutline" class="input-icon"></ion-icon>
                <input type="email" placeholder="Correo Electrónico" v-model="form.email" />
              </div>
              <div class="input-divider"></div>

              <div class="input-row">
                <ion-icon :icon="lockClosedOutline" class="input-icon"></ion-icon>
                <input type="password" placeholder="Contraseña" v-model="form.password" />
              </div>
              <div class="input-divider"></div>

              <div class="input-row">
                <ion-icon :icon="calendarOutline" class="input-icon"></ion-icon>
                <select v-model="form.delivery_route_day" class="custom-select">
                  <option value="1">Lunes (Día de Ruta)</option>
                  <option value="2">Martes (Día de Ruta)</option>
                  <option value="3">Miércoles (Día de Ruta)</option>
                  <option value="4">Jueves (Día de Ruta)</option>
                  <option value="5">Viernes (Día de Ruta)</option>
                  <option value="6">Sábado (Día de Ruta)</option>
                  <option value="7">Domingo (Día de Ruta)</option>
                </select>
              </div>
              <p class="route-hint">* Tu pedido tendrá envío gratis si se realiza este día.</p>
            </div>
            
            <button class="submit-btn" @click="registerClient" :disabled="loading">
              <ion-spinner name="crescent" v-if="loading" color="light"></ion-spinner>
              <ion-icon :icon="checkmarkOutline" v-else></ion-icon>
            </button>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { IonPage, IonContent, IonButton, IonIcon, IonSpinner, toastController } from '@ionic/vue';
import { personOutline, cardOutline, callOutline, businessOutline, mailOutline, lockClosedOutline, calendarOutline, checkmarkOutline, arrowBackOutline } from 'ionicons/icons';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { authState } from '../store/auth';

const router = useRouter();
const loading = ref(false);
const form = ref({
  nombre: '',
  apellidos: '',
  ci_nit: '',
  razon_social: '',
  telefono: '',
  email: '',
  password: '',
  delivery_route_day: '1'
});

const sanitizeNombre = (event: Event) => {
  const target = event.target as HTMLInputElement;
  form.value.nombre = target.value = target.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
};

const sanitizeApellidos = (event: Event) => {
  const target = event.target as HTMLInputElement;
  form.value.apellidos = target.value = target.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
};

const sanitizeCiNit = (event: Event) => {
  const target = event.target as HTMLInputElement;
  form.value.ci_nit = target.value = target.value.replace(/[^0-9a-zA-Z\-\s]/g, '');
};

const registerClient = async () => {
  if (!form.value.nombre || !form.value.apellidos || !form.value.email || !form.value.password) {
    const toast = await toastController.create({
      message: 'Faltan completar campos obligatorios',
      duration: 3000,
      color: 'warning'
    });
    toast.present();
    return;
  }
  loading.value = true;
  try {
    const res = await axios.post('/api/register/client', form.value);
    authState.setAuth(res.data.access_token, res.data.user);
    router.push('/home');
  } catch (error: any) {
    let errorMessage = 'Error al registrar el cliente';
    if (error.response?.data?.errors) {
      const errors = error.response.data.errors as Record<string, string[]>;
      errorMessage = (Object.values(errors)[0] as string[])?.[0] || errorMessage;
    } else if (error.response?.data?.message) {
      errorMessage = error.response.data.message;
    }

    const toast = await toastController.create({
      message: errorMessage,
      duration: 4000,
      color: 'danger'
    });
    toast.present();
  } finally {
    loading.value = false;
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

.blob-top-right {
  top: -15vh;
  right: -20vw;
  width: 70vw;
  height: 70vw;
  background-color: #04644c;
}

.blob-bottom-left {
  bottom: -15vh;
  left: -20vw;
  width: 60vw;
  height: 60vw;
  background-color: #000000;
}

.blob-bottom-right {
  bottom: -10vh;
  right: -30vw;
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
  padding: 30px;
  min-height: 100vh;
}

.back-btn {
  align-self: flex-start;
  margin-left: -15px;
  margin-top: 10px;
  --color: #333;
}

.login-form-wrapper {
  margin-top: 20px;
  padding-bottom: 50px;
}

.header-texts {
  text-align: center;
  margin-bottom: 20px;
}

.login-title {
  font-size: 2.5rem;
  font-weight: 700;
  color: #000;
  margin: 0;
}

.form-card {
  position: relative;
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
  flex-shrink: 0;
}

.input-row input, .custom-select {
  border: none;
  outline: none;
  width: 100%;
  font-size: 1rem;
  color: #333;
  background: transparent;
  font-family: inherit;
}

.custom-select {
  -webkit-appearance: none;
  -moz-appearance: none;
  appearance: none;
  color: #666;
}

.input-row input::placeholder {
  color: #999;
}

.input-divider {
  height: 1px;
  background: #f0f0f0;
  width: 100%;
}

.route-hint {
  font-size: 0.75rem;
  color: #888;
  margin-top: 10px;
  margin-bottom: 5px;
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
</style>
