// ✅ 1. Galería de imágenes estilo Amazon
function cambiarImagen(thumbnail) {
    const principal = document.getElementById("imagenPrincipal");
    if (principal && thumbnail.src) {
        principal.src = thumbnail.src;
    }
}

// ✅ 2. Búsqueda y filtro en tiempo real
function filtrarProductos() {
    const query = document.getElementById("busqueda").value.toLowerCase();
    document.querySelectorAll(".card").forEach(card => {
        const nombre = card.querySelector("h3").textContent.toLowerCase();
        card.style.display = nombre.includes(query) ? "" : "none";
    });
}

// ✅ 3. Ordenar productos sin recarga
function ordenarProductos(direccion) {
    const contenedor = document.getElementById("productosContenedor");
    const cards = Array.from(contenedor.querySelectorAll(".card"));
    cards.sort((a, b) => {
        const nombreA = a.querySelector("h3").textContent.toLowerCase();
        const nombreB = b.querySelector("h3").textContent.toLowerCase();
        return direccion === 'asc' ? nombreA.localeCompare(nombreB) : nombreB.localeCompare(nombreA);
    });
    contenedor.innerHTML = '';
    cards.forEach(card => contenedor.appendChild(card));
}

// ✅ 4. Guardar campos en LocalStorage (para formularios)
function activarAutoGuardado() {
    const campos = document.querySelectorAll("input, textarea, select");
    campos.forEach(campo => {
        const valorGuardado = localStorage.getItem(campo.name);
        if (valorGuardado) campo.value = valorGuardado;

        campo.addEventListener("input", () => {
            localStorage.setItem(campo.name, campo.value);
        });
    });
}

// ✅ 5. Confirmación antes de salir del formulario sin guardar
function activarConfirmacionSalida() {
    let formularioModificado = false;

    document.querySelectorAll("input, textarea, select").forEach(campo => {
        campo.addEventListener("input", () => {
            formularioModificado = true;
        });
    });

    window.addEventListener("beforeunload", (e) => {
        if (formularioModificado) {
            e.preventDefault();
            e.returnValue = ""; // Para navegadores modernos
        }
    });
}

// ✅ Inicialización automática si se desea
document.addEventListener("DOMContentLoaded", () => {
    if (document.getElementById("formularioProducto")) {
        activarAutoGuardado();
        activarConfirmacionSalida();
    }
});
