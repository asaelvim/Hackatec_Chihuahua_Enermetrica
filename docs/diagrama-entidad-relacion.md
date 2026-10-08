# Diagrama Entidad-Relación — Enermetrica

> Este diagrama usa **nombres en español** solo para fines de documentación y
> entendimiento del modelo de datos. La base de datos real (tablas y columnas
> en Laravel) sigue usando nombres en **inglés** (ver tabla de equivalencias al
> final), ya que renombrarlas implicaría migraciones y cambios en todo el
> código (modelos, controladores, la app Flutter, etc.).
>
> Formato: [Mermaid](https://mermaid.js.org/syntax/entityRelationshipDiagram.html).
> Puedes editarlo aquí mismo o pegarlo en https://mermaid.live para verlo y
> modificarlo visualmente.

```mermaid
erDiagram
    USUARIOS ||--o{ DISPOSITIVOS : "ordena encendido/apagado"
    USUARIOS ||--o{ TOKENS_NOTIFICACION_MOVIL : "registra"

    AREAS ||--o{ DISPOSITIVOS : "agrupa"
    AREAS ||--o{ HORARIOS : "aplica a"

    TIPOS_DISPOSITIVO ||--o{ DISPOSITIVOS : "clasifica"
    MODELOS_DISPOSITIVO ||--o{ DISPOSITIVOS : "especifica"

    DISPOSITIVOS ||--o{ DISPOSITIVOS : "controla (relevador)"
    DISPOSITIVOS ||--o{ HORARIOS : "aplica a"
    DISPOSITIVOS ||--o{ LECTURAS_CONSUMO : "genera"
    DISPOSITIVOS ||--o{ ANOMALIAS : "presenta"
    DISPOSITIVOS ||--o{ RESUMENES_CONSUMO_DIARIO : "resume"

    LECTURAS_CONSUMO ||--o{ ANOMALIAS : "origina"

    USUARIOS {
        bigint id PK
        string nombre
        string correo UK
        timestamp correo_verificado_en
        string contrasena
        timestamp creado_en
        timestamp actualizado_en
    }

    AREAS {
        bigint id PK
        string nombre
        timestamp creado_en
        timestamp actualizado_en
    }

    TIPOS_DISPOSITIVO {
        bigint id PK
        string nombre
        timestamp creado_en
        timestamp actualizado_en
    }

    MODELOS_DISPOSITIVO {
        bigint id PK
        string nombre
        timestamp creado_en
        timestamp actualizado_en
    }

    DISPOSITIVOS {
        bigint id PK
        string nombre
        bigint area_id FK
        bigint tipo_dispositivo_id FK
        bigint modelo_dispositivo_id FK
        bigint dispositivo_controlador_id FK "ESP32 dueño del relevador (si aplica)"
        tinyint canal_relevador "1-5, null si no es un relevador"
        enum estado "on | off | offline | maintenance"
        string estado_reportado "último estado confirmado por el ESP32"
        timestamp ultima_lectura_en
        string token_api UK "token del ESP32 para ingestar lecturas"
        timestamp ordenado_en "cuándo se envió el último comando"
        bigint ordenado_por FK "usuario que envió el comando"
        timestamp reportado_en "cuándo el ESP32 confirmó el estado"
        timestamp creado_en
        timestamp actualizado_en
    }

    LECTURAS_CONSUMO {
        bigint id PK
        bigint dispositivo_id FK
        decimal valor "watts"
        datetime leido_en
        timestamp creado_en
        timestamp actualizado_en
    }

    HORARIOS {
        bigint id PK
        string nombre
        enum alcance "company | area | device"
        bigint area_id FK "nulo si el alcance no es 'area'"
        bigint dispositivo_id FK "nulo si el alcance no es 'device'"
        time hora_inicio
        time hora_fin
        string dias_semana "ej. 0,1,2,3,4,5,6"
        boolean activo
        timestamp creado_en
        timestamp actualizado_en
    }

    ANOMALIAS {
        bigint id PK
        bigint dispositivo_id FK
        bigint lectura_consumo_id FK
        decimal z_score
        decimal valor
        timestamp notificado_en
        timestamp revisado_en
        timestamp creado_en
        timestamp actualizado_en
    }

    TOKENS_NOTIFICACION_MOVIL {
        bigint id PK
        bigint usuario_id FK
        string token_fcm UK
        enum plataforma "android | ios"
        timestamp creado_en
        timestamp actualizado_en
    }

    RESUMENES_CONSUMO_DIARIO {
        bigint id PK
        bigint dispositivo_id FK
        date fecha
        decimal total_kwh
        decimal promedio_watts
        decimal minimo_watts
        decimal maximo_watts
        int numero_lecturas
        timestamp creado_en
        timestamp actualizado_en
    }
```

## Equivalencia con los nombres reales (tablas/columnas en inglés)

| Entidad (español)           | Tabla real (inglés)             | Notas |
|------------------------------|----------------------------------|-------|
| USUARIOS                     | `users`                          | Incluye `name`, `email`, `password`, etc. |
| AREAS                        | `areas`                          | |
| TIPOS_DISPOSITIVO            | `device_types`                   | |
| MODELOS_DISPOSITIVO          | `device_models`                  | |
| DISPOSITIVOS                 | `devices`                        | `area_id`, `device_type_id`, `device_model_id`, `controller_device_id`, `relay_channel`, `status`, `reported_status`, `last_reading_at`, `api_token`, `commanded_at`, `commanded_by`, `reported_at` |
| LECTURAS_CONSUMO             | `consumption_readings`           | `device_id`, `value`, `read_at` |
| HORARIOS                     | `schedules`                      | `scope`, `area_id`, `device_id`, `start_time`, `end_time`, `weekdays`, `is_active` |
| ANOMALIAS                    | `anomalies`                      | `device_id`, `consumption_reading_id`, `z_score`, `value`, `notified_at`, `reviewed_at` |
| TOKENS_NOTIFICACION_MOVIL    | `device_tokens`                  | `user_id`, `fcm_token`, `platform` — **ojo**: a pesar del nombre, esta tabla guarda tokens de notificaciones push del celular del usuario, no de los dispositivos IoT (eso lo maneja `devices.api_token`) |
| RESUMENES_CONSUMO_DIARIO     | `daily_consumption_summaries`    | `device_id`, `date`, `total_kwh`, `avg_watts`, `min_watts`, `max_watts`, `readings_count` |

No se incluyen las tablas internas de Laravel (`sessions`, `cache`, `jobs`,
`password_reset_tokens`, `personal_access_tokens`) por no ser parte del
dominio de negocio.
