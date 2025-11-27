# 🔧 Solución para Hostinger - Error de Stripe

## ⚠️ Problema Detectado

El error indica que **Stripe NO está instalado físicamente** en el servidor de Hostinger:
```
Class "Stripe\StripeClient" not found
```

Esto significa que aunque `stripe/stripe-php` esté en `composer.json`, la carpeta `vendor/stripe/stripe-php/` está **vacía o no existe**.

## ✅ Solución OBLIGATORIA

### Opción 1: SSH (Recomendado - Más Rápido)

1. **Accede por SSH a Hostinger:**
   - Ve al panel de Hostinger
   - Busca "Terminal" o "SSH" en el menú
   - O usa un cliente SSH como PuTTY

2. **Navega a tu proyecto:**
   ```bash
   cd public_html/LumiSpace
   # O la ruta donde está tu proyecto
   ```

3. **Instala las dependencias:**
   ```bash
   composer install
   ```

4. **Verifica:**
   ```bash
   ls -la vendor/stripe/stripe-php/lib/StripeClient.php
   ```
   Debería mostrar el archivo.

### Opción 2: Sin SSH - Contactar Soporte

Si **NO tienes acceso SSH**, debes:

1. **Contactar al soporte de Hostinger:**
   - Abre un ticket de soporte
   - O usa el chat en vivo

2. **Pídeles que ejecuten:**
   ```
   cd public_html/LumiSpace
   composer install
   ```
   (Ajusta la ruta según tu proyecto)

3. **O específicamente para Stripe:**
   ```
   composer require stripe/stripe-php
   ```

### Opción 3: Subir Vendor Manualmente (Alternativa)

Si no puedes usar SSH ni contactar soporte:

1. **En tu entorno local:**
   ```bash
   composer install
   ```

2. **Sube vía FTP:**
   - Conecta a Hostinger por FTP
   - Sube la carpeta completa: `vendor/stripe/stripe-php/`
   - Asegúrate de mantener la estructura de carpetas

3. **O descarga Stripe directamente:**
   - Ve a: https://github.com/stripe/stripe-php/releases
   - Descarga la versión **v13.0.0** (o la que tengas en composer.json)
   - Extrae el contenido en: `vendor/stripe/stripe-php/`

## 🔍 Verificación

Después de instalar, verifica con estos scripts:

### Script 1: Diagnóstico Completo
```
https://tudominio.com/api/stripe/check-config.php
```
Debería mostrar: `"stripe_php_installed": true`

### Script 2: Verificación de Instalación
```
https://tudominio.com/api/stripe/install-stripe.php
```
Te dirá exactamente si Stripe está instalado físicamente.

### Script 3: Fix Autoload
```
https://tudominio.com/api/stripe/fix-autoload.php
```
Útil si Stripe está instalado pero no se carga.

## 📋 Checklist

- [ ] Accedí por SSH a Hostinger
- [ ] Ejecuté `composer install` en la carpeta del proyecto
- [ ] Verifiqué que `vendor/stripe/stripe-php/lib/StripeClient.php` existe
- [ ] Probé el script de diagnóstico y muestra `stripe_php_installed: true`
- [ ] El checkout de Stripe funciona correctamente

## ⚡ Solución Rápida (Si ya tienes Stripe localmente)

Si tu proyecto funciona localmente:

1. **Comprime la carpeta vendor:**
   ```bash
   # En tu máquina local
   cd C:\xampp\htdocs\LumiSpace
   tar -czf vendor-stripe.tar.gz vendor/stripe/
   # O usa WinRAR/7-Zip para comprimir
   ```

2. **Sube a Hostinger:**
   - Sube el archivo comprimido
   - Extrae en: `vendor/stripe/`

## 🚨 Nota Importante

**El código tiene un fallback automático**, pero **NO puede instalar Stripe por ti**. 

**DEBES ejecutar `composer install` en el servidor** para que Stripe se instale físicamente.

Si no puedes hacerlo tú, **contacta al soporte de Hostinger** - es un proceso estándar que ellos pueden hacer.

