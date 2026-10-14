# Diagrama entidad-relación

Generado a partir de las migraciones reales de `database/migrations/`. Incluye las tablas propias del sistema y las tablas de spatie/laravel-permission que se usan (equipos/`teams` desactivado en `config/permission.php`).

Notación: **PK** clave primaria, **FK** clave foránea, **UK** restricción única. Las restricciones `CHECK` y las únicas compuestas se indican en el comentario de la columna.

```mermaid
erDiagram
    users {
        bigint id PK
        varchar name
        varchar email UK
        timestamp email_verified_at "nullable"
        varchar password "hash bcrypt"
        boolean is_active "default true"
        varchar remember_token "nullable, no se usa"
        timestamp created_at
        timestamp updated_at
    }

    sessions {
        varchar id PK
        bigint user_id "nullable, índice (sin FK)"
        varchar ip_address "nullable"
        text user_agent "nullable"
        longtext payload
        int last_activity "índice"
    }

    roles {
        bigint id PK
        varchar name UK "UNIQUE(name, guard_name)"
        varchar guard_name UK "UNIQUE(name, guard_name)"
        timestamp created_at
        timestamp updated_at
    }

    permissions {
        bigint id PK
        varchar name UK "código, UNIQUE(name, guard_name)"
        varchar display_name "nullable, nombre legible"
        varchar module "nullable, 50"
        varchar guard_name UK "UNIQUE(name, guard_name)"
        timestamp created_at
        timestamp updated_at
    }

    role_has_permissions {
        bigint permission_id PK, FK "cascadeOnDelete"
        bigint role_id PK, FK "cascadeOnDelete"
    }

    model_has_roles {
        bigint role_id PK, FK "cascadeOnDelete"
        varchar model_type PK "modelo User"
        bigint model_id PK "id del usuario (polimórfico, sin FK)"
    }

    patients {
        bigint id PK
        varchar affiliation_code UK "AF-000001, nullable hasta generarse"
        enum document_type UK "DNI|CE|PASAPORTE, UNIQUE(document_type, document_number)"
        varchar document_number UK "20, UNIQUE(document_type, document_number)"
        varchar first_names "100"
        varchar last_names "100"
        date birth_date
        date affiliation_date
        varchar phone "nullable, 20"
        varchar email "nullable"
        enum status "ACTIVO|INACTIVO, default ACTIVO"
        timestamp created_at
        timestamp updated_at
    }

    products {
        bigint id PK
        varchar code UK "20"
        varchar name "150"
        text description "nullable"
        decimal price "10,2 - CHECK (price > 0)"
        int stock "UNSIGNED, default 0"
        enum status "ACTIVO|INACTIVO, default ACTIVO"
        timestamp created_at
        timestamp updated_at
    }

    sales {
        bigint id PK
        varchar code UK "V-000001, nullable hasta generarse"
        bigint patient_id FK "restrictOnDelete"
        bigint user_id FK "vendedor, restrictOnDelete"
        enum payment_method "EFECTIVO|TARJETA"
        decimal total "10,2"
        datetime sold_at
        timestamp created_at
        timestamp updated_at
    }

    sale_items {
        bigint id PK
        bigint sale_id FK "restrictOnDelete"
        bigint product_id FK "restrictOnDelete"
        int quantity "UNSIGNED - CHECK (quantity > 0)"
        decimal unit_price "10,2 - precio aplicado"
        decimal subtotal "10,2"
        timestamp created_at
        timestamp updated_at
    }

    roles ||--o{ role_has_permissions : "tiene"
    permissions ||--o{ role_has_permissions : "asignado en"
    roles ||--o{ model_has_roles : "asignado en"
    users ||--|| model_has_roles : "tiene un rol"
    users ||--o{ sessions : "abre"
    patients ||--o{ sales : "compra"
    users ||--o{ sales : "registra (vendedor)"
    sales ||--|{ sale_items : "contiene"
    products ||--o{ sale_items : "vendido en"
```

## Relaciones

| Relación | Cardinalidad | Explicación |
|---|---|---|
| `roles` — `role_has_permissions` — `permissions` | N:M | Un rol agrupa varios permisos y un permiso puede estar en varios roles. Si se borra un rol o permiso, se borran sus asignaciones (`cascadeOnDelete`). |
| `users` — `model_has_roles` — `roles` | 1 usuario : 1 rol | La tabla de Spatie admite varios roles, pero el sistema asigna siempre uno con `syncRoles([$rol])` (RN-01). Es polimórfica (`model_type`, `model_id`), por eso no tiene FK hacia `users`. |
| `users` — `sessions` | 1:N | Con `SESSION_DRIVER=database`, cada sesión abierta es una fila; `user_id` queda en null hasta el login. Solo tiene índice, no FK. |
| `patients` — `sales` | 1:N | Un paciente puede tener muchas ventas. `restrictOnDelete`: no se puede borrar un paciente con ventas (RN-06). |
| `users` — `sales` | 1:N | El vendedor es el usuario autenticado que confirmó la venta (RN-10). `restrictOnDelete`. |
| `sales` — `sale_items` | 1:N (mínimo 1) | Cabecera y detalle se guardan en la misma transacción (RN-13); la aplicación exige al menos una línea (RN-08). |
| `products` — `sale_items` | 1:N | Cada línea guarda `unit_price` y `subtotal` del momento de la venta, para que un cambio de precio no altere ventas pasadas (RN-10). `restrictOnDelete`. |

## Tablas no incluidas en el diagrama

- `model_has_permissions` (Spatie): existe, pero no se usa; los permisos se asignan solo a roles.
- `password_reset_tokens`: creada por la migración base de Laravel; no se usa (no hay recuperación de contraseña).
- `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`: infraestructura de Laravel.
