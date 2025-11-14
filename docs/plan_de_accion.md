# Plan de Acción - KPI Dashboard Industrial

**Versión**: 2.0  
**Última Actualización**: 13 de noviembre de 2025  
**Estado**: En Curso

---

## Resumen Ejecutivo

Plan de implementación del sistema de medición de producción con jornadas multi-día, control de operadores y cálculo de disponibilidad. Organizado en 4 fases SCRUM sin restricción de tiempo.

---

## FASE 1: Diseño y Migraciones

### Objetivo
Validar diseño de base de datos, crear migraciones Laravel y tablas de producción en PostgreSQL.

### Tareas

- [ ] **T1.1 - Validar DBML del diagrama**
  - Revisar diagrama de base de datos en `/docs/diagrama de base de datos.md`
  - Confirmar todas las relaciones (FKs)
  - Verificar no hay referencias a `tiempo_muerto`
  - Status: ✓ Completado o ✗ Pendiente

- [ ] **T1.2 - Crear migración para tabla `jornada`**
  - Crear archivo: `database/migrations/create_jornada_table.php`
  - Incluir: id, maquina_id, nombre, ts_inicio, ts_fin, operador_id_inicio, operador_id_actual, cantidad_producida_esperada, estado, timestamps
  - Status: ✓ Completado o ✗ Pendiente

- [ ] **T1.3 - Crear migración para tabla `cambio_operador_jornada`**
  - Crear archivo: `database/migrations/create_cambio_operador_jornada_table.php`
  - Incluir: id, jornada_id, operador_anterior_id, operador_nuevo_id, ts_cambio, razon, creado_por, timestamps
  - Status: ✓ Completado o ✗ Pendiente

- [ ] **T1.4 - Crear migración para tabla `puesta_en_marcha`**
  - Crear archivo: `database/migrations/create_puesta_en_marcha_table.php`
  - Incluir: id, jornada_id, maquina_id, ts_inicio, ts_fin, estado, cantidad_producida_esperada, timestamps
  - Status: ✓ Completado o ✗ Pendiente

- [ ] **T1.5 - Crear migración para tabla `produccion_detalle`**
  - Crear archivo: `database/migrations/create_produccion_detalle_table.php`
  - Incluir: id, puesta_en_marcha_id, maquina_id, ts, cantidad_producida, cantidad_buena, cantidad_fallada, tasa_defectos, payload_raw
  - Status: ✓ Completado o ✗ Pendiente

- [ ] **T1.6 - Crear migración para tabla `resumen_produccion`**
  - Crear archivo: `database/migrations/create_resumen_produccion_table.php`
  - Incluir: id, puesta_en_marcha_id, maquina_id, jornada_id, cantidad_total_producida, cantidad_total_buena, cantidad_total_fallada, cantidad_esperada, tasa_defectos_promedio, tiempo_marcha_segundos, eficiencia_produccion, timestamps
  - Status: ✓ Completado o ✗ Pendiente

- [ ] **T1.7 - Ejecutar migraciones en PostgreSQL**
  - Correr: `php artisan migrate`
  - Verificar: Todas las tablas existen en base de datos
  - Verificar: Todas las FKs se crearon correctamente
  - Status: ✓ Completado o ✗ Pendiente

- [ ] **T1.8 - Crear índices de performance**
  - Crear migración: `database/migrations/add_production_indexes.php`
  - Incluir índices para: produccion_detalle(puesta_en_marcha_id, ts), jornada(maquina_id, ts_inicio), resumen_produccion(maquina_id, creado_en)
  - Status: ✓ Completado o ✗ Pendiente

- [ ] **T1.9 - Crear modelos Eloquent**
  - Crear: `app/Models/Jornada.php`
  - Crear: `app/Models/CambioOperadorJornada.php`
  - Crear: `app/Models/PuestaEnMarcha.php`
  - Crear: `app/Models/ProduccionDetalle.php`
  - Crear: `app/Models/ResumenProduccion.php`
  - Status: ✓ Completado o ✗ Pendiente

- [ ] **T1.10 - Definir relaciones entre modelos**
  - Jornada hasMany PuestaEnMarcha
  - Jornada hasMany CambioOperadorJornada
  - PuestaEnMarcha hasMany ProduccionDetalle
  - PuestaEnMarcha hasOne ResumenProduccion
  - Status: ✓ Completado o ✗ Pendiente

### Deliverables FASE 1
- ✓ Todas las migraciones creadas y ejecutadas
- ✓ Tablas en PostgreSQL verificadas
- ✓ Modelos Eloquent funcionales
- ✓ Relaciones definidas y testeadas

---

## FASE 2: API REST

### Objetivo
Crear endpoints REST para registrar jornadas, cambios de operadores, producción detallada y generar resúmenes.

### Tareas

- [ ] **T2.1 - Crear controlador JornadaController**
  - Crear: `app/Http/Controllers/JornadaController.php`
  - Métodos: store (crear), show (ver detalles), update (actualizar ts_fin)
  - Status: ✓ Completado o ✗ Pendiente

- [ ] **T2.2 - Crear controlador CambioOperadorController**
  - Crear: `app/Http/Controllers/CambioOperadorController.php`
  - Métodos: store (registrar cambio)
  - Lógica: Actualizar operador_id_actual en jornada
  - Status: ✓ Completado o ✗ Pendiente

- [ ] **T2.3 - Crear controlador ProduccionController**
  - Crear: `app/Http/Controllers/ProduccionController.php`
  - Métodos: storeDetalle (registrar dato granular), finalizarPuesta (terminar sesión)
  - Status: ✓ Completado o ✗ Pendiente

- [ ] **T2.4 - Crear rutas API para producción**
  - Agregar rutas en: `routes/api.php`
  - POST `/api/jornadas` (crear)
  - PATCH `/api/jornadas/{id}` (actualizar)
  - POST `/api/operadores-cambio` (registrar cambio)
  - POST `/api/produccion` (registrar dato)
  - Status: ✓ Completado o ✗ Pendiente

- [ ] **T2.5 - Crear Request validators**
  - Crear: `app/Http/Requests/StoreJornadaRequest.php`
  - Crear: `app/Http/Requests/StoreCambioOperadorRequest.php`
  - Crear: `app/Http/Requests/StoreProduccionDetalleRequest.php`
  - Status: ✓ Completado o ✗ Pendiente

- [ ] **T2.6 - Implementar lógica de cálculo de resumen**
  - Método: ResumenProduccionService::generar()
  - Calcular: total_producida, total_buena, total_fallada, tasa_defectos, eficiencia
  - Status: ✓ Completado o ✗ Pendiente

- [ ] **T2.7 - Crear evento ProduccionRegistrada**
  - Crear: `app/Events/ProduccionRegistrada.php`
  - Implementar: ShouldBroadcast (Echo/Reverb)
  - Broadcastear: puesta_en_marcha_id, cantidad_producida, cantidad_buena
  - Status: ✓ Completado o ✗ Pendiente

- [ ] **T2.8 - Testear endpoints POST/PATCH**
  - Crear jornada: POST `/api/jornadas` → Verificar id retornado
  - Cambiar operador: POST `/api/operadores-cambio` → Verificar actualización
  - Registrar producción: POST `/api/produccion` → Verificar inserción
  - Finalizar: PATCH `/api/jornadas/{id}` → Verificar ts_fin y estado
  - Status: ✓ Completado o ✗ Pendiente

### Deliverables FASE 2
- ✓ Todos los endpoints REST funcionales
- ✓ Validadores de requests implementados
- ✓ Lógica de cálculo de resumen completa
- ✓ Eventos Echo/Reverb configurados
- ✓ APIs testeadas manualmente (Postman/curl)

---

## FASE 3: Dashboard y Reportes

### Objetivo
Crear vistas del dashboard para visualizar producción, disponibilidad, defectos y reportes históricos.

### Tareas

- [ ] **T3.1 - Crear vista dashboard producción en vivo**
  - Crear: `resources/views/dashboard/produccion-vivo.blade.php`
  - Mostrar: Jornada actual, puesta_en_marcha activa, operador actual
  - Mostrar: Últimos 10 registros de produccion_detalle
  - Status: ✓ Completado o ✗ Pendiente

- [ ] **T3.2 - Crear vista de métricas en tiempo real**
  - Crear: `resources/views/dashboard/metricas.blade.php`
  - Mostrar: Total producida, total buena, total fallada, tasa defectos (%)
  - Mostrar: Eficiencia (%), disponibilidad (%)
  - Status: ✓ Completado o ✗ Pendiente

- [ ] **T3.3 - Implementar actualización en vivo con Echo**
  - Escuchar evento: `ProduccionRegistrada`
  - Actualizar métricas sin refrescar página
  - Mostrar animación de cambios
  - Status: ✓ Completado o ✗ Pendiente

- [ ] **T3.4 - Crear vista de historial de operadores**
  - Crear: `resources/views/dashboard/operadores-jornada.blade.php`
  - Mostrar: Quién inició, cambios registrados, quién está ahora
  - Mostrar: Hora de cada cambio y razón
  - Status: ✓ Completado o ✗ Pendiente

- [ ] **T3.5 - Crear vista de resumen de puesta en marcha**
  - Crear: `resources/views/dashboard/resumen-puesta.blade.php`
  - Mostrar: Datos de resumen_produccion (totales, promedios)
  - Mostrar: Gráfico de producción por intervalo
  - Status: ✓ Completado o ✗ Pendiente

- [ ] **T3.6 - Crear reporte por rango de fechas**
  - Crear: `resources/views/reportes/produccion-rango.blade.php`
  - Filtros: Máquina, fecha inicio, fecha fin
  - Mostrar: Tabla con jornadas, puestas, totales, promedios
  - Status: ✓ Completado o ✗ Pendiente

- [ ] **T3.7 - Crear reporte de disponibilidad**
  - Crear: `resources/views/reportes/disponibilidad.blade.php`
  - Calcular: (horas_produccion) / (24 - horas_mantenimiento) * 100
  - Mostrar: Disponibilidad por máquina, por día, por semana
  - Status: ✓ Completado o ✗ Pendiente

- [ ] **T3.8 - Crear endpoint GET para reportes**
  - GET `/api/reportes/produccion?fecha_inicio=&fecha_fin=&maquina_id=`
  - GET `/api/reportes/disponibilidad?fecha_inicio=&fecha_fin=&maquina_id=`
  - Retornar JSON para consumo frontend/Excel
  - Status: ✓ Completado o ✗ Pendiente

### Deliverables FASE 3
- ✓ Dashboard en vivo funcional
- ✓ Actualización en tiempo real con Echo
- ✓ Vistas de reportes completas
- ✓ Endpoints GET para exportación

---

## FASE 4: Tests, Optimización y Deploy

### Objetivo
Implementar cobertura de tests, optimizar queries, securizar endpoints y preparar para producción.

### Tareas

- [ ] **T4.1 - Crear tests unitarios para modelos**
  - Test: Jornada→PuestaEnMarcha relación
  - Test: CambioOperadorJornada creación y actualización
  - Test: ResumenProduccion cálculos
  - Status: ✓ Completado o ✗ Pendiente

- [ ] **T4.2 - Crear tests de API endpoints**
  - Test: POST `/api/jornadas` con datos válidos
  - Test: POST `/api/jornadas` con datos inválidos
  - Test: POST `/api/operadores-cambio`
  - Test: POST `/api/produccion`
  - Status: ✓ Completado o ✗ Pendiente

- [ ] **T4.3 - Crear tests de lógica de negocio**
  - Test: Cálculo de disponibilidad
  - Test: Cálculo de tasa de defectos
  - Test: Cálculo de eficiencia
  - Status: ✓ Completado o ✗ Pendiente

- [ ] **T4.4 - Optimizar queries N+1**
  - Revisar: JornadaController, PuestaEnMarchaController
  - Agregar: eager loading (with) donde sea necesario
  - Verificar: Query count en cada endpoint
  - Status: ✓ Completado o ✗ Pendiente

- [ ] **T4.5 - Implementar autenticación en API**
  - Agregar: Middleware auth:sanctum en rutas
  - Verificar: Usuario tiene permiso para máquina/jornada
  - Status: ✓ Completado o ✗ Pendiente

- [ ] **T4.6 - Crear índices adicionales en PostgreSQL**
  - Crear índices en: maquina_id, ts_inicio DESC (columnas frecuentes en WHERE)
  - Verificar: EXPLAIN ANALYZE en queries complejas
  - Status: ✓ Completado o ✗ Pendiente

- [ ] **T4.7 - Configurar compresión TimescaleDB**
  - Implementar: add_compression_policy para produccion_detalle (6 meses)
  - Implementar: add_retention_policy (12 meses)
  - Status: ✓ Completado o ✗ Pendiente

- [ ] **T4.8 - Preparar environment producción**
  - Configurar: `.env` para PostgreSQL
  - Configurar: Reverb para broadcast
  - Configurar: Queue (para jobs asincronos)
  - Status: ✓ Completado o ✗ Pendiente

- [ ] **T4.9 - Ejecutar cobertura de tests**
  - Correr: `php artisan test`
  - Verificar: Cobertura >= 80%
  - Corregir: Fallos críticos
  - Status: ✓ Completado o ✗ Pendiente

- [ ] **T4.10 - Deploy a producción**
  - Backup: Base de datos previa a deploy
  - Ejecutar: php artisan migrate
  - Verificar: Todos los endpoints funcionan
  - Monitorear: Logs durante 24 horas
  - Status: ✓ Completado o ✗ Pendiente

### Deliverables FASE 4
- ✓ Cobertura de tests >= 80%
- ✓ Queries optimizadas
- ✓ Sistema securizado con auth
- ✓ Sistema en producción y funcional

---

## Criterios de Aceptación

### FASE 1 ✓
- [ ] Todas las migraciones ejecutadas sin errores
- [ ] Tablas existen en PostgreSQL con estructura correcta
- [ ] Modelos Eloquent se cargan sin errores
- [ ] Relaciones se pueden traversar sin N+1 queries

### FASE 2 ✓
- [ ] Todos los endpoints retornan 200/201 con datos válidos
- [ ] Validadores rechazan datos inválidos (400/422)
- [ ] Cambios de operador se registran correctamente
- [ ] Resumen se genera automáticamente al finalizar puesta

### FASE 3 ✓
- [ ] Dashboard carga sin errores
- [ ] Actualización en vivo funciona (Echo)
- [ ] Reportes generan datos correctos
- [ ] Exportación a JSON/CSV funciona

### FASE 4 ✓
- [ ] Tests pasan sin fallos
- [ ] Cobertura de código >= 80%
- [ ] Sistema está en producción
- [ ] No hay alertas críticas en logs

---

## Riesgos y Mitigación

| Riesgo | Impacto | Probabilidad | Mitigación |
|--------|---------|--------------|-----------|
| Delay en integración Echo/Reverb | Alto | Media | Testear broadcast temprano (Sprint 2) |
| Performance con volumen de datos | Alto | Media | Optimizar índices (Sprint 4) |
| Cambios en requerimientos mid-project | Medio | Alta | Validar con stakeholders cada Sprint |
| Falta de datos de máquina en testing | Medio | Media | Crear fixtures/seeders en Sprint 1 |

---

## Recursos Requeridos

- **Backend**: Laravel 11, PHP 8.2+
- **Base de Datos**: PostgreSQL 14+ con TimescaleDB
- **Frontend**: Blade, Alpine.js/Vue.js
- **Real-time**: Reverb, Echo
- **Testing**: Pest/PHPUnit

---

## Próximas Acciones

1. ✓ Validar este plan con stakeholders
2. ✓ Asignar recursos a cada Sprint
3. → Comenzar Sprint 1 (Migraciones)

---

## Control de Cambios

| Versión | Fecha | Cambio |
|---------|-------|--------|
| 1.0 | 13 Nov 2025 | Plan inicial (4 sprints) |
| 2.0 | - | Por definir |
