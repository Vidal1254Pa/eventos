# Control de Asistencia con Eventos (PHP + PostgreSQL)

## Cambios principales
- La aplicacion ahora carga configuracion desde un archivo `.env`.
- La conexion de base de datos fue ajustada a `PostgreSQL` usando `PDO`.
- Se reforzo la seguridad de sesion, cookies, cabeceras HTTP, CSRF y login.

## Requisitos
- PHP 8.1 o superior con extensiones `pdo` y `pdo_pgsql`
- PostgreSQL 13 o superior
- Composer

## Instalacion
1. Crea la base de datos en PostgreSQL.
2. Ejecuta `database.sql` sobre la base `asistencia_db`.
3. Copia `.env.example` a `.env` y ajusta las credenciales.
4. Verifica `BASE_URL` segun la ruta donde publicas el proyecto.
5. Instala dependencias:

```bash
composer install
```

6. Abre la aplicacion en tu navegador.

## Variables de entorno
Ejemplo base en `.env.example`:

```env
APP_ENV=production
APP_DEBUG=false
APP_NAME=Control de Asistencia
APP_TZ=America/Lima
BASE_URL=/eventos/

DB_DRIVER=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_NAME=asistencia_db
DB_USER=postgres
DB_PASS=postgres
DB_SSLMODE=prefer
```

## Usuario inicial
- Usuario: `admin`
- Clave: `admin123`

Cambia esa clave apenas ingreses por primera vez.

## Migracion desde esquema anterior
Si ya tenias una instalacion sin eventos en PostgreSQL, ejecuta:

- `migrate_add_eventos.sql`

## Lector de codigo de barras
- En `Registro > Asistencia`, usa el campo `Escanear DNI`.
- Un lector USB en modo teclado escribira el DNI y enviara `Enter`.
