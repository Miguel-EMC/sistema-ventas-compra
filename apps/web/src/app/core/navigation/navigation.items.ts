export interface NavigationItem {
  label: string;
  route: string;
  description: string;
  adminOnly?: boolean;
}

export const NAVIGATION_ITEMS: NavigationItem[] = [
  {
    label: 'Dashboard',
    route: '/dashboard',
    description: 'Estado general del negocio y avance de migracion.',
  },
  {
    label: 'Productos',
    route: '/products',
    description: 'Catalogo comercial, categorias y stock.',
  },
  {
    label: 'Activos',
    route: '/assets',
    description: 'Equipos e insumos internos que no se venden.',
  },
  {
    label: 'Clientes',
    route: '/customers',
    description: 'Base comercial para checkout, facturacion y seguimiento.',
  },
  {
    label: 'Caja',
    route: '/cash',
    description: 'Apertura, cierre y sesiones operativas del POS.',
  },
  {
    label: 'Compras',
    route: '/purchases',
    description: 'Ordenes, recepcion de mercaderia e ingreso de stock.',
  },
  {
    label: 'Proveedores',
    route: '/suppliers',
    description: 'Terceros para compras, costos y abastecimiento.',
  },
  {
    label: 'Ventas',
    route: '/sales',
    description: 'POS, borradores de venta, checkout y pagos.',
  },
  {
    label: 'Reportes',
    route: '/reports',
    description: 'Ventas, caja, stock critico y analitica.',
  },
  {
    label: 'Configuracion',
    route: '/settings',
    description: 'Empresa, moneda, idioma y parametros globales.',
  },
  {
    label: 'Usuarios',
    route: '/users',
    description: 'Alta, edicion y control de accesos del panel.',
    adminOnly: true,
  },
];
