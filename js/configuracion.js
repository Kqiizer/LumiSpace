let userSettings = {
    addresses: [],
    paymentMethods: [],
    account: {
        email: 'usuario@lumispace.com',
        phone: '',
        name: 'Usuario LumiSpace'
    }
};

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    // Cargar configuraciones guardadas
    const savedSettings = localStorage.getItem('lumispace_settings');
    
    if (savedSettings) {
        userSettings = JSON.parse(savedSettings);
    }
    
    // Cargar moneda guardada
    const savedCurrency = localStorage.getItem('lumispace_currency');
    if (savedCurrency) {
        document.getElementById('currencyValue').textContent = savedCurrency;
    }
}

// Guardar configuraciones
function saveSettings() {
    localStorage.setItem('lumispace_settings', JSON.stringify(userSettings));
}

// Navegación
function navigate(section) {
    const pages = {
        'addresses': showAddresses,
        'payment': showPaymentMethods,
        'manage': showManageAccount,
        'currency': showCurrencySelector,
        'privacy': showPrivacyPolicy,
        'terms': showTermsConditions,
        'contact-us': showContactUs,
        'about': showAbout
    };
    
    if (pages[section]) {
        pages[section]();
    }
}

//Direcciones
function showAddresses() {
    const addresses = userSettings.addresses;
    
    let addressesHTML = '';
    if (addresses.length === 0) {
        addressesHTML = `
            <div class="empty-state">
                <div class="empty-icon">📍</div>
                <p class="empty-text">No tienes direcciones guardadas</p>
            </div>
        `;
    } else {
        addressesHTML = addresses.map((addr, index) => `
            <div class="address-item">
                <div class="address-header">
                    <span class="address-name">${addr.name}</span>
                    ${addr.isDefault ? '<span class="address-badge">Predeterminada</span>' : ''}
                </div>
                <div class="address-details">
                    ${addr.street}<br>
                    ${addr.city}, ${addr.state} ${addr.zipCode}<br>
                    ${addr.country}
                </div>
                <div class="address-phone">📞 ${addr.phone}</div>
                <div class="action-buttons">
                    <button class="btn-secondary" onclick="editAddress(${index})">
                        Editar
                    </button>
                    <button class="btn-danger" onclick="deleteAddress(${index})">
                        Eliminar
                    </button>
                </div>
            </div>
        `).join('');
    }
    
    const content = `
        <div class="modal-body">
            ${addressesHTML}
            <button class="btn-primary" onclick="addNewAddress()">
                + Agregar Nueva Dirección
            </button>
        </div>
    `;
    showModal('Mi Libreta de Direcciones', content);
}

function addNewAddress() {
    const content = `
        <div class="modal-body">
            <form onsubmit="saveNewAddress(event)">
                <div class="form-group">
                    <label class="form-label">Nombre completo</label>
                    <input type="text" class="form-input" id="addrName" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Teléfono</label>
                    <input type="tel" class="form-input" id="addrPhone" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Calle y número</label>
                    <input type="text" class="form-input" id="addrStreet" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Ciudad</label>
                    <input type="text" class="form-input" id="addrCity" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <input type="text" class="form-input" id="addrState" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Código Postal</label>
                    <input type="text" class="form-input" id="addrZip" required>
                </div>
                <div class="form-group">
                    <label class="form-label">País</label>
                    <input type="text" class="form-input" id="addrCountry" value="México" required>
                </div>
                <button type="submit" class="btn-primary">Guardar</button>
                <button type="button" class="btn-secondary" onclick="showAddresses()">
                    Cancelar
                </button>
            </form>
        </div>
    `;
    showModal('Nueva Dirección', content);
}

function saveNewAddress(event) {
    event.preventDefault();
    const newAddress = {
        name: document.getElementById('addrName').value,
        phone: document.getElementById('addrPhone').value,
        street: document.getElementById('addrStreet').value,
        city: document.getElementById('addrCity').value,
        state: document.getElementById('addrState').value,
        zipCode: document.getElementById('addrZip').value,
        country: document.getElementById('addrCountry').value,
        isDefault: userSettings.addresses.length === 0
    };
    
    userSettings.addresses.push(newAddress);
    saveSettings();
    showSuccessMessage('Dirección guardada exitosamente');
    showAddresses();
}

function editAddress(index) {
    const addr = userSettings.addresses[index];
    const content = `
        <div class="modal-body">
            <form onsubmit="updateAddress(event, ${index})">
                <div class="form-group">
                    <label class="form-label">Nombre completo</label>
                    <input type="text" class="form-input" id="addrName" value="${addr.name}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Teléfono</label>
                    <input type="tel" class="form-input" id="addrPhone" value="${addr.phone}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Calle y número</label>
                    <input type="text" class="form-input" id="addrStreet" value="${addr.street}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Ciudad</label>
                    <input type="text" class="form-input" id="addrCity" value="${addr.city}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <input type="text" class="form-input" id="addrState" value="${addr.state}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Código Postal</label>
                    <input type="text" class="form-input" id="addrZip" value="${addr.zipCode}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">País</label>
                    <input type="text" class="form-input" id="addrCountry" value="${addr.country}" required>
                </div>
                <button type="submit" class="btn-primary">Guardar</button>
                <button type="button" class="btn-secondary" onclick="showAddresses()">
                    Cancelar
                </button>
            </form>
        </div>
    `;
    showModal('Editar Dirección', content);
}

function updateAddress(event, index) {
    event.preventDefault();
    userSettings.addresses[index] = {
        ...userSettings.addresses[index],
        name: document.getElementById('addrName').value,
        phone: document.getElementById('addrPhone').value,
        street: document.getElementById('addrStreet').value,
        city: document.getElementById('addrCity').value,
        state: document.getElementById('addrState').value,
        zipCode: document.getElementById('addrZip').value,
        country: document.getElementById('addrCountry').value
    };
    
    saveSettings();
    showSuccessMessage('Dirección actualizada exitosamente');
    showAddresses();
}

function deleteAddress(index) {
    if (confirm('¿Estás seguro de eliminar esta dirección?')) {
        userSettings.addresses.splice(index, 1);
        saveSettings();
        showSuccessMessage('Dirección eliminada');
        showAddresses();
    }
}

//Metodos de pago
function showPaymentMethods() {
    const methods = userSettings.paymentMethods;
    
    let methodsHTML = '';
    if (methods.length === 0) {
        methodsHTML = `
            <div class="empty-state">
                <div class="empty-icon">💳</div>
                <p class="empty-text">No tienes métodos de pago guardados</p>
            </div>
        `;
    } else {
        methodsHTML = methods.map((method, index) => `
            <div class="payment-card">
                <div class="payment-header">
                    <div class="card-icon">${method.type}</div>
                    <div>
                        <div class="card-number">•••• ${method.lastFour}</div>
                        <div class="card-expiry">Exp: ${method.expiry}</div>
                    </div>
                </div>
                <div class="action-buttons">
                    <button class="btn-secondary" onclick="editPayment(${index})">
                        Editar
                    </button>
                    <button class="btn-danger" onclick="deletePayment(${index})">
                        Eliminar
                    </button>
                </div>
            </div>
        `).join('');
    }
    
    const content = `
        <div class="modal-body">
            ${methodsHTML}
            <button class="btn-primary" onclick="addNewPayment()">
                + Agregar Método de Pago
            </button>
        </div>
    `;
    showModal('Mis Opciones de Pago', content);
}

function addNewPayment() {
    const content = `
        <div class="modal-body">
            <form onsubmit="saveNewPayment(event)">
                <div class="form-group">
                    <label class="form-label">Tipo de tarjeta</label>
                    <select class="form-input" id="cardType" required>
                        <option value="VISA">Visa</option>
                        <option value="MC">Mastercard</option>
                        <option value="AMEX">American Express</option>
                        <option value="DISC">Discover</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Número de tarjeta</label>
                    <input type="text" class="form-input" id="cardNumber" 
                           placeholder="1234 5678 9012 3456" maxlength="19" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Fecha de expiración (MM/AA)</label>
                    <input type="text" class="form-input" id="cardExpiry" 
                           placeholder="12/25" maxlength="5" required>
                </div>
                <div class="form-group">
                    <label class="form-label">CVV</label>
                    <input type="text" class="form-input" id="cardCvv" 
                           placeholder="123" maxlength="4" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nombre en la tarjeta</label>
                    <input type="text" class="form-input" id="cardName" required>
                </div>
                <button type="submit" class="btn-primary">Guardar</button>
                <button type="button" class="btn-secondary" onclick="showPaymentMethods()">
                    Cancelar
                </button>
            </form>
        </div>
    `;
    showModal('Nueva Tarjeta', content);
}

function saveNewPayment(event) {
    event.preventDefault();
    const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
    const newPayment = {
        type: document.getElementById('cardType').value,
        lastFour: cardNumber.slice(-4),
        expiry: document.getElementById('cardExpiry').value,
        name: document.getElementById('cardName').value
    };
    
    userSettings.paymentMethods.push(newPayment);
    saveSettings();
    showSuccessMessage('Método de pago guardado exitosamente');
    showPaymentMethods();
}

function editPayment(index) {
    showSuccessMessage('Función de edición disponible próximamente');
}

function deletePayment(index) {
    if (confirm('¿Estás seguro de eliminar este método de pago?')) {
        userSettings.paymentMethods.splice(index, 1);
        saveSettings();
        showSuccessMessage('Método de pago eliminado');
        showPaymentMethods();
    }
}

//Gestionar Cuenta
function showManageAccount() {
    const account = userSettings.account;
    
    const content = `
        <div class="modal-body">
            <form onsubmit="updateAccount(event)">
                <div class="form-group">
                    <label class="form-label">Nombre completo</label>
                    <input type="text" class="form-input" id="accountName" 
                           value="${account.name}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-input" id="accountEmail" 
                           value="${account.email}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Teléfono</label>
                    <input type="tel" class="form-input" id="accountPhone" 
                           value="${account.phone}" placeholder="Añadir número">
                </div>
                <button type="submit" class="btn-primary">Guardar Cambios</button>
            </form>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                <h3 style="font-size: 16px; margin-bottom: 15px;">Seguridad</h3>
                <button class="btn-secondary" onclick="changePassword()">
                    Cambiar Contraseña
                </button>
                <button class="btn-danger" onclick="deleteAccount()">
                    Eliminar Cuenta
                </button>
            </div>
        </div>
    `;
    showModal('Gestionar Mi Cuenta', content);
}

function updateAccount(event) {
    event.preventDefault();
    userSettings.account = {
        name: document.getElementById('accountName').value,
        email: document.getElementById('accountEmail').value,
        phone: document.getElementById('accountPhone').value
    };
    saveSettings();
    showSuccessMessage('Cuenta actualizada exitosamente');
    closeModal();
}

function changePassword() {
    alert('Función de cambio de contraseña disponible próximamente');
}

function deleteAccount() {
    if (confirm('¿Estás seguro de que deseas eliminar tu cuenta? Esta acción no se puede deshacer.')) {
        alert('Tu cuenta ha sido eliminada');
        localStorage.clear();
      
    }
}

// Moneda
function showCurrencySelector() {
    const currencies = [
        {code: 'MXN', symbol: '$', name: 'Peso Mexicano'},
        {code: 'USD', symbol: '$', name: 'Dólar Estadounidense'},
        {code: 'CAD', symbol: '$', name: 'Dólar Canadiense'}
    ];
    
    const currentCurrency = localStorage.getItem('lumispace_currency') || 'MXN';
    
    const content = `
        <div class="modal-body">
            ${currencies.map(currency => `
                <div class="settings-item" style="margin-bottom: 5px; cursor: pointer; border-radius: 8px;" 
                     onclick="selectCurrency('${currency.code}')">
                    <div style="display: flex; flex-direction: column;">
                        <span style="font-weight: 600;">${currency.code}</span>
                        <span style="color: #666; font-size: 13px;">${currency.name}</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <span style="color: #666; font-size: 18px;">${currency.symbol}</span>
                        ${currentCurrency === currency.code ? '<span style="color: var(--color-primary); font-size: 20px;">✓</span>' : ''}
                    </div>
                </div>
            `).join('')}
        </div>
    `;
    showModal('Moneda', content);
}

function selectCurrency(code) {
    localStorage.setItem('lumispace_currency', code);
    document.getElementById('currencyValue').textContent = code;
    showSuccessMessage(`Moneda cambiada a ${code}`);
    closeModal();
}

function showPrivacyPolicy() {
    const content = `
        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
            <div style="background: #f5f5f5; padding: 15px; border-radius: 12px; margin-bottom: 20px;">
                <strong>Fecha de entrada en vigor: 25 de Diciembre de 2025</strong>
            </div>
            
            <h3 style="font-size: 16px; margin-bottom: 15px;">Política de Privacidad de LumiSpace</h3>
            
            <p style="font-size: 14px; line-height: 1.7; margin-bottom: 20px;">
                En LumiSpace, nos comprometemos a proteger tu privacidad y tus datos personales. 
                Esta política explica cómo recopilamos, usamos y protegemos tu información.
            </p>
            
            <h4 style="font-size: 15px; margin: 20px 0 10px;">1. Información que Recopilamos</h4>
            <p style="font-size: 14px; line-height: 1.7; margin-bottom: 15px;">
                • Datos de cuenta (nombre, email, teléfono)<br>
                • Direcciones de envío<br>
                • Información de pago (encriptada)<br>
                • Historial de compras<br>
                • Preferencias de usuario
            </p>
            
            <h4 style="font-size: 15px; margin: 20px 0 10px;">2. Cómo Usamos tu Información</h4>
            <p style="font-size: 14px; line-height: 1.7; margin-bottom: 15px;">
                • Procesar tus pedidos<br>
                • Mejorar nuestros servicios<br>
                • Enviarte actualizaciones y promociones<br>
                • Cumplir con obligaciones legales
            </p>
            
            <h4 style="font-size: 15px; margin: 20px 0 10px;">3. Protección de Datos</h4>
            <p style="font-size: 14px; line-height: 1.7; margin-bottom: 15px;">
                Utilizamos encriptación SSL/TLS y medidas de seguridad avanzadas para 
                proteger tu información personal.
            </p>
            
            <h4 style="font-size: 15px; margin: 20px 0 10px;">4. Tus Derechos</h4>
            <p style="font-size: 14px; line-height: 1.7; margin-bottom: 15px;">
                Tienes derecho a acceder, corregir o eliminar tus datos personales en 
                cualquier momento contactándonos.
            </p>
            
            <h4 style="font-size: 15px; margin: 20px 0 10px;">5. Cookies</h4>
            <p style="font-size: 14px; line-height: 1.7; margin-bottom: 15px;">
                Utilizamos cookies para mejorar tu experiencia. Puedes gestionar las 
                cookies desde la configuración de tu navegador.
            </p>
            
            <p style="font-size: 13px; color: #666; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                Para más información, contáctanos en: lumispace0@gmail.com
            </p>
        </div>
    `;
    showModal('Política de Privacidad', content);
}

function showTermsConditions() {
    const content = `
        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
            <div style="background: #f5f5f5; padding: 15px; border-radius: 12px; margin-bottom: 20px;">
                <strong>FECHA DE VIGENCIA: 25 de Diciembre de 2025</strong>
            </div>
            
            <h3 style="font-size: 16px; margin-bottom: 15px;">Términos y Condiciones de Uso - LumiSpace</h3>
            
            <h4 style="font-size: 15px; margin: 20px 0 10px;">1. ACEPTACIÓN DE TÉRMINOS</h4>
            <p style="font-size: 14px; line-height: 1.7; margin-bottom: 15px;">
                Al acceder y usar los servicios de LumiSpace, aceptas estar sujeto a estos 
                términos y condiciones. Si no estás de acuerdo, por favor no uses nuestros servicios.
            </p>
            
            <h4 style="font-size: 15px; margin: 20px 0 10px;">2. USO DE SERVICIOS</h4>
            <p style="font-size: 14px; line-height: 1.7; margin-bottom: 15px;">
                • Debes tener al menos 18 años para usar nuestros servicios<br>
                • Eres responsable de mantener la seguridad de tu cuenta<br>
                • No puedes usar nuestros servicios para fines ilegales<br>
                • Nos reservamos el derecho de suspender cuentas que violen estos términos
            </p>
            
            <h4 style="font-size: 15px; margin: 20px 0 10px;">3. COMPRAS Y PAGOS</h4>
            <p style="font-size: 14px; line-height: 1.7; margin-bottom: 15px;">
                • Todos los precios están en la moneda seleccionada<br>
                • Los pagos se procesan de forma segura<br>
                • Te enviaremos confirmación de cada compra<br>
                • Consulta nuestra política de devoluciones para más información
            </p>
            
            <h4 style="font-size: 15px; margin: 20px 0 10px;">4. ENVÍOS Y ENTREGAS</h4>
            <p style="font-size: 14px; line-height: 1.7; margin-bottom: 15px;">
                • Los tiempos de entrega son estimados<br>
                • No somos responsables por retrasos del servicio postal<br>
                • Debes proporcionar información de envío precisa
            </p>
            
            <h4 style="font-size: 15px; margin: 20px 0 10px;">5. PROPIEDAD INTELECTUAL</h4>
            <p style="font-size: 14px; line-height: 1.7; margin-bottom: 15px;">
                Todo el contenido de LumiSpace está protegido por derechos de autor y 
                marcas registradas. No puedes usar nuestro contenido sin permiso expreso.
            </p>
            
            <h4 style="font-size: 15px; margin: 20px 0 10px;">6. LIMITACIÓN DE RESPONSABILIDAD</h4>
            <p style="font-size: 14px; line-height: 1.7; margin-bottom: 15px;">
                LumiSpace no será responsable de daños indirectos, incidentales o 
                consecuentes que surjan del uso de nuestros servicios.
            </p>
            
            <h4 style="font-size: 15px; margin: 20px 0 10px;">7. MODIFICACIONES</h4>
            <p style="font-size: 14px; line-height: 1.7; margin-bottom: 15px;">
                Nos reservamos el derecho de modificar estos términos en cualquier momento. 
                Te notificaremos de cambios importantes.
            </p>
            
            <p style="font-size: 13px; color: #666; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                Para consultas sobre estos términos: lumispace0@gmail.com
            </p>
        </div>
    `;
    showModal('Términos y Condiciones', content);
}

//Contacto
function showContactUs() {
    const socials = [
        {
            name: 'Instagram',
            handle: 'lumi_space0',
            url: 'https://www.instagram.com/lumi_space0',
            desc: 'Síguenos para novedades y ofertas'
        },
        {
            name: 'X (Twitter)',
            handle: 'LumiSapce_',
            url: 'https://twitter.com/LumiSapce_',
            desc: 'Actualizaciones en tiempo real'
        },
        {
            name: 'YouTube',
            handle: 'lumispace0',
            url: 'https://youtube.com/@lumispace0',
            desc: 'Reviews de productos'
        }
    ];
    
    const content = `
        <div class="modal-body">
            <div style="text-align: center; margin-bottom: 25px;">
                <h3 style="font-size: 18px; margin-bottom: 10px;">Estamos aquí para ayudarte</h3>
                <p style="color: #666; font-size: 14px;">Conéctate con nosotros en redes sociales</p>
            </div>
            
            ${socials.map(social => `
                <div class="social-card">
                    <div class="social-header">
                        <div class="social-icon" style="background: #333;"></div>
                        <div>
                            <strong style="font-size: 16px;">${social.name}</strong>
                            <div style="font-size: 12px; color: #666;">@${social.handle}</div>
                        </div>
                    </div>
                    <p style="font-size: 13px; color: #666; margin-bottom: 10px;">${social.desc}</p>
                    <div class="social-footer" style="padding-left: 0;">
                        <button class="follow-btn" onclick="window.open('${social.url}', '_blank')">
                            Seguir
                        </button>
                    </div>
                </div>
            `).join('')}
            
            <div style="margin-top: 30px; padding: 20px; background: #f5f5f5; border-radius: 12px; text-align: center;">
                <h4 style="font-size: 15px; margin-bottom: 10px;">Soporte por Email</h4>
                <p style="font-size: 14px; color: #666; margin-bottom: 10px;">
                    ¿Necesitas ayuda? Escríbenos a:
                </p>
                <a href="mailto:lumispace0@gmail.com" style="color: var(--color-primary); font-weight: 600; text-decoration: none;">
                    lumispace0@gmail.com
                </a>
            </div>
        </div>
    `;
    showModal('Conéctate con LumiSpace', content);
}

function showAbout() {
    const content = `
        <div class="modal-body">
            <div style="text-align: center; margin-bottom: 30px;">
                <h2 style="font-size: 24px; margin-bottom: 10px;">LumiSpace</h2>
                <p style="color: #666; font-size: 14px;">Versión 2.0.0</p>
            </div>
            
            <div style="background: linear-gradient(135deg, var(--color-light), var(--color-secondary)); 
                        padding: 25px; border-radius: 16px; margin-bottom: 25px; color: white;">
                <h3 style="font-size: 18px; margin-bottom: 10px;">Nuestra Misión</h3>
                <p style="font-size: 14px; line-height: 1.7; opacity: 0.95;">
                    En LumiSpace, nos dedicamos a brindarte los mejores productos con una 
                    experiencia de compra excepcional. Innovación, calidad y satisfacción 
                    del cliente son nuestros pilares fundamentales.
                </p>
            </div>
            
            <div style="padding: 20px; background: #f9f9f9; border-radius: 12px; margin-bottom: 20px;">
                <h4 style="font-size: 16px; margin-bottom: 15px;">¿Por qué elegirnos?</h4>
                <div style="font-size: 14px; line-height: 2;">
                    ✓ Productos de alta calidad<br>
                    ✓ Envío rápido y seguro<br>
                    ✓ Atención al cliente 24/7<br>
                    ✓ Garantía de satisfacción<br>
                    ✓ Pagos seguros
                </div>
            </div>
            
            <div style="text-align: center; padding: 20px; border-top: 1px solid #eee;">
                <p style="font-size: 13px; color: #666; line-height: 1.6;">
                    © 2025 LumiSpace. Todos los derechos reservados.<br>
                </p>
            </div>
        </div>
    `;
    showModal('Acerca de LumiSpace', content);
}
function showModal(title, content) {
    const modal = document.getElementById('modalContainer');
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <button onclick="closeModal()" style="background: none; border: none; padding: 8px; cursor: pointer;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </button>
                <h2 class="modal-title">${title}</h2>
                <div style="width: 40px;"></div>
            </div>
            ${content}
        </div>
    `;
    modal.classList.add('active');
    modal.onclick = (e) => {
        if (e.target === modal) closeModal();
    };
}

function closeModal() {
    const modal = document.getElementById('modalContainer');
    modal.classList.remove('active');
    setTimeout(() => {
        modal.innerHTML = '';
    }, 300);
}

function showSuccessMessage(message) {
    const existingMessage = document.querySelector('.success-message');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    const successDiv = document.createElement('div');
    successDiv.className = 'success-message';
    successDiv.textContent = message;
    successDiv.style.position = 'fixed';
    successDiv.style.top = '20px';
    successDiv.style.left = '50%';
    successDiv.style.transform = 'translateX(-50%)';
    successDiv.style.zIndex = '10000';
    successDiv.style.minWidth = '300px';
    successDiv.style.animation = 'slideDown 0.3s ease';
    
    document.body.appendChild(successDiv);
    
    setTimeout(() => {
        successDiv.style.animation = 'slideUp 0.3s ease';
        setTimeout(() => successDiv.remove(), 300);
    }, 3000);
}

function goBack() {
    if (confirm('¿Volver a la pantalla anterior?')) {
        window.history.back();
    }
}

function logout() {
    if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
        showSuccessMessage('Sesión cerrada exitosamente');
        
        setTimeout(() => {
            window.location.href = '/LumiSpace/views/login.php';
            alert('Redirigiendo a página de inicio de sesión...');
        }, 1000);
    }
}

const style = document.createElement('style');
style.textContent = `
    @keyframes slideDown {
        from { transform: translateX(-50%) translateY(-100%); opacity: 0; }
        to { transform: translateX(-50%) translateY(0); opacity: 1; }
    }
    @keyframes slideUp {
        from { transform: translateX(-50%) translateY(0); opacity: 1; }
        to { transform: translateX(-50%) translateY(-100%); opacity: 0; }
    }
`;
document.head.appendChild(style);