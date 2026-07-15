import { computed } from 'vue'
import { useAuthStore } from '../stores/auth'

/**
 * Reusable composable for frontend action gating based on user role.
 * Mirrors the backend VerifyOutletAccess middleware permissions.
 *
 * Usage:
 *   const { can, isAtLeast } = usePermission()
 *   if (can('manageOrders')) ...
 *   if (can('manageUsers')) ...
 *   if (isAtLeast('manager')) ...
 */
const ROLE_HIERARCHY = {
  developer: 5,
  admin: 4,
  manager: 3,
  cashier: 2,
  kitchen: 1,
}

export function usePermission() {
  const auth = useAuthStore()

  /** Check if user's highest role is at least the given level */
  function isAtLeast(minRole) {
    const min = ROLE_HIERARCHY[minRole] ?? 0
    const actual = ROLE_HIERARCHY[auth.highestRole] ?? 0
    return actual >= min
  }

  /** Check if user can perform a specific action */
  function can(action) {
    switch (action) {
      // ── Orders ──
      case 'viewOrders':
        return isAtLeast('cashier')
      case 'createOrder':
        return isAtLeast('cashier')
      case 'voidOrder':
        return isAtLeast('cashier')
      case 'refundOrder':
        return isAtLeast('manager')  // Refund tetap manager (soal uang)
      case 'splitBill':
        return isAtLeast('cashier')
      case 'mergeBill':
        return isAtLeast('cashier')
      case 'payCash':
        return isAtLeast('cashier')

      // ── Shifts ──
      case 'manageShifts':
        return isAtLeast('cashier')

      // ── Master Data Write ──
      case 'manageProducts':
        return isAtLeast('manager')
      case 'manageCategories':
        return isAtLeast('manager')
      case 'manageTaxes':
        return isAtLeast('manager')
      case 'manageDiscounts':
        return isAtLeast('manager')
      case 'manageTables':
        return isAtLeast('manager')

      // ── Users & Outlets ──
      case 'manageUsers':
        return isAtLeast('admin')
      case 'manageOutlets':
        return isAtLeast('admin')
      case 'assignRoles':
        return isAtLeast('admin')

      // ── Reports ──
      case 'viewReports':
        return isAtLeast('manager')

      // ── Finance ──
      case 'manageWithdraw':
        return isAtLeast('admin')

      // ── Developer only ──
      case 'developerAccess':
        return auth.highestRole === 'developer'

      default:
        return false
    }
  }

  return {
    can,
    isAtLeast,
    /** Shorthand helpers */
    isDeveloper: computed(() => auth.highestRole === 'developer'),
    isAdmin: computed(() => isAtLeast('admin')),
    isManager: computed(() => isAtLeast('manager')),
    isCashier: computed(() => isAtLeast('cashier')),
  }
}
