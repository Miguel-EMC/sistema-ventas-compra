import { requireRoles } from './role.guard';

export const adminGuard = requireRoles('admin', 'superadmin');
