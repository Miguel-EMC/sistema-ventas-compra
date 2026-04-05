export interface NavigationItem {
  label: string;
  route: string;
  description: string;
  icon: string;
  requiredRoles?: readonly string[];
}

export const NAVIGATION_ITEMS: NavigationItem[] = [
  {
    label: 'Dashboard',
    route: '/dashboard',
    description: 'Resumen general del negocio y actividad del dia.',
    icon: 'dashboard',
  },
  {
    label: 'Productos',
    route: '/products',
    description: 'Catalogo comercial, categorias y stock.',
    icon: 'products',
  },
  {
    label: 'Activos',
    route: '/assets',
    description: 'Equipos e insumos internos que no se venden.',
    icon: 'assets',
  },
  {
    label: 'Clientes',
    route: '/customers',
    description: 'Base comercial para checkout, facturacion y seguimiento.',
    icon: 'customers',
  },
  {
    label: 'Caja',
    route: '/cash',
    description: 'Apertura, cierre y sesiones operativas del POS.',
    icon: 'cash',
  },
  {
    label: 'Compras',
    route: '/purchases',
    description: 'Ordenes, recepcion de mercaderia e ingreso de stock.',
    icon: 'purchases',
  },
  {
    label: 'Proveedores',
    route: '/suppliers',
    description: 'Terceros para compras, costos y abastecimiento.',
    icon: 'suppliers',
  },
  {
    label: 'Ventas',
    route: '/sales',
    description: 'POS, borradores de venta, checkout y pagos.',
    icon: 'sales',
  },
  {
    label: 'Reportes',
    route: '/reports',
    description: 'Ventas, caja, stock critico y analitica.',
    icon: 'reports',
  },
  {
    label: 'Configuracion',
    route: '/settings',
    description: 'Empresa, moneda, idioma y parametros globales.',
    icon: 'settings',
  },
  {
    label: 'Usuarios',
    route: '/users',
    description: 'Alta, edicion y control de accesos del panel.',
    icon: 'users',
    requiredRoles: ['admin', 'superadmin'],
  },
  {
    label: 'Empresas',
    route: '/companies',
    description: 'Alta y seguimiento de tenants desde superadmin.',
    icon: 'companies',
    requiredRoles: ['superadmin'],
  },
];
