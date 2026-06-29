import { createRouter, createWebHistory } from '@ionic/vue-router';
import { RouteRecordRaw } from 'vue-router';
import TabsPage from '../views/TabsPage.vue'
import HomePage from '../views/HomePage.vue'
import CartPage from '../views/CartPage.vue'
import LoginPage from '../views/LoginPage.vue'
import SetupAdminPage from '../views/SetupAdminPage.vue'
import RegisterClientPage from '../views/RegisterClientPage.vue'
import AdminProductList from '../views/AdminProductList.vue'
import AdminProductForm from '../views/AdminProductForm.vue'
import AdminStaffList from '../views/AdminStaffList.vue'
import AdminStaffForm from '../views/AdminStaffForm.vue'
import LocationsPage from '../views/LocationsPage.vue'
import AddLocationPage from '../views/AddLocationPage.vue'
import DeliveryHomePage from '../views/DeliveryHomePage.vue'
import AdminPaymentsPage from '../views/AdminPaymentsPage.vue'
import ProfilePage from '../views/ProfilePage.vue'
import CheckoutPage from '../views/CheckoutPage.vue'
import CategoryProductsPage from '../views/CategoryProductsPage.vue'
import ClientOrdersPage from '../views/ClientOrdersPage.vue'
import RewardsPage from '../views/RewardsPage.vue'
import { authState } from '../store/auth';

const routes: Array<RouteRecordRaw> = [
  {
    path: '/',
    redirect: '/tabs/home'
  },
  {
    path: '/home',
    redirect: '/tabs/home'
  },
  {
    path: '/login',
    name: 'Login',
    component: LoginPage
  },
  {
    path: '/setup-admin',
    name: 'SetupAdmin',
    component: SetupAdminPage
  },
  {
    path: '/register-client',
    name: 'RegisterClient',
    component: RegisterClientPage
  },
  {
    path: '/tabs',
    component: TabsPage,
    children: [
      {
        path: '',
        redirect: '/tabs/home'
      },
      {
        path: 'home',
        name: 'Home',
        component: HomePage
      },
      {
        path: 'cart',
        name: 'Cart',
        component: CartPage,
        meta: { requiresAuth: true }
      },
      {
        path: 'orders',
        name: 'ClientOrders',
        component: ClientOrdersPage,
        meta: { requiresAuth: true }
      },
      {
        path: 'locations',
        name: 'Locations',
        component: LocationsPage,
        meta: { requiresAuth: true }
      },
      {
        path: 'admin/dashboard',
        name: 'AdminDashboard',
        component: () => import('@/views/AdminDashboard.vue'),
        meta: { requiresAuth: true, role: 'staff' }
      },
      {
        path: 'admin/products',
        name: 'AdminProductList',
        component: AdminProductList,
        meta: { requiresAuth: true, role: 'staff' }
      },
      {
        path: 'admin/staff',
        name: 'AdminStaffList',
        component: AdminStaffList,
        meta: { requiresAuth: true, role: 'admin' }
      },
      {
        path: 'admin/reports',
        name: 'AdminReports',
        component: () => import('@/views/AdminReportsPage.vue'),
        meta: { requiresAuth: true, role: 'admin' }
      },

      {
        path: 'delivery',
        name: 'DeliveryHome',
        component: DeliveryHomePage,
        meta: { requiresAuth: true, role: 'delivery' }
      },
      {
        path: 'admin/payments',
        name: 'AdminPayments',
        component: AdminPaymentsPage,
        meta: { requiresAuth: true, role: 'staff' }
      },
      {
        path: 'profile',
        name: 'Profile',
        component: ProfilePage,
        meta: { requiresAuth: true }
      }
    ]
  },
  // Formularios fuera del Tab para que ocupen toda la pantalla
  {
    path: '/admin/products/form',
    name: 'AdminProductForm',
    component: AdminProductForm,
    meta: { requiresAuth: true, role: 'staff' }
  },
  {
    path: '/admin/products/form/:id',
    name: 'AdminProductFormEdit',
    component: AdminProductForm,
    meta: { requiresAuth: true, role: 'staff' }
  },
  {
    path: '/admin/staff/form',
    name: 'AdminStaffForm',
    component: AdminStaffForm,
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/staff/form/:id',
    name: 'AdminStaffFormEdit',
    component: AdminStaffForm,
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/locations/add',
    name: 'AddLocation',
    component: AddLocationPage,
    meta: { requiresAuth: true }
  },
  {
    path: '/admin/stock',
    name: 'AdminStock',
    component: () => import('@/views/AdminStockPage.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/admin/pos',
    name: 'AdminPos',
    component: () => import('@/views/AdminPosPage.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/admin/batches',
    name: 'AdminBatches',
    component: () => import('@/views/AdminBatchesPage.vue'),
    meta: { requiresAuth: true, role: 'staff' }
  },
  {
    path: '/checkout',
    name: 'Checkout',
    component: CheckoutPage,
    meta: { requiresAuth: true }
  },
  {
    path: '/category/:id',
    name: 'CategoryProducts',
    component: CategoryProductsPage,
    meta: { requiresAuth: true }
  },
  {
    path: '/delivery/route/:id',
    name: 'DeliveryRoute',
    component: () => import('@/views/DeliveryRoutePage.vue'),
    meta: { requiresAuth: true, role: 'delivery' }
  },
  {
    path: '/rewards',
    name: 'RewardsPage',
    component: RewardsPage,
    meta: { requiresAuth: true }
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

router.beforeEach((to, from, next) => {
  const requiresAuth = to.meta.requiresAuth;
  const role = to.meta.role;

  if (requiresAuth && !authState.isAuthenticated) {
    next('/login');
  } else if (requiresAuth && authState.isAuthenticated) {
    // If no role specified, let them through (like Profile)
    if (!role) {
      next();
      return;
    }

    if (role === 'client' && !authState.isClient) {
      next('/tabs/home');
    } else if (role === 'staff' && (authState.isClient || authState.isDelivery)) {
      next('/tabs/home');
    } else if (role === 'delivery' && !authState.isDelivery) {
      next('/tabs/home');
    } else {
      next();
    }
  } else {
    // For /login redirect back if already auth
    if (to.path === '/login' && authState.isAuthenticated) {
      next('/tabs/home');
    } else {
      next();
    }
  }
})

export default router
