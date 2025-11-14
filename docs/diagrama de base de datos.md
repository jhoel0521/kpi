# Diseño de Base de Datos - KPI Dashboard Industrial

## Tabla de Contenidos
- [Resumen Ejecutivo](#resumen-ejecutivo)
- [Diagrama Entidad-Relación (DBDiagram)](#diagrama-entidad-relación-dbdiagram)
- [Descripción de Tablas](#descripción-de-tablas)
  - [Grupo 1: Gestión de Activos](#grupo-1-gestión-de-activos)
  - [Grupo 2: Ingesta de Datos](#grupo-2-ingesta-de-datos)
  - [Grupo 2.1: Producción y Jornadas](#grupo-21-producción-y-jornadas)
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
- **Medición de producción por jornadas** (turnos, puestas en marcha, cantidad producida/buena/fallada).
- **Control de tiempo muerto** (registro de pausas y sus causas).
- **Cálculo de KPIs** (agregaciones periódicas o streaming, incluyendo eficiencia de producción).
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
// GRUPO 2.1: PRODUCCIÓN Y JORNADAS
// ====================================
Table jornada {
  id bigint [pk, increment]
  maquina_id bigint [not null]
  nombre varchar [not null, note: "Día, Noche, Madrugada, Custom"]
  ts_inicio timestamp [not null, note: "cuándo INICIO la jornada (puede cruzar días)"]
  ts_fin timestamp [null, note: "cuándo TERMINO la jornada"]
  operador_id_inicio bigint [not null, note: "quién inició la jornada"]
  operador_id_actual bigint [null, note: "operador actual (puede cambiar)"]
  cantidad_producida_esperada bigint [null, note: "meta de la jornada completa"]
  estado varchar [default: "'activa'", note: "activa, completada, cancelada"]
  notas text [null]
  creado_en timestamp [default: "now()"]
  actualizado_en timestamp
  indexes {
    (maquina_id, ts_inicio) [type: btree]
    (ts_inicio, ts_fin) [type: btree]
  }
}

Table cambio_operador_jornada {
  id bigint [pk, increment]
  jornada_id bigint [not null]
  operador_anterior_id bigint [null, note: "quién estaba antes (puede ser null)"]
  operador_nuevo_id bigint [not null, note: "quién asume ahora"]
  ts_cambio timestamp [default: "now()", note: "cuándo se realizó el cambio"]
  razon varchar [null, note: "cambio de turno, descanso, relevo, etc"]
  notas text [null]
  creado_por bigint [null, note: "supervisor o admin que registró el cambio"]
  creado_en timestamp [default: "now()"]
  indexes {
    (jornada_id, ts_cambio) [type: btree]
  }
}

Table puesta_en_marcha {
  id bigint [pk, increment]
  jornada_id bigint [not null]
  maquina_id bigint [not null]
  ts_inicio timestamp [not null, note: "cuándo arrancó la máquina"]
  ts_fin timestamp [null, note: "cuándo paró la máquina"]
  estado varchar [default: "'en_marcha'", note: "en_marcha, parada, finalizada"]
  cantidad_producida_esperada bigint [null, note: "meta definida por gerencia"]
  creado_en timestamp [default: "now()"]
  actualizado_en timestamp
  indexes {
    (jornada_id, ts_inicio) [type: btree]
    (maquina_id, ts_inicio) [type: btree]
  }
}

Table produccion_detalle {
  id bigint [pk, increment]
  puesta_en_marcha_id bigint [not null]
  maquina_id bigint [not null]
  ts timestamp [not null, note: "timestamp del reporte de producción"]
  cantidad_producida bigint [not null, note: "total producido en este intervalo"]
  cantidad_buena bigint [not null, note: "cantidad en buen estado"]
  cantidad_fallada bigint [not null, note: "cantidad fallida/rechazada"]
  tasa_defectos numeric [null, note: "cantidad_fallada / cantidad_producida * 100"]
  payload_raw jsonb [null, note: "datos adicionales del equipo"]
  creado_en timestamp [default: "now()"]
  indexes {
    (puesta_en_marcha_id, ts) [type: btree]
    (maquina_id, ts) [type: btree]
    (ts) [type: btree]
  }
}

Table tiempo_muerto {
  id bigint [pk, increment]
  puesta_en_marcha_id bigint [not null]
  maquina_id bigint [not null]
  ts_inicio timestamp [not null, note: "cuándo empezó la parada"]
  ts_fin timestamp [null, note: "cuándo se reanudó"]
  razon varchar [not null, note: "falta_material, cambio_formato, mantenimiento, falla"]
  duracion_segundos int [null, note: "calculado: ts_fin - ts_inicio"]
  descripcion text [null]
  creado_en timestamp [default: "now()"]
  actualizado_en timestamp
  indexes {
    (puesta_en_marcha_id, ts_inicio) [type: btree]
  }
}

Table resumen_produccion {
  id bigint [pk, increment]
  puesta_en_marcha_id bigint [not null, unique]
  maquina_id bigint [not null]
  jornada_id bigint [not null]
  cantidad_total_producida bigint [not null]
  cantidad_total_buena bigint [not null]
  cantidad_total_fallada bigint [not null]
  cantidad_esperada bigint [null]
  tasa_defectos_promedio numeric [null]
  tiempo_marcha_segundos bigint [not null, note: "sum(puesta_en_marcha.duracion)"]
  tiempo_muerto_total_segundos bigint [default: 0, note: "sum(tiempo_muerto.duracion_segundos)"]
  eficiencia_produccion numeric [null, note: "cantidad_producida / cantidad_esperada * 100"]
  creado_en timestamp [default: "now()"]
  actualizado_en timestamp
  indexes {
    (maquina_id, creado_en) [type: btree]
    (jornada_id) [type: btree]
  }
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
Ref: jornada.maquina_id > maquina.id
Ref: jornada.operador_id_inicio > usuario.id
Ref: jornada.operador_id_actual > usuario.id
Ref: cambio_operador_jornada.jornada_id > jornada.id
Ref: cambio_operador_jornada.operador_anterior_id > usuario.id
Ref: cambio_operador_jornada.operador_nuevo_id > usuario.id
Ref: cambio_operador_jornada.creado_por > usuario.id
Ref: puesta_en_marcha.jornada_id > jornada.id
Ref: puesta_en_marcha.maquina_id > maquina.id
Ref: produccion_detalle.puesta_en_marcha_id > puesta_en_marcha.id
Ref: produccion_detalle.maquina_id > maquina.id
Ref: tiempo_muerto.puesta_en_marcha_id > puesta_en_marcha.id
Ref: tiempo_muerto.maquina_id > maquina.id
Ref: resumen_produccion.puesta_en_marcha_id > puesta_en_marcha.id
Ref: resumen_produccion.maquina_id > maquina.id
Ref: resumen_produccion.jornada_id > jornada.id
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

### GRUPO 2.1: Producción y Jornadas

#### `jornada`
Define una sesión de trabajo de una máquina (puede ser turno o jornada personalizada, y puede cruzar días).
- **Campos clave**: 
  - `maquina_id` (FK).
  - `nombre`: Turno ('Día', 'Noche', 'Madrugada') o nombre personalizado.
  - `ts_inicio`: Timestamp de INICIO (ej: 2024-12-20 20:00:00).
  - `ts_fin`: Timestamp de FIN (ej: 2024-12-21 01:00:00 → cruza a día siguiente).
  - `operador_id_inicio`: Quién inició la jornada (obligatorio, auditoría).
  - `operador_id_actual`: Operador actual (puede cambiar durante la jornada).
  - `cantidad_producida_esperada`: Meta para toda la jornada.
  - `estado`: 'activa', 'completada', 'cancelada'.
- **Ejemplo**: Máquina 5, "Noche", inicio 2024-12-20 20:00, fin 2024-12-21 01:00 (5 horas), operador Juan.
- **Uso**: Agrupar puestas en marcha, producción y cambios de responsables dentro de un período extendido.
- **Cambios de Responsables**: Registrados en tabla `cambio_operador_jornada` para auditoría completa.

#### `cambio_operador_jornada`
Registro de cambios de operadores/responsables durante una jornada.
- **Campos clave**: 
  - `jornada_id` (FK).
  - `operador_anterior_id`: Quién estaba antes (null si es el primer operador).
  - `operador_nuevo_id`: Quién asume (obligatorio).
  - `ts_cambio`: Timestamp exacto del cambio.
  - `razon`: Motivo ('cambio_turno', 'descanso', 'relevo', 'ausencia', etc).
  - `creado_por`: Supervisor/admin que registró el cambio.
- **Ejemplo**: Jornada noche 2024-12-20 20:00 inicio con Juan. A las 23:45, Juan sale y entra María por relevo → fila en cambio_operador_jornada.
- **Uso**: Trazabilidad completa de quién operó máquina en cada momento. Impacta auditoría y KPIs por operador.
- **Auditoría**: Quién hizo el cambio (creado_por) y cuándo se registró.

#### `puesta_en_marcha`
Sesión de producción dentro de una jornada (cada vez que la máquina arranca y se detiene).
- **Campos clave**: 
  - `jornada_id` (FK).
  - `maquina_id` (FK).
  - `ts_inicio`: Timestamp de arranque.
  - `ts_fin`: Timestamp de parada (null si está activa).
  - `cantidad_producida_esperada`: Meta definida por gerencia para esta sesión.
  - `estado`: 'en_marcha', 'parada', 'finalizada'.
- **Ejemplo**: Producción de 06:30 a 09:15 (duracion: 2h 45m).
- **Uso**: Bucket temporal para agrupar mediciones y cálculo de eficiencia.

#### `produccion_detalle`
Mediciones granulares de producción (cada X segundos, enviadas por la máquina).
- **Campos clave**: 
  - `puesta_en_marcha_id` (FK).
  - `maquina_id` (FK).
  - `ts`: Timestamp de la medición.
  - `cantidad_producida`: Unidades producidas en este intervalo.
  - `cantidad_buena`: Unidades en buen estado.
  - `cantidad_fallada`: Unidades rechazadas/defectuosas.
  - `tasa_defectos`: Calculada como (cantidad_fallada / cantidad_producida) * 100.
  - `payload_raw`: JSON con datos adicionales del equipo (presión, temperatura, etc.).
- **Índices**: `(puesta_en_marcha_id, ts)`, `(maquina_id, ts)` para queries rápidas.
- **Ejemplo**: A las 06:32:15, máquina reporta: 125 producidas, 123 buenas, 2 falladas (1.6% defectuosa).
- **Particionado**: Recomendado por `ts` (TimescaleDB).

#### `tiempo_muerto`
Registro de paradas/pausas dentro de una puesta en marcha (downtime).
- **Campos clave**: 
  - `puesta_en_marcha_id` (FK).
  - `maquina_id` (FK).
  - `ts_inicio`: Cuándo empezó la parada.
  - `ts_fin`: Cuándo se reanudó (null si sigue parada).
  - `razon`: Motivo ('falta_material', 'cambio_formato', 'mantenimiento', 'falla').
  - `duracion_segundos`: Calculado (ts_fin - ts_inicio).
  - `descripcion`: Detalles adicionales.
- **Ejemplo**: Parada de 07:45 a 08:10 por "cambio de formato" (25 min de downtime).
- **Uso**: Analizar causas de paros y calcular OEE (disponibilidad descontando downtime).

#### `resumen_produccion`
Agregado de toda la producción de una puesta en marcha (snapshot).
- **Campos clave**: 
  - `puesta_en_marcha_id` (FK, UNIQUE).
  - `maquina_id`, `jornada_id` (FKs para queries rápidas).
  - `cantidad_total_producida`: Sum de produccion_detalle.cantidad_producida.
  - `cantidad_total_buena`: Sum de produccion_detalle.cantidad_buena.
  - `cantidad_total_fallada`: Sum de produccion_detalle.cantidad_fallada.
  - `cantidad_esperada`: Del puesta_en_marcha.
  - `tasa_defectos_promedio`: (cantidad_total_fallada / cantidad_total_producida) * 100.
  - `tiempo_marcha_segundos`: Duración activa = (ts_fin - ts_inicio) - tiempo_muerto_total.
  - `tiempo_muerto_total_segundos`: Sum de tiempo_muerto.duracion_segundos.
  - `eficiencia_produccion`: (cantidad_total_producida / cantidad_esperada) * 100.
- **Ejemplo**: Puesta en marcha de 06:30-09:15 → 2250 producidas, 2205 buenas, 45 falladas, 0 downtime → 100% eficiencia.
- **Uso**: Cálculo rápido de KPIs sin agregaciones complejas; útil para dashboards.

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

### **Caso de Uso 8: Registrar Producción Detallada (Jornada y Puesta en Marcha)**

**Contexto**: La máquina reporta cada X segundos: cantidad producida, cantidad buena, cantidad fallada.

**Flujo**:
1. Supervisor inicia jornada (que puede cruzar días):

```sql
-- PASO 1: Crear jornada que cruza días (20-12-2024 20:00 a 21-12-2024 01:00)
INSERT INTO jornada (maquina_id, nombre, ts_inicio, operador_id_inicio, operador_id_actual, cantidad_producida_esperada, estado)
VALUES 
  (5, 'Noche', '2024-12-20 20:00:00'::timestamp, 1, 1, 3000, 'activa');
-- Resultado: id = 100
-- Nota: ts_fin será NULL inicialmente, se actualiza cuando termina
```

2. Durante la jornada, cambio de operador a las 23:45:

```sql
-- PASO 2: Registrar cambio de operador (Juan → María)
INSERT INTO cambio_operador_jornada 
  (jornada_id, operador_anterior_id, operador_nuevo_id, ts_cambio, razon, creado_por)
VALUES 
  (100, 1, 2, '2024-12-20 23:45:00'::timestamp, 'cambio_turno', 10);
-- Resultado: id = 500

-- PASO 3: Actualizar operador actual en jornada
UPDATE jornada SET operador_id_actual = 2 WHERE id = 100;

-- PASO 4: Auditoría registra automáticamente quién hizo el cambio
-- SELECT * FROM bitacora_auditoria WHERE entidad_tipo = 'cambio_operador_jornada' AND entidad_id = 500;
```

3. Query para auditoría: quién operó máquina en cada momento:

```sql
-- PASO 5: Historial de operadores de una jornada
SELECT 
  j.id as jornada_id,
  j.ts_inicio,
  j.ts_fin,
  u1.nombre as operador_inicio,
  u2.nombre as operador_actual,
  coj.operador_anterior_id,
  u3.nombre as operador_anterior,
  coj.operador_nuevo_id,
  u4.nombre as operador_nuevo,
  coj.ts_cambio,
  coj.razon
FROM jornada j
LEFT JOIN usuario u1 ON j.operador_id_inicio = u1.id
LEFT JOIN usuario u2 ON j.operador_id_actual = u2.id
LEFT JOIN cambio_operador_jornada coj ON j.id = coj.jornada_id
LEFT JOIN usuario u3 ON coj.operador_anterior_id = u3.id
LEFT JOIN usuario u4 ON coj.operador_nuevo_id = u4.id
WHERE j.id = 100
ORDER BY coj.ts_cambio;
```

**Resultado**:
- Jornada multi-día registrada (20-12 20:00 a 21-12 01:00).
- Cambios de operadores auditados.
- Trazabilidad completa de quién operó en cada período.

2. Máquina arranca y se crea puesta_en_marcha:

```sql
-- PASO 1 (Continuación): Máquina arranca dentro de la jornada
INSERT INTO puesta_en_marcha 
  (jornada_id, maquina_id, ts_inicio, cantidad_producida_esperada, estado)
VALUES 
  (100, 5, '2024-12-20 20:30:00'::timestamp, 2500, 'en_marcha');
-- Resultado: id = 1000

-- PASO 2: Máquina reporta cada 60 segundos
INSERT INTO produccion_detalle 
  (puesta_en_marcha_id, maquina_id, ts, cantidad_producida, cantidad_buena, cantidad_fallada, tasa_defectos, payload_raw)
VALUES 
  (1000, 5, '2024-12-20 20:31:00'::timestamp, 125, 123, 2, 1.6, '{"velocidad": 450, "presion": 8.5}'),
  (1000, 5, '2024-12-20 20:32:00'::timestamp, 125, 124, 1, 0.8, '{"velocidad": 450, "presion": 8.4}'),
  (1000, 5, '2024-12-20 20:33:00'::timestamp, 125, 125, 0, 0.0, '{"velocidad": 450, "presion": 8.5}');
-- Cada fila = 60 segundos de producción en tiempo real

-- PASO 3: Frontend (Echo) recibe eventos en tiempo real
-- Backend: ProduccionRegistrada::broadcast(puesta_en_marcha_id, cantidad_producida, cantidad_buena)
-- Dashboard actualiza: 375 producidas, 372 buenas, 3 falladas, operador = María
```

**Resultado**:
- Jornada multi-día registrada con operador inicial y cambios auditados.
- Puesta en marcha dentro de la jornada.
- Mediciones granulares en produccion_detalle.
- Dashboard actualizado en tiempo real vía Echo/Reverb con identificación de operador actual.

---

### **Caso de Uso 9: Registrar Tiempo Muerto (Paradas/Downtime)**

**Contexto**: Máquina se detiene por motivos (falta material, cambio, mantenimiento) y supervisoregistra la causa.

**Flujo**:
1. Supervisor nota que la máquina se detiene a las 07:45:

```sql
-- PASO 1: Registrar inicio de parada
INSERT INTO tiempo_muerto 
  (puesta_en_marcha_id, maquina_id, ts_inicio, razon, descripcion)
VALUES 
  (1000, 5, '2025-11-13 07:45:00'::timestamp, 'cambio_formato', 'Cambio de molde para formato XL');
-- Resultado: id = 5000

-- PASO 2: Cuando máquina se reanuda a las 08:10, actualizar fin_ts
UPDATE tiempo_muerto 
SET 
  ts_fin = '2025-11-13 08:10:00'::timestamp,
  duracion_segundos = EXTRACT(EPOCH FROM ('2025-11-13 08:10:00'::timestamp - '2025-11-13 07:45:00'::timestamp))::int
WHERE id = 5000;
-- duracion_segundos = 1500 (25 minutos)

-- PASO 3: Dashboard muestra acumulado de downtime
-- SELECT SUM(duracion_segundos) FROM tiempo_muerto 
--   WHERE puesta_en_marcha_id = 1000 AND ts_fin IS NOT NULL;
-- Resultado: 1500 segundos (25 min) de parada registrada
```

**Resultado**:
- Tiempo muerto registrado y cuantificado.
- Causa documentada para análisis.
- Disponibilidad real calculable: (tiempo_marcha - tiempo_muerto) / tiempo_total.

---

### **Caso de Uso 10: Calcular Resumen de Producción al Finalizar Puesta en Marcha**

**Contexto**: Cuando puesta_en_marcha finaliza, agregar todos los datos en resumen_produccion para KPIs rápidos.

**Flujo**:
1. Máquina se detiene a las 09:15 (fin de sesión):

```sql
-- PASO 1: Actualizar puesta_en_marcha con fin
UPDATE puesta_en_marcha 
SET 
  ts_fin = '2025-11-13 09:15:00'::timestamp,
  estado = 'finalizada'
WHERE id = 1000;

-- PASO 2: Calcular agregados desde produccion_detalle
-- Total producido en toda la sesión
SELECT 
  SUM(cantidad_producida) as total_producida,
  SUM(cantidad_buena) as total_buena,
  SUM(cantidad_fallada) as total_fallada,
  (SUM(cantidad_fallada)::numeric / SUM(cantidad_producida) * 100) as tasa_defectos_avg
FROM produccion_detalle
WHERE puesta_en_marcha_id = 1000;
-- Resultado: 2250 producidas, 2205 buenas, 45 falladas, 2.0% defectos

-- PASO 3: Obtener tiempo muerto total
SELECT 
  COALESCE(SUM(duracion_segundos), 0) as tiempo_muerto_total
FROM tiempo_muerto
WHERE puesta_en_marcha_id = 1000
  AND ts_fin IS NOT NULL;
-- Resultado: 1500 segundos (25 minutos)

-- PASO 4: Insertar resumen (snapshot)
INSERT INTO resumen_produccion 
  (puesta_en_marcha_id, maquina_id, jornada_id, cantidad_total_producida, 
   cantidad_total_buena, cantidad_total_fallada, cantidad_esperada, 
   tasa_defectos_promedio, tiempo_marcha_segundos, tiempo_muerto_total_segundos, 
   eficiencia_produccion)
VALUES 
  (1000, 5, 100, 2250, 2205, 45, 2500, 2.0, 9900, 1500, 
   (2250::numeric / 2500 * 100));
-- tiempo_marcha = 09:15 - 06:30 - 25min downtime = 165 - 25 = 140 min = 8400 sec
-- eficiencia = 2250 / 2500 * 100 = 90%

-- PASO 5: Crear KPIs derivados de esta sesión
-- KC1: Tasa de Defectos = 2.0% (alert si > 5%)
-- KC2: Eficiencia de Producción = 90% (alert si < 80%)
-- KC3: Disponibilidad = (8400 sec / 9900 sec) * 100 = 84.8%
```

**Resultado**:
- Resumen completo en `resumen_produccion`.
- KPIs calculados automáticamente y listos para dashboards.
- Histórico de cada sesión para análisis y reportes.

---

## Índices Recomendados

```sql
-- Índices de Rendimiento (MEDICIONES)
CREATE INDEX idx_medicion_sensor_ts ON medicion(sensor_id, ts DESC);
CREATE INDEX idx_medicion_ts ON medicion(ts DESC);
CREATE INDEX idx_medicion_sensor_calidad ON medicion(sensor_id, calidad_dato);

-- Índices de Rendimiento (PRODUCCIÓN)
CREATE INDEX idx_produccion_detalle_puesta_ts ON produccion_detalle(puesta_en_marcha_id, ts DESC);
CREATE INDEX idx_produccion_detalle_maquina_ts ON produccion_detalle(maquina_id, ts DESC);
CREATE INDEX idx_tiempo_muerto_puesta_ts ON tiempo_muerto(puesta_en_marcha_id, ts_inicio DESC);
CREATE INDEX idx_resumen_produccion_maquina_fecha ON resumen_produccion(maquina_id, creado_en DESC);
CREATE INDEX idx_jornada_maquina_ts ON jornada(maquina_id, ts_inicio DESC);
CREATE INDEX idx_cambio_operador_jornada_ts ON cambio_operador_jornada(jornada_id, ts_cambio DESC);

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
CREATE UNIQUE INDEX uq_resumen_produccion_puesta ON resumen_produccion(puesta_en_marcha_id);
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

### `produccion_detalle` (Table Particionada por Tiempo)
- **Retención**: 12 meses (rolling).
- **Granularidad**: Reportes cada 60 segundos, alto volumen.
- **Agregación**: Después de 6 meses, comprimir a reportes horarios.
- **Estrategia TimescaleDB**:
  ```sql
  SELECT add_compression_policy('produccion_detalle', INTERVAL '6 months');
  SELECT add_retention_policy('produccion_detalle', INTERVAL '12 months');
  ```

### `resumen_produccion`
- **Retención**: 24 meses (snapshots agregados, bajo volumen).
- **Uso**: Análisis histórico, reportes gerenciales.
- **Archiving**: Opcional, mover a tabla histórica anual.

### `jornada`
- **Retención**: 24 meses (pocos registros).
- **Archivo**: Mantener para correlación con producción.

### `tiempo_muerto`
- **Retención**: 24 meses.
- **Análisis**: Revisar mensualmente para identificar tendencias de downtime.

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
