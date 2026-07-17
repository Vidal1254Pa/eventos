# Control de Asistencia con Eventos (PHP + MySQL)

## Novedad principal
Ahora el sistema permite **programar eventos** (día y hora) y llevar la asistencia **por evento**.
Cada evento muestra su **cantidad de asistentes** y el panel calcula el **porcentaje** por evento.

## Requisitos
- XAMPP (Apache + PHP 8.1/8.2 + MySQL/MariaDB)
- Composer

## Instalación (nuevo)
1) Copia la carpeta `asistencia_control_php_eventos` a:
   `C:\xampp\htdocs\asistencia_control_php_eventos\`

2) Importa `database.sql` (crea tablas con eventos).

3) Ajusta `config.php`:
   - DB_USER, DB_PASS
   - BASE_URL: `/asistencia_control_php_eventos/`

4) Instala librerías:
```bash
composer install
```

5) Abre:
http://localhost/asistencia_control_php_eventos/

## Si ya tenías el sistema anterior
Ejecuta en phpMyAdmin el archivo:
- `migrate_add_eventos.sql`

## Acceso inicial
- Usuario: admin
- Clave: admin123

Autor: Mirko Renato Del Águila Rengifo


## Asistencia con código de barras
- En **Registro > Asistencia**, usa el campo **Escanear DNI**.
- Con un lector USB (modo teclado), el lector escribirá el DNI y enviará **Enter** para registrar.
- El sistema busca el asistente por `dni`.
