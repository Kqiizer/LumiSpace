/* =======================================================
   📦 Control de cantidades y precios
   ======================================================= */
function updateQuantity(btn, change) {
    const qtySpan = btn.parentElement.querySelector('.qty-value');
    let qty = parseInt(qtySpan.textContent);
    qty = Math.max(1, qty + change);
    qtySpan.textContent = qty;

    const productItem = btn.closest('.product-item');
    const unitPriceText = productItem.querySelector('.unit-price').textContent;
    const unitPrice = parseFloat(unitPriceText.replace('$', '').replace(' c/u', '').replace(',', ''));
    const totalPrice = (unitPrice * qty).toFixed(2);
    productItem.querySelector('.total-price').textContent = '$' + totalPrice;

    updateOrderSummary();
}

function removeProduct(btn) {
    if (confirm('¿Estás seguro de eliminar este producto?')) {
        btn.closest('.product-item').remove();
        updateOrderSummary();
    }
}

function updateOrderSummary() {
    const products = document.querySelectorAll('.product-item');
    let subtotal = 0;

    products.forEach(product => {
        const totalText = product.querySelector('.total-price').textContent;
        subtotal += parseFloat(totalText.replace('$', '').replace(',', ''));
    });

    const shipping = products.length > 0 ? 50.00 : 0.00;
    const tax = subtotal * 0.16;
    const total = subtotal + shipping + tax;

    document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
    document.getElementById('shipping').textContent = '$' + shipping.toFixed(2);
    document.getElementById('tax').textContent = '$' + tax.toFixed(2);
    document.getElementById('total').textContent = '$' + total.toFixed(2);
}

/* =======================================================
   💳 Modal de pagos
   ======================================================= */
function openPaymentModal() {
    document.getElementById('paymentModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.remove('active');
    document.body.style.overflow = 'auto';
}

function selectPaymentMethod(method) {
    // Reset estilos
    document.querySelectorAll('.payment-method').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.payment-form').forEach(form => form.classList.remove('active'));

    // Activar seleccionado
    const clicked = event.currentTarget;
    clicked.classList.add('active');

    const formMap = {
        'card': 'cardForm',
        'paypal': 'paypalForm',
        'transfer': 'transferForm',
        'cash': 'cashForm'
    };

    if (formMap[method]) {
        document.getElementById(formMap[method]).classList.add('active');
    }
}

function confirmPayment() {
    alert('✅ Pago procesado correctamente. ¡Gracias por tu compra!');
    closePaymentModal();
}

function redirectToPayPal() {
    const total = document.getElementById('total').textContent;
    alert('Redirigiendo a PayPal...\n\nTotal a pagar: ' + total);
    setTimeout(() => {
        alert('Pago completado exitosamente con PayPal ✓');
        closePaymentModal();
    }, 2000);
}

/* =======================================================
   🖱 Cerrar modal al hacer click fuera
   ======================================================= */
window.onclick = function(event) {
    const modal = document.getElementById('paymentModal');
    if (event.target === modal) {
        closePaymentModal();
    }
};

/* =======================================================
   📅 Ajustar fecha de entrega (5 días hábiles por defecto)
   ======================================================= */
document.addEventListener("DOMContentLoaded", () => {
    const deliveryDate = document.getElementById("deliveryDate");
    if (deliveryDate) {
        const today = new Date();
        today.setDate(today.getDate() + 5);
        deliveryDate.value = today.toISOString().split("T")[0];
    }
});
