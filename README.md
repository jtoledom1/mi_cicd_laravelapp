# Gastos Hormiga

Aplicación Laravel para anotar los **gastos hormiga**: esos gastos pequeños y diarios (el café, la botana, el transporte, la suscripción) que casi no se sienten pero que al mes suman. Registra cada gasto con monto, categoría, fecha y una nota opcional, y muestra cuánto llevas gastado en el mes, en el día y en el año.

La app es **multiusuario** y tiene **dos espacios**:

- **Personal**: cada persona ve únicamente sus propios gastos. Un gasto con `familia_id = NULL` es personal y solo lo ve su dueño.
- **Familia (hogar)**: varias personas comparten los gastos de la casa. Un gasto con `familia_id` lo ven todos los miembros del hogar, sin importar quién lo haya anotado.

Abrir un gasto ajeno (o de un hogar al que no perteneces) responde `404` para no revelar siquiera que existe.

## Los dos espacios y las cuentas

### Personal

Es el espacio por defecto. Cada usuario nace con una cuenta **General** personal y puede crear más cuentas para separar su dinero: *Dinero de mi abuelita*, *Lonche de la semana*, *Gasolina*…

### Hogar

Un hogar reúne a varias personas para compartir los gastos de la casa: la despensa, el gas, la luz. Cada hogar nace con su propia cuenta **General** y sus propias cuentas compartidas.

| Espacio | Quién ve los gastos | Se identifica por |
| ------- | ------------------- | ----------------- |
| Personal | Solo su dueño | `gastos_hormiga.familia_id = NULL` y `cuentas.user_id` |
| Hogar | Todos los miembros | `gastos_hormiga.familia_id` y `cuentas.familia_id` |

### Las cuentas son tipos de dinero

Una **cuenta** es una bolsa de dinero con nombre: el dinero de la abuelita, el presupuesto del gas, la despensa, la general. Cada gasto se carga a una cuenta y así sabes cuál se está acabando primero sin mezclar todo.

- Una cuenta con `user_id` es **personal** y solo la ve su dueño.
- Una cuenta con `familia_id` es **del hogar** y la ven todos los miembros.
- La cuenta **General** se crea automáticamente con cada usuario y con cada hogar. **No se puede eliminar**; cuando borras otra cuenta, sus gastos se mueven a la General del ámbito.
- Dentro de un mismo ámbito no puede haber dos cuentas con el mismo nombre (sin distinguir mayúsculas). El mismo nombre sí puede existir en personal y en el hogar, porque son espacios distintos.

## Requisitos

- **PHP 8.3 o superior** (el `composer.json` exige `^8.3`; probado con PHP 8.5)
- **Laravel 13** (se instala con Composer)
- **Composer 2**
- **SQLite** (la extensión `pdo_sqlite` viene incluida en PHP por defecto)
- Node.js + npm solo si quieres compilar los assets con Vite (opcional)

No hay que instalar extensiones PHP adicionales para las exportaciones: el **PDF** lo genera **dompdf** (`barryvdh/laravel-dompdf`) y el **Excel** **OpenSpout** (`openspout/openspout`). **No se necesita `gd`**.

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

Abre <http://localhost:8000>. La raíz redirige a `/gastos`, pero antes pasa por el **login**: sin sesión no se llega a los gastos. Entra con una de las cuentas del seeder (abajo) o crea la tuya en `/register`.

> El `.env.example` ya trae `APP_NAME="Gastos Hormiga"`, `APP_LOCALE=es`, `DB_CONNECTION=sqlite` y `SESSION_DRIVER=database`. Si cambias la ruta del archivo SQLite, ajusta `DB_DATABASE`.

## Credenciales del seeder

`php artisan migrate --seed` ejecuta `DatabaseSeeder`, que crea un hogar de ejemplo llamado **Casa Solar**:

| Correo               | Contraseña | Papel en el hogar | Datos de ejemplo |
| -------------------- | ---------- | ----------------- | ---------------- |
| `test@example.com`   | `password` | Propietario       | 42 gastos personales + gastos compartidos |
| `ana@example.com`    | `password` | Miembro           | 12 gastos personales + gastos compartidos |

Cuentas de ejemplo que crea el seeder:

| Cuenta | Espacio | Para qué |
| ------ | ------- | -------- |
| Dinero de mi abuelita | Personal de `test@example.com` | El dinero que le da la abuela |
| Lonche de la semana | Personal de `test@example.com` | El gasto diario de comer |
| General | Personal de cada usuario | Lo que no tiene cuenta propia |
| General | Hogar Casa Solar | Los gastos compartidos sin cuenta específica |
| Gas | Hogar Casa Solar | El presupuesto del gas |
| Despensa | Hogar Casa Solar | La comida de la casa |

El seeder es **opcional**: la app funciona sin datos de ejemplo; solo tendrás que registrar tu cuenta. Si prefieres empezar limpio: `php artisan migrate:fresh` (sin `--seed`).

## El hogar

La pantalla **Familia** (`/familia`) muestra la invitación a crear un hogar o a unirse con un código cuando todavía no perteneces a ninguno. Si ya tienes hogar, muestra sus miembros, su código de invitación, sus cuentas y lo gastado en el mes.

### Crear un hogar

1. Entra a **Familia** → **Crear un hogar**.
2. Escribe un nombre (obligatorio, máximo 80 caracteres).
3. Al crearlo quedas como **propietario** y miembro, y el hogar recibe su cuenta **General**.

Solo se puede pertenecer a **un hogar a la vez**. Si ya tienes uno, el intento de crear otro redirige con un mensaje de error y no lo duplica.

### Unirse con un código

1. Pide el **código de invitación** a quien creó el hogar (8 caracteres).
2. Entra a **Familia** → **Unirme con un código**.
3. Escríbelo (no importan las mayúsculas: el código se guarda en mayúsculas).

Si el código no existe, el formulario muestra el error en el campo `codigo` y no se crea ninguna membresía. Si ya tienes hogar, no puedes unirte a otro hasta salir del actual.

### Salir, eliminar y regenerar el código

- **Salir** (`DELETE /familia/salir`): un miembro normal se desvincula; el hogar sigue existiendo. El **propietario no puede salir**: para abandonarlo tiene que eliminarlo.
- **Eliminar** (`DELETE /familia`): solo el propietario. Borra el hogar en cascada (miembros, cuentas y gastos compartidos). A un miembro no propietario le responde `403`.
- **Regenerar el código** (`POST /familia/codigo`): solo el propietario. El código anterior deja de funcionar. A un miembro no propietario le responde `403`.

## Exportaciones

Desde el listado de gastos puedes descargar lo que estás viendo en **PDF** o **Excel**. Ambas exportaciones respetan los mismos filtros del listado:

| Filtro | Ejemplo | Qué hace |
| ------ | ------- | -------- |
| `mes` | `?mes=2026-11` | Solo los gastos de ese mes (`YYYY-MM`). Sin filtro: todo el historial. |
| `categoria` | `?categoria=Café` | Solo esa categoría válida. |
| `cuenta` | `?cuenta=3` | Solo los gastos de esa cuenta (debe pertenecer al ámbito). |
| `ambito` | `?ambito=familia` | Exporta el hogar en vez del espacio personal. |

- `GET /exportar/pdf` responde `200` con `Content-Type: application/pdf`.
- `GET /exportar/excel` responde `200` con el `Content-Type` de `.xlsx`.
- El nombre del archivo incluye el ámbito, el mes (o `historico`) y, si filtras, la cuenta. Por ejemplo: `gastos-hormiga-familia-2026-11-Gas.pdf`.
- En el ámbito familiar, el Excel añade la columna **Quién** y el PDF identifica a la persona que anotó cada gasto.
- Si no hay gastos, la descarga se genera igualmente (con los totales en cero).
- Si pides `ambito=familia` pero no perteneces a ningún hogar, la exportación cae al espacio personal en vez de fallar.

## Rutas principales

Las rutas marcadas con **Sesión** exigen estar autenticado; un invitado que las visita es redirigido a `/login`. Las de **Invitado** solo están disponibles sin sesión (si ya entraste, te mandan a tus gastos).

| Método      | URI                    | Nombre           | Acción                                   | Acceso   |
| ----------- | ---------------------- | ---------------- | ---------------------------------------- | -------- |
| `GET`       | `/login`               | `login`          | Formulario de entrada                    | Invitado |
| `POST`      | `/login`               | —                | Autentica al usuario                     | Invitado |
| `GET`       | `/register`            | `register`       | Formulario de registro                   | Invitado |
| `POST`      | `/register`            | —                | Crea la cuenta y deja la sesión iniciada | Invitado |
| `POST`      | `/logout`              | `logout`         | Cierra la sesión                         | Sesión   |
| `GET`       | `/`                    | —                | Redirige a `gastos.index`                | Sesión   |
| `GET`       | `/gastos`              | `gastos.index`   | Listado + totales y filtros (`?ambito=familia`) | Sesión |
| `GET`       | `/gastos/create`       | `gastos.create`  | Formulario de alta                       | Sesión   |
| `POST`      | `/gastos`              | `gastos.store`   | Guarda un gasto (personal o del hogar)   | Sesión   |
| `GET`       | `/gastos/{gasto}`      | `gastos.show`    | Detalle de un gasto                      | Sesión   |
| `GET`       | `/gastos/{gasto}/edit` | `gastos.edit`    | Formulario de edición                    | Sesión   |
| `PUT/PATCH` | `/gastos/{gasto}`      | `gastos.update`  | Actualiza un gasto                       | Sesión   |
| `DELETE`    | `/gastos/{gasto}`      | `gastos.destroy` | Elimina un gasto                         | Sesión   |
| `GET`       | `/familia`             | `familia.index`  | Hogar o invitación a crear/unirse        | Sesión   |
| `POST`      | `/familia`             | `familia.store`  | Crea el hogar                            | Sesión   |
| `POST`      | `/familia/unirse`      | `familia.unirse` | Se une con el código de invitación       | Sesión   |
| `DELETE`    | `/familia/salir`       | `familia.salir`  | Sale del hogar (no el propietario)       | Sesión   |
| `DELETE`    | `/familia`             | `familia.destroy`| Elimina el hogar (solo propietario)      | Sesión   |
| `POST`      | `/familia/codigo`      | `familia.codigo` | Regenera el código (solo propietario)    | Sesión   |
| `GET`       | `/cuentas`             | `cuentas.index`  | Lista de cuentas (`?ambito=familia`)     | Sesión   |
| `POST`      | `/cuentas`             | `cuentas.store`  | Crea una cuenta en el ámbito             | Sesión   |
| `PATCH`     | `/cuentas/{cuenta}`    | `cuentas.update` | Renombra una cuenta                      | Sesión   |
| `DELETE`    | `/cuentas/{cuenta}`    | `cuentas.destroy`| Borra la cuenta y mueve sus gastos a General | Sesión |
| `GET`       | `/resumen/mensual`     | `resumen.mensual`| Resumen del mes (`?mes=YYYY-MM`, `?ambito=familia`) | Sesión |
| `GET`       | `/resumen/anual`       | `resumen.anual`  | Resumen del año (`?anio=YYYY`, `?ambito=familia`) | Sesión |
| `GET`       | `/exportar/pdf`        | `exportar.pdf`   | Descarga PDF con los filtros             | Sesión   |
| `GET`       | `/exportar/excel`      | `exportar.excel` | Descarga Excel (.xlsx) con los filtros   | Sesión   |

### Filtros del listado

- `GET /gastos?ambito=familia` — gastos compartidos del hogar; sin el parámetro, solo los personales.
- `GET /gastos?mes=2026-11` — solo gastos de ese mes (formato `YYYY-MM`).
- `GET /gastos?categoria=Café` — solo gastos de esa categoría.
- `GET /gastos?cuenta=3` — solo los gastos de esa cuenta (del ámbito activo).
- Todos los filtros se pueden combinar. Los totales y el promedio diario respetan el mes y la cuenta seleccionados.
- El listado del ámbito personal solo incluye los gastos propios; el familiar, solo los del hogar.

## Resúmenes

Ambas pantallas trabajan con el ámbito activo (personal por defecto) y permiten moverse entre periodos. Agrega `?ambito=familia` para ver los totales del hogar.

### Resumen mensual (`/resumen/mensual`)

- Total gastado en el mes, número de gastos y promedio diario.
- Comparación con el mes anterior (total anterior y variación porcentual).
- Reparto por categoría, con importe, número de gastos y porcentaje del total.
- Ritmo día por día del mes, día más caro y los cinco gastos más altos.
- Navegación al mes anterior y al siguiente; por defecto muestra el mes actual. También acepta `?mes=YYYY-MM`.

### Resumen anual (`/resumen/anual`)

- Total del año, número de gastos y promedio por mes con datos.
- Los doce meses del año, con el mes de mayor gasto destacado.
- Comparación con el año anterior (total y variación porcentual).
- Reparto por categoría de todo el año.
- Selector de año; por defecto muestra el año actual. También acepta `?anio=YYYY`.

## Modelo y validación

### Gastos

Modelo: `App\Models\GastoHormiga` (tabla `gastos_hormiga`). Cada fila tiene un `user_id` obligatorio (quien la anotó) y, opcionalmente, `familia_id` y `cuenta_id`.

| Campo         | Tipo           | Reglas de validación                                       |
| ------------- | -------------- | ---------------------------------------------------------- |
| `descripcion` | string(120)    | `required`, `max:120`                                      |
| `monto`       | decimal(10,2)  | `required`, `numeric`, `min:0.01`, `max:99999.99`           |
| `categoria`   | string(40)     | `required`, `in:` una de las categorías válidas            |
| `fecha`       | date           | `required`, `date`, `before_or_equal:today`                |
| `notas`       | text, nullable | `nullable`, `max:500`                                      |
| `cuenta_id`   | FK, nullable   | `nullable`, `integer`; debe pertenecer al ámbito activo    |
| `ambito`      | string         | `nullable`, `in:personal,familia`                          |

Los errores se muestran en el formulario (en los tests: `assertSessionHasErrors`).

**Categorías** (`GastoHormiga::CATEGORIAS`): Café, Antojo, Transporte, Botana, Suscripción, Otro.

Casts: `fecha` → `date` (Carbon) y `monto` → `decimal:2` (string con dos decimales). Scopes `delMes('YYYY-MM')`, `personales()` y `delHogar($familiaId)`.

Al guardar, el controlador resuelve la cuenta (la del formulario o la General del ámbito) y de ahí deriva `familia_id = cuenta->familia_id`. Por eso un gasto cargado a una cuenta del hogar queda automáticamente como familiar.

### Hogar y cuentas

- **`Familia`**: `nombre` (máx. 80), `propietario_id`, `codigo_invitacion` (12, único). Relaciones `propietario()`, `miembros()` (tabla `familia_user`, un usuario por hogar), `cuentas()` y `gastos()`. `Familia::generarCodigo()` produce códigos de 8 caracteres.
- **`Cuenta`**: `nombre` (máx. 60), `user_id` (personal) o `familia_id` (hogar). Relaciones `user()`, `familia()` y `gastos()`. `esDeFamilia()` indica si es compartida.
- Al crear un usuario se crea su cuenta **General** personal; al crear una familia, su cuenta **General** del hogar.

### Autorización

- Un gasto personal solo lo puede ver/editar/borrar su dueño.
- Un gasto familiar lo puede ver cualquier miembro del hogar.
- Una cuenta personal solo la toca su dueño; una del hogar, cualquier miembro.
- Cualquier acceso ajeno responde `404`. Las acciones reservadas al propietario del hogar responden `403`.

### Registro

- `name`: `required`, `max:80`
- `email`: `required`, `email`, `unique:users,email`
- `password`: `required`, `min:8`, `confirmed`

Al registrarse, la cuenta queda autenticada y se redirige a `gastos.index` con un mensaje de bienvenida.

## Seguridad

- Las contraseñas se guardan **hasheadas con bcrypt** (cast `hashed` del modelo `User` y `Hash::make` de la factory); nunca en texto plano.
- El login tiene un **límite de 5 intentos fallidos** por correo + IP. Al superarlo responde con el mensaje «Demasiados intentos seguidos…» y hay que esperar antes de volver a intentar.
- Un usuario autenticado que visita `/login` o `/register` es redirigido a sus gastos; un invitado que intenta entrar a cualquier ruta privada va al login.
- El acceso a gastos y cuentas ajenos responde `404`.
- En el hogar, las operaciones de propietario (eliminar el hogar y regenerar el código) responden `403` a los demás miembros.

## Cómo correr los tests

Los tests usan PHPUnit 12 con SQLite **en memoria** (configurado en `phpunit.xml`), así que no tocan tu base de datos local.

```bash
# Toda la suite
php artisan test

# Solo autenticación
php artisan test tests/Feature/AuthTest.php
php artisan test --filter=AuthTest

# Solo el CRUD de gastos
php artisan test tests/Feature/GastoHormigaTest.php
php artisan test --filter=GastoHormigaTest

# Solo el hogar
php artisan test tests/Feature/FamiliaTest.php
php artisan test --filter=FamiliaTest

# Solo las cuentas
php artisan test tests/Feature/CuentaTest.php
php artisan test --filter=CuentaTest

# Solo las exportaciones
php artisan test tests/Feature/ExportarTest.php
php artisan test --filter=ExportarTest

# Solo resúmenes
php artisan test tests/Feature/ResumenTest.php
php artisan test --filter=ResumenTest

# Solo el modelo (Unit)
php artisan test tests/Unit/GastoHormigaModelTest.php
php artisan test --filter=GastoHormigaModelTest

# Equivalente vía Composer (limpia la caché de config antes)
composer test
```

### Pruebas incluidas

La app tiene **91 tests propios** (más el `ExampleTest` del esqueleto de Laravel, 92 en total):

- `tests/Feature/AuthTest.php` — **13 tests**: invitado redirigido al login, pantallas de login y registro, entrada con credenciales correctas, rechazo de contraseña incorrecta, bloqueo tras 5 intentos, registro (creación, sesión iniciada, validaciones, contraseña hasheada), cierre de sesión y redirecciones de la raíz y de `/login`.
- `tests/Feature/ResumenTest.php` — **12 tests**: invitado sin acceso, render del resumen mensual y anual, totales aislados por usuario, exclusión de otros meses, mes y año por defecto, parámetros `?mes` y `?anio`, reparto por categoría, año sin datos y estructura de `porMes`.
- `tests/Feature/GastoHormigaTest.php` — **26 tests**: los 21 originales del CRUD (raíz, listado, total del mes, alta, validaciones, detalle, edición, actualización, borrado, filtros y aislamiento entre usuarios) más **5 del ámbito familiar**: el index familiar muestra solo lo del hogar, el index personal no mezcla lo compartido, `store` con una cuenta del hogar guarda `familia_id`, otro miembro puede ver un gasto familiar y un usuario ajeno recibe `404`.
- `tests/Feature/FamiliaTest.php` — **14 tests**: invitado redirigido, pantalla sin hogar con la invitación, crear hogar (familia + membresía + cuenta General), no duplicar hogar, nombre obligatorio, unirse con código válido e inexistente, no unirse teniendo hogar, salir como miembro, el propietario no puede salir, eliminar el hogar en cascada, `403` al eliminar sin ser propietario, regenerar el código y `403` al regenerarlo sin ser propietario.
- `tests/Feature/CuentaTest.php` — **12 tests**: invitado redirigido, listado con la General automática, crear cuenta personal y del hogar, nombre único por ámbito (sin distinguir mayúsculas) pero repetible entre ámbitos, nombre obligatorio y máximo 60, renombrar lo propio, `404` al renombrar/borrar lo ajeno, borrar una cuenta mueve sus gastos a General, la General no se puede borrar y las cuentas de otros no aparecen.
- `tests/Feature/ExportarTest.php` — **10 tests**: invitado redirigido en PDF y Excel, `Content-Type` de cada formato, generación con gastos, filtro `?mes`, ámbito familia (solo gastos del hogar), export sin gastos, caída a personal cuando no hay hogar y filtro `?cuenta`.
- `tests/Unit/GastoHormigaModelTest.php` — **4 tests**: constante `CATEGORIAS`, cast de `monto` con dos decimales, cast de `fecha` a Carbon y scope `delMes`.

## Estructura del proyecto

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/LoginController.php             Login y logout
│   │   ├── Auth/RegisterController.php          Registro de cuentas
│   │   ├── GastoHormigaController.php           CRUD de gastos (resource `gastos`)
│   │   ├── FamiliaController.php                Crear/unirse/salir/eliminar el hogar
│   │   ├── CuentaController.php                 Cuentas personales y del hogar
│   │   ├── ExportController.php                 PDF (dompdf) y Excel (OpenSpout)
│   │   └── ResumenController.php                Resúmenes mensual y anual
│   └── Requests/
│       ├── GastoRequest.php                     Validación del formulario
│       └── Auth/{LoginRequest,RegisterRequest}.php
├── Models/
│   ├── GastoHormiga.php                         Gastos, categorías, casts y scopes
│   ├── Familia.php                              Hogar, miembros y código de invitación
│   ├── Cuenta.php                               Cuentas personales y del hogar
│   └── User.php                                 Usuario, gastos, familias y cuentas
└── Servicios/
    └── Ambito.php                               Resolución del espacio personal/familia

database/
├── factories/{GastoHormigaFactory,UserFactory}.php
├── migrations/                      users, gastos_hormiga, familias, familia_user, cuentas
├── seeders/DatabaseSeeder.php       Casa Solar con dos usuarios y cuentas de ejemplo
└── database.sqlite                  Base de datos local (no versionada)

resources/views/
├── layouts/app.blade.php            Layout de la app (navegación y cuenta)
├── auth/{login,register}.blade.php
├── gastos/                          Listado, alta, edición, detalle y formulario
├── familia/index.blade.php          Hogar: invitación, miembros y código
├── cuentas/index.blade.php          Lista y alta de cuentas
├── exportar/gastos.blade.php        Plantilla del PDF
└── resumen/{mensual,anual}.blade.php

routes/web.php                       Login, registro, gastos, familia, cuentas, resúmenes y exportar

tests/
├── Feature/AuthTest.php
├── Feature/ResumenTest.php
├── Feature/GastoHormigaTest.php
├── Feature/FamiliaTest.php
├── Feature/CuentaTest.php
├── Feature/ExportarTest.php
└── Unit/GastoHormigaModelTest.php
```

## Notas

- **Cada quien ve lo suyo; el hogar ve lo compartido.** No hay roles de administrador más allá del propietario del hogar, que es quien lo creó.
- La zona horaria por defecto es `UTC` (`config/app.php`). Si registras gastos cerca de medianoche y tu hora local no es UTC, ajusta `timezone` (por ejemplo `America/Mexico_City`) para que "hoy" y "este mes" coincidan con tu calendario.
- El total del mes del listado se calcula en el controlador y se pasa a la vista (`$totalMes`, `$totalHoy`, `$cantidadMes`, `$promedioDiario`, `$porCategoria`); el formato de moneda es solo presentación.
- Los resúmenes calculan sus datos en `ResumenController` y los pasan a las vistas; no hay tablas ni procesos en segundo plano.
- Las exportaciones generan el archivo al vuelo: el PDF con dompdf y el Excel con OpenSpout. No se guardan en disco ni requieren una cola de trabajos.
