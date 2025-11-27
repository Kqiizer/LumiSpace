# 🚀 Configuración de Stripe - LumiSpace

## Requisitos Previos

1. **Cuenta de Stripe**: Crea una cuenta en [stripe.com](https://stripe.com)
2. **Claves API**: Obtén tus claves desde el [Dashboard de Stripe](https://dashboard.stripe.com/apikeys)

## Configuración

### 1. Variables de Entorno

Agrega las siguientes variables a tu archivo `.env`:

```env
# Stripe API Keys
STRIPE_SECRET_KEY=sk_test_...
STRIPE_PUBLISHABLE_KEY=pk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

# Opcional: Configuración adicional
STRIPE_CURRENCY=mxn
STRIPE_APP_URL=https://tudominio.com
```

### 2. Base de Datos

Asegúrate de que la tabla `ventas` tenga los siguientes campos:

```sql
ALTER TABLE ventas 
ADD COLUMN IF NOT EXISTS stripe_payment_intent_id VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS stripe_charge_id VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS estado_pago ENUM('pendiente', 'completado', 'fallido', 'cancelado') DEFAULT 'pendiente';

-- Opcional: Guardar customer_id en usuarios
ALTER TABLE usuarios 
ADD COLUMN IF NOT EXISTS stripe_customer_id VARCHAR(255) NULL;

-- Tabla para direcciones de envío (opcional)
CREATE TABLE IF NOT EXISTS direcciones_envio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venta_id INT NOT NULL,
    direccion TEXT NOT NULL,
    nombre_cliente VARCHAR(255) NOT NULL,
    correo VARCHAR(255) NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE
);
```

### 3. Webhook de Stripe

1. Ve a [Stripe Dashboard > Webhooks](https://dashboard.stripe.com/webhooks)
2. Haz clic en "Add endpoint"
3. URL del endpoint: `https://tudominio.com/api/stripe/webhook.php`
4. Selecciona los siguientes eventos:
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
   - `payment_intent.canceled`
   - `charge.succeeded`
   - `charge.failed`
5. Copia el "Signing secret" y agrégalo a tu `.env` como `STRIPE_WEBHOOK_SECRET`

### 4. Instalación de Dependencias

Asegúrate de tener el SDK de Stripe instalado:

```bash
composer require stripe/stripe-php
```

## Flujo de Pago

1. **Cliente agrega productos al carrito**
2. **Cliente va a checkout** (`includes/checkout.php`)
3. **Cliente completa información** y datos de tarjeta
4. **Sistema crea Payment Intent** (`api/stripe/create-payment-intent.php`)
5. **Stripe procesa el pago** usando Stripe Elements
6. **Webhook confirma el pago** y registra la venta en la BD
7. **Cliente es redirigido** a página de éxito

## Archivos Creados

- `api/stripe/create-payment-intent.php` - Crea Payment Intents
- `api/stripe/webhook.php` - Maneja eventos de Stripe
- `js/checkout-stripe.js` - JavaScript para Stripe Elements
- `includes/checkout.php` - Página de checkout mejorada
- `css/checkout.css` - Estilos para checkout
- `views/checkout-success.php` - Página de éxito
- `views/checkout-cancel.php` - Página de cancelación

## Testing

### Tarjetas de Prueba

Usa estas tarjetas en modo test:

- **Éxito**: `4242 4242 4242 4242`
- **Requiere autenticación**: `4000 0025 0000 3155`
- **Rechazada**: `4000 0000 0000 0002`

Cualquier fecha futura y cualquier CVC funcionarán.

## Seguridad

- ✅ Todas las claves están en variables de entorno
- ✅ Webhooks verifican la firma de Stripe
- ✅ Payment Intents usan client secrets
- ✅ No se almacenan datos de tarjetas
- ✅ Stripe Elements maneja PCI compliance

## Soporte

Para más información, consulta la [documentación de Stripe](https://stripe.com/docs).

