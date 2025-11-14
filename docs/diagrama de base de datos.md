# Diseño de Base de Datos - KPI Dashboard Industrial

## Tabla de Contenidos
- [Resumen Ejecutivo](#resumen-ejecutivo)
- [Diagrama Entidad-Relación (DBDiagram)](#diagrama-entidad-relación-dbdiagram)
- [Descripción de Tablas](#descripción-de-tablas)
  - [Grupo 1: Gestión de Activos](#grupo-1-gestión-de-activos)
  - [Grupo 2: Ingesta de Datos](#grupo-2-ingesta-de-datos)
  - [Grupo 3: Configuración de KPI](#grupo-3-configuración-de-kpi)
  - [Grupo 4: Valores y Snapshots](#grupo-4-valores-y-snapshots)
  - [Grupo 5: Alertas y Eventos](#grupo-5-alertas-y-eventos)
  - [Grupo 6: Gestión de Usuarios](#grupo-6-gestión-de-usuarios)
  - [Grupo 7: Auditoría](#grupo-7-auditoría)
- [Ejemplos de Casos de Uso](#ejemplos-de-casos-de-uso)
- [Índices Recomendados](#índices-recomendados)
- [Políticas de Retención](#políticas-de-retención)

---

## Resumen Ejecutivo

Este diseño de base de datos soporta un sistema KPI industrial con:
- **Ingesta en tiempo real** (mediciones de sensores vía HTTP + cola de tareas).
- **Cálculo de KPIs** (agregaciones periódicas o streaming).
- **Alertas y notificaciones** (reglas condicionales con múltiples canales).
- **Auditoría completa** (trazabilidad de cambios críticos).
- **Gestión de activos** (máquinas, sensores, líneas de producción).

**Principios**:
- Tablas en **español** (según nomenclatura industrial local).
- Normalización **3FN** con constraints de integridad referencial.
- Particionado por tiempo para `medicion` y `valor_kpi` (TimescaleDB compatible).
- Blandas eliminaciones con `eliminado_en` (soft delete) donde sea aplicable.

---

## Diagrama Entidad-Relación (DBDiagram)

**📊 Copia y pega el siguiente código en [DBDiagram.io](https://dbdiagram.io) para visualizar el diagrama completo:**

```dbml
// ====================================
// GRUPO 1: GESTIÓN DE ACTIVOS
// ====================================
Table planta {
  id bigint [pk, increment]
  nombre varchar [not null, unique]
  ubicacion varchar
  pais varchar
  zona_horaria varchar [default: "'UTC'", note: "zona horaria"]
  creado_en timestamp [default: "now()"]
  actualizado_en timestamp [default: "now()"]
}

Table linea_produccion {
  id bigint [pk, increment]
  planta_id bigint [not null]
  nombre varchar [not null]
  descripcion text
  estado varchar [default: "'activa'", note: "activa, parada, mantenimiento"]
  creado_en timestamp [default: "now()"]
  actualizado_en timestamp
  eliminado_en timestamp [null, note: "soft delete"]
}

Table maquina {
  id bigint [pk, increment]
  linea_id bigint [not null]
  nombre varchar [not null]
  modelo varchar
  serie varchar [unique]
  estado varchar [default: "'operativa'", note: "operativa, parada, mantenimiento, falla"]
  locacion varchar
  creado_en timestamp [default: "now()"]
  actualizado_en timestamp
  eliminado_en timestamp [null]
}

Table sensor {
  id bigint [pk, increment]
  maquina_id bigint [not null]
  nombre varchar [not null]
  tipo_sensor varchar [not null, note: "temperatura, presion, vibracion, rpm"]
  unidad varchar [note: "C, bar, mm/s, rpm"]
  rango_min numeric
  rango_max numeric
  estado varchar [default: "'activo'", note: "activo, inactivo, offline"]
  creado_en timestamp [default: "now()"]
  actualizado_en timestamp
  eliminado_en timestamp [null]
}

Table fuente_datos {
  id bigint [pk, increment]
  sensor_id bigint [not null]
  nombre varchar
  tipo_protocolo varchar [not null, note: "HTTP, MQTT, OPC-UA"]
  url_endpoint varchar
  token_autenticacion varchar [note: "encriptado"]
  frecuencia_muestreo_ms int [default: 1000]
  activo boolean [default: true]
  creado_en timestamp [default: "now()"]
  actualizado_en timestamp
}

// ====================================
// GRUPO 2: INGESTA DE DATOS
// ====================================
Table medicion {
  id bigint [pk, increment]
  sensor_id bigint [not null]
  ts timestamp [not null, note: "timestamp sin zona"]
  valor numeric [not null]
  calidad_dato varchar [default: "'buena'", note: "buena, sospechosa, mala, faltante"]
  payload_raw jsonb [null]
  creado_en timestamp [default: "now()"]
  indexes {
    (sensor_id, ts) [type: btree]
    (ts) [type: btree]
  }
}

Table error_ingesta {
  id bigint [pk, increment]
  fuente_datos_id bigint
  codigo_error varchar
  mensaje_error text
  payload_recibido jsonb [null, note: "DLQ - Dead Letter Queue"]
  timestamp_error timestamp [default: "now()"]
}

// ====================================
// GRUPO 3: CONFIGURACIÓN DE KPI
// ====================================
Table definicion_kpi {
  id bigint [pk, increment]
  codigo varchar [unique, not null, note: "OEE, MTBF, etc"]
  nombre varchar [not null]
  descripcion text
  formula varchar [not null, note: "avg(valor), sum(valor)"]
  tipo_agregacion varchar [default: "'avg'", note: "avg, sum, max, min, count"]
  ventana_segundos int [default: 300, note: "5 minutos por defecto"]
  unidad_salida varchar [note: "unidades/h, %, ppm"]
  version int [default: 1]
  activa boolean [default: true]
  creado_en timestamp [default: "now()"]
  actualizado_en timestamp
  usuario_creador_id bigint
}

Table instancia_kpi {
  id bigint [pk, increment]
  definicion_kpi_id bigint [not null]
  maquina_id bigint [null, note: "si null, aplicar a linea/planta"]
  linea_id bigint [null]
  planta_id bigint [null]
  nombre varchar [not null]
  umbral_minimo numeric [null]
  umbral_maximo numeric [null]
  activa boolean [default: true]
  creado_en timestamp [default: "now()"]
  actualizado_en timestamp
  eliminado_en timestamp [null]
}

// ====================================
// GRUPO 4: VALORES Y SNAPSHOTS
// ====================================
Table valor_kpi {
  id bigint [pk, increment]
  instancia_kpi_id bigint [not null]
  ts timestamp [not null]
  valor numeric [not null]
  calidad varchar [default: "'buena'", note: "buena, incompleta, fuera_rango"]
  creado_en timestamp [default: "now()"]
  indexes {
    (instancia_kpi_id, ts) [type: btree]
    (ts) [type: btree]
  }
}

// ====================================
// GRUPO 5: ALERTAS Y EVENTOS
// ====================================
Table regla_alerta {
  id bigint [pk, increment]
  instancia_kpi_id bigint [not null]
  nombre varchar [not null]
  condicion varchar [not null, note: "valor > umbral_maximo"]
  canales_notificacion jsonb [not null, note: "[email, slack, sms]"]
  activa boolean [default: true]
  debounce_segundos int [default: 60, note: "evitar ruido"]
  creado_en timestamp [default: "now()"]
  actualizado_en timestamp
}

Table evento_alerta {
  id bigint [pk, increment]
  regla_alerta_id bigint [not null]
  instancia_kpi_id bigint [not null]
  ts_ocurrencia timestamp [not null]
  valor_disparador numeric
  estado varchar [default: "'abierta'", note: "abierta, reconocida, cerrada"]
  payload jsonb [null]
  creado_en timestamp [default: "now()"]
  actualizado_en timestamp
}

Table notificacion_enviada {
  id bigint [pk, increment]
  evento_alerta_id bigint [not null]
  canal varchar [not null, note: "email, slack, sms"]
  destinatario varchar
  estado_envio varchar [default: "'pendiente'", note: "pendiente, enviada, falla"]
  intentos int [default: 0, note: "reintentos con backoff exponencial"]
  error_mensaje text [null]
  ts_envio timestamp [null]
  creado_en timestamp [default: "now()"]
}

// ====================================
// GRUPO 6: GESTIÓN DE USUARIOS
// ====================================
Table usuario {
  id bigint [pk, increment]
  nombre varchar [not null]
  email varchar [unique, not null]
  password_hash varchar [not null, note: "bcrypt hasheado"]
  autenticacion_2fa_activa boolean [default: false]
  ultimo_login timestamp [null]
  activo boolean [default: true]
  creado_en timestamp [default: "now()"]
  actualizado_en timestamp
  eliminado_en timestamp [null]
}

Table rol {
  id bigint [pk, increment]
  nombre varchar [unique, not null]
  descripcion text
  creado_en timestamp [default: "now()"]
}

Table usuario_rol {
  usuario_id bigint [not null, pk]
  rol_id bigint [not null, pk]
  creado_en timestamp [default: "now()"]
}

Table permiso {
  id bigint [pk, increment]
  nombre varchar [unique, not null]
  descripcion text
  modulo varchar [not null, note: "kpi, alertas, activos, usuarios"]
  creado_en timestamp [default: "now()"]
}

Table rol_permiso {
  rol_id bigint [not null, pk]
  permiso_id bigint [not null, pk]
  creado_en timestamp [default: "now()"]
}

// ====================================
// GRUPO 7: AUDITORÍA
// ====================================
Table registro_mantenimiento {
  id bigint [pk, increment]
  maquina_id bigint [not null]
  tipo_mantenimiento varchar [not null, note: "correctivo, preventivo"]
  inicio_ts timestamp [not null]
  fin_ts timestamp [null]
  descripcion text
  usuario_id bigint
  creado_en timestamp [default: "now()"]
  actualizado_en timestamp
}

Table bitacora_auditoria {
  id bigint [pk, increment]
  usuario_id bigint
  entidad_tipo varchar [not null, note: "definicion_kpi, regla_alerta, maquina"]
  entidad_id bigint [not null]
  accion varchar [not null, note: "crear, actualizar, eliminar"]
  cambios_anteriores jsonb [null]
  cambios_nuevos jsonb [null]
  razon_cambio varchar [null]
  timestamp_cambio timestamp [default: "now()"]
  ip_origen varchar [null]
}

// ====================================
// RELACIONES (FOREIGN KEYS)
// ====================================
Ref: linea_produccion.planta_id > planta.id
Ref: maquina.linea_id > linea_produccion.id
Ref: sensor.maquina_id > maquina.id
Ref: fuente_datos.sensor_id > sensor.id
Ref: medicion.sensor_id > sensor.id
Ref: error_ingesta.fuente_datos_id > fuente_datos.id
Ref: definicion_kpi.usuario_creador_id > usuario.id
Ref: instancia_kpi.definicion_kpi_id > definicion_kpi.id
Ref: instancia_kpi.maquina_id > maquina.id
Ref: instancia_kpi.linea_id > linea_produccion.id
Ref: instancia_kpi.planta_id > planta.id
Ref: valor_kpi.instancia_kpi_id > instancia_kpi.id
Ref: regla_alerta.instancia_kpi_id > instancia_kpi.id
Ref: evento_alerta.regla_alerta_id > regla_alerta.id
Ref: evento_alerta.instancia_kpi_id > instancia_kpi.id
Ref: notificacion_enviada.evento_alerta_id > evento_alerta.id
Ref: usuario_rol.usuario_id > usuario.id
Ref: usuario_rol.rol_id > rol.id
Ref: rol_permiso.rol_id > rol.id
Ref: rol_permiso.permiso_id > permiso.id
Ref: registro_mantenimiento.maquina_id > maquina.id
Ref: registro_mantenimiento.usuario_id > usuario.id
Ref: bitacora_auditoria.usuario_id > usuario.id
```

---

## Descripción de Tablas

### GRUPO 1: Gestión de Activos

#### `planta`
Represanta la instalación o fábrica.
- **Campos clave**: `nombre`, `ubicacion`, `zona_horaria`.
- **Uso**: Agrupar múltiples líneas de producción y máquinas.

#### `linea_produccion`
Línea de producción dentro de una planta.
- **Campos clave**: `planta_id` (FK), `nombre`, `estado`.
- **Estado**: 'activa', 'parada', 'mantenimiento'.

#### `maquina`
Máquina física en una línea de producción.
- **Campos clave**: `linea_id` (FK), `nombre`, `serie` (UNIQUE), `estado`.
- **Estado**: 'operativa', 'parada', 'mantenimiento', 'falla'.
- **Soft Delete**: `eliminado_en` (no eliminar, solo marcar).

#### `sensor`
Sensor montado en una máquina (temperatura, presión, vibración, etc.).
- **Campos clave**: `maquina_id` (FK), `tipo_sensor`, `unidad`, `rango_min/max`.
- **Estado**: 'activo', 'inactivo', 'offline'.
- **Ejemplo**: Sensor de temperatura en compresor, rango 0-100°C.

#### `fuente_datos`
Fuente de datos (proveedor de lecturas) asociada a un sensor.
- **Campos clave**: `sensor_id` (FK), `tipo_protocolo` (HTTP, MQTT, OPC-UA), `url_endpoint`.
- **Token**: Encriptado, para seguridad.
- **Frecuencia**: `frecuencia_muestreo_ms` (ej: 1000 ms = 1 lectura/s).

---

### GRUPO 2: Ingesta de Datos

#### `medicion`
Mediciones/lecturas individuales de sensores (tabla de series temporales).
- **Campos clave**: `sensor_id` (FK), `ts` (timestamp), `valor`.
- **Calidad**: Flag de calidad ('buena', 'sospechosa', 'mala').
- **Payload Raw**: JSON con metadata adicional.
- **Índices críticos**: `(sensor_id, ts)`, `(ts)` para queries rápidas.
- **Particionado**: Recomendado por `ts` (ej: semanal/mensual con TimescaleDB).

#### `error_ingesta`
Registro de errores durante la ingesta de mediciones.
- **Uso**: DLQ (Dead Letter Queue) para mensajes rechazados.
- **Campos clave**: `fuente_datos_id`, `codigo_error`, `payload_recibido` (JSON).
- **Auditoría**: Revisar periódicamente para ajustar parsers o validaciones.

---

### GRUPO 3: Configuración de KPI

#### `definicion_kpi`
Define qué es un KPI (fórmula, ventana, agregación).
- **Campos clave**: 
  - `codigo`: Identificador único (ej: 'OEE', 'MTBF').
  - `formula`: Expresión de cálculo (ej: 'avg(valor)').
  - `tipo_agregacion`: 'avg', 'sum', 'max', 'min', 'count'.
  - `ventana_segundos`: Intervalo de cálculo (ej: 300 = 5 min).
  - `version`: Para tracking de cambios.
- **Usuario Creador**: Trazabilidad.

#### `instancia_kpi`
Instancia específica de un KPI en un contexto (máquina, línea, planta).
- **Campos clave**: 
  - `definicion_kpi_id` (FK).
  - `maquina_id` / `linea_id` / `planta_id` (uno debe no ser null).
  - `umbral_minimo`, `umbral_maximo`: Límites para alertas.
- **Ejemplo**: "KPI OEE para Línea 1" es una instancia de la definición "OEE".

---

### GRUPO 4: Valores y Snapshots

#### `valor_kpi`
Snapshots de valores KPI calculados (serie temporal de resultados).
- **Campos clave**: `instancia_kpi_id` (FK), `ts`, `valor`, `calidad`.
- **Índices**: `(instancia_kpi_id, ts)`, `(ts)`.
- **Particionado**: Recomendado por `ts` (tabla de series temporales).
- **Generación**: Cálculo periódico (batch o streaming) desde `medicion`.

---

### GRUPO 5: Alertas y Eventos

#### `regla_alerta`
Define condiciones para disparar alertas.
- **Campos clave**: 
  - `instancia_kpi_id` (FK).
  - `condicion`: Expresión JSON (ej: `"valor > umbral_maximo"`).
  - `canales_notificacion`: Array de canales (['email', 'slack', 'sms']).
  - `debounce_segundos`: Evitar ruido (no disparar alertas en <X segundos).
- **Evaluación**: En tiempo real (streaming) o batch periódico.

#### `evento_alerta`
Instancia de una alerta disparada.
- **Campos clave**: 
  - `regla_alerta_id` (FK).
  - `ts_ocurrencia`: Cuándo se disparó.
  - `valor_disparador`: Valor del KPI que causó la alerta.
  - `estado`: 'abierta', 'reconocida', 'cerrada'.
- **Trazabilidad**: Quién reconoció, cuándo se cerró.

#### `notificacion_enviada`
Log de notificaciones enviadas.
- **Campos clave**: 
  - `evento_alerta_id` (FK).
  - `canal`, `destinatario`.
  - `estado_envio`: 'pendiente', 'enviada', 'falla'.
  - `intentos`: Contador de reintentos.
- **Reintentos**: Queue system (Laravel) maneja reintentos con backoff exponencial.

---

### GRUPO 6: Gestión de Usuarios

#### `usuario`
Usuarios del sistema.
- **Campos clave**: `email` (UNIQUE), `password_hash`, `autenticacion_2fa_activa`.
- **Seguridad**: Passwords hasheados (bcrypt), 2FA opcional.

#### `rol`
Roles predefinidos (Admin, Operador, Supervisor, Analista).
- **Campos clave**: `nombre` (UNIQUE), `descripcion`.

#### `usuario_rol` (N:N)
Relación entre usuarios y roles.

#### `permiso`
Permisos granulares (crear_kpi, editar_alerta, ver_reportes, etc.).
- **Campos clave**: `nombre` (UNIQUE), `modulo`.

#### `rol_permiso` (N:N)
Relación entre roles y permisos.

---

### GRUPO 7: Auditoría

#### `registro_mantenimiento`
Registro de mantenimientos de máquinas.
- **Campos clave**: 
  - `maquina_id` (FK).
  - `tipo_mantenimiento`: 'correctivo', 'preventivo'.
  - `inicio_ts`, `fin_ts`.
  - `usuario_id`: Quién realizó el mantenimiento.
- **Uso**: Correlacionar con cambios en KPIs (paros planificados).

#### `bitacora_auditoria`
Log completo de cambios en entidades críticas.
- **Campos clave**: 
  - `entidad_tipo`: 'definicion_kpi', 'regla_alerta', 'maquina', 'usuario', etc.
  - `entidad_id`: ID de la entidad modificada.
  - `accion`: 'crear', 'actualizar', 'eliminar'.
  - `cambios_anteriores`, `cambios_nuevos`: JSON con diff.
  - `usuario_id`: Quién hizo el cambio.
  - `ip_origen`: De dónde vino la solicitud.
- **Compliance**: Non-repudiation, conformidad normativa.

---

## Ejemplos de Casos de Uso

### **Caso de Uso 1: Registrar Medición desde Sensor (Ingesta)**

**Flujo**:
1. Emulador/sensor envía HTTP POST a `/api/mediciones`.
2. Laravel Queue almacena el mensaje (buffering, 1 a n con redundancia).
3. Worker consume la tarea y ejecuta:

```sql
-- PASO 1: Validar fuente_datos y obtener sensor_id
SELECT s.id FROM sensor s
INNER JOIN fuente_datos fd ON s.id = fd.sensor_id
WHERE fd.id = $1 AND fd.activo = true;

-- PASO 2: Insertar medición
INSERT INTO medicion (sensor_id, ts, valor, calidad_dato, payload_raw)
VALUES ($1, '2025-11-13 14:32:45'::timestamp, 75.5, 'buena', '{"raw_payload": {...}}');

-- PASO 3: Disparar evento en Laravel Reverb (publicar a canal WebSocket)
-- Backend: MedicionRecibida::broadcast()
-- Frontend (Echo): echo.channel('mediciones').listen('MedicionRecibida', (e) => { /* actualizar UI */ });
```

**Resultado**:
- Fila insertada en `medicion`.
- Evento propagado a clientes WebSocket en tiempo real.
- Si hay error, registro en `error_ingesta` y reintento en cola.

---

### **Caso de Uso 2: Crear/Configurar KPI**

**Flujo**:
1. Admin abre formulario Blade y define KPI:
   - Nombre: "Eficiencia Global de Equipos (OEE)"
   - Fórmula: `avg(valor) * 100` (porcentaje)
   - Ventana: 300 segundos (5 min)
   - Unidad: "%"

2. Backend inserta:

```sql
-- PASO 1: Crear definición
INSERT INTO definicion_kpi 
  (codigo, nombre, descripcion, formula, tipo_agregacion, ventana_segundos, 
   unidad_salida, usuario_creador_id)
VALUES 
  ('OEE', 'Eficiencia Global de Equipos', 'OEE = Disponibilidad × Rendimiento × Calidad',
   'avg(valor) * 100', 'avg', 300, '%', $user_id);
-- Resultado: id = 1

-- PASO 2: Crear instancia para Línea 1
INSERT INTO instancia_kpi 
  (definicion_kpi_id, linea_id, nombre, umbral_minimo, umbral_maximo, activa)
VALUES 
  (1, 5, 'OEE Línea 1', 65.0, 100.0, true);
-- Resultado: id = 10
```

**Resultado**:
- Fila en `definicion_kpi` (definición reutilizable).
- Fila en `instancia_kpi` (instancia para Línea 1).

---

### **Caso de Uso 3: Evaluar KPI y Generar Snapshot**

**Flujo** (Job Laravel ejecutado cada 5 min):

```sql
-- PASO 1: Obtener instancia activa y su definición
SELECT ik.id, ik.definicion_kpi_id, dkpi.formula, dkpi.ventana_segundos, dkpi.tipo_agregacion
FROM instancia_kpi ik
INNER JOIN definicion_kpi dkpi ON ik.definicion_kpi_id = dkpi.id
WHERE ik.activa = true AND ik.linea_id = 5;

-- PASO 2: Obtener mediciones en ventana (últimos 5 min)
SELECT avg(m.valor) as resultado
FROM medicion m
INNER JOIN sensor s ON m.sensor_id = s.id
INNER JOIN maquina mq ON s.maquina_id = mq.id
WHERE mq.linea_id = 5
  AND m.ts BETWEEN (now() - interval '300 seconds') AND now();
-- Resultado: 78.5

-- PASO 3: Insertar snapshot
INSERT INTO valor_kpi (instancia_kpi_id, ts, valor, calidad)
VALUES (10, now(), 78.5, 'buena');

-- PASO 4: Disparar evento en Reverb (nuevo KPI disponible)
-- Backend: KpiCalculado::broadcast(instancia_id=10, valor=78.5)
-- Frontend (Echo): escucha y actualiza gráfico en dashboard
```

**Resultado**:
- Snapshot en `valor_kpi` para Línea 1, OEE = 78.5%.
- Dashboard en tiempo real muestra nuevo valor (via Echo + Reverb).

---

### **Caso de Uso 4: Crear Regla de Alerta y Notificar**

**Flujo**:
1. Admin crea regla:

```sql
-- PASO 1: Crear regla
INSERT INTO regla_alerta 
  (instancia_kpi_id, nombre, condicion, canales_notificacion, debounce_segundos)
VALUES 
  (10, 'OEE Crítico', 'valor < 65', '["email", "slack"]', 300);
-- Resultado: id = 3
```

2. Worker evalúa en tiempo real (subscrito a `valor_kpi`):

```sql
-- PASO 2: Nuevo snapshot llegó (78.5%), evaluar condiciones
-- SELECT * FROM valor_kpi ORDER BY id DESC LIMIT 1; -> valor = 78.5
-- ¿78.5 < 65? NO, no dispara

-- ESCENARIO: Después, nuevo snapshot llega con valor = 60%
INSERT INTO evento_alerta 
  (regla_alerta_id, instancia_kpi_id, ts_ocurrencia, valor_disparador)
VALUES 
  (3, 10, now(), 60.0);
-- Resultado: id = 100

-- PASO 3: Crear notificaciones (1 por canal)
INSERT INTO notificacion_enviada (evento_alerta_id, canal, destinatario, estado_envio)
VALUES 
  (100, 'email', 'supervisor@planta.com', 'pendiente'),
  (100, 'slack', '#alertas', 'pendiente');

-- PASO 4: Queue ejecuta SendAlertNotification::dispatch()
--   - Envía email con detalles
--   - Envía Slack message
--   - Actualiza estado_envio a 'enviada'
-- PASO 5: Frontend (Echo) notificado en tiempo real
--   - Suena alerta visual/sonora en dashboard
--   - Muestra banner con evento crítico
```

**Resultado**:
- Evento en `evento_alerta`.
- Notificaciones enviadas y registradas en `notificacion_enviada`.
- Usuarios notificados instantáneamente (Reverb + Echo).

---

### **Caso de Uso 5: Registrar Mantenimiento**

**Flujo**:
1. Técnico inicia sesión y registra mantenimiento:

```sql
-- PASO 1: Crear registro
INSERT INTO registro_mantenimiento 
  (maquina_id, tipo_mantenimiento, inicio_ts, usuario_id, descripcion)
VALUES 
  (7, 'preventivo', now(), $user_id, 
   'Cambio de aceite y filtro en compresor');
-- Resultado: id = 42

-- PASO 2: Marcar máquina como en mantenimiento (opcional)
UPDATE maquina SET estado = 'mantenimiento' WHERE id = 7;

-- PASO 3: Cuando finaliza, actualizar fin_ts
UPDATE registro_mantenimiento SET fin_ts = now() WHERE id = 42;

-- PASO 4: (Opcional) Revertir estado de máquina
UPDATE maquina SET estado = 'operativa' WHERE id = 7;

-- PASO 5: KPI evaluation puede IGNORAR mediciones de máquinas 
--         en 'mantenimiento' para no sesgar disponibilidad
```

**Resultado**:
- Trazabilidad completa del mantenimiento.
- Correlación con gaps en datos de sensores (mantenimiento ≠ falla).
- Auditoría automática en `bitacora_auditoria`.

---

### **Caso de Uso 6: Generar Informe Histórico**

**Flujo**:
1. Analista solicita reporte (Blade form):
   - Rango: 2025-11-01 a 2025-11-13
   - KPI: OEE
   - Línea: Línea 1

2. Backend ejecuta:

```sql
-- PASO 1: Query histórica
SELECT 
  vk.ts,
  vk.valor,
  vk.calidad,
  dkpi.nombre,
  ik.nombre as instancia
FROM valor_kpi vk
INNER JOIN instancia_kpi ik ON vk.instancia_kpi_id = ik.id
INNER JOIN definicion_kpi dkpi ON ik.definicion_kpi_id = dkpi.id
WHERE ik.linea_id = 5
  AND ik.nombre LIKE '%OEE%'
  AND vk.ts BETWEEN '2025-11-01' AND '2025-11-13'
ORDER BY vk.ts DESC;

-- PASO 2: Agregar estadísticas
SELECT 
  avg(valor) as promedio,
  max(valor) as maximo,
  min(valor) as minimo,
  stddev(valor) as desviacion_estandar,
  count(*) as muestras
FROM valor_kpi
WHERE instancia_kpi_id = 10
  AND ts BETWEEN '2025-11-01' AND '2025-11-13';
-- Resultado: avg=76.2%, max=95%, min=45%, stddev=8.5%, muestras=288

-- PASO 3: Correlacionar con mantenimientos
SELECT rm.* FROM registro_mantenimiento rm
INNER JOIN maquina m ON rm.maquina_id = m.id
WHERE m.linea_id = 5
  AND rm.inicio_ts BETWEEN '2025-11-01' AND '2025-11-13'
ORDER BY rm.inicio_ts;
```

3. Generar CSV/PDF y notificar al usuario (queue job asíncrono).

**Resultado**:
- Reporte completo con datos históricos, estadísticas, correlaciones con mantenimientos.
- Exportable a CSV/PDF.

---

### **Caso de Uso 7: Auditoría de Cambios**

**Flujo** (automático en Laravel con Observers):

```sql
-- EJEMPLO: Admin actualiza definición de KPI
-- Laravel Observer intercepta: definicion_kpi.updated()

INSERT INTO bitacora_auditoria 
  (usuario_id, entidad_tipo, entidad_id, accion, cambios_anteriores, cambios_nuevos, timestamp_cambio, ip_origen)
VALUES 
  ($admin_id, 'definicion_kpi', 1, 'actualizar',
   '{"ventana_segundos": 300, "umbral": 80}',
   '{"ventana_segundos": 600, "umbral": 75}',
   now(),
   '192.168.1.100');

-- RESULTADO: Auditoría completa de quién, qué, cuándo, dónde
```

---

## Índices Recomendados

```sql
-- Índices de Rendimiento (MEDICIONES)
CREATE INDEX idx_medicion_sensor_ts ON medicion(sensor_id, ts DESC);
CREATE INDEX idx_medicion_ts ON medicion(ts DESC);
CREATE INDEX idx_medicion_sensor_calidad ON medicion(sensor_id, calidad_dato);

-- Índices de Rendimiento (VALORES KPI)
CREATE INDEX idx_valor_kpi_instancia_ts ON valor_kpi(instancia_kpi_id, ts DESC);
CREATE INDEX idx_valor_kpi_ts ON valor_kpi(ts DESC);

-- Índices de Rendimiento (ALERTAS)
CREATE INDEX idx_evento_alerta_regla_ts ON evento_alerta(regla_alerta_id, ts_ocurrencia DESC);
CREATE INDEX idx_evento_alerta_estado ON evento_alerta(estado);

-- Índices de AUDITORÍA
CREATE INDEX idx_bitacora_entidad ON bitacora_auditoria(entidad_tipo, entidad_id);
CREATE INDEX idx_bitacora_usuario_ts ON bitacora_auditoria(usuario_id, timestamp_cambio DESC);
CREATE INDEX idx_bitacora_ts ON bitacora_auditoria(timestamp_cambio DESC);

-- Índices ÚNICOS (Integridad)
CREATE UNIQUE INDEX uq_definicion_kpi_codigo ON definicion_kpi(codigo);
CREATE UNIQUE INDEX uq_usuario_email ON usuario(email);
CREATE UNIQUE INDEX uq_rol_nombre ON rol(nombre);
CREATE UNIQUE INDEX uq_permiso_nombre ON permiso(nombre);
CREATE UNIQUE INDEX uq_maquina_serie ON maquina(serie);
```

---

## Políticas de Retención

### `medicion` (Table Particionada por Tiempo)
- **Retención**: 12 meses (rolling).
- **Agregación**: Después de 3 meses, comprimir a medias horarias.
- **Estrategia TimescaleDB**:
  ```sql
  SELECT add_compression_policy('medicion', INTERVAL '3 months');
  SELECT add_retention_policy('medicion', INTERVAL '12 months');
  ```

### `valor_kpi`
- **Retención**: 24 meses (snapshots son menos voluminosos).
- **Archiving**: Opcional, mover a tabla histórica anual.

### `evento_alerta`
- **Retención**: 12 meses.
- **Resolución**: Cerrar eventos después de 30 días sin reconocimiento.

### `bitacora_auditoria`
- **Retención**: Indefinida (compliance).
- **Archiving**: Anual a almacenamiento frío (S3, Azure Blob).

### `error_ingesta`
- **Retención**: 6 meses o tras resolución.
- **Revisión**: Mensual para identificar patrones.

---

## Conclusión

Este diseño proporciona:
✅ **Escalabilidad**: Particionado por tiempo, índices optimizados.  
✅ **Integridad**: Constraints, soft deletes, auditoría completa.  
✅ **Interoperabilidad**: Compatible con TimescaleDB, ClickHouse (futuro).  
✅ **Compliance**: Bitácora, non-repudiation, retención regulada.  
✅ **Realtime**: Integración con Laravel Reverb + Echo.  
✅ **Resiliencia**: Colas de tareas con reintentos, DLQ para errores.  

---

**Cómo visualizar el diagrama:**
1. Copia el código DBML de la sección "Diagrama Entidad-Relación"
2. Abre [DBDiagram.io](https://dbdiagram.io)
3. Pega el código en el editor
4. ¡Automáticamente verás el diagrama completo con todas las relaciones!
