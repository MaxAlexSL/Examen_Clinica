# Seguridad

El examen toma como referencia las guías de OWASP. Además, los controles se relacionan con el Anexo A de ISO/IEC 27001:2022 y con la Ley N.º 29733 (Protección de Datos Personales, Perú), ya que los datos de salud son **datos sensibles**.

> El sistema **no "cumple ISO 27001"**: esa norma certifica el sistema de gestión de una organización. Aquí se muestra qué controles técnicos de la aplicación contribuyen a esos objetivos.

## Controles implementados

| Control en la aplicación | OWASP | ISO 27001:2022 (Anexo A) |
|---|---|---|
| Autorización en el servidor por código de permiso; rol sin permisos no opera; retirar un permiso bloquea en la siguiente petición | Authorization | 5.15 Control de acceso · 5.18 Derechos de acceso · 8.3 Restricción de acceso a la información |
| Solo quien tiene `usuarios.*`/`roles.*` gestiona accesos; garantía de al menos un administrador | Authorization | 8.2 Derechos de acceso privilegiado |
| Vendedor solo ve sus ventas (Policy + `visibleTo`), también al cambiar el id en la URL | Authorization (IDOR) | 8.3 |
| Contraseñas con hash bcrypt del framework | Password Storage | 5.17 Información de autenticación |
| Sesión renovada al ingresar, invalidada al salir; usuario inactivo expulsado en la siguiente petición; sin "Recordarme" | Session Management | 8.5 Autenticación segura · 5.16 Gestión de identidades |
| Mensaje de login genérico + límite de intentos | Authentication | 8.5 |
| Páginas autenticadas sin caché (`no-store`), `Clear-Site-Data` al salir; tras el login siempre se va al dashboard | Session Management | 8.3 |
| Validación en el servidor (Form Requests), CSRF, escape `{{ }}`, Eloquent parametrizado | Input Validation | 8.28 Codificación segura |
| Precio y total calculados en el servidor; nunca se leen del navegador | Input Validation | 8.28 |
| Transacción con bloqueo de filas: sin ventas parciales ni stock negativo | — | 8.28 |
| Solo datos ficticios; `.env` fuera del repositorio; BD de pruebas separada | — | 8.31 Separación de entornos · 8.33 Información de prueba |
| Usuario de BD con mínimo privilegio | — | 8.2 |
| Pruebas automatizadas de casos de seguridad | — | 8.29 Pruebas de seguridad |
| El formulario de venta envía al navegador solo nombre, código y documento de los pacientes activos | — | 5.34 Privacidad y protección de datos personales · Ley 29733 |

## Credenciales de demostración

Las credenciales del README son **exclusivas del entorno local de prueba**. No deben reutilizarse en ningún otro entorno.
