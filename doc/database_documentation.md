# Documentación de Base de Datos

Este documento describe la estructura de la base de datos del sistema de Colas, incluyendo tablas, campos y relaciones actualizados según las migraciones vigentes.

## Índice de Tablas

- [users](#tabla-users)
- [settings](#tabla-settings)
- [call_statuses](#tabla-call_statuses)
- [services](#tabla-services)
- [tickets](#tabla-tickets)
- [calls](#tabla-calls)
- [counters](#tabla-counters)
- [counter_assignments](#tabla-counter_assignments)
- [assignment_services](#tabla-assignment_services)
- [notifications](#tabla-notifications)
- [permissions / roles](#tablas-de-permisos-spatie)

---

## Tabla: `users`

**Modelo:** `App\Models\User`

Almacena la información de los usuarios del sistema (empleados, administradores, operadores).

### Campos

| Campo | Tipo | Atributos | Descripción |
|---|---|---|---|
| id | bigint | PK, AI | Identificador único del usuario. |
| name | string | Not Null | Nombre completo del usuario. |
| email | string | Unique, Not Null | Correo electrónico para acceso. |
| email_verified_at | timestamp | Nullable | Fecha de verificación de correo. |
| password | string | Not Null | Contraseña encriptada. |
| preferences | json | Nullable | Preferencias de usuario (configuraciones personales). |
| image_path | string | Nullable | Ruta del avatar del usuario. |
| status | boolean | Default(true) | Estado del usuario (Activo/Inactivo). |
| two_factor_secret | text | Nullable | Secreto para 2FA. |
| two_factor_recovery_codes | text | Nullable | Códigos de recuperación 2FA. |
| created_at | timestamp | Nullable | Fecha de creación. |
| updated_at | timestamp | Nullable | Fecha de última actualización. |
| deleted_at | timestamp | Nullable | Fecha de borrado lógico (SoftDelete). |

---

## Tabla: `settings`

**Modelo:** `App\Models\Setting`

Almacena la configuración global del sistema y de la organización.

### Campos

| Campo | Tipo | Atributos | Descripción |
|---|---|---|---|
| id | bigint | PK, AI | Identificador único. |
| name | string | Not Null | Nombre de la organización. |
| address | text | Nullable | Dirección física. |
| email | string | Nullable | Correo de contacto. |
| phone | string | Nullable | Teléfono de contacto. |
| location | string | Nullable | Ubicación/Coordenadas (opcional). |
| logo_path | string | Nullable | Ruta del logo del sistema. |
| footer_text | string | Default('...') | Texto para el pie de página de tickets/pantallas. |
| theme_color | string | Default('#000000') | Color principal de la marca. |
| display_notification | text | Nullable | Texto de marquesina (scrolling text) en TV. |
| display_font_size | integer | Default(24) | Tamaño de fuente para la TV. |
| display_font_color | string | Default('#000000') | Color de fuente para la TV. |
| print_preview_enabled | boolean | Default(true) | Si se muestra previsualización antes de imprimir. |
| voice_enabled | boolean | Default(true) | Si el sistema de voz (TTS) está activo. |
| kiosk_token | string | Nullable | Token de seguridad para el Kiosko. |
| kiosk_code | string | Not Null, Default('1234') | Código especial/maestro para validaciones en kiosko. |
| ticket_cooldown_minutes | integer | Default(10) | Minutos de espera para sacar un nuevo ticket con el mismo DNI. |
| created_at | timestamp | Nullable | Fecha de creación. |
| updated_at | timestamp | Nullable | Fecha de última actualización. |

---

## Tabla: `call_statuses`

**Modelo:** `App\Models\CallStatuse`

Define los posibles estados de una llamada o ticket.

| Slug | Nombre | Descripción |
|---|---|---|
| `waiting` | En Espera | El ticket está en cola. |
| `calling` | Llamando | El operador está llamando al cliente. |
| `in_progress` | En Atención | El cliente está siendo atendido. |
| `completed` | Finalizado | La atención terminó con éxito. |
| `no_show` | No Presentó | El cliente no acudió al llamado. |
| `transferred` | Derivado | El ticket fue derivado a otro servicio. |

---

## Tabla: `services`

**Modelo:** `App\Models\Service`

Define los servicios o trámites disponibles.

### Campos

| Campo | Tipo | Atributos | Descripción |
|---|---|---|---|
| id | bigint | PK, AI | Identificador único. |
| name | string | Not Null | Nombre del servicio. |
| prefix | string | Not Null | Prefijo del ticket (Ej: "A"). |
| start_number | integer | Default(1) | Número inicial diario. |
| status | boolean | Default(true), Index | Estado del servicio. |
| ask_document | boolean | Default(true) | Indica si el kiosko solicita DNI para este servicio. |
| created_at | timestamp | Nullable | Fecha de creación. |
| updated_at | timestamp | Nullable | Fecha de actualización. |
| deleted_at | timestamp | Nullable | Borrado lógico. |

---

## Tabla: `tickets`

**Modelo:** `App\Models\Ticket`

Representa un turno emitido.

### Campos

| Campo | Tipo | Atributos | Descripción |
|---|---|---|---|
| id | bigint | PK, AI | Identificador único. |
| service_id | bigint | FK (services) | Servicio asociado. |
| ticket_number | string | Index | Número visual (Ej: "A-001" o "DA-001" si es derivado). |
| number | integer | Not Null | Secuencial numérico puro. |
| position | integer | Not Null | Posición matemática para el ordenamiento (Cremallera). |
| priority | integer | Default(0), Index | Prioridad (1: Preferencial / VIP). |
| call_status_id | bigint | FK (call_statuses) | Estado actual del ticket. |
| client_document | string | Not Null | Documento de identidad del cliente. |
| client_name | string | Nullable | Nombre extraído de RENIEC o ingresado. |
| client_phone | string | Nullable | Teléfono opcional. |
| client_email | string | Nullable | Email opcional. |
| created_at | timestamp | Nullable | Fecha de emisión. |

---

## Tabla: `calls`

**Modelo:** `App\Models\Call`

Registro de interacciones de los operadores con los tickets.

### Campos

| Campo | Tipo | Atributos | Descripción |
|---|---|---|---|
| id | bigint | PK, AI | Identificador único. |
| ticket_id | bigint | FK (tickets) | Ticket atendido. |
| service_id | bigint | FK (services) | Servicio (Snapshot). |
| counter_id | bigint | FK (counters) | Ventanilla asociada. |
| user_id | bigint | FK (users) | Operador asociado. |
| call_status_id | bigint | FK (call_statuses) | Estado de esta interacción. |
| token_letter | string | Nullable | Letra del ticket capturada. |
| token_number | integer | Not Null | Número del ticket capturado. |
| called_date | date | Index | Fecha del registro. |
| called_at | timestamp | Default(now) | Hora de la llamada. |
| started_at | timestamp | Nullable | Inicio real de la atención. |
| ended_at | timestamp | Nullable | Fin de la atención. |
| waiting_duration | integer | Default(0) | Segundos de espera. |
| served_duration | integer | Default(0) | Segundos de atención. |
| turn_around_duration | integer | Default(0) | Tiempo total (espera + atención). |

---

## Tabla: `counters`

**Modelo:** `App\Models\Counter`

Representa las ventanillas físicas.

### Campos

| Campo | Tipo | Atributos | Descripción |
|---|---|---|---|
| id | bigint | PK, AI | Identificador único. |
| name | string | Not Null | Nombre de la ventanilla (Ej: "Ventanilla 1"). |
| status | boolean | Default(true) | Estado operativo. |

---

## Tabla: `counter_assignments`

**Modelo:** `App\Models\CounterAssignment`

Vínculo entre operador, ventanilla y servicios durante una jornada.

### Campos

| Campo | Tipo | Atributos | Descripción |
|---|---|---|---|
| id | bigint | PK, AI | Identificador único. |
| user_id | bigint | FK (users) | Operador. |
| counter_id | bigint | FK (counters) | Ventanilla ocupada. |
| opened_at | timestamp | Not Null | Hora de inicio de sesión. |
| closed_at | timestamp | Nullable | Hora de cierre de sesión. |

---

## Tabla: `assignment_services`

Pivote para definir qué servicios atiende un operador en una sesión específica.

---

## Tabla: `notifications`

Tabla polimórfica estándar de Laravel para almacenamiento de notificaciones.

---

## Tablas de Permisos (Spatie)

Gestión de Roles y Permisos mediante el paquete `spatie/laravel-permission`.
- `roles`
- `permissions`
- `model_has_roles`
- `role_has_permissions`
- `model_has_permissions`
