/**
 * Auto-Fix para Errores JavaScript Comunes
 * Este script debe ser incluido en todas las páginas como el primer script
 */

(function() {
    'use strict';
    
    // Configuración
    var CONFIG = {
        POLYFILL_BASE_URL: '/assets/js/',
        DEBUG: true // Cambiar a false en producción
    };
    
    function log(message, type) {
        if (!CONFIG.DEBUG) return;
        
        var prefix = '[AutoFix] ';
        switch(type) {
            case 'error':
                console.error(prefix + message);
                break;
            case 'warn':
                console.warn(prefix + message);
                break;
            case 'info':
                console.info(prefix + message);
                break;
            default:
                console.log(prefix + message);
        }
    }
    
    // 1. Fix para documentPictureInPicture
    function fixPictureInPicture() {
        if (typeof window.documentPictureInPicture === 'undefined') {
            log('documentPictureInPicture no está definido, aplicando fix...', 'warn');
            
            // Implementación inmediata del polyfill
            window.documentPictureInPicture = {
                window: null,
                requestWindow: function(options) {
                    log('documentPictureInPicture.requestWindow llamado (no soportado)', 'warn');
                    return Promise.reject(new Error('Picture-in-Picture API no soportada'));
                },
                addEventListener: function(type, listener, options) {
                    log('documentPictureInPicture.addEventListener llamado (no soportado)', 'warn');
                },
                removeEventListener: function(type, listener, options) {
                    log('documentPictureInPicture.removeEventListener llamado (no soportado)', 'warn');
                },
                supported: false
            };
            
            log('documentPictureInPicture polyfill aplicado', 'info');
        } else {
            log('documentPictureInPicture ya está disponible', 'info');
        }
    }
    
    // 2. Fix para errores de CORS en fuentes
    function fixFontCORS() {
        // Interceptar errores de carga de fuentes
        var originalAddEventListener = window.addEventListener;
        window.addEventListener = function(type, listener, options) {
            if (type === 'error') {
                var wrappedListener = function(event) {
                    // Verificar si es un error de fuente
                    if (event.target && event.target.tagName === 'LINK' && 
                        event.target.href && event.target.href.includes('fonts/')) {
                        log('Error de carga de fuente detectado: ' + event.target.href, 'warn');
                        
                        // Intentar recargar con protocolo correcto
                        var correctedHref = event.target.href.replace('https://', 'http://');
                        if (correctedHref !== event.target.href) {
                            log('Intentando recargar fuente con HTTP: ' + correctedHref, 'info');
                            event.target.href = correctedHref;
                        }
                    }
                    
                    return listener.call(this, event);
                };
                return originalAddEventListener.call(this, type, wrappedListener, options);
            }
            return originalAddEventListener.call(this, type, listener, options);
        };
    }
    
    // 3. Manejo global de errores
    function setupGlobalErrorHandler() {
        // Capturar errores de JavaScript
        window.addEventListener('error', function(event) {
            var errorMessage = event.error ? event.error.message : event.message;
            
            // Filtrar errores específicos que queremos manejar
            if (errorMessage && errorMessage.includes('documentPictureInPicture')) {
                log('Error de documentPictureInPicture interceptado: ' + errorMessage, 'warn');
                fixPictureInPicture();
                event.preventDefault();
                return false;
            }
            
            // CORS errors
            if (errorMessage && (errorMessage.includes('CORS') || errorMessage.includes('cross-origin'))) {
                log('Error CORS detectado: ' + errorMessage, 'warn');
                fixFontCORS();
            }
        });
        
        // Capturar errores de promesas no manejadas
        window.addEventListener('unhandledrejection', function(event) {
            var reason = event.reason ? event.reason.message || event.reason : 'Unknown';
            
            if (reason.includes && reason.includes('Picture-in-Picture')) {
                log('Promise rechazada por Picture-in-Picture: ' + reason, 'warn');
                event.preventDefault();
            }
        });
    }
    
    // 4. Verificación de APIs disponibles
    function checkAPIs() {
        var apis = {
            'fetch': 'fetch' in window,
            'Promise': 'Promise' in window,
            'localStorage': 'localStorage' in window,
            'sessionStorage': 'sessionStorage' in window,
            'documentPictureInPicture': 'documentPictureInPicture' in window,
            'pictureInPictureEnabled': 'pictureInPictureEnabled' in document
        };
        
        log('APIs disponibles:');
        for (var api in apis) {
            log('  ' + api + ': ' + (apis[api] ? '✅' : '❌'));
        }
        
        return apis;
    }
    
    // 5. Inicialización
    function initialize() {
        log('Inicializando Auto-Fix para errores JavaScript...', 'info');
        
        // Aplicar fixes inmediatamente
        fixPictureInPicture();
        fixFontCORS();
        setupGlobalErrorHandler();
        
        // Verificar APIs
        var apis = checkAPIs();
        
        // Reportar estado
        var unsupportedAPIs = Object.keys(apis).filter(function(api) {
            return !apis[api];
        });
        
        if (unsupportedAPIs.length > 0) {
            log('APIs no soportadas: ' + unsupportedAPIs.join(', '), 'warn');
        } else {
            log('Todas las APIs están disponibles', 'info');
        }
        
        log('Auto-Fix inicializado correctamente', 'info');
    }
    
    // Ejecutar inmediatamente
    initialize();
    
    // Exponer utilidades globales
    window.AutoFix = {
        fixPictureInPicture: fixPictureInPicture,
        checkAPIs: checkAPIs,
        log: log
    };
    
})();