import { Routes } from '@angular/router';
import { adminGuard } from './core/auth/admin.guard';
import { authGuard } from './core/auth/auth.guard';
import { guestGuard } from './core/auth/guest.guard';

export const routes: Routes = [
  {
    path: 'help',
    loadComponent: () => import('./features/help/help.page').then((m) => m.HelpPageComponent),
    title: 'Ayuda | VentasPOS',
  },
  {
    path: 'login',
    loadComponent: () =>
      import('./features/auth/login.page').then((m) => m.LoginPageComponent),
    title: 'Acceso | VentasPOS',
    canActivate: [guestGuard],
  },
  {
    path: '',
    loadComponent: () =>
      import('./core/layout/app-shell.component').then((m) => m.AppShellComponent),
    canActivate: [authGuard],
    children: [
      {
        path: '',
        pathMatch: 'full',
        redirectTo: 'dashboard',
      },
      {
        path: 'dashboard',
        loadComponent: () =>
          import('./features/dashboard/dashboard.page').then((m) => m.DashboardPageComponent),
        title: 'Dashboard | VentasPOS',
      },
      {
        path: 'products',
        loadComponent: () =>
          import('./features/products/products.page').then((m) => m.ProductsPageComponent),
        title: 'Productos | VentasPOS',
      },
      {
        path: 'assets',
        loadComponent: () =>
          import('./features/assets/assets.page').then((m) => m.AssetsPageComponent),
        title: 'Activos | VentasPOS',
      },
      {
        path: 'customers',
        loadComponent: () =>
          import('./features/customers/customers.page').then((m) => m.CustomersPageComponent),
        title: 'Clientes | VentasPOS',
      },
      {
        path: 'cash',
        loadComponent: () =>
          import('./features/cash/cash.page').then((m) => m.CashPageComponent),
        title: 'Caja | VentasPOS',
      },
      {
        path: 'purchases',
        loadComponent: () =>
          import('./features/purchases/purchases.page').then((m) => m.PurchasesPageComponent),
        title: 'Compras | VentasPOS',
      },
      {
        path: 'suppliers',
        loadComponent: () =>
          import('./features/suppliers/suppliers.page').then((m) => m.SuppliersPageComponent),
        title: 'Proveedores | VentasPOS',
      },
      {
        path: 'sales',
        loadComponent: () =>
          import('./features/sales/sales.page').then((m) => m.SalesPageComponent),
        title: 'Ventas | VentasPOS',
      },
      {
        path: 'reports',
        loadComponent: () =>
          import('./features/reports/reports.page').then((m) => m.ReportsPageComponent),
        title: 'Reportes | VentasPOS',
      },
      {
        path: 'settings',
        loadComponent: () =>
          import('./features/settings/settings.page').then((m) => m.SettingsPageComponent),
        title: 'Configuracion | VentasPOS',
      },
      {
        path: 'users',
        loadComponent: () =>
          import('./features/users/users.page').then((m) => m.UsersPageComponent),
        title: 'Usuarios | VentasPOS',
        canActivate: [adminGuard],
      },
    ],
  },
  {
    path: '**',
    redirectTo: '',
  },
];
