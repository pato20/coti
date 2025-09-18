### **Manual de Usuario - Sistema de Gestión de Cotizaciones y Servicios**

**I. Introducción**

**Bienvenida al Sistema de Gestión de Cotizaciones y Servicios**

Bienvenido a su nueva herramienta integral para la gestión eficiente de cotizaciones, clientes, productos y órdenes de trabajo. Este sistema ha sido diseñado para simplificar sus procesos comerciales, permitiéndole generar cotizaciones profesionales, llevar un control detallado de sus proyectos y mantener una comunicación fluida con sus clientes.

**Características Principales**

*   **Gestión de Clientes:** Mantenga un registro organizado de toda la información relevante de sus clientes.
*   **Catálogo de Productos y Servicios:** Administre fácilmente su inventario de productos y los servicios que ofrece, con precios base y descripciones detalladas.
*   **Creación de Cotizaciones Profesionales:** Genere cotizaciones personalizadas de forma rápida, incluyendo productos, servicios, ítems genéricos, IVA y descuentos.
*   **Cálculo Avanzado para Cercos Eléctricos:** Calcule automáticamente el costo de la mano de obra para instalaciones de cercos eléctricos, considerando metros, hilos, tipo de instalación y certificación.
*   **Gestión de Órdenes de Trabajo:** Convierta cotizaciones en órdenes de trabajo, realice seguimiento del progreso y registre pagos.
*   **Agenda Integrada:** Planifique y gestione visitas y mantenciones con sus clientes.
*   **Reportes:** Obtenga informes detallados para un análisis completo de su negocio.
*   **Sistema de Actualización Automático:** Mantenga su aplicación al día con las últimas características y mejoras de forma sencilla.
*   **Configuración Flexible:** Personalice la información de su empresa y los parámetros clave del sistema.

**Requisitos del Sistema**

Para el correcto funcionamiento de la aplicación, su entorno de servidor debe cumplir con los siguientes requisitos mínimos:

*   **Servidor Web:** Apache o Nginx.
*   **PHP:** Versión 7.4 o superior (se recomienda PHP 8.0+).
    *   Extensiones PHP requeridas: `pdo_mysql`, `curl`, `zip`, `gd` (para manejo de imágenes).
*   **Base de Datos:** MySQL o MariaDB (versión 5.7+ para MySQL, 10.2+ para MariaDB).
*   **Espacio en Disco:** Suficiente espacio para los archivos de la aplicación y la base de datos.
*   **Conexión a Internet:** Necesaria para el sistema de actualización y el envío de emails/WhatsApp (si se configuran).

---

**II. Instalación y Configuración Inicial**

Esta sección le guiará a través del proceso de instalación inicial de la aplicación y la configuración de los parámetros esenciales para su funcionamiento.

**Proceso de Instalación (Guía paso a paso con `install.php`)**

El sistema cuenta con un instalador guiado que le permitirá configurar la base de datos y los datos iniciales de forma sencilla.

1.  **Preparación:**
    *   Asegúrese de haber subido todos los archivos de la aplicación a su servidor web (por ejemplo, dentro de la carpeta `htdocs` de XAMPP).
    *   Verifique que su servidor MySQL/MariaDB esté en funcionamiento.

2.  **Inicio del Instalador:**
    *   Abra su navegador web y navegue a la URL donde ha subido la aplicación, seguido de `/install.php`.
    *   Ejemplo: `http://localhost/cerco_app/install.php`
    *   Si la aplicación detecta que no está instalada, le redirigirá automáticamente a esta página.

3.  **Paso 1: Configuración de la Base de Datos:**
    *   Se le solicitarán los datos de conexión a su servidor de base de datos.
    *   **Servidor (Host):** Generalmente `localhost`.
    *   **Nombre de la Base de Datos:** Ingrese el nombre que desea para la base de datos de la aplicación (ej: `mi_app_db`). Si no existe, el instalador intentará crearla.
    *   **Usuario:** El nombre de usuario de su base de datos (ej: `root` para XAMPP).
    *   **Contraseña:** La contraseña de su usuario de base de datos (ej: vacía para `root` en XAMPP).
    *   Haga clic en **"Continuar"**.

4.  **Paso 2: Crear Usuario Administrador:**
    *   Una vez configurada la base de datos, se le pedirá crear la cuenta principal de administrador del sistema.
    *   **Nombre Completo:** Su nombre o el nombre del administrador.
    *   **Nombre de Usuario:** El nombre de usuario para iniciar sesión (ej: `admin`).
    *   **Email:** Una dirección de correo electrónico válida.
    *   **Contraseña:** Una contraseña segura para su cuenta de administrador.
    *   Haga clic en **"Crear Usuario y Continuar"**.

5.  **Paso 3: Información de la Empresa:**
    *   Finalmente, ingrese los datos generales de su empresa. Esta información se utilizará en cotizaciones, reportes y en el menú lateral de la aplicación.
    *   **Nombre de la Empresa:** El nombre oficial de su empresa.
    *   **Subtítulo (Opcional):** Un texto corto que aparecerá debajo del nombre de la empresa en el menú lateral (ej: "Servicios Eléctricos").
    *   **RUT:** El número de identificación fiscal de su empresa.
    *   **Dirección:** La dirección física de su empresa.
    *   **Teléfono:** Un número de contacto.
    *   **Email de Contacto:** Una dirección de correo electrónico para consultas generales.
    *   **Logo (Opcional):** Puede subir el archivo de su logo. Se recomienda un logo cuadrado para una mejor visualización en el menú.
    *   Haga clic en **"Finalizar Instalación"**.

6.  **Instalación Completada:**
    *   El sistema le informará que la instalación ha sido completada con éxito.
    *   Por seguridad, el archivo `install.php` se "bloquea" para evitar reinstalaciones accidentales.
    *   Haga clic en **"Ir al Login"** para acceder a la aplicación.

**Configuración de la Empresa**

Una vez instalado el sistema, puede modificar la información de su empresa en cualquier momento desde la sección de **Configuración**.

1.  Inicie sesión en la aplicación.
2.  En el menú lateral, navegue a **Configuración > General**.
3.  La primera pestaña, **"Información de Empresa"**, le permitirá editar:
    *   **Datos Generales:** Nombre, Subtítulo, RUT, Dirección.
    *   **Logo:** Ver el logo actual y subir uno nuevo.
    *   **Información de Contacto:** Teléfono, Email, WhatsApp.
4.  Realice los cambios deseados y haga clic en **"Guardar Cambios de Empresa"**.

**Configuración de Ajustes del Sistema**

En la misma sección de **Configuración**, puede ajustar los parámetros generales de la aplicación.

1.  Inicie sesión en la aplicación.
2.  En el menú lateral, navegue a **Configuración > General**.
3.  Haga clic en la pestaña **"Ajustes del Sistema"**.
4.  Aquí encontrará diferentes secciones:
    *   **Ajustes de Email (SMTP):** Configure los datos de su servidor SMTP para que la aplicación pueda enviar correos electrónicos (ej: cotizaciones).
        *   `Servidor SMTP`: (ej: `smtp.gmail.com`)
        *   `Puerto SMTP`: (ej: `587` o `465`)
        *   `Usuario SMTP`: Su dirección de correo electrónico.
        *   `Contraseña SMTP`: La contraseña de su correo electrónico.
    *   **Ajustes de WhatsApp:** Configure los datos para la integración con la API de WhatsApp Business (si aplica).
        *   `Token de WhatsApp Business API`
        *   `Número de teléfono de WhatsApp`
    *   **Ajustes Financieros:**
        *   `Porcentaje de IVA`: El porcentaje de Impuesto al Valor Agregado que se aplicará en las cotizaciones.
        *   `Moneda del sistema`: El símbolo de la moneda principal (ej: `CLP`, `USD`).
5.  Ajuste los valores según sus tarifas y haga clic en **"Guardar Ajustes"**.

**Configuración de Precios de Cercos Eléctricos**

Esta sección es crucial para el cálculo automático de la mano de obra en las cotizaciones de cercos eléctricos.

1.  Inicie sesión en la aplicación.
2.  En el menú lateral, navegue a **Configuración > General**.
3.  Haga clic en la pestaña **"Ajustes del Sistema"**.
4.  Desplácese hasta la sección **"Ajustes de Precios para Cercos Eléctricos"**.
5.  Aquí podrá definir los siguientes valores:
    *   **[Cerco] Precio Base por Metro Lineal:** El costo base por cada metro lineal de cerco.
    *   **[Cerco] % Adicional por Complejidad Media:** Porcentaje que se suma al precio base por complejidad "Media".
    *   **[Cerco] % Adicional por Complejidad Compleja:** Porcentaje que se suma al precio base por complejidad "Compleja".
    *   **[Cerco] Valor Adicional para 5 Hilos:** Costo fijo adicional si la instalación es de 5 hilos.
    *   **[Cerco] Valor Adicional para 6 Hilos:** Costo fijo adicional si la instalación es de 6 hilos.
    *   **[Cerco] Valor Fijo por Certificación SEC:** Costo fijo que se añade si la cotización requiere certificación.
6.  Ajuste los valores según sus tarifas y haga clic en **"Guardar Ajustes"**.

---

**III. Uso del Sistema**

Esta sección detalla cómo utilizar las funcionalidades principales de la aplicación en su día a día.

**A. Gestión de Usuarios y Acceso**

El sistema permite la gestión de diferentes tipos de usuarios con roles específicos para controlar el acceso a las funcionalidades.

*   **Inicio de Sesión:**
    1.  Acceda a la URL de su aplicación.
    2.  Será redirigido a la página de `login.php`.
    3.  Ingrese su "Usuario o Email" y su "Contraseña".
    4.  Haga clic en "Iniciar Sesión".
    5.  Si las credenciales son correctas, accederá al Dashboard.

*   **Roles de Usuario:**
    *   **Administrador:** Acceso completo a todas las funcionalidades del sistema, incluyendo la gestión de usuarios y la configuración general.
    *   **Vendedor:** Puede gestionar clientes, crear y gestionar cotizaciones, y acceder al catálogo y reportes básicos.
    *   **Técnico:** Puede gestionar órdenes de trabajo, y acceder a cotizaciones y clientes relacionados con sus tareas.

*   **Creación y Edición de Usuarios (Solo Administradores):**
    1.  En el menú lateral, navegue a **Administración > Usuarios**.
    2.  Verá una tabla con todos los usuarios existentes.
    3.  Para crear un nuevo usuario, haga clic en el botón "Nuevo Usuario".
    4.  Para editar un usuario existente, haga clic en el botón "Editar" (icono de lápiz) en la fila del usuario.
    5.  Complete los campos:
        *   **Nombre Completo:** Nombre y apellido del usuario.
        *   **Nombre de Usuario:** Nombre único para iniciar sesión.
        *   **Email:** Correo electrónico del usuario.
        *   **Contraseña:** Contraseña para el usuario.
        *   **Rol:** Asigne el rol adecuado (Administrador, Vendedor, Técnico).
        *   **Activo:** Marque si el usuario está activo o inactivo.
    6.  Haga clic en "Guardar Usuario".

**B. Gestión de Clientes**

Mantenga un registro organizado de todos sus clientes.

*   **Acceso:** En el menú lateral, navegue a **Clientes**.
*   **Creación de Clientes:**
    1.  Haga clic en el botón "Nuevo Cliente".
    2.  Complete los campos: Nombre, RUT, Email, Teléfono, Dirección.
    3.  Haga clic en "Guardar Cliente".
*   **Edición de Clientes:**
    1.  En la tabla de clientes, haga clic en el botón "Editar" (icono de lápiz) en la fila del cliente que desea modificar.
    2.  Realice los cambios necesarios.
    3.  Haga clic en "Guardar Cliente".
*   **Búsqueda de Clientes:** Utilice la barra de búsqueda en la parte superior de la tabla para filtrar clientes por nombre, RUT, email, etc.

**C. Gestión de Catálogo (Productos y Servicios)**

Administre su inventario de productos y los servicios que ofrece.

*   **Acceso:** En el menú lateral, navegue a **Catálogo**.
*   **Añadir/Editar Categorías:**
    1.  En la sección "Categorías", haga clic en "Nueva Categoría" o en "Editar" junto a una existente.
    2.  Ingrese el "Nombre" de la categoría y seleccione su "Tipo" (Producto o Servicio).
    3.  Haga clic en "Guardar Categoría".
*   **Añadir/Editar Productos y Servicios:**
    1.  En la sección "Productos y Servicios", haga clic en "Nuevo Producto/Servicio" o en "Editar" junto a uno existente.
    2.  Complete los campos:
        *   **Categoría:** Asigne una categoría existente.
        *   **Nombre:** Nombre del producto o servicio.
        *   **Descripción:** Detalles adicionales.
        *   **Precio Base:** Precio unitario.
        *   **Costo:** Costo interno (opcional).
        *   **Unidad:** Unidad de medida (ej: "unidad", "metro", "hora").
        *   **Tipo:** Producto o Servicio.
        *   **Activo:** Marque si está disponible.
        *   **Stock:** Cantidad en inventario (solo para productos).
    3.  Haga clic en "Guardar Producto/Servicio".

**D. Creación y Gestión de Cotizaciones**

Genere cotizaciones detalladas y profesionales para sus clientes.

*   **Acceso:** En el menú lateral, navegue a **Cotizaciones**.
*   **Crear Nueva Cotización:**
    1.  Haga clic en el botón "Nueva Cotización".
    2.  **Información General:**
        *   **Cliente:** Seleccione un cliente existente.
        *   **Fecha Cotización:** Fecha de emisión.
        *   **Fecha Vencimiento:** Fecha límite para la validez de la cotización.
    3.  **Detalles de la Cotización (Ítems):**
        *   Haga clic en "Añadir Ítem" para seleccionar un producto/servicio de su catálogo. El precio base se autocompletará.
        *   Haga clic en "Añadir Genérico" para añadir un ítem con descripción y precio personalizados.
        *   Ingrese la "Cantidad", "Precio" y "Descuento (%)" para cada ítem.
    4.  **Opciones:**
        *   **Observaciones:** Notas adicionales para la cotización.
        *   **Activar IVA:** Marque para aplicar el IVA configurado en el sistema.
        *   **Aplicar Descuento General:** Marque para aplicar un porcentaje de descuento al total de la cotización.
        *   **Es Cerco Eléctrico:** Marque esta casilla para activar las opciones de cálculo de mano de obra para cercos eléctricos.
            *   **Metros Lineales:** Ingrese los metros.
            *   **Número de Hilos:** Seleccione la cantidad de hilos.
            *   **Tipo de Instalación:** Seleccione Básica, Media o Compleja.
            *   **Necesita Certificación SEC:** Marque si aplica.
            *   Haga clic en **"Calcular y Añadir Mano de Obra"**. El sistema añadirá una línea genérica con el precio calculado.
    5.  Los totales (Subtotal, IVA, Total) se recalcularán automáticamente a medida que añada ítems o cambie opciones.
    6.  Haga clic en "Guardar Cotización".

*   **Edición de Cotizaciones:**
    1.  En la tabla de cotizaciones, haga clic en el botón "Editar" (icono de lápiz) en la fila de la cotización.
    2.  Realice los cambios necesarios en la información general, ítems o opciones.
    3.  Haga clic en "Actualizar Cotización".

*   **Estados de la Cotización:**
    *   **Pendiente:** Creada, pero no enviada.
    *   **Enviada:** Enviada al cliente.
    *   **Aceptada:** El cliente ha aceptado la cotización.
    *   **Rechazada:** El cliente ha rechazado la cotización.
    *   **Vencida:** La fecha de vencimiento ha pasado.
    *   Puede cambiar el estado de una cotización desde la tabla principal.

*   **Generar PDF de Cotización:**
    1.  En la tabla de cotizaciones, haga clic en el botón "Ver PDF" (icono de PDF).
    2.  Se abrirá un PDF con el detalle de la cotización.

*   **Enviar Cotización por WhatsApp/Email:**
    1.  En la tabla de cotizaciones, haga clic en el botón "Enviar" (icono de sobre o WhatsApp).
    2.  Siga las instrucciones para enviar la cotización al cliente.

**E. Gestión de Órdenes de Trabajo**

Convierta cotizaciones en órdenes de trabajo y realice un seguimiento de sus proyectos.

*   **Acceso:** En el menú lateral, navegue a **Órdenes de Trabajo**.
*   **Crear Órdenes desde Cotizaciones:**
    1.  En la tabla de cotizaciones, apruebe una cotización. El sistema le preguntará si desea crear una Orden de Trabajo.
    2.  También puede crear una orden directamente desde la página de Órdenes de Trabajo.
*   **Seguimiento y Porcentaje de Avance:**
    1.  En la tabla de órdenes, haga clic en "Ver" (icono de ojo) para ver el detalle de una orden.
    2.  Puede actualizar el porcentaje de avance y el estado de la orden.
*   **Registro de Pagos:**
    1.  Desde la vista de detalle de la orden, puede registrar pagos parciales o totales.
*   **Estados de la Orden:**
    *   **Pendiente:** Orden creada, esperando inicio.
    *   **En Proceso:** Trabajo en ejecución.
    *   **Pausada:** Trabajo temporalmente detenido.
    *   **Completada:** Trabajo finalizado.
    *   **Cancelada:** Orden anulada.

**F. Gestión de Agenda**

Planifique y gestione sus citas y tareas.

*   **Acceso:** En el menú lateral, navegue a **Agenda**.
*   **Agendar Visitas y Mantenciones:**
    1.  Haga clic en "Nueva Cita".
    2.  Complete los detalles: Título, Descripción, Cliente (opcional), Fechas y Horas, Tipo (Visita o Mantención).
    3.  Haga clic en "Guardar Cita".
*   **Reagendar y Cancelar Eventos:**
    1.  Desde la vista de la agenda, puede editar o eliminar citas existentes.

**G. Reportes**

Obtenga información valiosa sobre el rendimiento de su negocio.

*   **Acceso:** En el menú lateral, navegue a **Reportes > Reporte Detallado**.
*   Utilice los filtros disponibles para generar reportes por fechas, clientes, estados, etc.
*   Puede exportar los reportes a Excel o PDF.

---

**IV. Mantenimiento y Actualizaciones**

Esta sección cubre aspectos importantes para el mantenimiento de su aplicación, incluyendo la gestión de copias de seguridad y el proceso de actualización.

**Copias de Seguridad y Restauración de la Base de Datos**

Es fundamental realizar copias de seguridad periódicas de su base de datos para proteger su información.

1.  **Acceso:** En el menú lateral, navegue a **Configuración > General**.
2.  Haga clic en la pestaña **"Copias de Seguridad"**.
3.  **Crear Copia de Seguridad:**
    *   Haga clic en el botón **"Crear y Descargar"**.
    *   El sistema generará un archivo `.sql` con una copia completa de su base de datos y lo descargará a su equipo.
    *   Estos backups también se guardan en la carpeta `backups/` de su instalación.
4.  **Restaurar Copia de Seguridad:**
    *   **¡ATENCIÓN!** La restauración de una copia de seguridad es una acción **irreversible** que reemplazará todos los datos actuales de su base de datos con los del archivo seleccionado. Utilice esta función con extrema precaución.
    *   Haga clic en **"Seleccionar archivo"** y elija el archivo `.sql` de su copia de seguridad.
    *   Haga clic en **"Restaurar desde Archivo"**.
    *   Confirme la acción cuando se le solicite.

**Sistema de Actualización de la Aplicación**

El sistema cuenta con una funcionalidad para actualizar la aplicación a la última versión disponible de forma automática.

1.  **Acceso:** En el menú lateral, navegue a **Configuración > Actualizador**.
2.  **Verificación de Versión:**
    *   Verá su "Versión actual instalada".
    *   Haga clic en el botón **"Buscar Actualizaciones"**.
    *   El sistema se conectará al repositorio remoto para verificar si hay una nueva versión disponible.
3.  **Actualización Disponible:**
    *   Si hay una nueva versión, el sistema le mostrará la versión disponible y las notas de la versión.
    *   Haga clic en **"Actualizar Ahora"** para iniciar el proceso.
    *   **Importante:** Durante la actualización, la aplicación entrará en modo mantenimiento.
4.  **Proceso de Actualización:**
    *   La pantalla mostrará un log detallado de cada paso: descarga del paquete, verificación de integridad, creación de copia de seguridad, extracción de archivos, instalación y ejecución de migraciones de base de datos.
    *   Una vez completado, el sistema le informará que la actualización ha finalizado con éxito y le permitirá volver al Dashboard.

**Gestión de Migraciones de Base de Datos (Para Administradores Avanzados)**

Este sistema permite aplicar cambios en la estructura de la base de datos de forma automática durante el proceso de actualización.

1.  **Ubicación de las Migraciones:**
    *   Los archivos de migración son scripts SQL (`.sql`) que se guardan en la carpeta `database/migrations/` de su instalación.
    *   Cada archivo de migración debe tener un nombre único que indique la versión o el cambio que aplica (ej: `1.0.4_add_subtitulo_to_empresa.sql`).
2.  **Funcionamiento:**
    *   Cuando se ejecuta una actualización, el actualizador escanea la carpeta `database/migrations/` dentro del paquete de actualización.
    *   Compara los archivos encontrados con un registro en la tabla `migrations` de su base de datos.
    *   Solo ejecuta los archivos `.sql` que aún no han sido aplicados.
    *   Esto asegura que los cambios en la base de datos se apliquen de forma incremental y solo una vez.
3.  **Creación de Nuevas Migraciones:**
    *   Si usted (o un desarrollador) realiza un cambio en la estructura de la base de datos (ej: añadir una nueva tabla, añadir una columna), debe crear un nuevo archivo `.sql` con ese cambio y guardarlo en la carpeta `database/migrations/`.
    *   Este archivo debe ser incluido en el paquete `update.zip` de la siguiente versión de la aplicación.

---

**V. Ayuda y Soporte**

Esta sección le proporciona información sobre la versión de su aplicación y cómo obtener soporte.

**Información de la Versión y Notas de Lanzamiento**

Para conocer la versión actual de su aplicación y los cambios que se han implementado en la última actualización:

1.  En el menú lateral, navegue a **Configuración > Ayuda**.
2.  Verá la **"Versión Actual"** de la aplicación instalada.
3.  En la sección **"Última Actualización"**, encontrará las notas de lanzamiento correspondiente a la última versión que se instaló en su sistema. Aquí se detallan las nuevas características, mejoras y correcciones de errores.

**Información del Desarrollador y Contacto para Soporte**

En la misma página de **Ayuda**, encontrará una sección dedicada a la información del desarrollador de la aplicación.

*   Aquí se indicará el nombre del desarrollador o de la empresa desarrolladora.
*   Se proporcionarán los datos de contacto para soporte técnico, consultas o cualquier incidencia que pueda surgir con el sistema.
*   Utilice esta información para comunicarse directamente con el equipo de soporte cuando necesite asistencia.
