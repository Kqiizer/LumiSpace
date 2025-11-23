document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('checkout-form');
  const button = document.getElementById('stripe-pay-button');
  const errorBox = document.getElementById('stripe-error');

  if (!form || !button) return;

  const publishableKey = form.dataset.stripeKey;
  const createSessionUrl = form.dataset.createSession;

  if (!publishableKey || !createSessionUrl) return;

  const stripe = Stripe(publishableKey);

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (button.disabled) return;

    setLoading(true);
    showError('');

    try {
      const body = new FormData(form);
      const response = await fetch(createSessionUrl, {
        method: 'POST',
        body,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });

      const payload = await response.json();

      if (!response.ok || payload.error) {
        throw new Error(payload.error || 'No pudimos iniciar el pago.');
      }

      if (payload.url) {
        window.location.href = payload.url;
        return;
      }

      if (payload.sessionId) {
        const { error } = await stripe.redirectToCheckout({
          sessionId: payload.sessionId,
        });
        if (error) throw error;
      }
    } catch (error) {
      showError(error.message || 'Ocurrió un error inesperado.');
      setLoading(false);
    }
  });

  function setLoading(isLoading) {
    button.disabled = isLoading;
    button.dataset.loading = isLoading ? 'true' : 'false';
    button.innerHTML = isLoading
      ? '<i class="fas fa-spinner fa-spin"></i> Redirigiendo...'
      : '<i class="fas fa-lock"></i> Pagar con Stripe';
  }

  function showError(message) {
    if (!errorBox) return;
    if (!message) {
      errorBox.style.display = 'none';
      errorBox.textContent = '';
      return;
    }
    errorBox.textContent = message;
    errorBox.style.display = 'block';
  }
});

