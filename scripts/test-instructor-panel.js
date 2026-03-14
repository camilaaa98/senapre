/**
 * Testing Automatizado - Panel Instructor SenApre
 * Validación de funcionalidades principales
 * Principios SOLID: Testing como responsabilidad separada
 */

class InstructorPanelTester {
    constructor() {
        this.testResults = [];
        this.baseUrl = window.location.origin;
        this.timeout = 10000;
    }

    // Utilidades de testing
    async runTest(testName, testFunction) {
        console.log(`🧪 Ejecutando: ${testName}`);
        const startTime = Date.now();
        
        try {
            await Promise.race([
                testFunction(),
                new Promise((_, reject) => 
                    setTimeout(() => reject(new Error('Timeout')), this.timeout)
                )
            ]);
            
            const duration = Date.now() - startTime;
            this.logSuccess(testName, duration);
            this.testResults.push({ name: testName, status: 'PASS', duration });
        } catch (error) {
            console.error(`❌ Error en ${testName}:`, error);
            this.logFailure(testName, error);
            this.testResults.push({ name: testName, status: 'FAIL', error: error.message });
        }
    }

    logSuccess(testName, duration) {
        console.log(`✅ ${testName} - ${duration}ms`);
        this.showNotification(`${testName} completado`, 'success');
    }

    logFailure(testName, error) {
        console.error(`❌ ${testName}: ${error.message}`);
        this.showNotification(`Error en ${testName}: ${error.message}`, 'error');
    }

    showNotification(message, type) {
        // Crear notificación visual si no existe
        if (typeof mostrarNotificacion === 'function') {
            mostrarNotificacion(message, type);
        } else {
            // Fallback a console
            const icon = type === 'success' ? '✅' : '❌';
            console.log(`${icon} ${message}`);
        }
    }

    // Tests de UI
    async testDashboardElements() {
        const requiredElements = [
            '#fichasCount',
            '#aprendicesCount', 
            '#promedioAsistencia',
            '#alertasCount',
            '#calendar'
        ];

        for (const selector of requiredElements) {
            const element = document.querySelector(selector);
            if (!element) {
                throw new Error(`Elemento no encontrado: ${selector}`);
            }
        }
    }

    async testSidebarNavigation() {
        const menuItems = document.querySelectorAll('.sidebar-menu .menu-link');
        if (menuItems.length === 0) {
            throw new Error('No se encontraron items de menú');
        }

        // Verificar que todos los enlaces tengan href
        for (const item of menuItems) {
            if (item.tagName === 'A' && !item.href) {
                throw new Error('Item de menú sin href');
            }
        }
    }

    async testResponsiveBreakpoints() {
        const originalWidth = window.innerWidth;
        
        // Test mobile
        window.innerWidth = 480;
        window.dispatchEvent(new Event('resize'));
        await new Promise(resolve => setTimeout(resolve, 100));
        
        // Test tablet
        window.innerWidth = 768;
        window.dispatchEvent(new Event('resize'));
        await new Promise(resolve => setTimeout(resolve, 100));
        
        // Test desktop
        window.innerWidth = 1024;
        window.dispatchEvent(new Event('resize'));
        await new Promise(resolve => setTimeout(resolve, 100));
        
        // Restaurar
        window.innerWidth = originalWidth;
        window.dispatchEvent(new Event('resize'));
    }

    async testFormValidation() {
        const forms = document.querySelectorAll('form');
        for (const form of forms) {
            const inputs = form.querySelectorAll('input[required], select[required]');
            for (const input of inputs) {
                // Test validación HTML5
                if (!input.checkValidity()) {
                    console.warn(`Input inválido: ${input.name || input.id}`);
                }
            }
        }
    }

    async testAPIConnectivity() {
        if (typeof authSystem !== 'undefined' && authSystem.getCurrentUser()) {
            const user = authSystem.getCurrentUser();
            
            // Test dashboard API
            const response = await fetch(`${this.baseUrl}/api/instructor-dashboard.php?id_usuario=${user.id_usuario}`);
            if (!response.ok) {
                throw new Error(`API Dashboard error: ${response.status}`);
            }
            
            const data = await response.json();
            if (!data.success) {
                throw new Error(`API Dashboard failure: ${data.message}`);
            }
        }
    }

    async testLocalStorage() {
        const testKey = 'instructor_test_key';
        const testValue = 'test_value';
        
        localStorage.setItem(testKey, testValue);
        const retrieved = localStorage.getItem(testKey);
        
        if (retrieved !== testValue) {
            throw new Error('LocalStorage no funciona correctamente');
        }
        
        localStorage.removeItem(testKey);
    }

    async testConsoleErrors() {
        // Capturar errores de consola durante el test
        const originalError = console.error;
        let errors = [];
        
        console.error = function(...args) {
            errors.push(args);
            originalError.apply(console, args);
        };
        
        // Esperar un momento para capturar errores
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        // Restaurar
        console.error = originalError;
        
        if (errors.length > 0) {
            throw new Error(`Se detectaron ${errors.length} errores de consola`);
        }
    }

    async testPerformanceMetrics() {
        if ('performance' in window) {
            const navigation = performance.getEntriesByType('navigation')[0];
            const loadTime = navigation.loadEventEnd - navigation.loadEventStart;
            
            if (loadTime > 3000) {
                console.warn(`Tiempo de carga lento: ${loadTime}ms`);
            }
            
            return { loadTime };
        }
    }

    async testAccessibility() {
        // Test básico de accesibilidad
        const images = document.querySelectorAll('img:not([alt])');
        if (images.length > 0) {
            throw new Error(`${images.length} imágenes sin atributo alt`);
        }
        
        const links = document.querySelectorAll('a[href=""]');
        if (links.length > 0) {
            console.warn(`${links.length} enlaces vacíos encontrados`);
        }
    }

    // Ejecutar todos los tests
    async runAllTests() {
        console.log('🚀 Iniciando tests del panel instructor...');
        
        const tests = [
            { name: 'Elementos del Dashboard', fn: () => this.testDashboardElements() },
            { name: 'Navegación Sidebar', fn: () => this.testSidebarNavigation() },
            { name: 'Diseño Responsivo', fn: () => this.testResponsiveBreakpoints() },
            { name: 'Validación de Formularios', fn: () => this.testFormValidation() },
            { name: 'Conectividad API', fn: () => this.testAPIConnectivity() },
            { name: 'LocalStorage', fn: () => this.testLocalStorage() },
            { name: 'Errores de Consola', fn: () => this.testConsoleErrors() },
            { name: 'Métricas de Performance', fn: () => this.testPerformanceMetrics() },
            { name: 'Accesibilidad Básica', fn: () => this.testAccessibility() }
        ];

        for (const test of tests) {
            await this.runTest(test.name, test.fn);
        }

        this.generateReport();
    }

    generateReport() {
        const passed = this.testResults.filter(r => r.status === 'PASS').length;
        const failed = this.testResults.filter(r => r.status === 'FAIL').length;
        const total = this.testResults.length;

        console.log('\n📊 Reporte de Tests:');
        console.log(`✅ Pasados: ${passed}/${total}`);
        console.log(`❌ Fallidos: ${failed}/${total}`);
        console.log(`📈 Tasa de éxito: ${((passed/total) * 100).toFixed(1)}%`);

        if (failed > 0) {
            console.log('\n❌ Tests Fallidos:');
            this.testResults
                .filter(r => r.status === 'FAIL')
                .forEach(r => console.log(`  - ${r.name}: ${r.error}`));
        }

        // Mostrar resumen visual
        this.showTestSummary(passed, failed, total);
    }

    showTestSummary(passed, failed, total) {
        const summaryDiv = document.createElement('div');
        summaryDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            z-index: 10000;
            max-width: 300px;
            font-family: system-ui;
        `;

        const successRate = ((passed/total) * 100).toFixed(1);
        const statusColor = failed === 0 ? '#10b981' : '#ef4444';
        const statusIcon = failed === 0 ? '✅' : '⚠️';

        summaryDiv.innerHTML = `
            <h3 style="margin: 0 0 10px 0; color: #1f2937;">${statusIcon} Tests Completados</h3>
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                <span>✅ Pasados:</span><strong>${passed}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                <span>❌ Fallidos:</span><strong>${failed}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                <span>📈 Éxito:</span><strong style="color: ${statusColor}">${successRate}%</strong>
            </div>
            <button onclick="this.parentElement.remove()" style="
                width: 100%;
                padding: 8px;
                background: #3b82f6;
                color: white;
                border: none;
                border-radius: 6px;
                cursor: pointer;
            ">Cerrar</button>
        `;

        document.body.appendChild(summaryDiv);

        // Auto-remover después de 10 segundos
        setTimeout(() => {
            if (summaryDiv.parentElement) {
                summaryDiv.remove();
            }
        }, 10000);
    }
}

// Auto-ejecutar si estamos en una página del instructor
if (window.location.pathname.includes('instructor-')) {
    // Esperar a que cargue la página
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                const tester = new InstructorPanelTester();
                tester.runAllTests();
            }, 2000); // Esperar 2s después del DOM ready
        });
    } else {
        setTimeout(() => {
            const tester = new InstructorPanelTester();
            tester.runAllTests();
        }, 2000);
    }
}

// Exponer para ejecución manual
window.InstructorPanelTester = InstructorPanelTester;
