# Diseño de Base de Datos - KPI Dashboard Industrial

## Tabla de Contenidos

- Resumen Ejecutivo
- Diagrama Entidad-Relación (DBDiagram)
- Descripción de Tablas
  - Grupo 1: Gestión de Activos
  - Grupo 2: Ingesta de Datos
  - Grupo 2.1: Producción y Jornadas
  - Grupo 3: Configuración de KPI
  - Grupo 4: Valores y Snapshots
  - Grupo 5: Alertas y Eventos
  - Grupo 6: Gestión de Usuarios
  - Grupo 7: Auditoría
- Ejemplos de Casos de Uso
- Índices Recomendados
- Políticas de Retención

## Diseño de Base de Datos - KPI Dashboard Industrial

**Versión:** 3.0 (Guía Maestra)

### Resumen Ejecutivo

Este diseño de base de datos (v3.0) soporta un sistema KPI industrial con:

- Ingesta en tiempo real (mediciones de sensores).
- Medición de producción por jornadas (turnos multi-día, puestas en marcha).
- Cálculo de OEE (v3.0):
  - Disponibilidad: Captura registro_mantenimiento (Planificado) y la nueva tabla incidencia_parada (No Planificado).
  - Rendimiento y Calidad: Capturados en produccion_detalle.
- Cálculo de KPIs (v3.0): Utiliza un Patrón Strategy en la capa de aplicación (PHP) en lugar de fórmulas SQL complejas. El campo definicion_kpi.codigo se usará como clave para un Factory.
- Máquina de Estados (FSM) (v3.0): La lógica de negocio (Validators de Laravel) asegurará transiciones de estado válidas (ej. maquina.estado vs puesta_en_marcha.estado).

### Diagrama Entidad-Relación (DBDiagram)

📊 Copia y pega el siguiente código en DBDiagram.io para visualizar el diagrama v3.0:

```sql
// ====================================
// GRUPO 1: GESTIÓN DE ACTIVOS
// ====================================
Table planta {
  id bigint [pk, increment]
  nombre varchar [not null, unique]
  zona_horaria varchar [default: "'UTC'"]
  creado_en timestamp [default: "now()"]
}

Table linea_produccion {
  id bigint [pk, increment]
  planta_id bigint [not null]
  nombre varchar [not null]
  estado varchar [default: "'activa'"]
  creado_en timestamp [default: "now()"]
}

Table maquina {
  id bigint [pk, increment]
  linea_id bigint [not null]
  nombre varchar [not null]
  serie varchar [unique]
  estado varchar [default: "'operativa'", note: "FSM: operativa, parada, mantenimiento, falla"]
  creado_en timestamp [default: "now()"]
}

Table sensor {
  id bigint [pk, increment]
  maquina_id bigint [not null]
  nombre varchar [not null]
  tipo_sensor varchar [not null]
  unidad varchar
  creado_en timestamp [default: "now()"]
}

Table fuente_datos {
  id bigint [pk, increment]
  sensor_id bigint [not null]
  tipo_protocolo varchar [not null, note: "HTTP, MQTT, OPC-UA"]
  url_endpoint varchar
  token_autenticacion varchar [note: "encriptado"]
  frecuencia_muestreo_ms int [default: 1000]
  creado_en timestamp [default: "now()"]
}

// ====================================
// GRUPO 2: INGESTA DE DATOS
// ====================================
Table medicion {
  id bigint [pk, increment]
  sensor_id bigint [not null]
  ts timestamp [not null]
  valor numeric [not null]
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
  mensaje_error text
  payload_recibido jsonb [null, note: "DLQ - Dead Letter Queue"]
  timestamp_error timestamp [default: "now()"]
}

// ====================================
// GRUPO 2.1: PRODUCCIÓN Y OEE (v3.0)
// ====================================
Table jornada {
  id bigint [pk, increment]
  maquina_id bigint [not null]
  nombre varchar [not null, note: "Día, Noche, Madrugada"]
  ts_inicio timestamp [not null, note: "Tiempo Programado Inicio"]
  ts_fin timestamp [null, note: "Tiempo Programado Fin"]
  operador_id_inicio bigint [not null]
  operador_id_actual bigint [null]
  cantidad_producida_esperada bigint [null, note: "Meta de la jornada"]
  estado varchar [default: "'activa'", note: "FSM: activa, completada, cancelada"]
  creado_en timestamp [default: "now()"]
  actualizado_en timestamp
  indexes {
    (maquina_id, ts_inicio) [type: btree]
  }
}

Table cambio_operador_jornada {
  id bigint [pk, increment]
  jornada_id bigint [not null]
  operador_anterior_id bigint [null]
  operador_nuevo_id bigint [not null]
  ts_cambio timestamp [default: "now()"]
  razon varchar [null, note: "cambio de turno, relevo"]
  creado_por bigint [null, note: "supervisor"]
  creado_en timestamp [default: "now()"]
  indexes {
    (jornada_id, ts_cambio) [type: btree]
  }
}

Table puesta_en_marcha {
  id bigint [pk, increment]
  jornada_id bigint [not null]
  maquina_id bigint [not null]
  ts_inicio timestamp [not null, note: "Inicio de UPTIME"]
  ts_fin timestamp [null, note: "Fin de UPTIME"]
  estado varchar [default: "'en_marcha'", note: "FSM: en_marcha, parada, finalizada"]
  cantidad_producida_esperada bigint [null, note: "Meta (para Rendimiento)"]
  creado_en timestamp [default: "now()"]
  actualizado_en timestamp
  indexes {
    (jornada_id, ts_inicio) [type: btree]
  }
}

// NUEVA TABLA (v3.0) para DOWNTIME NO PLANIFICADO
Table incidencia_parada {
  id bigint [pk, increment]
  puesta_en_marcha_id bigint [not null, note: "Ocurrió DURANTE esta puesta en marcha"]
  maquina_id bigint [not null]
  ts_inicio_parada timestamp [not null]
  ts_fin_parada timestamp [null]
  duracion_segundos bigint [null]
  motivo varchar [not null, note: "Falla eléctrica, Falta material, Atasco"]
  notas text [null]
  creado_por bigint [null, note: "operador/supervisor que reporta"]
  creado_en timestamp [default: "now()"]
  actualizado_en timestamp
  indexes {
    (puesta_en_marcha_id) [type: btree]
    (maquina_id, ts_inicio_parada) [type: btree]
  }
}

Table produccion_detalle {
  id bigint [pk, increment]
  puesta_en_marcha_id bigint [not null]
  maquina_id bigint [not null]
  ts timestamp [not null, note: "timestamp del reporte"]
  cantidad_producida bigint [not null, note: "Total (para Rendimiento)"]
  cantidad_buena bigint [not null, note: "Buenas (para Calidad)"]
  cantidad_fallada bigint [not null, note: "Malas (para Calidad)"]
  tasa_defectos numeric [null]
  payload_raw jsonb [null]
  creado_en timestamp [default: "now()"]
  indexes {
    (puesta_en_marcha_id, ts) [type: btree]
    (maquina_id, ts) [type: btree]
  }
}

Table resumen_produccion {
  id bigint [pk, increment]
  puesta_en_marcha_id bigint [not null, unique]
  maquina_id bigint [not null]
  jornada_id bigint [not null]
  
  // Agregados de produccion_detalle
  cantidad_total_producida bigint [not null]
  cantidad_total_buena bigint [not null]
  cantidad_total_fallada bigint [not null]
  cantidad_esperada bigint [null]
  
  // Agregados de incidencia_parada (v3.0)
  total_paradas_no_planificadas_segundos bigint [default: 0]
  
  // Agregados de puesta_en_marcha
  tiempo_marcha_segundos bigint [not null, note: "ts_fin - ts_inicio"]

  // KPIs calculados (v3.0)
  oee_calculado numeric [null]
  disponibilidad_calculada numeric [null]
  rendimiento_calculado numeric [null]
  calidad_calculada numeric [null]
  
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
  codigo varchar [unique, not null, note: "OEE, DISPONIBILIDAD, RENDIMIENTO, CALIDAD"]
  nombre varchar [not null]
  descripcion text
  formula varchar [null, note: "v3.0: Solo para KPIs simples (ej. avg). KPIs complejos (OEE) se manejan con Strategy Pattern en PHP, usando 'codigo' como clave."]
  tipo_agregacion varchar [default: "'avg'"]
  unidad_salida varchar [note: "%"]
  version int [default: 1]
  activa boolean [default: true]
  creado_en timestamp [default: "now()"]
  usuario_creador_id bigint
}

Table instancia_kpi {
  id bigint [pk, increment]
  definicion_kpi_id bigint [not null]
  maquina_id bigint [null]
  linea_id bigint [null]
  nombre varchar [not null]
  umbral_minimo numeric [null]
  umbral_maximo numeric [null]
  activa boolean [default: true]
  creado_en timestamp [default: "now()"]
}

// ====================================
// GRUPO 4: VALORES Y SNAPSHOTS
// ====================================
Table valor_kpi {
  id bigint [pk, increment]
  instancia_kpi_id bigint [not null]
  ts timestamp [not null]
  valor numeric [not null]
  calidad varchar [default: "'buena'"]
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
  creado_en timestamp [default: "now()"]
}

Table evento_alerta {
  id bigint [pk, increment]
  regla_alerta_id bigint [not null]
  instancia_kpi_id bigint [not null]
  ts_ocurrencia timestamp [not null]
  valor_disparador numeric
  estado varchar [default: "'abierta'"]
  creado_en timestamp [default: "now()"]
}

// ====================================
// GRUPO 6: GESTIÓN DE USUARIOS
// ====================================
Table usuario {
  id bigint [pk, increment]
  nombre varchar [not null]
  email varchar [unique, not null]
  password_hash varchar [not null]
  activo boolean [default: true]
  creado_en timestamp [default: "now()"]
}

Table rol {
  id bigint [pk, increment]
  nombre varchar [unique, not null] // Admin, Supervisor, Operador, Analista
  creado_en timestamp [default: "now()"]
}

Table usuario_rol {
  usuario_id bigint [not null, pk]
  rol_id bigint [not null, pk]
}

// ====================================
// GRUPO 7: AUDITORÍA Y MANTENIMIENTO
// ====================================

// DOWNTIME PLANIFICADO
Table registro_mantenimiento {
  id bigint [pk, increment]
  maquina_id bigint [not null]
  tipo_mantenimiento varchar [not null, note: "correctivo, preventivo"]
  inicio_ts timestamp [not null]
  fin_ts timestamp [null]
  descripcion text
  usuario_id bigint
  creado_en timestamp [default: "now()"]
}

Table bitacora_auditoria {
  id bigint [pk, increment]
  usuario_id bigint
  entidad_tipo varchar [not null, note: "definicion_kpi, maquina, usuario"]
  entidad_id bigint [not null]
  accion varchar [not null, note: "crear, actualizar, eliminar"]
  cambios_anteriores jsonb [null]
  cambios_nuevos jsonb [null]
  timestamp_cambio timestamp [default: "now()"]
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

// Relaciones v3.0
Ref: incidencia_parada.puesta_en_marcha_id > puesta_en_marcha.id
Ref: incidencia_parada.maquina_id > maquina.id
Ref: incidencia_parada.creado_por > usuario.id

Ref: produccion_detalle.puesta_en_marcha_id > puesta_en_marcha.id
Ref: produccion_detalle.maquina_id > maquina.id
Ref: resumen_produccion.puesta_en_marcha_id > puesta_en_marcha.id
Ref: resumen_produccion.maquina_id > maquina.id
Ref: resumen_produccion.jornada_id > jornada.id
Ref: definicion_kpi.usuario_creador_id > usuario.id
Ref: instancia_kpi.definicion_kpi_id > definicion_kpi.id
Ref: instancia_kpi.maquina_id > maquina.id
Ref: instancia_kpi.linea_id > linea_produccion.id
Ref: valor_kpi.instancia_kpi_id > instancia_kpi.id
Ref: regla_alerta.instancia_kpi_id > instancia_kpi.id
Ref: evento_alerta.regla_alerta_id > regla_alerta.id
Ref: evento_alerta.instancia_kpi_id > instancia_kpi.id
Ref: usuario_rol.usuario_id > usuario.id
Ref: usuario_rol.rol_id > rol.id
Ref: registro_mantenimiento.maquina_id > maquina.id
Ref: registro_mantenimiento.usuario_id > usuario.id
Ref: bitacora_auditoria.usuario_id > usuario.id

### Ejemplos de Casos de Uso (v3.0)

#### Caso de Uso 8 (v3.0): Registrar Producción y Parada No Planificada

**Flujo (FSM):**

- Supervisor crea jornada (ID: 100) (06:00-14:00).
- (FSM Check): StorePuestaEnMarchaRequest valida que maquina.estado == 'operativa'.
- Supervisor crea puesta_en_marcha (ID: 1000) (Inicia 06:15). puesta_en_marcha.estado = 'en_marcha'.
- Máquina envía produccion_detalle (se asocia a ID 1000).
- (Parada No Planificada): Máquina se atasca (08:30).
- (FSM Check): StoreIncidenciaParadaRequest valida que puesta_en_marcha.estado == 'en_marcha'.
- Operador crea incidencia_parada (ID: 50) (ts_inicio_parada: 08:30, motivo: "Atasco").
- Lógica Opcional: PuestaEnMarchaController actualiza puesta_en_marcha.estado = 'parada'.
- Máquina se repara (08:45).
- Operador actualiza incidencia_parada (ID: 50) (ts_fin_parada: 08:45, duracion_segundos: 900).
- Lógica Opcional: PuestaEnMarchaController revierte puesta_en_marcha.estado = 'en_marcha'.
- Máquina sigue enviando produccion_detalle.
- Supervisor finaliza puesta_en_marcha (ID: 1000) (Fin 13:50). estado = 'finalizada'.
- (Cálculo OEE): ResumenProduccionService::generar(1000) se dispara.
- El servicio suma todos los produccion_detalle (Rendimiento y Calidad).
- El servicio suma todas las incidencia_parada (Total Downtime No Planificado = 900 seg).
- El servicio consulta registro_mantenimiento (Total Downtime Planificado = 0 seg).
- OeeStrategy calcula los KPIs y los guarda en resumen_produccion.