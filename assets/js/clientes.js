// Funciones para gestión de clientes
function editarCliente(cliente) {
  document.getElementById("clienteAction").value = "update"
  document.getElementById("clienteId").value = cliente.id
  document.getElementById("clienteNombre").value = cliente.nombre
  document.getElementById("clienteEmail").value = cliente.email
  document.getElementById("clienteTelefono").value = cliente.telefono
  document.getElementById("clienteRut").value = cliente.rut || ""
  document.getElementById("clienteDireccion").value = cliente.direccion || ""
  document.getElementById("clienteTipo").value = cliente.tipo_cliente

  document.querySelector("#clienteModal .modal-title").textContent = "Editar Cliente"

  const modal = new window.bootstrap.Modal(document.getElementById("clienteModal"))
  modal.show()
}

function eliminarCliente(id) {
  if (confirm("¿Está seguro de que desea eliminar este cliente?")) {
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
document.getElementById("clienteModal").addEventListener("hidden.bs.modal", () => {
  document.getElementById("clienteAction").value = "create"
  document.getElementById("clienteId").value = ""
  document.querySelector("#clienteModal form").reset()
  document.querySelector("#clienteModal .modal-title").textContent = "Nuevo Cliente"
})

// Validación de RUT chileno
function validarRut(rut) {
  if (!/^[0-9]+[-|‐]{1}[0-9kK]{1}$/.test(rut)) {
    return false
  }

  const tmp = rut.split("-")
  let digv = tmp[1]
  const rut_num = tmp[0]

  if (digv == "K") digv = "k"

  return dv(rut_num) == digv
}

function dv(T) {
  let M = 0,
    S = 1
  for (; T; T = Math.floor(T / 10)) {
    S = (S + (T % 10) * (9 - (M++ % 6))) % 11
  }
  return S ? S - 1 : "k"
}

// Aplicar validación de RUT
document.getElementById("clienteRut").addEventListener("blur", function () {
  const rut = this.value.trim()
  if (rut && !validarRut(rut)) {
    this.classList.add("is-invalid")
    if (!this.nextElementSibling || !this.nextElementSibling.classList.contains("invalid-feedback")) {
      const feedback = document.createElement("div")
      feedback.className = "invalid-feedback"
      feedback.textContent = "RUT inválido. Formato: 12345678-9"
      this.parentNode.appendChild(feedback)
    }
  } else {
    this.classList.remove("is-invalid")
    const feedback = this.parentNode.querySelector(".invalid-feedback")
    if (feedback) {
      feedback.remove()
    }
  }
})

// Formatear RUT mientras se escribe
document.getElementById("clienteRut").addEventListener("input", function () {
  let rut = this.value.replace(/[^0-9kK]/g, "")
  if (rut.length > 1) {
    rut = rut.slice(0, -1) + "-" + rut.slice(-1)
  }
  this.value = rut
})
