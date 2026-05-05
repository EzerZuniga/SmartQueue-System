# Colas V2 - Sistema de Gestión de Colas

Este es un sistema de gestión de colas moderno construido con Laravel y Vue.js. Permite a las organizaciones gestionar las colas de clientes de manera eficiente, brindando una experiencia fluida tanto para el ciudadano como para el operador.

## Tecnologías Utilizadas

La aplicación se basa en un stack de tecnología moderno, separando el backend y el frontend e integrándolos con Inertia.js.

### Backend
- **PHP 8.2+**
- **Laravel 12**: El framework principal de la aplicación.
- **Laravel Reverb**: Servidor de WebSockets de alto rendimiento para comunicación en tiempo real.
- **Laravel Wayfinder**: Para la construcción automática de rutas en el frontend.
- **Spatie Permission**: Gestión de roles y permisos (RBAC).
- **Pest**: Framework de pruebas enfocado en la simplicidad.

### Frontend
- **Vue.js 3 (Composition API)**: Framework interactivo para la UI.
- **TypeScript**: Tipado estático para un código más robusto.
- **Vite**: Herramienta de compilación ultrarrápida.
- **Inertia.js**: Une Laravel y Vue sin necesidad de crear una API REST compleja.
- **Tailwind CSS**: Estilizado basado en utilidades.
- **Shadcn-Vue**: Biblioteca de componentes de alta calidad.
- **Lucide Icons**: Set de iconos consistente.

---

## Flujo General del Proyecto

El sistema gestiona el ciclo de vida completo de una atención:

1.  **Emisión de Ticket (Kiosko)**: El cliente selecciona un servicio. El sistema genera un ticket (ej. `A-001`) y calcula su posición usando un algoritmo de "Cremallera" (intercalando Normal y Preferencial).
2.  **Espera Activa**: El ticket entra en estado `waiting`. Los clientes pueden ver su turno en la pantalla de TV.
3.  **Llamada (Operador)**: El operador desde su ventanilla llama al siguiente ticket. El estado cambia a `calling` y se notifica en tiempo real vía WebSockets.
4.  **Atención**: Una vez el cliente llega, el operador inicia la atención (`in_progress`).
5.  **Derivación (Opcional)**: Si el trámite requiere otro área, el operador puede **Derivar** el ticket. El ticket mantiene su antigüedad original pero se le antepone una **"D"** (ej. `DA-001`) para identificarlo como derivado en el nuevo servicio.
6.  **Finalización**: La atención se marca como `completed`, registrando tiempos de espera y atención para analítica.

---

## Instalación y Ejecución

### 1. Requisitos Previos
- PHP 8.2 o superior
- Node.js & NPM
- Composer

### 2. Configuración Inicial
```bash
# Clonar y entrar al proyecto
git clone <url-del-repositorio>
cd colas

# Instalar dependencias
composer install
npm install

# Configurar entorno
cp .env.example .env
php artisan key:generate
```
*Configura tu base de datos y credenciales de Reverb en el `.env`.*

### 3. Base de Datos
```bash
php artisan migrate --seed
```

### 4. Ejecución en Desarrollo
Para poner en marcha el sistema, necesitas ejecutar dos procesos principales en terminales separadas:

**Terminal 1 (Servidor y Vite):**
```bash
composer dev
```
*(Este comando levanta automáticamente `php artisan serve`, `queue:listen` y `vite`)*

**Terminal 2 (WebSockets):**
```bash
php artisan reverb:start
```

---

## Comandos Útiles de Calidad

Para mantener el código limpio y funcional, utiliza estos comandos antes de realizar un commit:

### Estilo y Calidad de Código
- **PHP Linting**: `composer pint` (Aplica el estilo estándar de Laravel).
- **JS/Vue Linting**: `npm run lint` o `npm run format`.

### Pruebas y Compilación
- **Ejecutar Tests**: `php artisan test` o `composer test`.
- **Compilar para Producción**: `npm run build`.

---

## Documentación Adicional
- [Documentación de Base de Datos](./doc/database_documentation.md)
- [Guía de Roles y Permisos](./doc/roles-permisos.md)