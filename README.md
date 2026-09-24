# Calculadora Bisutería API

API REST en Laravel para calcular el precio de venta de piezas de bisutería a partir de sus costos de materiales, mano de obra, prestaciones, costos indirectos y margen de ganancia deseado.

## Stack

- PHP 8.2+ / Laravel 12
- Laravel Sanctum (autenticación por token)
- SQLite por defecto para desarrollo y tests

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

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
```

O directamente:

```bash
composer run setup
```

## Levantar el servidor

```bash
php artisan serve
```

## Tests

```bash
php artisan test
```

## Rutas

Todas las rutas de la API viven bajo `/api` (ver [routes/api.php](routes/api.php)). Las rutas protegidas requieren un token de Sanctum vía header `Authorization: Bearer {token}`.
