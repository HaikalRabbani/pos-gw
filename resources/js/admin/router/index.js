import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const routes = [
  { path: '/login', name: 'Login', component: () => import('../pages/auth/Login.vue') },
  { path: '/pin-login', name: 'PinLogin', component: () => import('../pages/auth/PinLogin.vue') },
  { path: '/no-access', name: 'NoAccess', component: () => import('../pages/auth/NoAccess.vue') },
  {
    path: '/',
    component: () => import('../components/layout/MainLayout.vue'),
    meta: { requiresAuth: true, requiresAdminAccess: true },
    children: [
      { path: '', redirect: '/dashboard' },
      { path: 'dashboard', name: 'Dashboard', component: () => import('../pages/dashboard/Dashboard.vue') },
      { path: 'menu', name: 'Menu', component: () => import('../pages/menu/MenuManagement.vue') },
      { path: 'orders', name: 'Orders', component: () => import('../pages/orders/Orders.vue') },
      { path: 'shifts', name: 'Shifts', component: () => import('../pages/shifts/ShiftManagement.vue') },
      { path: 'report', name: 'Report', component: () => import('../pages/report/Report.vue') },
      { path: 'users', name: 'Users', component: () => import('../pages/users/UserManagement.vue') },
      { path: 'outlets', name: 'Outlets', component: () => import('../pages/outlets/OutletManagement.vue') },
      { path: 'discounts', name: 'Discounts', component: () => import('../pages/master/DiscountManagement.vue') },
      { path: 'taxes', name: 'Taxes', component: () => import('../pages/master/TaxManagement.vue') },
      { path: 'tables', name: 'Tables', component: () => import('../pages/tables/TableManagement.vue') },
      { path: 'withdraw', name: 'Withdraw', component: () => import('../pages/withdraw/WithdrawManagement.vue') },
      { path: 'profile', name: 'Profile', component: () => import('../pages/auth/ProfileSettings.vue') },
    ],
  },
]

const router = createRouter({ history: createWebHistory(), routes })

router.beforeEach((to, _, next) => {
  const auth = useAuthStore()

  // Must be logged in for protected routes
  if (to.meta.requiresAuth && !auth.token) return next('/login')

  // If already logged in and going to login page, redirect to dashboard
  if (to.path === '/login' && auth.token) return next('/dashboard')

  // Check admin panel access: cashier/kitchen staff cannot access admin panel
  if (to.meta.requiresAdminAccess && auth.token && !auth.canAccessAdmin) {
    return next('/no-access')
  }

  next()
})

export default router
