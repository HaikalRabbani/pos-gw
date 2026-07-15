/**
 * Shared role -> badge color mapping.
 * Used by MainLayout (topbar badge) and UserManagement (role column/dialog)
 * so the two stay in sync when a role is added or its color changes.
 */
const ROLE_BADGE_CLASS = {
  developer: 'bg-purple-100 text-purple-700',
  admin: 'bg-amber-100 text-amber-700',
  manager: 'bg-blue-100 text-blue-700',
  cashier: 'bg-teal-100 text-teal-700',
  kitchen: 'bg-slate-100 text-slate-700',
}

const DEFAULT_BADGE_CLASS = 'bg-slate-100 text-slate-600'

export function roleBadgeClass(role) {
  return ROLE_BADGE_CLASS[role] || DEFAULT_BADGE_CLASS
}

const ROLE_LABEL = {
  developer: 'Developer',
  admin: 'Owner',
  manager: 'Manager',
  cashier: 'Kasir',
  kitchen: 'Dapur',
}

export function roleLabel(role) {
  return ROLE_LABEL[role] || role
}
