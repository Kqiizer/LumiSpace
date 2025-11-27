# 🔧 Solución Rápida - Error de Stripe

## Problema Detectado

Según el diagnóstico, tienes estos problemas:
1. ✅ Composer instalado
2. ❌ Stripe PHP SDK no se puede cargar (pero está instalado)
3. ❌ Archivo .env no existe
4. ❌ Claves de Stripe no configuradas

## Solución Paso a Paso

### Paso 1: Crear archivo .env

**Opción A - Automático:**
Visita en tu navegador:
```
http://localhost/LumiSpace/api/stripe/setup-env.php
```

**Opción B - Manual:**
1. Crea un archivo llamado `.env` en la raíz del proyecto (mismo nivel que `composer.json`)
2. Copia este contenido:

```env
BASE_URL=/LumiSpace/

# Stripe - Reemplaza con tus claves reales
STRIPE_SECRET_KEY=sk_test_tu_clave_aqui
STRIPE_PUBLISHABLE_KEY=pk_test_tu_clave_aqui
STRIPE_CURRENCY=mxn
```

### Paso 2: Obtener Claves de Stripe

1. Ve a [Stripe Dashboard](https://dashboard.stripe.com/apikeys)
2. Asegúrate de estar en **modo TEST** (toggle en la esquina superior)
3. Copia:
   - **Secret key** (sk_test_...) → `STRIPE_SECRET_KEY`
   - **Publishable key** (pk_test_...) → `STRIPE_PUBLISHABLE_KEY`

### Paso 3: Verificar Instalación de Stripe

Stripe PHP ya está instalado según `composer.json`. Si el diagnóstico dice que no se puede cargar:

1. Abre PowerShell en la carpeta del proyecto
2. Ejecuta:
```powershell
# Si tienes Composer globalmente
composer dump-autoload

# O si usas XAMPP, encuentra la ruta de composer
# Normalmente está en: C:\xampp\php\composer.phar
```

### Paso 4: Verificar Configuración

Visita nuevamente:
```
http://localhost/LumiSpace/api/stripe/check-config.php
```

Debería mostrar todos los checks en verde ✅

## Solución Rápida (Si sigue fallando)

Si después de crear el `.env` sigue dando error, verifica:

1. **Permisos del archivo .env:**
   - Asegúrate de que el archivo sea legible
   - En Windows, haz clic derecho → Propiedades → Desmarca "Solo lectura"

2. **Formato del .env:**
   - No uses comillas alrededor de los valores
   - No dejes espacios alrededor del `=`
   - Ejemplo correcto: `STRIPE_SECRET_KEY=sk_test_51Abc123...`
   - Ejemplo incorrecto: `STRIPE_SECRET_KEY = "sk_test_51Abc123..."`

3. **Reiniciar servidor:**
   - Reinicia Apache en XAMPP
   - Limpia la caché del navegador

## Prueba Final

Después de configurar todo, intenta hacer una compra de prueba. El error debería ser más específico ahora y te dirá exactamente qué falta.

## Contacto

Si después de seguir estos pasos sigue fallando, revisa los logs de PHP en:
- XAMPP: `C:\xampp\php\logs\php_error_log`
- O en la consola del navegador (F12)

