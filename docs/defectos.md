# Defectos encontrados en pruebas y su corrección

Defectos detectados durante las pruebas manuales del autor, después de tener el flujo funcional.

| # | Defecto | Riesgo | Corrección | Referencia |
|---|---|---|---|---|
| D-01 | El sistema podía quedarse sin administrador (quitarse `roles.editar`/`roles.ver`, cambiar el rol o desactivar al último administrador) | Pérdida total de la gestión de accesos; obligaba a reinstalar | Regla "al menos un usuario activo con permisos de administración" validada al editar roles, cambiar rol y desactivar usuarios; comando de emergencia `php artisan acceso:restaurar-admin {email}` | ISO 27001 8.2 · OWASP Authorization |
| D-02 | Tras cerrar sesión, el botón "Atrás" mostraba páginas del usuario anterior | Exposición de datos de pacientes y ventas en equipos compartidos | Middleware `PreventBackHistory` (`Cache-Control: no-store`) en las rutas autenticadas, `Clear-Site-Data: "cache"` en logout, recarga en `pageshow` si el navegador restaura la página desde su caché, y tras el login siempre se va al dashboard (se descarta `url.intended`, que llevaba a la última página del usuario anterior); títulos de página genéricos | OWASP Session Management |
| D-03 | En "Nueva venta" el filtro de pacientes no filtraba y los productos se elegían en un desplegable con todo el catálogo | Selección del paciente o producto equivocado | Búsqueda por código, documento o nombre (coincidencia por inicio de palabra) con máximo 8 resultados visibles, aviso "No hay coincidencias. Solicite la afiliación del paciente.", productos sin stock no seleccionables y sugerencia de los 5 más vendidos. De cada paciente solo se envía al navegador nombre, código y documento | Ley 29733 (minimización) |

## Ajustes de usabilidad

| # | Ajuste |
|---|---|
| U-01 | Listados de pacientes, productos y ventas ordenados del más reciente al más antiguo (usuarios y roles, por nombre) |
| U-02 | Mensajes de duplicado claros: "El documento ya existe." / "El código ya existe." |
| U-03 | Botón "Volver al listado" visible en la parte superior (componente reutilizable) |
| U-04 | Ficha ("Ver") también para productos |

## Limitación conocida

El navegador no permite que una aplicación web borre su historial: las URLs visitadas siguen en él. Al volver a una de ellas ya no se muestra la copia en caché; el servidor la responde de nuevo: sin sesión redirige al login, y con otra sesión responde según los permisos del usuario actual (por ejemplo, 403 o solo sus propias ventas). **Recomendación operativa:** en equipos compartidos, cerrar el navegador al terminar el turno.
