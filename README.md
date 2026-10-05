# Calculadora Bisutería API

API REST en Laravel para calcular el precio de venta de piezas de bisutería a partir de sus costos de materiales, mano de obra, prestaciones, costos indirectos y margen de ganancia deseado.

## Stack

- PHP 8.2+ / Laravel 12
- Laravel Sanctum (autenticación por token)
- MySQL para desarrollo (SQLite en memoria para los tests)

## Módulos principales

- **Auth**: registro, login, logout y recuperación de contraseña por código.
- **Materiales y categorías**: catálogo de insumos con su costo unitario.
- **Diseños**: piezas compuestas por materiales y cantidades.
- **Configuración**: salario mensual, horas productivas y margen por defecto del usuario.
- **Costos indirectos y prestaciones**: costos fijos y porcentajes que se prorratean en el cálculo.
- **Empaques (packagings)**: costo de empaque asociado a una venta.
- **Cálculos**: genera el desglose de costos y el precio de venta sugerido para un diseño.
- **Historial y estadísticas**: registro de cálculos previos y resumen de métricas del usuario.

## Instalación

1. Crear en MySQL una base de datos vacía llamada `calculadora_bisuteria`.
2. Copiar `.env.example` a `.env` y poner el usuario y la contraseña de MySQL en `DB_USERNAME` y `DB_PASSWORD`.
3. Ejecutar:

```bash
composer install
php artisan key:generate
php artisan migrate
php artisan storage:link
```

`storage:link` es necesario para que se vean las fotos de los diseños.

O directamente:

```bash
composer run setup
```

## Levantar el servidor

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

`--host=0.0.0.0` permite que la app en el celular se conecte a la API usando la IP de la PC en la red local.

## Tests

```bash
php artisan test
```

## Rutas

Todas las rutas de la API viven bajo `/api` (ver [routes/api.php](routes/api.php)). Las rutas protegidas requieren un token de Sanctum vía header `Authorization: Bearer {token}`.
