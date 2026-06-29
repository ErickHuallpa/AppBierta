# Sistema Distribuidora de Cervezas (Ionic + Laravel)

Este repositorio contiene el código completo del sistema, dividido en dos partes:
- **Backend**: Creado con Laravel (PHP) para la API y la base de datos MySQL.
- **Frontend / Móvil**: Creado con Ionic y Vue.js para la aplicación web y la aplicación móvil Android (APK).

---

## 🛠️ Requisitos Previos (Para tu PC)

Para que el proyecto funcione en tu computadora, debes tener instalados los siguientes programas:
1. **[XAMPP](https://www.apachefriends.org/)** (o cualquier servidor con MySQL y PHP >= 8.2).
2. **[Composer](https://getcomposer.org/)** (Gestor de paquetes de PHP).
3. **[Node.js](https://nodejs.org/)** (Se recomienda la versión LTS).
4. **[Android Studio](https://developer.android.com/studio)** (Con el SDK de Android configurado para generar el APK).

---

## ⚙️ 1. Configuración del Backend (Laravel)

Abre una terminal y sigue estos pasos:

1. Entra a la carpeta del backend:
   ```bash
   cd distribuidora-backend
   ```
2. Instala las dependencias de PHP:
   ```bash
   composer install
   ```
3. Configura el archivo de entorno:
   - Copia el archivo `.env.example` y renómbralo a `.env`.
   - Abre el `.env` y asegúrate de que la conexión a la base de datos esté así:
     ```env
     DB_CONNECTION=mysql
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_DATABASE=distribuidora_db
     DB_USERNAME=root
     DB_PASSWORD=
     ```
4. Genera la clave de la aplicación:
   ```bash
   php artisan key:generate
   ```
5. Prepara la base de datos:
   - Abre XAMPP y enciende los módulos **Apache** y **MySQL**.
   - Entra a phpMyAdmin (`http://localhost/phpmyadmin`) y **crea una base de datos** llamada `distribuidora_db` vacía.
   - Regresa a la consola y ejecuta las migraciones y seeders (esto llenará la BD con datos de prueba):
     ```bash
     php artisan migrate --seed
     ```
6. Inicia el servidor del backend:
   ```bash
   php artisan serve
   ```
   *El servidor quedará corriendo en `http://localhost:8000`. ¡No cierres esta consola!*

---

## 📱 2. Configuración del Frontend (Ionic / Vue)

Abre **otra terminal nueva** (dejando la del backend corriendo):

1. Entra a la carpeta de la app:
   ```bash
   cd AppBierta
   ```
2. Instala las dependencias de Node.js:
   ```bash
   npm install
   ```
3. Inicia la aplicación en modo desarrollo (Navegador):
   ```bash
   npm run dev
   ```
   *Puedes abrir la aplicación en tu navegador accediendo a `http://localhost:8100`.*

---

## 🚀 3. Conectar la App al Backend (Para el APK)

Cuando pruebas en tu navegador de la computadora, la app se conecta mágicamente a `http://localhost:8000`. Pero cuando instalas el **APK en un celular real**, el celular no sabe qué es "localhost". Debes indicarle a la app la IP de tu computadora o usar un túnel.

### Método Recomendado: Usar la IP de tu computadora (Red Local)
Ambos (celular y laptop) deben estar conectados al mismo WiFi.
1. Abre una consola y escribe `ipconfig`. Busca tu "Dirección IPv4" (Por ejemplo: `192.168.1.50`).
2. Ve al archivo `AppBierta/src/main.ts`.
3. Busca la línea (aprox. línea 12):
   ```typescript
   if (Capacitor.isNativePlatform()) {
     axios.defaults.baseURL = 'https://cervezas-app-1234.loca.lt'; 
   }
   ```
4. Cámbiala por la IP de tu computadora junto con el puerto 8000:
   ```typescript
   if (Capacitor.isNativePlatform()) {
     axios.defaults.baseURL = 'http://192.168.1.50:8000'; // <-- Pon tu IP real aquí
   }
   ```
5. En Laravel, para que acepte conexiones de red, en lugar de `php artisan serve` normal, debes apagar el servidor actual (Ctrl + C) y encenderlo así:
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```

---

## 📦 4. Cómo Generar el APK en Android Studio

Una vez que configuraste la IP en `main.ts`, estás listo para crear la aplicación instalable (`.apk`). En la consola (dentro de la carpeta `AppBierta`):

1. **Construye la versión final del frontend:**
   ```bash
   npm run build
   ```
2. **Sincroniza los archivos web con Android:**
   ```bash
   npx cap sync android
   ```
   *(Si te da error diciendo que la plataforma no existe, ejecuta primero `npx cap add android` y luego el sync).*
3. **Abre el proyecto en Android Studio:**
   ```bash
   npx cap open android
   ```
   *(Esto abrirá Android Studio automáticamente. Puede tardar unos minutos en cargar, verás una barra de progreso abajo a la derecha indicando que Gradle se está sincronizando).*

4. **Generar el archivo APK (Dentro de Android Studio):**
   - Espera a que termine de cargar por completo (que no haya barritas cargando ni procesos corriendo abajo a la derecha).
   - Ve al menú superior y haz clic en **Build** > **Build Bundle(s) / APK(s)** > **Build APK(s)**.
   - Empezará a compilar (puede tomar unos minutos dependiendo de la PC).
   - Cuando termine, aparecerá un mensaje emergente abajo a la derecha diciendo "Build APK(s) successfully". Haz clic en el enlace azul que dice **"locate"**.
   - Se abrirá la carpeta de Windows donde está tu archivo `app-debug.apk`. 

¡Ese archivo `.apk` es el que debes pasar por WhatsApp o cable a tu celular para instalarlo y probarlo!
