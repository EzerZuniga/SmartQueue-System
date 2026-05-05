# Documentación de Procesos de Negocio

Este documento detalla la lógica y reglas de negocio aplicadas a los procesos críticos del sistema de colas.

## 1. Limitador para sacar Ticket (Cooldown)

Este proceso controla la frecuencia con la que un ciudadano puede obtener un turno, evitando duplicados innecesarios o saturación malintencionada del sistema.

### Flujo de Ejecución:
1.  **Verificación de Requisito**: Al seleccionar un servicio en el Kiosko, el sistema comprueba si el campo `ask_document` del servicio está activo (`true`).
2.  **Captura de Datos**: Si el servicio lo requiere, se abre un modal que solicita el documento de identidad. El sistema valida que el ingreso sea de **8 u 11 caracteres** numéricos.
3.  **Validación en Controlador**: Una vez enviado el formulario, el `TicketController` aplica la lógica de tiempo:
    *   **Código Maestro**: Si se ingresa el `kiosk_code` (configurado en ajustes), se permite la salida del ticket ignorando cualquier restricción.
    *   **Excepción Anónima**: El documento `00000000` está exento de bloqueos para permitir la atención de personas indocumentadas sin afectar a otros.
    *   **Cálculo de Cooldown**: El sistema busca el último ticket del usuario. Si el tiempo transcurrido desde su creación es menor a los `ticket_cooldown_minutes` (definidos en la configuración global), la solicitud es rechazada.

### Mensaje de Error:
En caso de incumplir el tiempo de espera, el Kiosko mostrará: *"Ya tiene un turno activo."*

---

## 2. Derivación de Tickets

La derivación permite mover a un cliente de un servicio a otro sin que pierda su tiempo de espera original y manteniendo una trazabilidad clara.

### Proceso Técnico
1.  **Cierre de Interacción Actual**: La llamada en curso se marca con el estado `transferred` (Derivado) y se registra la duración de atención parcial hasta ese momento.
2.  **Cambio de Identidad (Visual)**: Al número de ticket original se le antepone el prefijo **"D"** (ej. `F-001` → `DF-001`). Esto indica a los operadores y al público que es un ticket re-enrutado.
3.  **Mantenimiento de Antigüedad**: El campo `created_at` del ticket **no se modifica**. Esto garantiza que, al llegar al nuevo servicio, el cliente se posicione según su hora de llegada original al sistema, no según la hora de derivación.
4.  **Estado de Espera**: El ticket vuelve al estado `waiting` dentro de la cola del nuevo servicio.

---

## 3. Algoritmo de Prioridad (Cremallera / Zipper)

El sistema utiliza un algoritmo de ordenamiento intercalado para equilibrar la atención entre clientes normales y preferenciales.

### Funcionamiento
*   Cada ticket recibe una `position` matemática al ser creado.
*   **VIP/Preferencial**: Se le asignan posiciones impares (`1, 3, 5, 7...`).
*   **Normal**: Se le asignan posiciones pares (`2, 4, 6, 8...`).
*   Al llamar al "Siguiente", el operador siempre recibe el ticket con la `position` más baja, logrando un flujo de **1 Preferencial x 1 Normal** de forma automática.

### Casos Especiales
*   Si solo hay clientes de un tipo (ej. solo Normales), el orden se mantiene cronológico según su posición asignada.
*   Los servicios cuyo prefijo sea **"P"** (ej. Preferencial Directo) marcan todos sus tickets como prioridad 1 automáticamente.

---

## 4. Validación de Documentos (RENIEC)

El Kiosko integra un servicio de consulta para validar la existencia de los documentos y obtener el nombre del ciudadano.

1.  **Validación de Formato**: Se aceptan solo números de 8 (DNI) u 11 (RUC) dígitos.
2.  **Consulta Externa**: Si el documento es válido y no es una excepción (Maestro/Anónimo), se invoca al `ReniecService`.
3.  **Persistencia**: Si la consulta es exitosa, el nombre se guarda permanentemente en el campo `client_name` del ticket para futuras referencias en reportes.
