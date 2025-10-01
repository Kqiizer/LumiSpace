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
    
    const shipping = 50.00;
    const tax = subtotal * 0.16;
    const total = subtotal + shipping + tax;
    
    document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
    document.getElementById('shipping').textContent = '$' + shipping.toFixed(2);
    document.getElementById('tax').textContent = '$' + tax.toFixed(2);
    document.getElementById('total').textContent = '$' + total.toFixed(2);
}

function openPaymentModal() {
    document.getElementById('paymentModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.remove('active');
    document.body.style.overflow = 'auto';
}

function selectPaymentMethod(method) {
    document.querySelectorAll('.payment-method').forEach(el => el.classList.remove('active'));
    event.target.closest('.payment-method').classList.add('active');
    
    document.querySelectorAll('.payment-form').forEach(form => form.classList.remove('active'));
    
    const formMap = {
        'card': 'cardForm',
        'paypal': 'paypalForm',
        'transfer': 'transferForm',
        'cash': 'cashForm'
    };
    
    document.getElementById(formMap[method]).classList.add('active');
}

function confirmPayment() {
    alert('Pago procesado correctamente. ¡Gracias por tu compra!');
    closePaymentModal();
}

window.onclick = function(event) {
    const modal = document.getElementById('paymentModal');
    if (event.target === modal) {
        closePaymentModal();
    }
}