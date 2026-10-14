# Especificación de requerimientos

Fuente: Examen Técnico N.º 001-2026-AJMZ-CPS — "Accesos, afiliación de pacientes y venta de productos".

## Actores

| Actor | Responsabilidad |
|---|---|
| Administrador | Gestiona usuarios, roles y permisos; acceso total; ve todas las ventas. |
| Admisión | Consulta, registra, edita y desactiva pacientes. |
| Vendedor | Consulta pacientes y productos, registra ventas y consulta solo sus propias ventas. |

> Los actores son la **configuración inicial**. El sistema autoriza por **permiso**, no por nombre de rol: un rol nuevo con los mismos permisos funciona igual.

## Requerimientos funcionales (RF)

| Código | Módulo | Requerimiento |
|---|---|---|
| RF-01 | Acceso | Iniciar sesión con correo y contraseña |
| RF-02 | Acceso | Cerrar sesión |
| RF-03 | Acceso | Impedir el ingreso y la operación con sesión previa a usuarios inactivos |
| RF-04 | Usuarios | Registrar y editar usuarios (nombre, correo único, contraseña, estado, rol) |
| RF-05 | Roles | Crear y editar roles |
| RF-06 | Roles | Asignar y retirar permisos a un rol |
| RF-07 | Roles | Catálogo de permisos (código, nombre, módulo) cargado por seeder |
| RF-08 | Pacientes | Registrar paciente con código de afiliación generado por el sistema |
| RF-09 | Pacientes | Editar paciente |
| RF-10 | Pacientes | Listar y buscar por documento o nombre |
| RF-11 | Pacientes | Consultar ficha del paciente (incluye historial de ventas visible para el usuario) |
| RF-12 | Pacientes | Desactivar / reactivar paciente |
| RF-13 | Productos | Registrar producto con stock inicial |
| RF-14 | Productos | Editar producto (sin modificar el stock) |
| RF-15 | Productos | Listar, buscar y consultar ficha del producto |
| RF-16 | Productos | Desactivar / reactivar producto |
| RF-17 | Ventas | Registrar venta: paciente activo → productos y cantidades → revisar total → confirmar |
| RF-18 | Ventas | Mostrar el detalle guardado tras confirmar |
| RF-19 | Ventas | Listar ventas con filtros por fecha y paciente, con total |
| RF-20 | Ventas | Consultar detalle de una venta |

## Reglas de negocio (RN)

| Código | Regla |
|---|---|
| RN-01 | Un usuario tiene un solo rol |
| RN-02 | Un rol sin permisos no opera; retirar un permiso bloquea la acción en la siguiente petición |
| RN-03 | Código de afiliación único generado por el sistema (`AF-000001`) |
| RN-04 | No se repite la combinación tipo + número de documento |
| RN-05 | La fecha de nacimiento no puede ser futura |
| RN-06 | Desactivar no elimina; no se borran físicamente productos ni pacientes con ventas |
| RN-07 | Precio > 0; stock entero ≥ 0; tras el alta el stock solo cambia por ventas |
| RN-08 | Venta solo a paciente ACTIVO, con productos ACTIVOS, ≥ 1 línea, cantidades enteras > 0 y stock suficiente |
| RN-09 | Si un producto se repite en varias líneas, se suman sus cantidades para validar stock |
| RN-10 | Precio desde la BD; subtotales y total calculados en el servidor; vendedor tomado de la sesión; precio aplicado guardado en cada línea |
| RN-11 | Medios de pago EFECTIVO/TARJETA; soles con 2 decimales; venta confirmada no se edita ni elimina |
| RN-12 | Quien tiene `ventas.ver_todas` ve todas las ventas; quien tiene `ventas.ver` solo las propias |
| RN-13 | Cabecera, detalles y descuento de stock en una sola transacción |
| RN-14 | Siempre debe existir al menos un usuario activo con permisos de administración de roles y usuarios |

## Requerimientos no funcionales (RNF)

| Código | Requerimiento |
|---|---|
| RNF-01 | Contraseñas con hash del framework; sesión renovada al ingresar e invalidada al salir |
| RNF-02 | Autorización en el servidor por código de permiso (no se depende de ocultar botones) |
| RNF-03 | Validación en el servidor, CSRF, escape de salida y consultas parametrizadas |
| RNF-04 | Integridad en BD: claves foráneas, restricciones únicas, CHECK, importes DECIMAL |
| RNF-05 | Instalable localmente con migraciones, seeders y `.env.example` sin secretos |
| RNF-06 | Solo datos ficticios; datos de salud tratados como sensibles (Ley 29733) |
| RNF-07 | Mensajes de validación claros en español |
| RNF-08 | Páginas autenticadas no almacenadas en caché del navegador |
| RNF-09 | Pruebas automatizadas de los casos críticos |

## Fuera de alcance

Integración con ERP de CPS y SUNAT, pasarelas de pago, seguros, membresías, historia clínica, prescripción, multisede, reportes avanzados, registro público y recuperación de contraseña por correo.

## Matriz de trazabilidad

Rutas definidas en `routes/web.php` y `routes/auth.php`. Pruebas en `tests/Feature/` (archivo::método). Casos de aceptación detallados en [casos-aceptacion.md](casos-aceptacion.md).

| RF/RN | Ruta o clase que lo implementa | Prueba automatizada | Caso |
|---|---|---|---|
| RF-01 | `POST /login` → `AuthenticatedSessionController@store`, `LoginRequest` | `Auth/AuthenticationTest::test_users_can_authenticate_using_the_login_screen`, `::test_users_can_not_authenticate_with_invalid_password` | 01 |
| RF-02 | `POST /logout` (`logout`) → `AuthenticatedSessionController@destroy` | `Auth/AuthenticationTest::test_users_can_logout`, `::test_dashboard_redirects_to_login_after_logout` | 01 |
| RF-03 | `LoginRequest::authenticate` (`is_active = true`), middleware `EnsureUserIsActive` | `Auth/AuthenticationTest::test_inactive_users_can_not_authenticate`, `::test_active_session_is_terminated_when_user_is_deactivated` | — |
| RF-04 | `users.*` → `UserController`, `StoreUserRequest`, `UpdateUserRequest` | `UserManagementTest::test_admin_creates_user_with_role_and_hashed_password`, `::test_password_is_kept_when_left_blank_on_update` | 02 |
| RF-05 | `roles.*` → `RoleController`, `StoreRoleRequest`, `UpdateRoleRequest` | `RoleManagementTest::test_admin_creates_role_with_permissions`, `::test_role_name_must_be_unique` | — |
| RF-06 | `PUT /roles/{role}` (`roles.update`) → `RoleController@update` | `RoleManagementTest::test_removing_a_permission_blocks_the_next_request`, `::test_unknown_permissions_are_rejected` | 03 |
| RF-07 | `PermissionSeeder` (código, `display_name`, `module`) | Indirecta: todas las pruebas cargan el seeder en `setUp()` | — |
| RF-08 | `POST /pacientes` (`patients.store`) → `PatientService::create` | `PatientManagementTest::test_admission_creates_patient_with_generated_code` | 04 |
| RF-09 | `PUT /pacientes/{patient}` (`patients.update`) → `PatientService` | `PatientManagementTest::test_update_keeps_own_document_and_affiliation_code` | — |
| RF-10 | `GET /pacientes` (`patients.index`) → `PatientController@index` | `PatientManagementTest::test_list_shows_latest_affiliated_first`, `::test_seller_can_list_but_not_create_or_deactivate` | — |
| RF-11 | `GET /pacientes/{patient}` (`patients.show`) → `PatientController@show` | `Ventas/FlujoVentaHttpTest::test_la_ficha_del_paciente_muestra_su_historial_de_ventas`, `PatientManagementTest::test_admission_can_open_show_create_and_edit_pages` | — |
| RF-12 | `PATCH /pacientes/{patient}/desactivar` y `/activar` | `PatientManagementTest::test_deactivate_only_changes_status` | — |
| RF-13 | `POST /productos` (`products.store`) → `ProductService`, `StoreProductRequest` | `ProductManagementTest::test_admin_creates_product` | — |
| RF-14 | `PUT /productos/{product}` (`products.update`), `UpdateProductRequest` (sin `stock`) | `ProductManagementTest::test_update_ignores_stock` | — |
| RF-15 | `GET /productos` y `GET /productos/{product}` | `ProductManagementTest::test_list_shows_price_format_and_out_of_stock_badge`, `::test_seller_can_view_product_but_admission_can_not` | — |
| RF-16 | `PATCH /productos/{product}/desactivar` y `/activar` | `ProductManagementTest::test_deactivate_only_changes_status` | — |
| RF-17 | `GET /ventas/nueva` → `POST /ventas/revisar` → `POST /ventas` (`SaleController`, `SaleService`) | `Ventas/FlujoVentaHttpTest::test_revisar_muestra_el_total_y_no_guarda_nada`, `::test_confirmar_crea_la_venta_y_redirige_al_detalle` | 05 |
| RF-18 | `SaleController@store` → redirección a `sales.show` | `Ventas/FlujoVentaHttpTest::test_confirmar_crea_la_venta_y_redirige_al_detalle` | 05 |
| RF-19 | `GET /ventas` (`sales.index`) → `SaleController@index` | `Ventas/FlujoVentaHttpTest::test_listado_muestra_la_venta_y_el_total_filtrado`, `Ventas/VentaAjenaTest::test_el_administrador_filtra_las_ventas_por_vendedor` | 09 |
| RF-20 | `GET /ventas/{sale}` (`sales.show`) → `SalePolicy::view` | `Ventas/VentaAjenaTest::test_vendedor_no_puede_ver_el_detalle_de_una_venta_ajena` | 09 |
| RN-01 | `syncRoles([$rol])` en `UserController` y `UserSeeder` | `UserManagementTest::test_admin_creates_user_with_role_and_hashed_password` | 02 |
| RN-02 | Middleware `permission:` en cada ruta; permisos leídos en cada petición | `RoleManagementTest::test_role_without_permissions_can_not_access_users`, `::test_removing_a_permission_blocks_the_next_request`, `Ventas/PermisoVentaRetiradoTest::test_sin_ventas_crear_el_vendedor_recibe_403_y_no_se_crea_venta` | 03 |
| RN-03 | `PatientService::create` (código desde el `id` en la transacción); UNIQUE `affiliation_code` | `PatientManagementTest::test_admission_creates_patient_with_generated_code` | — |
| RN-04 | `StorePatientRequest` (`Rule::unique`), UNIQUE(`document_type`, `document_number`), `PatientService` (error 1062) | `PatientManagementTest::test_duplicate_document_is_rejected`, `::test_same_number_with_different_type_is_allowed`, `::test_database_duplicate_error_becomes_validation_error` | 04 |
| RN-05 | `StorePatientRequest` (`birth_date` ≤ hoy) | `PatientManagementTest::test_future_birth_date_is_rejected` | — |
| RN-06 | Sin rutas `destroy`; FKs `restrictOnDelete`; desactivar solo cambia `status` | `PatientManagementTest::test_deactivate_only_changes_status`, `ProductManagementTest::test_deactivate_only_changes_status` | — |
| RN-07 | `StoreProductRequest`, `UpdateProductRequest`; CHECK `price > 0`; `stock` UNSIGNED | `ProductManagementTest::test_zero_or_negative_price_is_rejected`, `::test_negative_or_decimal_initial_stock_is_rejected`, `::test_update_ignores_stock` | — |
| RN-08 | `SaleService::calculate`, `StoreSaleRequest`; CHECK `quantity > 0` | `Ventas/ReglasVentaTest` (7 pruebas) | 06 |
| RN-09 | `SaleService::groupLines` | `Ventas/StockInsuficienteTest::test_vender_6_mas_5_del_mismo_producto_en_dos_lineas_se_rechaza`, `Ventas/VentaCorrectaTest::test_lineas_repetidas_se_agrupan_en_un_solo_detalle` | 06 |
| RN-10 | `SaleService` (precio de la BD, céntimos, `unit_price` por línea, vendedor = usuario autenticado) | `Ventas/PrecioManipuladoTest::test_precio_total_y_vendedor_del_request_se_ignoran`, `Ventas/PrecioHistoricoTest::test_cambiar_el_precio_no_altera_la_venta_registrada` | 05, 07, 08 |
| RN-11 | `SaleService::PAYMENT_METHODS`; sin rutas para editar ni eliminar ventas | `Ventas/ReglasVentaTest::test_metodo_de_pago_invalido_se_rechaza` | — |
| RN-12 | `SalePolicy`, scope `Sale::visibleTo` | `Ventas/VentaAjenaTest` (6 pruebas), `Ventas/SalePolicyTest` (4 pruebas) | 09 |
| RN-13 | `SaleService::create` (`DB::transaction` + `lockForUpdate`) | `Ventas/TransaccionRollbackTest::test_fallo_en_la_segunda_linea_revierte_venta_detalles_y_stock`, `Ventas/StockInsuficienteTest` | 10 |
| RN-14 | `AdminGuard` (en `UpdateRoleRequest` y `UpdateUserRequest`), comando `acceso:restaurar-admin` | `AdminGuardTest` (9 pruebas) | — |
| RNF-08 | Middleware `PreventBackHistory`, logout con `Clear-Site-Data` | `Auth/PreventBackHistoryTest` (4 pruebas) | — |
