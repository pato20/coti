// Funciones para gestión de productos
function editarProducto(producto) {
  document.getElementById("productoAction").value = "update";
  document.getElementById("productoId").value = producto.id;
  document.getElementById("productoNombre").value = producto.nombre;
  document.getElementById("productoDescripcion").value = producto.descripcion || "";
  document.getElementById("productoCosto").value = producto.costo;
  document.getElementById("productoPrecioBase").value = producto.precio_base;
  document.getElementById("productoTipo").value = producto.tipo;
  document.getElementById("productoCategoria").value = producto.categoria_id;
  document.getElementById("productoUnidad").value = producto.unidad;

  document.getElementById("productoModalTitle").textContent = "Editar Producto/Servicio";

  const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("productoModal"));
  modal.show();
}

function eliminarProducto(id) {
  if (confirm("¿Está seguro de que desea eliminar este producto/servicio?")) {
    const form = document.createElement("form")
    form.method = "POST"
    form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `
    document.body.appendChild(form)
    form.submit()
  }
}

// Limpiar formulario al cerrar modal
document.getElementById("productoModal").addEventListener("hidden.bs.modal", () => {
  document.getElementById("productoAction").value = "create"
  document.getElementById("productoId").value = ""
  document.querySelector("#productoModal form").reset()
  document.querySelector("#productoModal .modal-title").textContent = "Nuevo Producto/Servicio"
})

// Filtros de tabla
document.getElementById("filtroTipo").addEventListener("change", filtrarTabla)
document.getElementById("filtroCategoria").addEventListener("change", filtrarTabla)
document.getElementById("buscarProducto").addEventListener("input", filtrarTabla)

function filtrarTabla() {
  const filtroTipo = document.getElementById("filtroTipo").value.toLowerCase()
  const filtroCategoria = document.getElementById("filtroCategoria").value.toLowerCase()
  const busqueda = document.getElementById("buscarProducto").value.toLowerCase()

  const filas = document.querySelectorAll("#tablaProductos tbody tr")

  filas.forEach((fila) => {
    const tipo = fila.dataset.tipo.toLowerCase()
    const categoria = fila.dataset.categoria.toLowerCase()
    const texto = fila.textContent.toLowerCase()

    const coincideTipo = !filtroTipo || tipo === filtroTipo
    const coincideCategoria = !filtroCategoria || categoria === filtroCategoria
    const coincideBusqueda = !busqueda || texto.includes(busqueda)

    if (coincideTipo && coincideCategoria && coincideBusqueda) {
      fila.style.display = ""
    } else {
      fila.style.display = "none"
    }
  })
}

// Formatear precio mientras se escribe
document.getElementById("productoPrecioBase").addEventListener("input", function () {
  const valor = this.value.replace(/[^\d.]/g, "")
  this.value = valor
})

// --- GESTIÓN DE CATEGORÍAS ---
const categoriasModal = new bootstrap.Modal(document.getElementById('categoriasModal'));

document.getElementById('categoriasModal').addEventListener('show.bs.modal', cargarCategorias);

async function cargarCategorias() {
    try {
        const response = await fetch('ajax/gestionar_categorias.php');
        const data = await response.json();
        if (data.success) {
            const tbody = document.getElementById('listaCategorias');
            tbody.innerHTML = '';
            data.categorias.forEach(cat => {
                tbody.innerHTML += `
                    <tr>
                        <td><input type="text" class="form-control form-control-sm" value="${cat.nombre}" data-id="${cat.id}" data-field="nombre"></td>
                        <td>
                            <select class="form-select form-select-sm" data-id="${cat.id}" data-field="tipo">
                                <option value="producto" ${cat.tipo === 'producto' ? 'selected' : ''}>Producto</option>
                                <option value="servicio" ${cat.tipo === 'servicio' ? 'selected' : ''}>Servicio</option>
                            </select>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-success" onclick="actualizarCategoria(${cat.id})">Guardar</button>
                            <button class="btn btn-sm btn-danger" onclick="eliminarCategoria(${cat.id})">Eliminar</button>
                        </td>
                    </tr>
                `;
            });
        }
    } catch (error) {
        console.error('Error al cargar categorías:', error);
    }
}

document.getElementById('formNuevaCategoria').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    data.action = 'create';

    try {
        const response = await fetch('ajax/gestionar_categorias.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            alert(result.message); // Display success message
            this.reset();
            cargarCategorias();
            // No need to reload the entire page if only categories are updated
            // location.reload(); // Removed for now, can be added back if necessary
        } else {
            alert('Error al crear categoría: ' + result.message); // Display error message
        }
    } catch (error) {
        console.error('Error en la comunicación con el servidor:', error);
        alert('Error en la comunicación con el servidor. Por favor, inténtelo de nuevo.');
    }
});

async function actualizarCategoria(id) {
    const row = document.querySelector(`[data-id="${id}"]`).closest('tr');
    const nombre = row.querySelector('[data-field="nombre"]').value;
    const tipo = row.querySelector('[data-field="tipo"]').value;
    
    const data = { id, nombre, tipo, action: 'update' };

    await fetch('ajax/gestionar_categorias.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });
    cargarCategorias();
    location.reload();
}

async function eliminarCategoria(id) {
    if (confirm('¿Está seguro? Si la categoría está en uso, no se podrá eliminar.')) {
        const data = { id, action: 'delete' };
        await fetch('ajax/gestionar_categorias.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        cargarCategorias();
        location.reload();
    }
}

// Cambiar opciones de unidad según el tipo
document.getElementById("productoTipo").addEventListener("change", function () {
  const unidadSelect = document.getElementById("productoUnidad")
  const tipo = this.value

  // Limpiar opciones actuales
  unidadSelect.innerHTML = ""

  if (tipo === "producto") {
    unidadSelect.innerHTML = `
            <option value="unidad">Unidad</option>
            <option value="metro">Metro</option>
            <option value="metro_lineal">Metro Lineal</option>
            <option value="kg">Kilogramo</option>
            <option value="rollo">Rollo</option>
            <option value="caja">Caja</option>
        `
  } else {
    unidadSelect.innerHTML = `
            <option value="servicio">Servicio</option>
            <option value="hora">Hora</option>
            <option value="metro_lineal">Metro Lineal</option>
            <option value="visita">Visita</option>
            <option value="instalacion">Instalación</option>
        `
  }
})
