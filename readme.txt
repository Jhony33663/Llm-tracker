=== LLM Tracker for WordPress ===
Contributors: Jonathan Mata
Tags: llm, tracker, analytics, bot detection, ai, artificial intelligence, chatgpt, claude, gemini
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin para rastrear las visitas de LLMs (Language Learning Models) a tu archivo llms.txt con registro detallado de fecha, hora y origen.

== Descripción ==

LLM Tracker for WordPress es un plugin especializado diseñado para detectar y rastrear las visitas de modelos de lenguaje artificial (LLMs) como ChatGPT, Claude, Gemini, y otros bots a tu sitio WordPress. El plugin monitorea específicamente el acceso al archivo llms.txt y proporciona estadísticas detalladas sobre quién visita tu sitio.

== Características Principales ==

* **Detección Automática de LLMs**: Identifica automáticamente cuando un LLM accede a tu archivo llms.txt
* **Registro Detallado**: Guarda información completa de cada visita incluyendo:
  - Fecha y hora exacta
  - Dirección IP del visitante
  - User Agent completo
  - Página de origen (referer)
  - País y ciudad (con API de geolocalización)
  - Headers HTTP relevantes
* **Panel de Administración**: Interfaz intuitiva con gráficos y estadísticas
* **Filtros Avanzados**: Filtra visitas por tipo, fecha, IP, o bot específico
* **Exportación de Datos**: Exporta los datos en formato CSV para análisis externos
* **Notificaciones por Email**: Recibe alertas cuando se detectan nuevos LLMs
* **Archivo llms.txt Personalizable**: Genera y personaliza tu archivo llms.txt
* **Compatible con WordPress Multisitio**

== Instalación ==

1. Sube la carpeta `llm-tracker-for-wordpress` al directorio `/wp-content/plugins/` de tu WordPress
2. Activa el plugin desde la página 'Plugins' en WordPress
3. Ve a 'LLM Tracker' en el menú de administración para configurar las opciones
4. Tu archivo llms.txt estará disponible en `tudominio.com/llms.txt`

== Configuración ==

Una vez activado el plugin, puedes configurar las siguientes opciones:

**General**
- Activar tracking en todas las páginas
- Incluir usuarios logueados en el tracking
- Configurar notificaciones por email

**Tracking**
- Habilitar geolocalización (requiere API key)
- Excluir IPs específicas del tracking
- Configurar limpieza automática de datos

**llms.txt**
- Personalizar el contenido del archivo llms.txt
- Regenerar el archivo automáticamente

**Avanzado**
- Limpiar datos antiguos
- Exportar datos en CSV
- Ver estadísticas de la base de datos

== Uso ==

### Acceso al Panel de Administración

1. En tu panel de WordPress, ve a **LLM Tracker** en el menú lateral
2. En el dashboard verás las estadísticas principales:
   - Total de visitas
   - Visitas de bots/LLMs detectados
   - Visitas humanas
   - Top de LLMs más frecuentes

### Ver Historial de Visitas

1. Haz clic en **Visits History** en el submenú
2. Usa los filtros para buscar visitas específicas:
   - Por tipo (bots/humanos)
   - Por bot específico
   - Por rango de fechas
   - Por dirección IP
3. Haz clic en "Detalles" para ver información completa de cada visita

### Personalizar llms.txt

1. Ve a **Settings** en el submenú
2. Haz clic en la pestaña **llms.txt**
3. Edita el contenido según tus necesidades
4. Guarda los cambios o regenera el archivo

== LLMs Detectados ==

El plugin puede detectar los siguientes LLMs y bots:

* ChatGPT / OpenAI
* Claude / Anthropic
* Gemini / Google
* LLaMA
* Mistral
* Google Bot
* Bing Bot
* Yahoo Bot
* DuckDuck Bot
* Baidu Spider
* Yandex Bot
* Facebook Bot
* Twitter Bot
* LinkedIn Bot
* Y cualquier bot/crawler genérico

== Preguntas Frecuentes ==

**¿Qué es el archivo llms.txt?**
Es un archivo de configuración que proporciona información sobre tu sitio a los modelos de lenguaje artificial, similar a cómo robots.txt funciona para los motores de búsqueda.

**¿El plugin afecta el rendimiento de mi sitio?**
No, el plugin está optimizado para tener mínimo impacto en el rendimiento. Usa consultas eficientes y caché cuando es posible.

**¿Puedo excluir ciertas IPs del tracking?**
Sí, en la configuración puedes agregar una lista de IPs que serán ignoradas por el sistema de tracking.

**¿Los datos se eliminan automáticamente?**
Sí, puedes configurar la limpieza automática para eliminar registros más antiguos que un número específico de días.

**¿Puedo exportar los datos?**
Sí, puedes exportar todos los datos de visitas en formato CSV desde la página de configuración avanzada.

== Capturas de Pantalla ==

1. Dashboard principal con estadísticas
2. Historial de visitas con filtros
3. Configuración del plugin
4. Detalles de una visita específica

== Changelog ==

= 1.0.0 =
* Lanzamiento inicial del plugin
* Detección automática de LLMs
* Panel de administración completo
* Sistema de filtros avanzados
* Exportación de datos
* Notificaciones por email
* Geolocalización opcional

== Mejoras Futuras ==

* Integración con más APIs de geolocalización
* Detección de patrones de comportamiento
* Reportes automáticos por email
* Integración con Google Analytics
* Soporte para más formatos de exportación
* API REST para acceso programático

== Créditos ==

* **Autor**: Jonathan Mata
* **LinkedIn**: https://ec.linkedin.com/in/jonathan-david-mata-rodriguez-62a925203
* **Plugin URL**: https://uide.edu.ec
* **Licencia**: GPL v2 o posterior

Este plugin ha sido desarrollado para ayudar a los administradores de sitios WordPress a entender mejor cómo los modelos de lenguaje artificial interactúan con su contenido.