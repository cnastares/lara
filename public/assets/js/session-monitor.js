/**
 * Monitor de Sesiones en Tiempo Real
 * Detecta pérdida de sesión y problemas de autenticación
 */

(function() {
    'use strict';
    
    var SessionMonitor = {
        config: {
            checkInterval: 30000, // 30 segundos
            timeout: 10000, // 10 segundos timeout
            debug: true,
            maxRetries: 3
        },
        
        status: {
            isLoggedIn: false,
            lastCheck: null,
            retryCount: 0,
            errors: []
        },
        
        log: function(message, type) {
            if (!this.config.debug) return;
            
            var prefix = '[SessionMonitor] ';
            var timestamp = new Date().toLocaleTimeString();
            var logMessage = prefix + timestamp + ' - ' + message;
            
            switch(type) {
                case 'error':
                    console.error(logMessage);
                    break;
                case 'warn':
                    console.warn(logMessage);
                    break;
                default:
                    console.log(logMessage);
            }
        },
        
        // Verificar estado de sesión
        checkSession: function() {
            var self = this;
            
            this.log('Verificando estado de sesión...');
            
            fetch('/user/check-session', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(function(response) {
                self.status.lastCheck = new Date();
                self.status.retryCount = 0;
                
                if (response.ok) {
                    return response.json();
                } else if (response.status === 401) {
                    self.log('Usuario no autenticado', 'warn');
                    self.handleSessionLoss();
                    return { authenticated: false, status: 'unauthenticated' };
                } else {
                    throw new Error('HTTP ' + response.status + ': ' + response.statusText);
                }
            })
            .then(function(data) {
                self.handleSessionResponse(data);
            })
            .catch(function(error) {
                self.handleSessionError(error);
            });
        },
        
        // Manejar respuesta de verificación de sesión
        handleSessionResponse: function(data) {
            if (data.authenticated) {
                if (!this.status.isLoggedIn) {
                    this.log('Sesión restaurada exitosamente');
                }
                this.status.isLoggedIn = true;
                this.logSessionInfo(data);
            } else {
                if (this.status.isLoggedIn) {
                    this.log('Pérdida de sesión detectada', 'warn');
                }
                this.status.isLoggedIn = false;
                this.handleSessionLoss();
            }
        },
        
        // Manejar errores de verificación
        handleSessionError: function(error) {
            this.status.retryCount++;
            this.status.errors.push({
                timestamp: new Date(),
                error: error.message
            });
            
            this.log('Error verificando sesión: ' + error.message, 'error');
            this.log('Intentos fallidos: ' + this.status.retryCount + '/' + this.config.maxRetries);
            
            if (this.status.retryCount >= this.config.maxRetries) {
                this.log('Máximo de reintentos alcanzado', 'error');
                this.handleConnectionFailure();
            }
        },
        
        // Manejar pérdida de sesión
        handleSessionLoss: function() {
            this.log('🚨 SESIÓN PERDIDA - Usuario debe re-autenticarse', 'error');
            
            // Crear notificación visual
            this.showSessionLossNotification();
            
            // Opcional: Redirigir a login después de un delay
            // setTimeout(() => {
            //     window.location.href = '/login';
            // }, 5000);
        },
        
        // Manejar fallos de conexión
        handleConnectionFailure: function() {
            this.log('Fallo de conexión - Pausando monitoreo', 'error');
            this.showConnectionErrorNotification();
        },
        
        // Mostrar información de sesión
        logSessionInfo: function(data) {
            var info = [];
            
            if (data.user) {
                info.push('Usuario: ' + (data.user.name || data.user.email || 'N/A'));
                info.push('ID: ' + data.user.id);
            }
            
            if (data.session_id) {
                info.push('Session ID: ' + data.session_id.substring(0, 8) + '...');
            }
            
            if (data.expires_at) {
                info.push('Expira: ' + new Date(data.expires_at).toLocaleString());
            }
            
            this.log('✅ Sesión activa - ' + info.join(', '));
        },
        
        // Mostrar notificación de pérdida de sesión
        showSessionLossNotification: function() {
            // Crear elemento de notificación
            var notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #f44336;
                color: white;
                padding: 15px 20px;
                border-radius: 5px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                z-index: 10000;
                font-family: Arial, sans-serif;
                font-size: 14px;
                max-width: 300px;
            `;
            
            notification.innerHTML = `
                <strong>⚠️ Sesión Expirada</strong><br>
                Debes iniciar sesión nuevamente.<br>
                <small>Serás redirigido en 5 segundos...</small>
            `;
            
            document.body.appendChild(notification);
            
            // Remover notificación después de 10 segundos
            setTimeout(function() {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 10000);
        },
        
        // Mostrar notificación de error de conexión
        showConnectionErrorNotification: function() {
            var notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #ff9800;
                color: white;
                padding: 15px 20px;
                border-radius: 5px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                z-index: 10000;
                font-family: Arial, sans-serif;
                font-size: 14px;
                max-width: 300px;
            `;
            
            notification.innerHTML = `
                <strong>⚠️ Error de Conexión</strong><br>
                No se puede verificar el estado de la sesión.<br>
                <small>Verifica tu conexión a internet.</small>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(function() {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 8000);
        },
        
        // Verificar cookies manualmente
        checkCookies: function() {
            var cookies = document.cookie.split(';');
            var sessionCookies = [];
            
            cookies.forEach(function(cookie) {
                var name = cookie.trim().split('=')[0];
                if (name.includes('session') || name.includes('laravel') || name.includes('XSRF')) {
                    sessionCookies.push(cookie.trim());
                }
            });
            
            this.log('Cookies de sesión encontradas: ' + sessionCookies.length);
            sessionCookies.forEach(function(cookie) {
                console.log('  - ' + cookie.substring(0, 50) + '...');
            });
            
            return sessionCookies;
        },
        
        // Obtener estadísticas
        getStats: function() {
            return {
                status: this.status,
                config: this.config,
                cookies: this.checkCookies()
            };
        },
        
        // Inicializar monitor
        init: function() {
            var self = this;
            
            this.log('Inicializando monitor de sesiones...');
            this.log('Intervalo de verificación: ' + (this.config.checkInterval / 1000) + ' segundos');
            
            // Verificación inicial
            this.checkSession();
            
            // Verificaciones periódicas
            setInterval(function() {
                self.checkSession();
            }, this.config.checkInterval);
            
            // Verificar cookies iniciales
            this.checkCookies();
            
            this.log('Monitor de sesiones iniciado');
        }
    };
    
    // Auto-inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            SessionMonitor.init();
        });
    } else {
        SessionMonitor.init();
    }
    
    // Exponer globalmente para debugging
    window.SessionMonitor = SessionMonitor;
    
})();