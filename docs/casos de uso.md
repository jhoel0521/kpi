# Casos de Uso - KPI Dashboard Industrial

## Índice

1. Resumen
2. Casos de Uso Priorizados
   - 1. Visualizar Dashboard en Tiempo Real
   - 2. Registrar Medición desde Sensor (Ingesta)
   - 3. Crear/Configurar KPI
   - 4. Evaluar KPI y Generar Snapshot
   - 5. Crear Regla de Alerta y Notificar
   - 6. Gestionar Activos (CRUD: Máquinas y Sensores)
   - 7. Registrar y Gestionar Mantenimiento
   - 8. Generar Informe Histórico / Exportar Datos
   - 9. Registrar Producción Detallada (Jornada y Puesta en Marcha)
   - 10. Generar Resumen de Producción y KPIs de Eficiencia
   - 11. Gestión de Usuarios y Roles
   - 12. Auditoría / Log de Cambios Críticos
5. Mapeo de Casos de Uso → Endpoints / Eventos / Consultas
6. Diagrama de Casos de Uso (PlantUML)
7. Criterios de Aceptación
8. Requisitos No Funcionales
9. Nuevas Tablas para Producción
10. Siguientes Pasos Sugeridos

## Casos de Uso - KPI Dashboard Industrial

**Versión:** 3.0 (Guía Maestra)

### Resumen

Dominio: KPI Dashboard industrial (OEE, Disponibilidad, Rendimiento, Calidad).

Alcance: Ingesta de datos, cálculo de KPIs, alertas, gestión de activos y usuarios, reportes históricos.

Tecnologías: Laravel (Backend), Blade (Frontend), Reverb (Realtime), Echo (Cliente Realtime).

### Casos de Uso Priorizados

#### 1. Visualizar Dashboard en Tiempo Real

**Actor(es):** Operador, Supervisor

**Flujo Principal:**

- Usuario abre app.
- Cliente establece conexión WebSocket con Laravel Reverb.
- Backend publica actualizaciones (mediciones, valores OEE, producción) al cliente via Laravel Echo.
- UI actualiza widgets (gráficos, últimos valores, estado máquinas, producción acumulada).

**Tablas Implicadas:** resumen_produccion, produccion_detalle, maquina.

#### 2. Registrar Medición desde Sensor (Ingesta)

**Actor(es):** Sensor/Máquina (edge)

**Flujo Principal:**

- Emulador/sensor envía mensaje por HTTP (POST /api/produccion-detalle).
- Ingestor (Controlador Laravel) valida payload (ver T2.7 FSM) y escribe en produccion_detalle.
- Ingestor publica evento ProduccionRegistrada a Reverb.

**Tablas Implicadas:** produccion_detalle.

**NFR (I4.0):** QoS en mensajería, buffering en cola, persistencia de DLQ (error_ingesta).

#### 3. Crear/Configurar KPI

**Actor(es):** Administrador

**Flujo Principal:**

- Admin crea definicion_kpi (código 'OEE', 'DISPONIBILIDAD').
- Admin crea instancia_kpi apuntando a una maquina.

**Tablas Implicadas:** definicion_kpi, instancia_kpi.

#### 4. Evaluar KPI y Generar Snapshot (Cálculo)

**Actor(es):** Sistema (Servicio Laravel)

**Flujo Principal:**

- Ocurre al finalizar una puesta_en_marcha.
- Se invoca ResumenProduccionService::generar().
- El servicio usa KpiStrategyFactory para obtener la estrategia (ej: OeeStrategy).
- OeeStrategy consulta produccion_detalle, incidencia_parada y registro_mantenimiento para calcular OEE, Disponibilidad, Rendimiento y Calidad.
- Inserta resultado en resumen_produccion.

**Nota de Arquitectura (Patrón Strategy) (v3.0):**

El campo definicion_kpi.codigo (ej: "OEE") se usará como clave en un Factory (KpiStrategyFactory).

El Factory devolverá una clase PHP específica (OeeStrategy.php) que implementa KpiStrategyInterface.

Esto permite que la lógica de cálculo compleja (como OEE) viva en código PHP mantenible, en lugar de en un string formula en la BD.

**Tablas Implicadas:** resumen_produccion, produccion_detalle, incidencia_parada, registro_mantenimiento.

#### 5. Crear Regla de Alerta y Notificar

**Actor(es):** Administrador

**Flujo Principal:**

- Admin crea regla_alerta (ej: si OEE < 70% en resumen_produccion).
- Sistema evalúa la condición.
- Si se cumple, crea evento_alerta y notifica.

**Tablas Implicadas:** regla_alerta, evento_alerta.

#### 6. Gestionar Activos (CRUD)

**Actor(es):** Administrador

**Flujo Principal:** CRUD para maquina, sensor, linea_produccion.

**Tablas Implicadas:** maquina, sensor, linea_produccion.

#### 7. Registrar Mantenimiento (Downtime Planificado)

**Actor(es):** Supervisor, Ingeniero de mantenimiento

**Flujo Principal:**

- Crear registro_mantenimiento (inicio_ts, fin_ts).
- Validación FSM: Lógica de negocio (T2.7) debe asegurar que maquina.estado se actualice a 'mantenimiento'.

**Tablas Implicadas:** registro_mantenimiento, maquina.

#### 8. Generar Informe Histórico / Exportar Datos

**Actor(es):** Analista

**Flujo Principal:**

- Usuario solicita reporte de Disponibilidad (ej: última semana).
- Backend ejecuta query (T3.7) que consulta jornada, registro_mantenimiento y incidencia_parada.
- Genera reporte (CSV/PDF).

**Tablas Implicadas:** resumen_produccion, jornada, registro_mantenimiento, incidencia_parada.

#### 9. Registrar Producción Detallada (Jornada y Puesta en Marcha)

**Actor(es):** Supervisor, Máquina

**Precondiciones:** Máquina integrada, supervisor autenticado.

**Flujo Principal:**

- Supervisor registra jornada (ej: 06:00 a 14:00).
- Máquina arranca, se crea puesta_en_marcha (ej: 06:15).
- Validación de Máquina de Estados (FSM) (v3.0):
  - El StorePuestaEnMarchaRequest (T2.7) debe validar:
    - Que maquina.estado sea 'operativa'.
    - Que jornada.estado sea 'activa'.
  - Si falla, retornar error 422 (ej: "No se puede iniciar producción, la máquina está en mantenimiento").
- Máquina envía datos, se insertan en produccion_detalle.
- Si hay cambio de operador, se registra en cambio_operador_jornada.
- Dashboard actualiza en tiempo real: producción, operador actual.

**Tablas Implicadas:** jornada, cambio_operador_jornada, puesta_en_marcha, produccion_detalle.

#### 10. Registrar Incidencia de Parada (Downtime No Planificado)

**Actor(es):** Supervisor, Operador

**Precondiciones:** Una puesta_en_marcha debe estar activa.

**Flujo Principal:**

- Máquina se detiene por falla eléctrica (10:30 AM).
- Operador/Supervisor registra en la API (POST /api/incidencias-parada).
- Validación FSM (v3.0):
  - El StoreIncidenciaParadaRequest (T2.7) debe validar:
    - Que exista una puesta_en_marcha activa.
  - (Opcional) Actualizar puesta_en_marcha.estado a 'parada_no_planificada'.
- Se crea fila en incidencia_parada (ts_inicio_parada = 10:30, motivo="Falla eléctrica").
- Máquina se repara (10:45 AM).
- Operador/Supervisor actualiza la incidencia (PATCH) con ts_fin_parada.

**Postcondición:** El registro de parada se usará para el cálculo de Disponibilidad (Caso 4).

**Tablas Implicadas:** incidencia_parada, puesta_en_marcha.

### Nuevas Tablas para Producción (v3.0)

El sistema ahora incluye capacidad completa de medición de OEE:

**Jornadas y Puestas en Marcha:**

- jornada: Define turnos (Tiempo Programado Total).
- puesta_en_marcha: Sesión de producción (Tiempo de Trabajo/Uptime).

**Mediciones de Producción:**

- produccion_detalle: Reportes granulares (Base para Calidad y Rendimiento).
- resumen_produccion: Snapshot agregado de toda una sesión para KPIs rápidos.

**Gestión de Downtime (v3.0):**

- registro_mantenimiento: Downtime Planificado.
- incidencia_parada: Downtime No Planificado (fallas, falta material, etc.).

**Auditoría de Operador:**

- cambio_operador_jornada: Trazabilidad de quién operó la máquina.

### KPIs Derivados de Producción (OEE)

- Disponibilidad: (Tiempo_Programado - Downtime_Planificado - Downtime_No_Planificado) / (Tiempo_Programado - Downtime_Planificado)
- Rendimiento: (Total_Producido / Tiempo_Operativo) / Tasa_Ideal_Produccion
- Calidad: Total_Bueno / Total_Producido
- OEE: Disponibilidad × Rendimiento × Calidad