<template>
  <ion-page>
    <ion-header>
      <ion-toolbar color="dark">
        <ion-buttons slot="start">
          <ion-back-button default-href="/tabs/profile" color="light" text=""></ion-back-button>
        </ion-buttons>
        <ion-title>Gestión de Personal</ion-title>
        <ion-buttons slot="end">
          <ion-button router-link="/admin/staff/form">
            <ion-icon slot="icon-only" :icon="addOutline"></ion-icon>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <div v-if="loading" class="ion-text-center">
        <ion-spinner></ion-spinner>
      </div>

      <ion-grid v-else>
        <ion-row>
          <ion-col size="12" size-md="6" size-lg="4" v-for="emp in staff" :key="emp.id">
            <ion-card>
              <ion-card-header>
                <ion-card-title>{{ emp.persona?.nombre }} {{ emp.persona?.apellidos }}</ion-card-title>
                <ion-card-subtitle>{{ emp.email }}</ion-card-subtitle>
                <div class="ion-margin-top">
                  <ion-badge :color="emp.role === 'admin' ? 'danger' : (emp.role === 'delivery' ? 'warning' : 'primary')">
                    {{ emp.role === 'admin' ? 'Administrador' : (emp.role === 'delivery' ? 'Repartidor' : 'Personal Interno') }}
                  </ion-badge>
                </div>
              </ion-card-header>
              <ion-card-content class="ion-text-right">
                <ion-button fill="clear" color="primary" @click="editStaff(emp.id)">Editar</ion-button>
                <ion-button fill="clear" color="danger" @click="deleteStaff(emp.id)">Borrar</ion-button>
              </ion-card-content>
            </ion-card>
          </ion-col>
        </ion-row>
      </ion-grid>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton, IonIcon, IonMenuButton, IonGrid, IonRow, IonCol, IonCard, IonCardHeader, IonCardTitle, IonCardSubtitle, IonCardContent, IonSpinner, toastController, alertController, IonBadge } from '@ionic/vue';
import { addOutline } from 'ionicons/icons';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const staff = ref<any[]>([]);
const loading = ref(true);

const loadStaff = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/employees');
    staff.value = res.data;
  } catch (e) { }
  loading.value = false;
};

onMounted(() => {
  loadStaff();
});

const editStaff = (id: number) => {
  router.push(`/admin/staff/form/${id}`);
};

const deleteStaff = async (id: number) => {
  const alert = await alertController.create({
    header: 'Confirmar',
    message: '¿Estás seguro de borrar este empleado?',
    buttons: [
      { text: 'Cancelar', role: 'cancel' },
      { text: 'Aceptar', role: 'confirm' }
    ]
  });
  await alert.present();
  const { role } = await alert.onDidDismiss();
  if (role !== 'confirm') return;

  try {
    await axios.delete(`/api/employees/${id}`);
    loadStaff();
    const toast = await toastController.create({
      message: 'Empleado eliminado',
      duration: 2000,
      color: 'success'
    });
    await toast.present();
  } catch (error) {
    const toast = await toastController.create({
      message: 'Error al eliminar',
      duration: 2000,
      color: 'danger'
    });
    toast.present();
  }
};
</script>
