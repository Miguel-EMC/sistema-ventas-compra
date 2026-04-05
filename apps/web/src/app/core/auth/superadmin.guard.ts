import { requireRoles } from './role.guard';

export const superadminGuard = requireRoles('superadmin');
