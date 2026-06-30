<template>
  <ion-app>
    <ion-router-outlet></ion-router-outlet>
  </ion-app>
</template>

<script setup lang="ts">
import { IonApp, IonRouterOutlet } from '@ionic/vue';
import { authState } from './store/auth';

const colorTheme = localStorage.getItem('color-theme');
if (colorTheme === 'dark') {
  document.documentElement.classList.add('ion-palette-dark');
  document.body.classList.add('dark');
} else if (colorTheme === 'light') {
  document.documentElement.classList.remove('ion-palette-dark');
  document.body.classList.remove('dark');
} else {
  // Check system preference by default
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');
  if (prefersDark.matches) {
    document.documentElement.classList.add('ion-palette-dark');
    document.body.classList.add('dark');
  }
}
</script>
