import { Component } from '@angular/core';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-help-page',
  imports: [RouterLink],
  template: `
    <div class="min-h-screen bg-white px-6 py-10 text-slate-900 sm:px-8 lg:px-10">
      <div class="mx-auto max-w-5xl">
        <header class="mb-10 flex items-start justify-between gap-6">
          <div class="max-w-2xl space-y-3">
            <p class="text-[0.72rem] font-semibold uppercase tracking-[0.28em] text-emerald-700/80">
              Ayuda
            </p>
            <h1 class="text-4xl font-semibold tracking-tight text-slate-900">
              Soporte para el acceso al sistema
            </h1>
            <p class="text-sm leading-6 text-slate-500 sm:text-base">
              Revisa las recomendaciones principales para iniciar sesion correctamente y resolver
              problemas comunes de acceso.
            </p>
          </div>

          <a
            routerLink="/login"
            class="hidden shrink-0 rounded-full border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700 sm:inline-flex"
          >
            Volver al login
          </a>
        </header>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1.3fr)_minmax(18rem,0.9fr)]">
          <section class="rounded-[2rem] border border-slate-200 bg-white p-7 shadow-[0_30px_80px_rgba(15,23,42,0.08)] sm:p-8">
            <div class="grid gap-4 sm:grid-cols-2">
              <article class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-sm font-semibold tracking-[0.2em] text-emerald-700">
                  01
                </div>
                <h2 class="mb-2 text-base font-semibold text-slate-900">Verifica tus datos</h2>
                <p class="text-sm leading-6 text-slate-500">
                  Confirma que el usuario y la contrasena correspondan a una cuenta activa dentro
                  del sistema.
                </p>
              </article>

              <article class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-sm font-semibold tracking-[0.2em] text-emerald-700">
                  02
                </div>
                <h2 class="mb-2 text-base font-semibold text-slate-900">Revisa permisos</h2>
                <p class="text-sm leading-6 text-slate-500">
                  Si el login funciona pero no ves el modulo esperado, revisa el rol asignado al
                  usuario en la API.
                </p>
              </article>

              <article class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-sm font-semibold tracking-[0.2em] text-emerald-700">
                  03
                </div>
                <h2 class="mb-2 text-base font-semibold text-slate-900">Sesion expirada</h2>
                <p class="text-sm leading-6 text-slate-500">
                  Si vienes de una redireccion antigua o una sesion previa, recarga la pagina e
                  intenta ingresar nuevamente.
                </p>
              </article>

              <article class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-sm font-semibold tracking-[0.2em] text-emerald-700">
                  04
                </div>
                <h2 class="mb-2 text-base font-semibold text-slate-900">Validacion tecnica</h2>
                <p class="text-sm leading-6 text-slate-500">
                  Si el problema persiste, valida que la API responda y que el navegador no tenga
                  almacenamiento viejo.
                </p>
              </article>
            </div>
          </section>

          <aside class="grid gap-6">
            <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-[0_24px_60px_rgba(15,23,42,0.06)]">
              <p class="mb-3 text-sm font-semibold text-slate-900">Recomendaciones rapidas</p>
              <ul class="space-y-3 text-sm leading-6 text-slate-500">
                <li class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
                  Usa la ruta principal de acceso del sistema y evita formularios antiguos.
                </li>
                <li class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
                  Si cambiaste permisos o usuarios, cierra sesion y entra otra vez.
                </li>
                <li class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
                  Si algo falla en Docker, valida primero que web y api esten levantados.
                </li>
              </ul>
            </section>

            <section class="rounded-[2rem] border border-emerald-100 bg-emerald-50/70 p-6">
              <p class="mb-2 text-sm font-semibold text-emerald-900">Soporte funcional</p>
              <p class="text-sm leading-6 text-emerald-800/80">
                Esta pantalla resume la ayuda esencial de acceso. Si quieres, el siguiente paso es
                convertirla en un centro de soporte mas completo con preguntas frecuentes y estado
                del entorno.
              </p>
            </section>

            <a
              routerLink="/login"
              class="inline-flex items-center justify-center rounded-full border border-slate-200 px-4 py-3 text-sm font-medium text-slate-600 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700 sm:hidden"
            >
              Volver al login
            </a>
          </aside>
        </div>
      </div>
    </div>
  `,
})
export class HelpPageComponent {}
