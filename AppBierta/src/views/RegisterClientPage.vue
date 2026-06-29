<template>
  <ion-page>
    <ion-header>
      <ion-toolbar color="primary">
        <ion-buttons slot="start">
          <ion-back-button default-href="/login"></ion-back-button>
        </ion-buttons>
        <ion-title>Registro de Cliente</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <ion-list>
          <ion-item lines="none" class="input-item">
            <ion-input label="Nombre" label-placement="floating" type="text" :value="form.nombre" maxlength="50" required @ionInput="form.nombre = $event.target.value = ($event.target.value || '').toString().replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '')"></ion-input>
          </ion-item>
          <ion-item lines="none" class="input-item">
            <ion-input label="Apellidos" label-placement="floating" type="text" :value="form.apellidos" maxlength="50" required @ionInput="form.apellidos = $event.target.value = ($event.target.value || '').toString().replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '')"></ion-input>
          </ion-item>
          <ion-item lines="none" class="input-item">
            <ion-input label="CI / NIT" label-placement="floating" type="text" :value="form.ci_nit" maxlength="20" @ionInput="form.ci_nit = $event.target.value = ($event.target.value || '').toString().replace(/[^0-9a-zA-Z\-\s]/g, '')"></ion-input>
          </ion-item>
          <ion-item lines="none" class="input-item">
            <ion-input label="Razón Social (Para Factura)" label-placement="floating" type="text" v-model="form.razon_social" maxlength="100"></ion-input>
          </ion-item>
          <ion-item lines="none" class="input-item">
            <ion-input label="Correo Electrónico" label-placement="floating" type="email" v-model="form.email" required></ion-input>
          </ion-item>
          <ion-item lines="none" class="input-item">
            <ion-select label="Día de Ruta (Entrega Gratis)" label-placement="floating" v-model="form.delivery_route_day" interface="action-sheet">
              <ion-select-option value="1">Lunes</ion-select-option>
              <ion-select-option value="2">Martes</ion-select-option>
              <ion-select-option value="3">Miércoles</ion-select-option>
              <ion-select-option value="4">Jueves</ion-select-option>
              <ion-select-option value="5">Viernes</ion-select-option>
              <ion-select-option value="6">Sábado</ion-select-option>
              <ion-select-option value="7">Domingo</ion-select-option>
            </ion-select>
          </ion-item>
          <div class="ion-padding-horizontal">
            <p style="font-size: 0.8rem; color: #666; margin-top: 0; margin-bottom: 15px;">* Tu pedido tendrá envío gratis sin importar el monto si se realiza en este día. Otros días requiere pedido mayor a 1500 Bs.</p>
          </div>
        <ion-item lines="none" class="input-item">
          <ion-input label="Contraseña" label-placement="floating" type="password" v-model="form.password" required></ion-input>
        </ion-item>
      </ion-list>

      <ion-button expand="block" color="primary" class="ion-margin-top" @click="registerClient" :disabled="loading">
        {{ loading ? 'Registrando...' : 'Registrarse' }}
      </ion-button>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonList, IonItem, IonLabel, IonInput, IonButton, IonButtons, IonBackButton, IonSelect, IonSelectOption, toastController } from '@ionic/vue';
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
  email: '',
  password: '',
  delivery_route_day: '1'
});

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
      // Tomamos el primer error de validación que retorne el backend
      const errors = error.response.data.errors;
      errorMessage = Object.values(errors)[0]?.[0] as string || errorMessage;
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
