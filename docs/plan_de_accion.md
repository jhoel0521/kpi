# Plan de Acción - KPI Dashboard Industrial

**Versión:** 3.0 (Guía Maestra)

**Última Actualización:** 17 de noviembre de 2025

**Estado:** Listo para FASE 1

## Resumen Ejecutivo

Plan de implementación del sistema de medición de producción con jornadas multi-día, control de operadores y cálculo de OEE (Disponibilidad, Rendimiento, Calidad). Organizado en 4 fases SCRUM.

**Arquitectura de Downtime (v3.0):**

- Tiempo Programado: Definido por jornada.
- Tiempo de Trabajo (UPTIME): Definido por puesta_en_marcha.
- Downtime Planificado: Registrado en registro_mantenimiento.
- Downtime No Planificado: Registrado en incidencia_parada.

## FASE 1: Diseño y Migraciones

### Objetivo

Crear la estructura fundamental de la base de datos (migraciones Laravel), modelos Eloquent y sus relaciones.

### Tareas

- [ ] **T1.1 - Validar DBML del diagrama (v3.0)**  
  Revisar diagrama en /docs/diagrama_de_base_de_datos.md  
  Confirmar relaciones (FKs)  
  Confirmar nueva tabla incidencia_parada y su relación con puesta_en_marcha  
  **Status:** ✗ Pendiente

- [ ] **T1.2 - Crear migración para tabla jornada**  
  Crear archivo: database/migrations/create_jornada_table.php  
  Incluir: id, maquina_id, nombre, ts_inicio, ts_fin, operador_id_inicio, operador_id_actual, cantidad_producida_esperada, estado, timestamps  
  **Status:** ✗ Pendiente

- [ ] **T1.3 - Crear migración para tabla cambio_operador_jornada**  
  Crear archivo: database/migrations/create_cambio_operador_jornada_table.php  
  Incluir: id, jornada_id, operador_anterior_id, operador_nuevo_id, ts_cambio, razon, creado_por, timestamps  
  **Status:** ✗ Pendiente

- [ ] **T1.4 - Crear migración para tabla puesta_en_marcha**  
  Crear archivo: database/migrations/create_puesta_en_marcha_table.php  
  Incluir: id, jornada_id, maquina_id, ts_inicio, ts_fin, estado, cantidad_producida_esperada, timestamps  
  **Status:** ✗ Pendiente

- [ ] **T1.5 - Crear migración para tabla incidencia_parada (NUEVO v3.0)**  
  Crear archivo: database/migrations/create_incidencia_parada_table.php  
  Incluir: id, puesta_en_marcha_id (FK), maquina_id (FK), ts_inicio_parada, ts_fin_parada, duracion_segundos, motivo, notas, creado_por (user_id)  
  **Status:** ✗ Pendiente

- [ ] **T1.6 - Crear migración para tabla produccion_detalle**  
  Crear archivo: database/migrations/create_produccion_detalle_table.php  
  Incluir: id, puesta_en_marcha_id, maquina_id, ts, cantidad_producida, cantidad_buena, cantidad_fallada, tasa_defectos, payload_raw  
  **Status:** ✗ Pendiente

- [ ] **T1.7 - Crear migración para tabla resumen_produccion**  
  Crear archivo: database/migrations/create_resumen_produccion_table.php  
  Incluir: id, puesta_en_marcha_id, maquina_id, jornada_id, ... (campos de T1.6)  
  **Status:** ✗ Pendiente

- [ ] **T1.8 - Ejecutar migraciones en PostgreSQL**  
  Correr: php artisan migrate  
  Verificar: Todas las tablas existen  
  **Status:** ✗ Pendiente

- [ ] **T1.9 - Crear índices de performance**  
  Crear migración: database/migrations/add_production_indexes.php  
  Incluir índices para: produccion_detalle(puesta_en_marcha_id, ts), incidencia_parada(puesta_en_marcha_id), jornada(maquina_id, ts_inicio), resumen_produccion(maquina_id, creado_en)  
  **Status:** ✗ Pendiente

- [ ] **T1.10 - Crear modelos Eloquent**  
  Crear: app/Models/Jornada.php  
  Crear: app/Models/CambioOperadorJornada.php  
  Crear: app/Models/PuestaEnMarcha.php  
  Crear: app/Models/IncidenciaParada.php (NUEVO v3.0)  
  Crear: app/Models/ProduccionDetalle.php  
  Crear: app/Models/ResumenProduccion.php  
  **Status:** ✗ Pendiente

- [ ] **T1.11 - Definir relaciones entre modelos**  
  Jornada hasMany PuestaEnMarcha  
  Jornada hasMany CambioOperadorJornada  
  PuestaEnMarcha hasMany ProduccionDetalle  
  PuestaEnMarcha hasMany IncidenciaParada (NUEVO v3.0)  
  PuestaEnMarcha hasOne ResumenProduccion  
  **Status:** ✗ Pendiente

### Deliverables FASE 1

- ✓ Todas las migraciones creadas y ejecutadas
- ✓ Tablas en PostgreSQL verificadas
- ✓ Modelos Eloquent funcionales
- ✓ Relaciones definidas y testeadas

## FASE 2: API REST y Lógica de Negocio

### Objetivo

Crear endpoints REST, validadores de estado (FSM) y la lógica de cálculo de KPIs (Strategy Pattern).

### Tareas

- [ ] **T2.1 - Crear controlador JornadaController**  
  Métodos: store (crear), show (ver), update (cerrar)  
  **Status:** ✗ Pendiente

- [ ] **T2.2 - Crear controlador CambioOperadorController**  
  Métodos: store (registrar cambio)  
  Lógica: Actualizar operador_id_actual en jornada  
  **Status:** ✗ Pendiente

- [ ] **T2.3 - Crear controlador PuestaEnMarchaController**  
  Métodos: store (iniciar), update (finalizar)  
  **Status:** ✗ Pendiente

- [ ] **T2.4 - Crear controlador IncidenciaParadaController (NUEVO v3.0)**  
  Métodos: store (registrar parada no planificada), update (finalizar parada)  
  **Status:** ✗ Pendiente

- [ ] **T2.5 - Crear controlador ProduccionController**  
  Métodos: storeDetalle (registrar dato granular)  
  **Status:** ✗ Pendiente

- [ ] **T2.6 - Crear rutas API**  
  Agregar rutas en: routes/api.php  
  POST /api/jornadas (crear)  
  POST /api/puestas-en-marcha (iniciar)  
  POST /api/incidencias-parada (registrar parada)  
  POST /api/produccion-detalle (registrar dato)  
  **Status:** ✗ Pendiente

- [ ] **T2.7 - Crear Request validators (Lógica FSM - v3.0)**  
  Crear: app/Http/Requests/StoreJornadaRequest.php  
  Crear: app/Http/Requests/StorePuestaEnMarchaRequest.php  
  Lógica FSM: Validar que maquina.estado sea 'operativa'.  
  Lógica FSM: Validar que no exista otra puesta_en_marcha activa para esa máquina.  
  Crear: app/Http/Requests/StoreIncidenciaParadaRequest.php  
  Lógica FSM: Validar que puesta_en_marcha.estado sea 'en_marcha'.  
  Crear: app/Http/Requests/StoreProduccionDetalleRequest.php  
  **Status:** ✗ Pendiente

- [ ] **T2.8 - Implementar lógica de cálculo (Patrón Strategy - v3.0)**  
  Objetivo: Calcular KPIs (OEE, Disponibilidad, etc.) usando clases de estrategia.  
  - [ ] T2.8.1 - Crear Interfaz app/Services/KpiCalculation/KpiStrategyInterface.php  
    Método: public function calculate(PuestaEnMarcha $pem): array;  
  - [ ] T2.8.2 - Crear Estrategias  
    app/Services/KpiCalculation/OeeStrategy.php (Implementa la interfaz)  
    app/Services/KpiCalculation/DisponibilidadStrategy.php (Implementa la interfaz)  
  - [ ] T2.8.3 - Crear Factory  
    app/Services/KpiCalculation/KpiStrategyFactory.php  
    Método: public function make(string $kpiCode): KpiStrategyInterface;  
  - [ ] T2.8.4 - Lógica de Resumen  
    Crear app/Services/ResumenProduccionService.php  
    Método: generar(PuestaEnMarcha $pem)  
    Lógica:  
    Calcular totales de produccion_detalle (total_producida, total_buena, etc.).  
    Calcular SUM(duracion_segundos) de incidencia_parada.  
    Usar KpiStrategyFactory para obtener cálculos de OEE y Disponibilidad.  
    Guardar todo en resumen_produccion.  
  **Status:** ✗ Pendiente

- [ ] **T2.9 - Crear evento ProduccionRegistrada**  
  Crear: app/Events/ProduccionRegistrada.php (ShouldBroadcast)  
  Broadcastear: puesta_en_marcha_id, cantidad_producida, cantidad_buena  
  Disparar este evento desde ProduccionController  
  **Status:** ✗ Pendiente

- [ ] **T2.10 - Testear endpoints POST/PATCH**  
  Testear API con Postman  
  **Status:** ✗ Pendiente

### Deliverables FASE 2

- ✓ Todos los endpoints REST funcionales
- ✓ Validadores FSM implementados
- ✓ Lógica de cálculo de resumen (Strategy) completa
- ✓ Eventos Echo/Reverb configurados

## FASE 3: Dashboard y Reportes

### Objetivo

Crear vistas (Blade) para visualizar producción en vivo, OEE, disponibilidad, y reportes históricos.

### Tareas

- [ ] **T3.1 - Crear vista dashboard producción en vivo**  
  Mostrar: Jornada actual, puesta_en_marcha activa, operador actual  
  Mostrar: Últimos 10 registros de produccion_detalle  
  **Status:** ✗ Pendiente

- [ ] **T3.2 - Crear vista de métricas OEE en tiempo real**  
  Mostrar: OEE (%), Disponibilidad (%), Rendimiento (%), Calidad (%)  
  Mostrar: Total producida, total buena, total fallada  
  **Status:** ✗ Pendiente

- [ ] **T3.3 - Implementar actualización en vivo con Echo**  
  Escuchar evento: ProduccionRegistrada  
  Actualizar métricas (T3.2) sin refrescar página  
  **Status:** ✗ Pendiente

- [ ] **T3.4 - Crear vista de historial de operadores**  
  Mostrar: Quién inició jornada, cambio_operador_jornada (historial)  
  **Status:** ✗ Pendiente

- [ ] **T3.5 - Crear vista de resumen de puesta en marcha**  
  Mostrar: Datos de resumen_produccion  
  Mostrar: Lista de incidencia_parada ocurridas en esa puesta en marcha  
  **Status:** ✗ Pendiente

- [ ] **T3.6 - Crear reporte por rango de fechas**  
  Filtros: Máquina, fecha inicio, fecha fin  
  Mostrar: Tabla con jornadas, puestas, totales, promedios  
  **Status:** ✗ Pendiente

- [ ] **T3.7 - Crear reporte de disponibilidad (v3.0)**  
  Lógica: (Tiempo_Programado - SUM(parada_planificada) - SUM(parada_no_planificada)) / Tiempo_Programado * 100  
  Query: Consultar jornada (Tiempo Programado), registro_mantenimiento (Planificado) y incidencia_parada (No Planificado) en el rango de fechas.  
  Mostrar: Disponibilidad por máquina, por día, por semana  
  **Status:** ✗ Pendiente

- [ ] **T3.8 - Crear endpoint GET para reportes**  
  GET /api/reportes/produccion?fecha_inicio=&fecha_fin=&maquina_id=  
  GET /api/reportes/disponibilidad?fecha_inicio=&fecha_fin=&maquina_id=  
  **Status:** ✗ Pendiente

### Deliverables FASE 3

- ✓ Dashboard en vivo funcional
- ✓ Actualización en tiempo real con Echo
- ✓ Vistas de reportes OEE/Disponibilidad completas

## FASE 4: Tests, Optimización y Deploy

### Objetivo

Implementar cobertura de tests (Pest/PHPUnit), optimizar queries y securizar endpoints.

### Tareas

- [ ] **T4.1 - Crear tests unitarios para modelos**  
  Test: Relaciones Eloquent (T1.11)  
  **Status:** ✗ Pendiente

- [ ] **T4.2 - Crear tests de API endpoints (Feature Tests)**  
  Test: POST /api/jornadas (201 OK)  
  Test: POST /api/jornadas con datos inválidos (422)  
  **Status:** ✗ Pendiente

- [ ] **T4.3 - Crear tests de lógica de negocio (FSM y Strategy) (v3.0)**  
  Test: (FSM) Intentar crear puesta_en_marcha mientras máquina está en 'mantenimiento' -> debe fallar (422).  
  Test: (Strategy) OeeCalculatorStrategy con datos de prueba -> debe retornar OEE correcto.  
  Test: (Strategy) Cálculo de Disponibilidad con 1 parada planificada y 2 no planificadas.  
  **Status:** ✗ Pendiente

- [ ] **T4.4 - Optimizar queries N+1**  
  Revisar controladores y agregar eager loading (with)  
  Usar Laravel Debugbar o Pail  
  **Status:** ✗ Pendiente

- [ ] **T4.5 - Implementar autenticación en API**  
  Agregar: Middleware auth:sanctum en routes/api.php  
  **Status:** ✗ Pendiente

- [ ] **T4.6 - Configurar compresión TimescaleDB (Opcional)**  
  Si el volumen es muy alto, aplicar políticas de compresión/retención.  
  **Status:** ✗ Pendiente

- [ ] **T4.7 - Ejecutar cobertura de tests**  
  Correr: php artisan test --coverage  
  Verificar: Cobertura >= 80%  
  **Status:** ✗ Pendiente

- [ ] **T4.8 - Deploy a producción**  
  **Status:** ✗ Pendiente

### Deliverables FASE 4

- ✓ Cobertura de tests >= 80%
- ✓ Queries optimizadas
- ✓ Sistema securizado con auth
- ✓ Sistema en producción y funcional