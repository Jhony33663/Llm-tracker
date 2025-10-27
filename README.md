🤖 LLM Tracker - by C0d3k

🎯 Plugin avanzado para WordPress que detecta, rastrea y analiza visitas de Language Learning Models (LLMs) y bots en tu sitio web.

📋 Características Principales
🔍 Detección Inteligente
100+ tipos de bots predefinidos (ChatGPT, Claude, Gemini, Googlebot, etc.)
Detección por User Agent - Análisis avanzado de cadenas de identificación
Detección por Comportamiento - Patrones de navegación sospechosos
Detección Cliente-Side - Análisis de características del navegador
Verificación de IP - Comparación con rangos oficiales de proveedores
📊 Panel de Administración Completo
Dashboard en tiempo real con estadísticas clave
Gráficos interactivos de tendencias de visitas
Historial detallado con paginación avanzada
Sistema de filtros múltiples (fecha, tipo, IP, bot específico)
Exportación de datos en formato CSV
🛡️ Características de Seguridad
Validación de datos en todas las entradas
Protección contra inyección SQL con prepared statements
Verificación de permisos de administrador
Headers de seguridad implementados
🌍 Geolocalización (Opcional)
Integración con APIs de geolocalización
Detección automática de país y ciudad
Soporte para múltiples proveedores (ipstack, ipapi, etc.)
🚀 Instalación
Requisitos
WordPress 5.0 o superior
PHP 7.4 o superior
MySQL 5.6 o superior
Acceso FTP al servidor
Instalación Automática (Recomendada)
En tu panel de WordPress, ve a Plugins → Añadir Nuevo
Busca "LLM Tracker - by C0d3k"
Click en Instalar ahora
Click en Activar
Instalación Manual
Descarga el plugin desde GitHub
Descomprime el archivo llm-tracker-for-wordpress.zip
Sube la carpeta llm-tracker-for-wordpress a /wp-content/plugins/
En tu panel de WordPress, ve a Plugins
Busca "LLM Tracker" y click en Activar
⚙️ Configuración
Configuración Básica
Ve a LLM Tracker → Settings
Configura las opciones básicas:
✅ Trackear todas las páginas
✅ Trackear usuarios logueados
📧 Notificaciones por email
Configuración Avanzada
Geolocalización:
Activa la opción "Geolocalización"
Añade tu API key (ipstack, ipapi, etc.)
Filtros de Exclusión:
Añade IPs a excluir (una por línea)
Ideal para excluir tráfico interno
Limpieza Automática:
Configura días para eliminación automática
Previene sobrecarga de la base de datos
📖 Uso del Plugin
Acceder al Panel
En tu panel de WordPress, ve a LLM Tracker
Dashboard - Vista general de estadísticas
Visits History - Historial completo con filtros
Settings - Configuración del plugin
Usar los Filtros
📅 Filtros de Fecha
Atajos rápidos: Hoy, Ayer, 7 días, 30 días, 90 días, Este mes, Mes pasado
Rango personalizado: Selecciona fechas específicas con el calendario
🤖 Filtros de Tipo
Todos - Muestra todas las visitas
Solo Bots/LLMs - Filtra solo bots detectados
Solo Humanos - Solo visitas de usuarios reales
Bots específicos - Filtra por tipo (ChatGPT, Googlebot, etc.)
🌐 Filtro de IP
Búsqueda parcial o completa de direcciones IP
Útil para investigar actividad sospechosa
Exportar Datos
Aplica los filtros deseados
Click en "Exportar CSV"
Descarga el archivo con todos los datos filtrados
🎯 Tipos de Bots Detectados
LLMs Principales
ChatGPT/OpenAI - GPT-3, GPT-4, GPT-4o
Claude/Anthropic - Claude 1, 2, 3
Gemini/Google - Gemini Pro, Ultra
LLaMA - Meta LLaMA variants
Crawlers Clásicos
Googlebot - Búsqueda de Google
Bingbot - Búsqueda de Microsoft
Slurp - Yahoo Search
DuckDuckBot - DuckDuckGo
Baidu Spider - Baidu Search
Social Media Bots
Facebook External Hit - Facebook crawler
Twitterbot - Twitter/X crawler
LinkedInBot - LinkedIn crawler
WhatsApp - WhatsApp bot
📊 Métricas y Estadísticas
Dashboard Principal
Total de visitas - Todas las visitas registradas
Visitas de bots - Conteo de bots detectados
Visitas humanas - Visitas de usuarios reales
Últimas 24h - Actividad reciente
Estadísticas Avanzadas
Tendencias diarias - Gráfico de evolución
Top bots - Los bots más frecuentes
IPs únicas - Visitantes únicos
Porcentajes - Distribución bot/humano
🔧 Desarrollo y Contribución
Estructura del Plugin

Line Wrapping

Collapse
Copy
1
2
3
4
5
6
7
8
9
10
11
12
13
llm-tracker-for-wordpress/
├── llm-tracker.php          # Archivo principal
├── admin/
│   ├── dashboard.php        # Panel principal
│   ├── visits.php          # Historial de visitas
│   └── settings.php        # Configuración
├── assets/
│   ├── css/
│   │   └── admin.css       # Estilos administración
│   └── js/
│       └── admin.js        # Scripts administración
├── llms.txt                # Archivo de configuración LLM
└── readme.txt              # Documentación
Tecnologías Utilizadas
PHP 7.4+ - Backend y lógica principal
MySQL/MariaDB - Almacenamiento de datos
WordPress API - Integración con WordPress
Chart.js - Gráficos interactivos
Bootstrap - Componentes UI
jQuery - Interactividad frontend
Contribuir
Fork el repositorio
Crea una rama para tu feature: git checkout -b feature/nueva-funcionalidad
Commit tus cambios: git commit -m 'Añadir nueva funcionalidad'
Push a la rama: git push origin feature/nueva-funcionalidad
Abre un Pull Request
🐛 Reportar Issues
¿Encontraste un bug? Por favor:

Verifica si ya existe un issue
Si no existe, crea un nuevo issue con:
Versión de WordPress
Versión del plugin
Descripción detallada del problema
Pasos para reproducir
Capturas de pantalla (si aplica)
📝 Changelog
v1.0.0 - 27/10/2025
✨ Lanzamiento inicial
🔍 Detección de 100+ tipos de bots
📊 Panel de administración completo
📈 Gráficos interactivos
🎛️ Sistema de filtros avanzados
📤 Exportación de datos CSV
🌍 Soporte de geolocalización
🛡️ Validación de seguridad
📱 Diseño responsive
🔮 Roadmap
Próximas Versiones
v1.1.0 - Alertas en tiempo real
v1.2.0 - Integración con Google Analytics
v1.3.0 - API REST para desarrolladores
v1.4.0 - Machine Learning para detección avanzada
v2.0.0 - Versión multi-sitio
📄 Licencia
Este plugin está licenciado bajo GPL v2 o posterior.


Line Wrapping

Collapse
Copy
1
2
3
4
5
6
7
LLM Tracker - by C0d3k
Copyright (C) 2025 C0d3k

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.
👨‍💻 Autor
C0d3k - Desarrollador Web & Especialista en Seguridad

¿Te gusta este plugin? ¡Apóyalo de estas formas:

⭐ Dale una estrella en GitHub
🐛 Reporta bugs para mejorar el plugin
💡 Sugiere funcionalidades
🏆 Agradecimientos

A la comunidad de WordPress por el increíble ecosistema
A todos los beta testers por su valioso feedback
A los creadores de Chart.js por las increíbles visualizaciones
A ti, por usar este plugin 🎉
<div align="center">

⬆️ Volver al inicio

Hecho con ❤️ por C0d3k

</div>
