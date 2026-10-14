# Examen_clinica — Accesos, afiliación de pacientes y venta de productos

## 1. Descripción

Plataforma web para el personal de una clínica, desarrollada como examen técnico (Asistente de Sistemas). Permite:

- iniciar sesión y operar según los **permisos** del rol asignado;
- administrar usuarios, roles y permisos;
- afiliar, editar, consultar y desactivar pacientes (código `AF-000001` generado por el sistema);
- registrar productos con stock inicial y consultarlos;
- registrar ventas (paciente → productos → revisar → confirmar) con descuento de stock en una sola transacción, y consultarlas según permisos.

Especificación completa en [docs/requerimientos.md](docs/requerimientos.md) y modelo de datos en [docs/diagrama-er.md](docs/diagrama-er.md).

## 2. Tecnologías y versiones

Versiones exactas instaladas (de `composer.lock`, `package-lock.json` y `php -v`):

| Componente | Versión |
|---|---|
| PHP | 8.4.25 |
| Laravel (`laravel/framework`) | 13.36.0 |
| Laravel Breeze (Blade) | 2.4.2 |
| spatie/laravel-permission | 8.3.0 |
| PHPUnit | 12.5.39 |
| MariaDB (XAMPP) | 10.4.32 |
| Tailwind CSS | 3.4.19 |
| @tailwindcss/forms | 0.5.11 |
| Alpine.js | 3.17.4 |
| Vite | 7.3.7 |
| laravel-vite-plugin | 2.1.0 |
| Axios | 1.20.0 |
| Composer (usado en desarrollo) | 2.10.3 |
| Node.js / npm (usados en desarrollo) | 24.13.1 / 11.14.0 |

Zona horaria `America/Lima`, idioma `es`, importes en soles (S/) con 2 decimales.

## 3. Requisitos previos

- Windows con **XAMPP** (MariaDB 10.4.32). [COMPLETAR: versión de XAMPP]
- **PHP 8.3 o superior** (`composer.json` exige `^8.3`; desarrollado con 8.4.25) con las extensiones que exige Laravel (entre ellas `pdo_mysql`, `mbstring`, `openssl` y `fileinfo`), accesible desde la terminal.
- **Composer** 2.x.
- **Node.js** 20.19 o superior (lo exige Vite 7; desarrollado con 24.13.1) y npm.
- **Git**.

## 4. Instalación desde cero (Windows / XAMPP)

1. **Clonar el repositorio**

   ```bash
   git clone [COMPLETAR: URL del repositorio] Examen_clinica
   cd Examen_clinica
   ```

2. **Instalar dependencias**

   ```bash
   composer install
   npm install
   ```

3. **Crear el archivo de entorno y la clave de la aplicación**

   ```bash
   copy .env.example .env
   php artisan key:generate
   ```

4. **Crear las bases de datos y el usuario** (iniciar MySQL/MariaDB desde el panel de XAMPP). Abrir la consola de MariaDB como `root`, por ejemplo con `C:\xampp\mysql\bin\mysql.exe -u root -p` (ruta por defecto de XAMPP), y ejecutar, reemplazando `<contraseña>` por una contraseña elegida:

   ```sql
   CREATE DATABASE examen_clinica CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE DATABASE examen_clinica_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

   CREATE USER 'clinica'@'localhost' IDENTIFIED BY '<contraseña>';
   GRANT ALL PRIVILEGES ON examen_clinica.* TO 'clinica'@'localhost';
   GRANT ALL PRIVILEGES ON examen_clinica_test.* TO 'clinica'@'localhost';
   FLUSH PRIVILEGES;
   ```

   El usuario `clinica` solo tiene privilegios sobre sus dos bases (mínimo privilegio). `examen_clinica_test` la usan las pruebas automatizadas.

5. **Configurar `.env`** con la misma contraseña (el resto ya viene en `.env.example`):

   ```
   DB_CONNECTION=mariadb
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=examen_clinica
   DB_USERNAME=clinica
   DB_PASSWORD=<contraseña>
   ```

6. **Crear las tablas y cargar los datos iniciales**

   ```bash
   php artisan migrate:fresh --seed
   ```

7. **Compilar los estilos y scripts**

   ```bash
   npm run build
   ```

8. **Levantar el servidor**

   ```bash
   php artisan serve
   ```

   Abrir <http://127.0.0.1:8000> en el navegador.

## 5. Credenciales de prueba

| Usuario | Correo | Rol | Contraseña |
|---|---|---|---|
| Administrador Clínica | `clinica@example.com` | Administrador | `clinica123` |
| Personal de Admisión | `admision@example.com` | Admisión | `clinica123` |
| Vendedor Uno | `vendedor1@example.com` | Vendedor | `clinica123` |
| Vendedor Dos | `vendedor2@example.com` | Vendedor | `clinica123` |

> ⚠️ Estas credenciales son **exclusivas del entorno local de prueba** y las crea el seeder. No deben reutilizarse en ningún otro entorno.

Si se pierde el acceso de administración, se puede restaurar con:

```bash
php artisan acceso:restaurar-admin clinica@example.com
```

## 6. Datos iniciales cargados

Todos los datos son **ficticios** (`database/seeders/`):

- **17 permisos** con código, nombre legible y módulo (`PermissionSeeder`).
- **3 roles**: Administrador, Admisión y Vendedor, con la matriz de la sección 7 (`RoleSeeder`).
- **4 usuarios** activos, uno por fila de la tabla anterior (`UserSeeder`).
- **5 pacientes** (`AF-000001` a `AF-000005`): DNI, CE y pasaporte; el último (`Castillo Vega, Rosa María`) está INACTIVO (`PatientSeeder`).
- **5 productos** (`ProductSeeder`):

| Código | Producto | Precio | Stock | Estado |
|---|---|---|---|---|
| PRD-001 | Botella reutilizable | S/ 12.50 | 10 | ACTIVO |
| PRD-002 | Kit de aseo | S/ 20.00 | 5 | ACTIVO |
| PRD-003 | Termómetro digital | S/ 35.00 | 8 | ACTIVO |
| PRD-004 | Mascarillas (caja x50) | S/ 15.00 | 0 | ACTIVO |
| PRD-005 | Alcohol en gel 500 ml | S/ 9.90 | 12 | INACTIVO |

No se cargan ventas.

## 7. Módulos y matriz de permisos

El sistema autoriza siempre por **código de permiso**, nunca por nombre de rol: un rol nuevo con los mismos permisos funciona igual. La matriz siguiente es la configuración inicial.

| Módulo | Permiso | Descripción | Administrador | Admisión | Vendedor |
|---|---|---|:-:|:-:|:-:|
| Usuarios | `usuarios.ver` | Ver usuarios | ✔ | | |
| Usuarios | `usuarios.crear` | Crear usuarios | ✔ | | |
| Usuarios | `usuarios.editar` | Editar usuarios (incluye estado y rol) | ✔ | | |
| Roles | `roles.ver` | Ver roles | ✔ | | |
| Roles | `roles.crear` | Crear roles | ✔ | | |
| Roles | `roles.editar` | Editar roles y sus permisos | ✔ | | |
| Pacientes | `pacientes.ver` | Ver pacientes | ✔ | ✔ | ✔ |
| Pacientes | `pacientes.crear` | Afiliar pacientes | ✔ | ✔ | |
| Pacientes | `pacientes.editar` | Editar pacientes | ✔ | ✔ | |
| Pacientes | `pacientes.desactivar` | Desactivar / reactivar pacientes | ✔ | ✔ | |
| Productos | `productos.ver` | Ver productos | ✔ | | ✔ |
| Productos | `productos.crear` | Crear productos | ✔ | | |
| Productos | `productos.editar` | Editar productos (sin stock) | ✔ | | |
| Productos | `productos.desactivar` | Desactivar / reactivar productos | ✔ | | |
| Ventas | `ventas.crear` | Registrar ventas | ✔ | | ✔ |
| Ventas | `ventas.ver` | Ver ventas propias | ✔ | | ✔ |
| Ventas | `ventas.ver_todas` | Ver todas las ventas | ✔ | | |

Sin un permiso, el servidor responde **403** aunque se escriba la URL a mano; el menú solo oculta las opciones por comodidad.

## 8. Pruebas automatizadas

Las pruebas usan MariaDB real en la base `examen_clinica_test` (configurada en `phpunit.xml`), no SQLite, para validar claves foráneas, `CHECK`, transacciones y bloqueos.

```bash
php artisan test
```

Resultado de la última ejecución:

```
Tests:    98 passed (409 assertions)
```

Pruebas que cubren los casos de aceptación (rutas relativas a `tests/Feature/`):

| Prueba | Caso |
|---|---|
| `Auth/AuthenticationTest.php` | 01 |
| `UserManagementTest.php` (`test_seller_can_not_access_users_index`, `test_seller_can_not_change_own_role`) | 02 |
| `Ventas/PermisoVentaRetiradoTest.php`, `RoleManagementTest.php::test_removing_a_permission_blocks_the_next_request` | 03 |
| `PatientManagementTest.php` (`test_duplicate_document_is_rejected` y relacionadas) | 04 |
| `Ventas/VentaCorrectaTest.php`, `Ventas/FlujoVentaHttpTest.php` | 05 |
| `Ventas/StockInsuficienteTest.php` | 06 |
| `Ventas/PrecioManipuladoTest.php` | 07 |
| `Ventas/PrecioHistoricoTest.php` | 08 |
| `Ventas/VentaAjenaTest.php`, `Ventas/SalePolicyTest.php` | 09 |
| `Ventas/TransaccionRollbackTest.php` | 10 |

Otras pruebas: `AdminGuardTest.php` (siempre queda un administrador), `Auth/PreventBackHistoryTest.php` (sin caché tras cerrar sesión), `ProductManagementTest.php`, `Ventas/ReglasVentaTest.php`, `Ventas/DobleEnvioTest.php`. La relación completa requerimiento → prueba está en la [matriz de trazabilidad](docs/requerimientos.md#matriz-de-trazabilidad).

## 9. Seguridad

- Autorización en el servidor por código de permiso en cada ruta (`permission:`) y con `SalePolicy` para el detalle de ventas.
- Contraseñas con hash bcrypt; sesión regenerada al ingresar e invalidada al salir; usuarios inactivos no ingresan y son expulsados en su siguiente petición; sin "Recordarme".
- Mensaje de login genérico y límite de 5 intentos.
- Sin registro público ni recuperación de contraseña.
- Form Requests con `validated()`, CSRF, escape `{{ }}` y consultas Eloquent parametrizadas.
- Precio, subtotales, total y vendedor calculados en el servidor; venta en una transacción con bloqueo de filas.
- Páginas autenticadas sin caché del navegador y `Clear-Site-Data` al cerrar sesión.
- Siempre queda al menos un usuario activo capaz de administrar roles y usuarios.
- Solo datos ficticios; `.env` fuera del repositorio; base de pruebas separada.

Detalle y relación con OWASP, ISO/IEC 27001:2022 y la Ley N.º 29733 en [docs/seguridad.md](docs/seguridad.md).

## 10. Casos de aceptación

Los 10 casos del examen (login/logout, accesos del vendedor, retiro de permiso, documento duplicado, venta de ejemplo S/ 57.50, stock insuficiente, precio manipulado, precio histórico, venta ajena y reversión de la transacción) están cubiertos por pruebas automatizadas. Pasos manuales, capturas esperadas en `docs/evidencias/` y resultados en [docs/casos-aceptacion.md](docs/casos-aceptacion.md).

## 11. Decisiones técnicas

Resumen de las principales:

- Laravel 13 + Breeze + spatie/laravel-permission; un solo rol por usuario y autorización por permiso.
- Lógica de ventas en `SaleService`: precio desde la BD, cálculo en céntimos, `unit_price` guardado por línea, `DB::transaction` con `lockForUpdate`.
- Flujo Revisar → Confirmar con token de un solo uso para evitar ventas duplicadas.
- Stock no editable tras el alta; estado del producto independiente del stock.
- Pruebas sobre MariaDB y usuario de BD con mínimo privilegio.

Las 18 decisiones, con motivo y alternativa descartada, en [docs/decisiones.md](docs/decisiones.md).

## 12. Defectos corregidos

Defectos detectados en las pruebas manuales y ajustes de usabilidad: [docs/defectos.md](docs/defectos.md).

## 13. Pendientes y mejoras futuras

- Contraseña temporal al crear un usuario, con cambio obligatorio en el primer ingreso.
- Opción "Cambiar mi contraseña" para cada usuario.
- Anulación de ventas con devolución de stock (hoy una venta confirmada no se edita ni se elimina).
- Registro de auditoría (quién cambió qué y cuándo).
- Reposición de stock (entradas de mercadería).
- Reportes.

## 14. Horas dedicadas

15 h aprox.

## 15. Uso de IA y librerías

- **Claude**: planificación, análisis de requerimientos y diseño.
- **Claude Code**: generación de código guiada por `CLAUDE.md` (convenciones, reglas de negocio y de seguridad del proyecto).

Todo el código fue revisado, probado manualmente y cubierto con pruebas automatizadas por el autor, quien puede explicarlo y modificarlo.

Librerías de terceros: Laravel Breeze, spatie/laravel-permission, Tailwind CSS y Alpine.js.

## 16. Autor

Max Alex Soncco Luque
