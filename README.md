# Gastos Hormiga

Aplicación Laravel para anotar los **gastos hormiga**: esos gastos pequeños y diarios (el café, la botana, el transporte, la suscripción) que casi no se sienten pero que al mes suman. Registra cada gasto con monto, categoría, fecha y una nota opcional, y muestra cuánto llevas gastado en el mes y en el día

Es un **CRUD básico sin autenticación**: cualquiera con acceso a la URL puede crear, ver, editar y borrar gastos. Está pensada para uso personal o como proyecto de práctica.

## Requisitos

- **PHP 8.3 o superior** (el `composer.json` exige `^8.3`; probado con PHP 8.5)
- **Composer 2**
- **SQLite** (la extensión `pdo_sqlite` viene incluida en PHP por defecto)
- Node.js + npm solo si quieres compilar los assets con Vite (opcional)

## Instalación

```bash
# 1. Dependencias de PHP
composer install

# 2. Archivo de entorno
cp .env.example .env          # Linux / macOS
copy .env.example .env        # Windows (CMD)
Copy-Item .env.example .env   # Windows (PowerShell)

# 3. Clave de la aplicación
php artisan key:generate

# 4. Base de datos SQLite
touch database/database.sqlite                      # Linux / macOS
type nul > database\database.sqlite                 # Windows (CMD)
New-Item -ItemType File database\database.sqlite    # Windows (PowerShell)

# 5. Tablas y datos de ejemplo
php artisan migrate --seed

# 6. Servidor local
php artisan serve
```

Abre <http://localhost:8000> (la raíz redirige a `/gastos`).

> El `.env.example` ya trae `APP_NAME="Gastos Hormiga"`, `APP_LOCALE=es`, `DB_CONNECTION=sqlite` y `SESSION_DRIVER=database`. Si cambias la ruta del archivo SQLite, ajusta `DB_DATABASE`.

## Seeder (opcional)

`php artisan migrate --seed` ejecuta `DatabaseSeeder`. El seeder es **opcional**: la app funciona sin datos de ejemplo. En la versión actual crea:

- Un usuario de demostración con estas credenciales:
  - Correo: `test@example.com`
  - Contraseña: `password`
- Unos 40 gastos de ejemplo repartidos en los últimos 30 días (incluidos algunos de hoy) para que el listado y los totales no salgan vacíos.

Como el CRUD **no tiene autenticación**, no necesitas iniciar sesión con ese usuario; existe solo como dato de ejemplo. Si prefieres empezar limpio: `php artisan migrate:fresh` (sin `--seed`).

## Rutas principales

| Método      | URI                    | Nombre           | Acción                       |
| ----------- | ---------------------- | ---------------- | ---------------------------- |
| `GET`       | `/`                    | —                | Redirige a `gastos.index`    |
| `GET`       | `/gastos`              | `gastos.index`   | Listado + totales y filtros  |
| `GET`       | `/gastos/create`       | `gastos.create`  | Formulario de alta           |
| `POST`      | `/gastos`              | `gastos.store`   | Guarda un gasto              |
| `GET`       | `/gastos/{gasto}`      | `gastos.show`    | Detalle de un gasto          |
| `GET`       | `/gastos/{gasto}/edit` | `gastos.edit`    | Formulario de edición        |
| `PUT/PATCH` | `/gastos/{gasto}`      | `gastos.update`  | Actualiza un gasto           |
| `DELETE`    | `/gastos/{gasto}`      | `gastos.destroy` | Elimina un gasto             |

### Filtros del listado

- `GET /gastos?categoria=Café` — solo gastos de esa categoría.
- `GET /gastos?mes=2026-11` — solo gastos de ese mes (formato `YYYY-MM`).
- Ambos filtros se pueden combinar. Los totales y el promedio diario respetan el mes seleccionado.

## Modelo y validación

Modelo: `App\Models\GastoHormiga` (tabla `gastos_hormiga`).

| Campo         | Tipo           | Reglas de validación                            |
| ------------- | -------------- | ----------------------------------------------- |
| `descripcion` | string(120)    | `required`, `max:120`                           |
| `monto`       | decimal(10,2)  | `required`, `numeric`, `min:0.01`               |
| `categoria`   | string(40)     | `required`, `in:` una de las categorías válidas |
| `fecha`       | date           | `required`, `date`, `before_or_equal:today`     |
| `notas`       | text, nullable | `nullable`, `max:500`                           |

Los errores se muestran en el formulario (en los tests: `assertSessionHasErrors`).

**Categorías** (`GastoHormiga::CATEGORIAS`): Café, Antojo, Transporte, Botana, Suscripción, Otro.

Casts: `fecha` → `date` (Carbon) y `monto` → `decimal:2` (string con dos decimales). Scope `delMes('YYYY-MM')` para filtrar por mes.

## Cómo correr los tests

Los tests usan PHPUnit 12 con SQLite **en memoria** (configurado en `phpunit.xml`), así que no tocan tu base de datos local.

```bash
# Toda la suite
php artisan test

# Solo el CRUD (Feature)
php artisan test tests/Feature/GastoHormigaTest.php
php artisan test --filter=GastoHormigaTest

# Solo el modelo (Unit)
php artisan test tests/Unit/GastoHormigaModelTest.php
php artisan test --filter=GastoHormigaModelTest

# Equivalente vía Composer (limpia la caché de config antes)
composer test
```

### Pruebas incluidas

`tests/Feature/GastoHormigaTest.php` — 15 tests del CRUD: redirección de la raíz, listado, cálculo del total del mes, alta, validaciones (campos obligatorios, monto inválido, fecha futura, categoría inexistente), detalle, edición, actualización, borrado y filtros por categoría y por mes.

`tests/Unit/GastoHormigaModelTest.php` — 4 tests del modelo: constante `CATEGORIAS`, cast de `monto` con dos decimales, cast de `fecha` a Carbon y scope `delMes`.

## Estructura del proyecto

```
app/
├── Http/
│   ├── Controllers/GastoHormigaController.php   CRUD (resource `gastos`)
│   └── Requests/GastoRequest.php                Validación del formulario
└── Models/GastoHormiga.php                      Modelo, categorías, casts y scope delMes

database/
├── factories/GastoHormigaFactory.php
├── migrations/                      Tabla `gastos_hormiga` (+ tablas base de Laravel)
├── seeders/DatabaseSeeder.php
└── database.sqlite                  Base de datos local (no versionada)

resources/views/
├── layouts/app.blade.php            Layout base
└── gastos/
    ├── index.blade.php              Listado, totales y filtros
    ├── create.blade.php
    ├── edit.blade.php
    ├── show.blade.php
    └── partials/form.blade.php      Formulario compartido por create y edit

routes/web.php                       Rutas del CRUD y redirección de la raíz

tests/
├── Feature/GastoHormigaTest.php
└── Unit/GastoHormigaModelTest.php
```

## Notas

- **Sin autenticación**: no hay login ni middleware `auth`; todas las rutas son públicas. No publiques la app en un servidor abierto sin añadir autenticación.
- La zona horaria por defecto es `UTC` (`config/app.php`). Si registras gastos cerca de medianoche y tu hora local no es UTC, ajusta `timezone` (por ejemplo `America/Mexico_City`) para que "hoy" y "este mes" coincidan con tu calendario.
- El total del mes se calcula en el controlador y se pasa a la vista (`$totalMes`, `$totalHoy`, `$cantidadMes`, `$promedioDiario`, `$porCategoria`); el formato de moneda es solo presentación.
