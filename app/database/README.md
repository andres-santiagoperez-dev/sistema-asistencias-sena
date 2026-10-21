# Estructura y Gestión de Base de Datos - Sistema de Asistencias SENA

Este directorio contiene la arquitectura de persistencia y el esquema relacional de la aplicación (`sena_asistencia`).

---

## Organización de Archivos

```
app/database/
├── schema.sql            # Definición DDL canónica y consolidada (100% actualizada)
├── seed_test_data.sql    # Fixtures y datos de prueba para entorno local/desarrollo
└── migrations/           # Parches incrementales ordenados cronológicamente
    ├── 001_create_llamados_atencion.sql
    └── 002_create_suplencias_horarios.sql
```

---

## Guía de Despliegue y Uso

### 1. Instalación desde Cero (Nueva Instalación / Staging / CI-CD)
Para aprovisionar un servidor o base de datos limpia:
1. Importar **únicamente** `schema.sql`. Contiene todas las tablas maestras, llaves foráneas e índices consolidados.
2. (Opcional en desarrollo) Importar `seed_test_data.sql` para cargar usuarios, roles y fichas de prueba.

### 2. Actualización de una Base de Datos Existente en Producción
Si el sistema ya está en producción con información de aprendices, instructores y asistencias reales:
- **NO reimportar `schema.sql`** (evita conflictos o sobreescritura de datos).
- Ejecutar secuencialmente los scripts dentro de `migrations/` que aún no hayan sido aplicados en dicho servidor:
  - `001_create_llamados_atencion.sql`: Agrega auditoría de marcación manual y tabla de llamados de atención disciplinarios.
  - `002_create_suplencias_horarios.sql`: Agrega el módulo de gestión, solicitud y aprobación de suplencias de instructores.
