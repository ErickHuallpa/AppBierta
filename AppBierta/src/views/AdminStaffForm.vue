<template>
  <ion-page>
    <ion-header>
      <ion-toolbar color="dark">
        <ion-buttons slot="start">
          <ion-back-button default-href="/admin/staff"></ion-back-button>
        </ion-buttons>
        <ion-title>{{ isEdit ? 'Editar Empleado' : 'Nuevo Empleado' }}</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <div v-if="fetching" class="ion-text-center">
        <ion-spinner></ion-spinner>
      </div>
      <ion-list v-else>
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
          <ion-select v-model="emp.role" label="Rol del Empleado" label-placement="floating">
            <ion-select-option value="admin">Administrador Principal</ion-select-option>
            <ion-select-option value="employee">Personal Interno (Admin de Pagos, Productos, etc)</ion-select-option>
            <ion-select-option value="delivery">Repartidor (Delivery)</ion-select-option>
          </ion-select>
        </ion-item>
        <ion-item>
          <ion-label position="floating">{{ isEdit ? 'Nueva Contraseña (Opcional)' : 'Contraseña' }}</ion-label>
          <ion-input type="password" v-model="emp.password"></ion-input>
        </ion-item>
      </ion-list>

      <ion-button expand="block" color="success" class="ion-margin-top" @click="saveStaff" :disabled="loading || fetching">
        {{ loading ? 'Guardando...' : 'Guardar Empleado' }}
      </ion-button>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonList, IonItem, IonLabel, IonInput, IonButton, IonButtons, IonBackButton, IonSpinner, toastController, IonSelect, IonSelectOption } from '@ionic/vue';
import axios from 'axios';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();
const loading = ref(false);
const fetching = ref(false);
const isEdit = ref(false);

const emp = ref({
  id: null,
  nombre: '',
  apellidos: '',
  email: '',
  password: '',
  role: 'employee'
});

onMounted(async () => {
  if (route.params.id) {
    isEdit.value = true;
    fetching.value = true;
    try {
      const res = await axios.get('/api/employees');
      const found = res.data.find((e: any) => e.id == route.params.id);
      if (found) {
        emp.value = {
          id: found.id,
          nombre: found.persona?.nombre || '',
          apellidos: found.persona?.apellidos || '',
          email: found.email,
          password: '',
          role: found.role || 'employee'
        };
      }
    } catch (e) { }
    fetching.value = false;
  }
});

const saveStaff = async () => {
  loading.value = true;
  try {
    if (isEdit.value) {
      await axios.put(`/api/employees/${emp.value.id}`, emp.value);
    } else {
      await axios.post('/api/register/employee', emp.value);
    }
    
    const toast = await toastController.create({
      message: isEdit.value ? 'Empleado actualizado' : 'Empleado creado',
      duration: 3000,
      color: 'success'
    });
    toast.present();
    router.push('/admin/staff');
  } catch (error: any) {
    const toast = await toastController.create({
      message: error.response?.data?.error || 'Error al guardar (Verifica campos y email único)',
      duration: 3000,
      color: 'danger'
    });
    toast.present();
  } finally {
    loading.value = false;
  }
};
</script>
