import { ChangeDetectionStrategy, Component, computed, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { resolveApiError } from '../../core/http/resolve-api-error';
import { TenantService } from './tenant.service';
import {
  CreateTenantPayload,
  TENANT_PLAN_OPTIONS,
  TenantPlan,
  TenantProvisioning,
  TenantStatus,
  type TenantCompanyRecord,
} from './tenant.types';

@Component({
  selector: 'app-companies',
  standalone: true,
  imports: [ReactiveFormsModule],
  templateUrl: './companies.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class CompaniesComponent {
  private readonly fb = inject(FormBuilder);
  private readonly tenantService = inject(TenantService);

  protected readonly companies = signal<TenantCompanyRecord[]>([]);
  protected readonly loading = signal(true);
  protected readonly submitting = signal(false);
  protected readonly drawerOpen = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly createdAccess = signal<
    ({ companyName: string } & TenantProvisioning) | null
  >(null);

  protected readonly planOptions = TENANT_PLAN_OPTIONS;

  protected readonly totalCompanies = computed(() => this.companies().length);
  protected readonly activeCompanies = computed(
    () => this.companies().filter((company) => company.status === 'active').length,
  );
  protected readonly pendingCompanies = computed(
    () => this.companies().filter((company) => company.status === 'pending').length,
  );
  protected readonly enterpriseCompanies = computed(
    () => this.companies().filter((company) => company.plan === 'enterprise').length,
  );

  protected readonly form = this.fb.nonNullable.group({
    name: ['', [Validators.required, Validators.maxLength(120)]],
    domain: [
      '',
      [Validators.required, Validators.maxLength(80), Validators.pattern(/^[a-z0-9]+(?:-[a-z0-9]+)*$/)],
    ],
    plan: ['pro' as TenantPlan, [Validators.required]],
    admin_email: ['', [Validators.required, Validators.email, Validators.maxLength(160)]],
  });

  public constructor() {
    void this.loadCompanies();
  }

  protected async loadCompanies(): Promise<void> {
    this.loading.set(true);
    this.error.set(null);

    try {
      this.companies.set(await this.tenantService.listCompanies());
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.loading.set(false);
    }
  }

  protected openDrawer(): void {
    this.drawerOpen.set(true);
    this.error.set(null);
  }

  protected closeDrawer(): void {
    this.drawerOpen.set(false);
    this.form.reset({
      name: '',
      domain: '',
      plan: 'pro',
      admin_email: '',
    });
  }

  protected normalizeDomain(): void {
    this.form.controls.domain.setValue(this.slugify(this.form.controls.domain.getRawValue()), {
      emitEvent: false,
    });
  }

  protected async submit(): Promise<void> {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    this.submitting.set(true);
    this.error.set(null);
    this.createdAccess.set(null);

    const payload: CreateTenantPayload = {
      name: this.form.controls.name.getRawValue(),
      domain: this.slugify(this.form.controls.domain.getRawValue()),
      plan: this.form.controls.plan.getRawValue(),
      admin_email: this.form.controls.admin_email.getRawValue().trim(),
    };

    try {
      const result = await this.tenantService.createCompany(payload);
      const company = result.company;

      this.companies.update((companies) => [
        company,
        ...companies.filter((existingCompany) => existingCompany.id !== company.id),
      ]);

      this.closeDrawer();
      this.createdAccess.set({
        companyName: company.name,
        ...result.provisioning,
      });
    } catch (error) {
      this.error.set(resolveApiError(error));
    } finally {
      this.submitting.set(false);
    }
  }

  protected hasError(controlName: 'name' | 'domain' | 'plan' | 'admin_email'): boolean {
    const control = this.form.controls[controlName];

    return control.invalid && (control.touched || control.dirty);
  }

  protected fieldError(controlName: 'name' | 'domain' | 'plan' | 'admin_email'): string | null {
    const control = this.form.controls[controlName];

    if (!this.hasError(controlName)) {
      return null;
    }

    if (control.hasError('required')) {
      return 'Este campo es obligatorio.';
    }

    if (controlName === 'admin_email' && control.hasError('email')) {
      return 'Ingresa un correo valido.';
    }

    if (controlName === 'domain' && control.hasError('pattern')) {
      return 'Usa solo minusculas, numeros y guiones.';
    }

    if (control.hasError('maxlength')) {
      return 'El valor excede la longitud permitida.';
    }

    return 'Revisa este campo.';
  }

  protected planLabel(plan: TenantPlan): string {
    return (
      this.planOptions.find((option) => option.value === plan)?.label ??
      plan.charAt(0).toUpperCase() + plan.slice(1)
    );
  }

  protected statusLabel(status: TenantStatus): string {
    switch (status) {
      case 'active':
        return 'Activo';
      case 'pending':
        return 'Pendiente';
      case 'suspended':
        return 'Suspendido';
      default:
        return status;
    }
  }

  protected statusClasses(status: TenantStatus): string {
    switch (status) {
      case 'active':
        return 'border-emerald-200 bg-emerald-50 text-emerald-700';
      case 'pending':
        return 'border-amber-200 bg-amber-50 text-amber-700';
      case 'suspended':
        return 'border-rose-200 bg-rose-50 text-rose-700';
      default:
        return 'border-slate-200 bg-slate-100 text-slate-600';
    }
  }

  protected formatDate(value: string): string {
    return new Intl.DateTimeFormat('es-EC', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    }).format(new Date(value));
  }

  private slugify(value: string): string {
    return value
      .trim()
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '');
  }
}
