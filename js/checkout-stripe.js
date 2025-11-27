/**
 * ========================================
 * STRIPE CHECKOUT - LumiSpace
 * ========================================
 * Maneja el proceso de pago con Stripe Elements
 */

(function() {
    'use strict';

    const BASE = window.BASE_URL || document.body.dataset.base || '/';
    let stripe = null;
    let elements = null;
    let cardElement = null;
    let paymentIntentClientSecret = null;
    let paymentIntentId = null;

    // Inicializar cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('checkout-form');
        const stripeKey = form?.dataset.stripeKey;

        if (!form || !stripeKey) {
            console.error('Stripe no configurado correctamente');
            return;
        }

        // Inicializar Stripe
        stripe = Stripe(stripeKey);
        elements = stripe.elements();

        // Crear elemento de tarjeta
        const cardContainer = document.getElementById('card-element');
        if (cardContainer) {
            cardElement = elements.create('card', {
                style: {
                    base: {
                        fontSize: '16px',
                        color: '#424770',
                        '::placeholder': {
                            color: '#aab7c4',
                        },
                        fontFamily: 'Inter, system-ui, sans-serif',
                    },
                    invalid: {
                        color: '#9e2146',
                    },
                },
            });

            cardElement.mount('#card-element');

            // Manejar errores en tiempo real
            cardElement.on('change', function(event) {
                const displayError = document.getElementById('card-errors');
                if (event.error) {
                    displayError.textContent = event.error.message;
                    displayError.style.display = 'block';
                } else {
                    displayError.textContent = '';
                    displayError.style.display = 'none';
                }
            });
        }

        // Manejar envío del formulario
        form.addEventListener('submit', handleFormSubmit);
    });

    /**
     * Manejar envío del formulario
     */
    async function handleFormSubmit(event) {
        event.preventDefault();

        const form = event.target;
        const submitButton = form.querySelector('button[type="submit"]');
        const errorDiv = document.getElementById('stripe-error');

        // Validar formulario
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        // Deshabilitar botón
        if (submitButton) {
            submitButton.disabled = true;
            const originalText = submitButton.innerHTML;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

            try {
                // Obtener datos del formulario
                const formData = new FormData(form);
                const nombre = formData.get('nombre');
                const correo = formData.get('correo');
                const direccion = formData.get('direccion');

                // Crear Payment Intent
                const response = await fetch(BASE + 'api/stripe/create-payment-intent.php', {
                    method: 'POST',
                    body: formData
                });

                // Verificar si la respuesta es JSON válido
                let data;
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    data = await response.json();
                } else {
                    // Si no es JSON, leer como texto para ver el error
                    const text = await response.text();
                    console.error('Respuesta no JSON:', text);
                    throw new Error('Error del servidor: La respuesta no es válida. Revisa la consola para más detalles.');
                }

                if (!response.ok || data.error) {
                    const errorMsg = data.error || `Error ${response.status}: ${response.statusText}`;
                    console.error('Error del servidor:', {
                        status: response.status,
                        statusText: response.statusText,
                        data: data
                    });
                    throw new Error(errorMsg);
                }

                paymentIntentClientSecret = data.clientSecret;
                paymentIntentId = data.paymentIntentId;

                // Confirmar el pago
                await confirmPayment();

            } catch (error) {
                console.error('Error:', error);
                showError(error.message || 'Error al procesar el pago. Por favor intenta de nuevo.');
                
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                }
            }
        }
    }

    /**
     * Confirmar el pago con Stripe
     */
    async function confirmPayment() {
        const submitButton = document.querySelector('button[type="submit"]');
        const errorDiv = document.getElementById('stripe-error');

        try {
            // Confirmar el pago
            const {error, paymentIntent} = await stripe.confirmCardPayment(
                paymentIntentClientSecret,
                {
                    payment_method: {
                        card: cardElement,
                        billing_details: {
                            name: document.querySelector('input[name="nombre"]').value,
                            email: document.querySelector('input[name="correo"]').value,
                        },
                    },
                }
            );

            if (error) {
                // Mostrar error al usuario
                showError(error.message);
                
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="fas fa-lock"></i> Pagar con Stripe';
                }
            } else if (paymentIntent && paymentIntent.status === 'succeeded') {
                // Pago exitoso - redirigir a página de éxito
                window.location.href = BASE + 'views/checkout-success.php?payment_intent=' + paymentIntent.id;
            }
        } catch (error) {
            console.error('Error confirmando pago:', error);
            showError('Error inesperado al procesar el pago. Por favor intenta de nuevo.');
            
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-lock"></i> Pagar con Stripe';
            }
        }
    }

    /**
     * Mostrar error
     */
    function showError(message) {
        const errorDiv = document.getElementById('stripe-error');
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            errorDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    /**
     * Ocultar error
     */
    function hideError() {
        const errorDiv = document.getElementById('stripe-error');
        if (errorDiv) {
            errorDiv.style.display = 'none';
        }
    }

})();
