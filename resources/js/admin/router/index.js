import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const routes = [
  { path: '/login', name: 'Login', component: () => import('../pages/auth/Login.vue') },
  { path: '/pin-login', name: 'PinLogin', component: () => import('../pages/auth/PinLogin.vue') },
  {
    path: '/',
    component: () => import('../components/layout/MainLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '', redirect: '/dashboard' },
      { path: 'dashboard', name: 'Dashboard', component: () => import('../pages/dashboard/Dashboard.vue') },
      { path: 'pos', name: 'POS', component: () => import('../pages/pos/PosCashier.vue') },
      { path: 'kitchen', name: 'Kitchen', component: () => import('../pages/kitchen/KitchenDisplay.vue') },
      { path: 'menu', name: 'Menu', component: () => import('../pages/menu/MenuManagement.vue') },
      { path: 'orders', name: 'Orders', component: () => import('../pages/orders/Orders.vue') },
      { path: 'report', name: 'Report', component: () => import('../pages/report/Report.vue') },
      { path: 'users', name: 'Users', component: () => import('../pages/users/UserManagement.vue') },
    ],
  },
]

const router = createRouter({ history: createWebHistory(), routes })

router.beforeEach((to, _, next) => {
  const auth = useAuthStore()
  if (to.meta.requiresAuth && !auth.token) return next('/login')
  if (to.path === '/login' && auth.token) return next('/dashboard')
  next()
})

export default router
