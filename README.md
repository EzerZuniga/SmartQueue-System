# SmartQueue System (Colas V2)

![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Vue](https://img.shields.io/badge/Vue-3-42B883?style=for-the-badge&logo=vue.js&logoColor=white)
![TypeScript](https://img.shields.io/badge/TypeScript-5.2-3178C6?style=for-the-badge&logo=typescript&logoColor=white)
![Tailwind](https://img.shields.io/badge/TailwindCSS-4-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-2ea44f?style=for-the-badge)

Plataforma integral para gestión de turnos y atención al ciudadano, construida con Laravel + Inertia + Vue.  
El sistema cubre el ciclo completo de colas: emisión de tickets, llamada por ventanilla, atención, derivación, métricas y reportes exportables, con notificaciones en tiempo real.

---

## Tabla de contenidos

1. [Características clave](#características-clave)
2. [Arquitectura y stack](#arquitectura-y-stack)
3. [Flujo de negocio](#flujo-de-negocio)
4. [Comenzar](#comenzar)
5. [Scripts disponibles](#scripts-disponibles)
6. [Configuración de entorno](#configuración-de-entorno)
7. [Tiempo real, colas y scheduler](#tiempo-real-colas-y-scheduler)
8. [Estructura del proyecto](#estructura-del-proyecto)
9. [Calidad y pruebas](#calidad-y-pruebas)
10. [Documentación funcional](#documentación-funcional)
11. [Contribución](#contribución)
12. [Licencia](#licencia)

---

## Características clave

- **Gestión operativa de turnos**: emisión de tickets por servicio, atención por ventanilla y seguimiento de estados (`waiting`, `calling`, `in_progress`, `completed`, `no_show`, `transferred`).
- **Algoritmo de prioridad tipo cremallera**: balance automático entre atención normal y preferencial.
- **Derivación de tickets**: transferencia entre servicios manteniendo antigüedad para no penalizar al ciudadano.
- **Kiosko con validaciones**: soporte de DNI/RUC, cooldown configurable y excepciones de operación.
- **Tablero y visualización pública**: pantalla de TV y dashboard con métricas de demanda/distribución.
- **Reportería y exportación**: reportes de tickets, llamadas y rendimiento con exportación a Excel.
- **Seguridad y control de acceso**: autenticación con Fortify, 2FA y RBAC con Spatie Permission.
- **Eventos en tiempo real**: notificaciones y actualizaciones operativas mediante Laravel Reverb.

## Arquitectura y stack

| Capa | Tecnologías | Descripción |
| --- | --- | --- |
| Backend | Laravel 12, PHP 8.2+, Eloquent ORM | Lógica de negocio, reglas operativas, autorización y persistencia. |
| Frontend | Vue 3, TypeScript, Inertia.js | SPA server-driven con páginas modulares y tipado fuerte. |
| UI | Tailwind CSS 4, Radix/Reka, componentes reutilizables | Interfaz administrativa moderna y consistente. |
| Tiempo real | Laravel Reverb, Broadcasting, Notifications | Actualización de llamados, tickets y alertas en vivo. |
| Datos y reportes | MySQL/PostgreSQL (compatible), Maatwebsite Excel | Registro transaccional y exportación analítica. |
| Calidad | Pest, Laravel Pint, ESLint, Prettier | Pruebas, formato y consistencia en CI/CD. |

## Flujo de negocio

1. **Emisión de ticket (kiosko)**: el ciudadano selecciona servicio y el sistema genera ticket (ej. `A-001`).
2. **En cola**: el ticket entra en espera con posición calculada por prioridad.
3. **Llamado por operador**: desde ventanilla se llama el siguiente ticket disponible.
4. **Atención**: el operador inicia y finaliza la atención, registrando tiempos.
5. **Derivación opcional**: si aplica, el ticket se transfiere a otro servicio conservando antigüedad.
6. **Cierre y analítica**: la operación queda trazada para reportes y monitoreo.

## Comenzar

### Requisitos previos

- PHP 8.2 o superior
- Composer 2.x
- Node.js 18 LTS o superior
- npm 9+ (o yarn/pnpm equivalente)
- Base de datos relacional configurada (MySQL/PostgreSQL)

### Instalación rápida

```bash
# 1) Clonar repositorio
git clone <url-del-repositorio>
cd SmartQueue-System

# 2) Dependencias
composer install
npm install

# 3) Entorno
cp .env.example .env
php artisan key:generate

# 4) Migraciones y seeders
php artisan migrate --seed
```

También puedes usar el flujo automatizado:

```bash
composer setup
```

### Desarrollo local

Terminal 1:

```bash
composer dev
```

Terminal 2 (tiempo real):

```bash
php artisan reverb:start
```

Aplicación disponible por defecto en `http://127.0.0.1:8000`.

## Scripts disponibles

### Composer

| Comando | Descripción |
| --- | --- |
| `composer setup` | Instalación y bootstrap inicial (dependencias, `.env`, key, migración y build). |
| `composer dev` | Levanta `artisan serve`, `queue:listen` y `vite` en paralelo. |
| `composer dev:ssr` | Modo SSR con procesos de servidor, cola, logs y renderer SSR. |
| `composer test` | Limpia caché de config y ejecuta pruebas. |
| `composer pint` | Formateo y estilo de código PHP. |

### npm

| Comando | Descripción |
| --- | --- |
| `npm run dev` | Servidor Vite para frontend en desarrollo. |
| `npm run build` | Build de producción. |
| `npm run build:ssr` | Build cliente + servidor SSR. |
| `npm run lint` | Linting de JS/TS/Vue con ESLint. |
| `npm run format` | Formateo de `resources/` con Prettier. |
| `npm run format:check` | Verificación de formato sin escribir cambios. |

## Configuración de entorno

El archivo `.env` controla configuración sensible y operativa:

- Conexión a base de datos
- Credenciales de broadcasting/Reverb
- Parámetros de correo/notificaciones
- Ajustes del sistema (por ejemplo, límites y comportamiento de kiosko)

Buenas prácticas:

- No versionar `.env`
- Usar secretos del proveedor CI/CD en producción
- Mantener `.env.example` actualizado con variables obligatorias

## Tiempo real, colas y scheduler

- **Broadcasting**: eventos operativos (tickets, llamadas, dashboard) se publican en tiempo real.
- **Queue worker**: requerido para procesamiento asíncrono de notificaciones y tareas relacionadas.
- **Tareas programadas**: el sistema incluye comandos de mantenimiento y automatización diaria.

Para ejecutar scheduler localmente:

```bash
php artisan schedule:work
```

## Estructura del proyecto

```text
SmartQueue-System/
├── app/
│   ├── Http/Controllers/     # Controladores de dominio (tickets, llamadas, reportes, etc.)
│   ├── Models/               # Modelos de negocio
│   ├── Events/               # Eventos de tiempo real
│   ├── Notifications/        # Notificaciones del sistema
│   ├── Policies/             # Autorización por recurso
│   └── Console/Commands/     # Comandos programables
├── database/                 # Migraciones, factories, seeders
├── resources/js/
│   ├── core/                 # Entrypoints de frontend (app/ssr)
│   ├── pages/                # Páginas Inertia (mapeadas desde backend)
│   ├── shared/               # Reutilizables: components, layouts, composables, lib, types
│   ├── routes/               # Rutas tipadas para frontend (wayfinder)
│   ├── actions/              # Actions generadas para consumo tipado
│   └── wayfinder/            # Configuración/integración de wayfinder
├── routes/                   # Definición de rutas web, settings, channels, console
├── doc/                      # Documentación funcional y base de datos
├── tests/                    # Suite de pruebas
└── README.md
```

## Calidad y pruebas

- Ejecuta `composer test` antes de abrir un PR.
- Ejecuta `composer pint`, `npm run lint` y `npm run format:check`.
- Mantén reglas de dominio en backend y UI desacoplada en frontend.
- Revisa permisos/roles en cambios que involucren rutas o acciones críticas.

## Documentación funcional

- [Procesos de negocio](./doc/procesos.md)
- [Documentación de base de datos](./doc/database_documentation.md)

## Contribución

1. Crea una rama desde `main` (`feature/...`, `fix/...`).
2. Implementa cambios con pruebas y linting en verde.
3. Documenta impacto funcional/técnico en el PR.
4. Adjunta evidencia visual si hay cambios de UI u operación.

## Licencia

Este proyecto se distribuye bajo licencia **MIT**.  
Consulta [LICENSE](./LICENSE) para más detalles.
