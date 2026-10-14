# Decisiones técnicas

| # | Decisión | Motivo | Alternativa descartada |
|---|---|---|---|
| DT-01 | Laravel 13 + Breeze (Blade/Tailwind) + spatie/laravel-permission | Stack sugerido por el examen; Breeze resuelve autenticación probada; Spatie es el estándar para roles y permisos | Autenticación y permisos desde cero (más código que mantener y explicar) |
| DT-02 | Autorización **siempre por código de permiso**, nunca por nombre de rol | Un rol nuevo con los mismos permisos funciona sin tocar código | `if ($user->role === 'Vendedor')` |
| DT-03 | Un solo rol por usuario (`syncRoles([$rol])`), sin permisos directos al usuario | Lo pide el alcance mínimo y simplifica la auditoría de accesos | Varios roles por usuario |
| DT-04 | Sin tabla `persona`; los datos personales viven en `patients` | El alcance no relaciona usuarios con pacientes y el paciente no tiene cuenta | Tabla `persona` compartida (queda como mejora si se incorporan médicos o apoderados) |
| DT-05 | Códigos `AF-000001` y `V-000001` generados a partir del `id` dentro de la transacción | Únicos incluso con registros simultáneos | Contador en memoria o `MAX()+1` |
| DT-06 | Estado del producto independiente del stock | Estado = decisión administrativa; stock = disponibilidad. El examen los pide como casos distintos | Desactivar automáticamente al llegar a stock 0 |
| DT-07 | Stock no editable tras el alta | El examen exige que cambie solo por ventas; `UpdateProductRequest` no acepta `stock` | Edición libre del stock |
| DT-08 | Lógica de venta en `SaleService` (`quote()` y `create()`) | Reutilizable entre "Revisar" y "Confirmar", testeable sin HTTP y explicable en un solo archivo | Lógica en el controlador |
| DT-09 | Precio leído de la BD, importes calculados en céntimos enteros y `unit_price` guardado por línea | Evita precios manipulados, errores de redondeo y conserva el precio histórico | Confiar en el precio/total enviado por el navegador |
| DT-10 | `DB::transaction` + `lockForUpdate` sobre paciente y productos (ordenados por id) | Todo o nada; evita stock negativo con ventas simultáneas y deadlocks | Guardar en pasos sin transacción |
| DT-11 | Flujo Revisar → Confirmar con token de un solo uso en sesión + redirección (PRG) | Evita ventas duplicadas por doble clic o recarga | Confirmar directo sin protección |
| DT-12 | Búsqueda de pacientes y productos en el navegador (Alpine.js) sobre los registros ACTIVOS cargados con la página: coincidencia por inicio de palabra, máx. 8 resultados visibles, sugerencia de los 5 productos más vendidos | Respuesta inmediata sin peticiones extra; de cada paciente solo se envía nombre, código de afiliación y documento (no teléfono, correo ni fecha de nacimiento) | Un `<select>` con todo el catálogo. *Mejora futura:* búsqueda en el servidor (AJAX) si el catálogo crece |
| DT-13 | Garantía de al menos un administrador activo + comando `acceso:restaurar-admin` | Evita perder la gestión de accesos; recuperación sin reinstalar | Proteger solo "el rol propio" |
| DT-14 | Mensaje de login genérico ("Credenciales incorrectas o usuario inactivo") + rate limiting | No revela si el correo existe o si la cuenta está inactiva | Mensajes diferenciados |
| DT-15 | Se eliminaron de Breeze: registro público, recuperación por correo, verificación de email y perfil | Fuera de alcance; el perfil permitía eliminar la propia cuenta | Mantenerlos ocultos |
| DT-16 | Pruebas sobre MariaDB (`examen_clinica_test`), no SQLite | Las pruebas validan claves foráneas, CHECK, transacciones y bloqueos reales | SQLite en memoria (ignora `lockForUpdate`) |
| DT-17 | Usuario de BD propio (`clinica`) con privilegios solo sobre sus bases | Mínimo privilegio | Usar `root` |
| DT-18 | Sin opción "Recordarme" en el login (se ignora el campo `remember` aunque se envíe) | En equipos compartidos de la clínica la sesión no debe sobrevivir a su vencimiento ni al cierre del navegador | Mantener la casilla de Breeze |
