const translations = {
    es: {
        address_book: "Mi Libreta de Direcciones",
        payment_options: "Mis Opciones de Pago",
        manage_account: "Gestionar Mi Cuenta",
        show_attending: "Mostrar&Atuendo",
        language: "Idioma",
        currency: "moneda",
        contact_preferences: "Preferencias de Contacto",
        blocked_contacts: "Lista Bloqueada de Contactos",
        clear_cache: "Borrar caché",
        privacy_policy: "Política de Privacidad y Cookies",
        terms_conditions: "Términos y Condiciones",
        rating_comments: "Calificación y comentarios",
        contact_us: "Mantener el Contacto con Nosotros",
        ad_options: "Opción de Anuncios",
        about_us: "Quienes Somos",
        switch_accounts: "Cambiar cuentas",
        logout: "Desconectarse"
    },
    en: {
        address_book: "My Address Book",
        payment_options: "My Payment Options",
        manage_account: "Manage My Account",
        show_attending: "Show&Outfit",
        language: "Language",
        currency: "currency",
        contact_preferences: "Contact Preferences",
        blocked_contacts: "Blocked Contacts List",
        clear_cache: "Clear cache",
        privacy_policy: "Privacy Policy and Cookies",
        terms_conditions: "Terms and Conditions",
        rating_comments: "Rating and comments",
        contact_us: "Keep in Touch with Us",
        ad_options: "Ad Options",
        about_us: "About Us",
        switch_accounts: "Switch accounts",
        logout: "Log Out"
    },
    fr: {
        address_book: "Mon Carnet d'Adresses",
        payment_options: "Mes Options de Paiement",
        manage_account: "Gérer Mon Compte",
        show_attending: "Afficher&Tenue",
        language: "Langue",
        currency: "devise",
        contact_preferences: "Préférences de Contact",
        blocked_contacts: "Liste de Contacts Bloqués",
        clear_cache: "Vider le cache",
        privacy_policy: "Politique de Confidentialité et Cookies",
        terms_conditions: "Termes et Conditions",
        rating_comments: "Évaluation et commentaires",
        contact_us: "Gardez le Contact avec Nous",
        ad_options: "Options de Publicité",
        about_us: "Qui Sommes Nous",
        switch_accounts: "Changer de compte",
        logout: "Se Déconnecter"
    },
    de: {
        address_book: "Mein Adressbuch",
        payment_options: "Meine Zahlungsoptionen",
        manage_account: "Mein Konto Verwalten",
        show_attending: "Zeigen&Outfit",
        language: "Sprache",
        currency: "währung",
        contact_preferences: "Kontaktpräferenzen",
        blocked_contacts: "Liste blockierter Kontakte",
        clear_cache: "Cache leeren",
        privacy_policy: "Datenschutzrichtlinie und Cookies",
        terms_conditions: "Geschäftsbedingungen",
        rating_comments: "Bewertung und Kommentare",
        contact_us: "Bleiben Sie in Kontakt mit Uns",
        ad_options: "Anzeigenoptionen",
        about_us: "Über Uns",
        switch_accounts: "Konto wechseln",
        logout: "Abmelden"
    },
    it: {
        address_book: "La Mia Rubrica",
        payment_options: "Le Mie Opzioni di Pagamento",
        manage_account: "Gestisci il Mio Account",
        show_attending: "Mostra&Outfit",
        language: "Lingua",
        currency: "valuta",
        contact_preferences: "Preferenze di Contatto",
        blocked_contacts: "Elenco Contatti Bloccati",
        clear_cache: "Cancella cache",
        privacy_policy: "Informativa sulla Privacy e Cookie",
        terms_conditions: "Termini e Condizioni",
        rating_comments: "Valutazione e commenti",
        contact_us: "Rimani in Contatto con Noi",
        ad_options: "Opzioni Annunci",
        about_us: "Chi Siamo",
        switch_accounts: "Cambia account",
        logout: "Disconnetti"
    },
    pt: {
        address_book: "Minha Lista de Endereços",
        payment_options: "Minhas Opções de Pagamento",
        manage_account: "Gerenciar Minha Conta",
        show_attending: "Mostrar&Roupa",
        language: "Idioma",
        currency: "moeda",
        contact_preferences: "Preferências de Contato",
        blocked_contacts: "Lista de Contatos Bloqueados",
        clear_cache: "Limpar cache",
        privacy_policy: "Política de Privacidade e Cookies",
        terms_conditions: "Termos e Condições",
        rating_comments: "Avaliação e comentários",
        contact_us: "Mantenha Contato Conosco",
        ad_options: "Opções de Anúncios",
        about_us: "Quem Somos",
        switch_accounts: "Trocar contas",
        logout: "Desconectar"
    }
};



document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
   
    const savedLanguage = localStorage.getItem('selectedLanguage') || 'es';
    const languageSelect = document.getElementById('languageSelect');
    
    if (languageSelect) {
        languageSelect.value = savedLanguage;
    }
    
    
    applyTranslations(savedLanguage);
    
    
    animateItemsOnLoad();
    
  
    initializeHoverEffects();
    
    
    startCacheUpdater();
    
    console.log('Aplicación inicializada correctamente');
}



function changeLanguage(languageCode) {
    
    if (!translations[languageCode]) {
        console.error('Idioma no disponible:', languageCode);
        return;
    }
    
    
    localStorage.setItem('selectedLanguage', languageCode);
    
    
    applyTranslations(languageCode);
    
    
    showLanguageChangeNotification(languageCode);
    
    console.log(' Idioma cambiado a:', languageCode);
}

function applyTranslations(languageCode) {
    const elements = document.querySelectorAll('[data-translate]');
    
    elements.forEach(element => {
        const key = element.getAttribute('data-translate');
        
        if (translations[languageCode] && translations[languageCode][key]) {
           
            element.style.opacity = '0.5';
            
            setTimeout(() => {
                element.textContent = translations[languageCode][key];
                element.style.transition = 'opacity 0.3s ease';
                element.style.opacity = '1';
            }, 100);
        }
    });
}

function showLanguageChangeNotification(languageCode) {
    const languageNames = {
        es: 'Español',
        en: 'English',
        fr: 'Français',
        de: 'Deutsch',
        it: 'Italiano',
        pt: 'Português'
    };
    
    
    const notification = document.createElement('div');
    notification.className = 'language-notification';
    notification.style.cssText = `
        position: fixed;
        top: 80px;
        left: 50%;
        transform: translateX(-50%) translateY(-20px);
        background: linear-gradient(135deg, #8b7355 0%, #6d5a42 100%);
        color: white;
        padding: 14px 28px;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(109, 90, 66, 0.4);
        z-index: 1000;
        font-weight: 500;
        font-size: 15px;
        opacity: 0;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        pointer-events: none;
    `;
    
    notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 10px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
            <span>Idioma: ${languageNames[languageCode]}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Animar entrada
    requestAnimationFrame(() => {
        setTimeout(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(-50%) translateY(0)';
        }, 10);
    });
    
    // Animar salida
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(-50%) translateY(-20px)';
        
        setTimeout(() => {
            if (notification.parentNode) {
                document.body.removeChild(notification);
            }
        }, 400);
    }, 2500);
}



function animateItemsOnLoad() {
    const items = document.querySelectorAll('.settings-item');
    const sections = document.querySelectorAll('.settings-section');
    
    
    sections.forEach((section, index) => {
        section.style.opacity = '0';
        section.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            section.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
            section.style.opacity = '1';
            section.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    
    items.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateX(-20px)';
        
        setTimeout(() => {
            item.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
            item.style.opacity = '1';
            item.style.transform = 'translateX(0)';
        }, index * 30);
    });
}

function initializeHoverEffects() {
    const items = document.querySelectorAll('.settings-item');
    
    items.forEach(item => {
        item.addEventListener('mouseenter', function(e) {
            createRippleEffect(this, e);
        });
        
        item.addEventListener('click', function(e) {
            createClickEffect(this, e);
        });
    });
}

function createRippleEffect(element, event) {
    const rect = element.getBoundingClientRect();
    const x = event.clientX - rect.left;
    const y = event.clientY - rect.top;
    
    const ripple = document.createElement('div');
    ripple.style.cssText = `
        position: absolute;
        left: ${x}px;
        top: ${y}px;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(139, 115, 85, 0.15);
        transform: translate(-50%, -50%);
        pointer-events: none;
        transition: width 0.6s ease-out, height 0.6s ease-out, opacity 0.6s ease-out;
        opacity: 1;
    `;
    
    element.style.position = 'relative';
    element.appendChild(ripple);
    
    requestAnimationFrame(() => {
        ripple.style.width = '500px';
        ripple.style.height = '500px';
        ripple.style.opacity = '0';
    });
    
    setTimeout(() => {
        if (ripple.parentNode) {
            ripple.parentNode.removeChild(ripple);
        }
    }, 600);
}

function createClickEffect(element, event) {
    element.style.transform = 'scale(0.98)';
    
    setTimeout(() => {
        element.style.transform = 'scale(1)';
    }, 150);
}


function navigate(section) {
    const container = document.querySelector('.settings-list');
    
    // Animación de salida
    container.style.transform = 'translateX(-15px)';
    container.style.opacity = '0.6';
    
    setTimeout(() => {
        console.log(` Navegando a: ${section}`);
        
      
        const languageCode = localStorage.getItem('selectedLanguage') || 'es';
        const messages = {
            es: `Navegando a la sección: ${section}`,
            en: `Navigating to section: ${section}`,
            fr: `Navigation vers la section: ${section}`,
            de: `Navigation zum Abschnitt: ${section}`,
            it: `Navigazione alla sezione: ${section}`,
            pt: `Navegando para a seção: ${section}`
        };
        
        alert(messages[languageCode]);
        
        // Restaurar animación
        container.style.transition = 'all 0.3s ease';
        container.style.transform = 'translateX(0)';
        container.style.opacity = '1';
    }, 200);
}

function goBack() {
    const container = document.querySelector('.container');
    
    // Animación de salida
    container.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
    container.style.transform = 'translateX(100%)';
    container.style.opacity = '0';
    
    setTimeout(() => {
        console.log('⬅️ Volviendo atrás');
        
        
        
        const languageCode = localStorage.getItem('selectedLanguage') || 'es';
        const messages = {
            es: 'Volviendo a la pantalla anterior',
            en: 'Going back to previous screen',
            fr: 'Retour à l\'écran précédent',
            de: 'Zurück zum vorherigen Bildschirm',
            it: 'Tornando alla schermata precedente',
            pt: 'Voltando para a tela anterior'
        };
        
        alert(messages[languageCode]);
        
        // Restaurar para demostración
        container.style.transform = 'translateX(0)';
        container.style.opacity = '1';
    }, 400);
}



function logout() {
    const languageCode = localStorage.getItem('selectedLanguage') || 'es';
    
    const logoutMessages = {
        es: '¿Estás seguro de que deseas cerrar sesión?',
        en: 'Are you sure you want to log out?',
        fr: 'Êtes-vous sûr de vouloir vous déconnecter?',
        de: 'Möchten Sie sich wirklich abmelden?',
        it: 'Sei sicuro di voler disconnetterti?',
        pt: 'Tem certeza de que deseja desconectar?'
    };
    
    const successMessages = {
        es: ' Sesión cerrada exitosamente',
        en: ' Successfully logged out',
        fr: ' Déconnexion réussie',
        de: ' Erfolgreich abgemeldet',
        it: ' Disconnessione riuscita',
        pt: ' Desconectado com sucesso'
    };
    
    if (confirm(logoutMessages[languageCode])) {
        const button = document.querySelector('.logout-button');
        const container = document.querySelector('.container');
        
        // Animar botón
        button.style.transform = 'scale(0.95)';
        button.style.opacity = '0.5';
        
        setTimeout(() => {
            console.log('🚪 Cerrando sesión...');
            
            // Mostrar mensaje de éxito
            alert(successMessages[languageCode]);
            
            
            container.style.transition = 'all 0.5s ease';
            container.style.opacity = '0';
            container.style.transform = 'scale(0.95)';
            
            setTimeout(() => {
                
                container.style.opacity = '1';
                container.style.transform = 'scale(1)';
                button.style.transform = 'scale(1)';
                button.style.opacity = '1';
            }, 500);
        }, 300);
    }
}



function startCacheUpdater() {
   
    setInterval(updateCacheSize, 5000);
}

function updateCacheSize() {
    const cacheElement = Array.from(document.querySelectorAll('[data-translate="clear_cache"]'))
        .map(el => el.closest('.settings-item'))
        .find(item => item)
        ?.querySelector('.item-value');
    
    if (cacheElement) {
        const sizes = ['777.6M', '750.2M', '812.4M', '690.8M', '825.1M', '703.5M'];
        const randomSize = sizes[Math.floor(Math.random() * sizes.length)];
        
        // Animación de cambio
        cacheElement.style.transition = 'opacity 0.3s ease';
        cacheElement.style.opacity = '0.5';
        
        setTimeout(() => {
            cacheElement.textContent = randomSize;
            cacheElement.style.opacity = '1';
        }, 300);
    }
}

function detectSystemTheme() {
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        return 'dark';
    }
    return 'light';
}


function preventScroll(prevent) {
    if (prevent) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
}


console.log(`

`);