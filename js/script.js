function confirmarEliminacion() {
    return confirm("Estas seguro de que deseas eliminar este registro?");
}

function mostrarOpcionesPregunta() {
    var tipo = document.getElementById("tipo");
    var opcionesMultiple = document.getElementById("opciones_multiple");
    var respuestaCorrecta = document.getElementById("respuesta_correcta");

    if (!tipo || !opcionesMultiple || !respuestaCorrecta) {
        return;
    }

    var respuestaSeleccionada = respuestaCorrecta.getAttribute("data-valor") || respuestaCorrecta.value;

    if (tipo.value === "verdadero_falso") {
        opcionesMultiple.style.display = "none";
        respuestaCorrecta.innerHTML = "";
        agregarOpcion(respuestaCorrecta, "", "Selecciona la respuesta");
        agregarOpcion(respuestaCorrecta, "Verdadero", "Verdadero");
        agregarOpcion(respuestaCorrecta, "Falso", "Falso");
    } else {
        opcionesMultiple.style.display = "block";
        respuestaCorrecta.innerHTML = "";
        agregarOpcion(respuestaCorrecta, "", "Selecciona la respuesta");
        agregarOpcion(respuestaCorrecta, "A", "A");
        agregarOpcion(respuestaCorrecta, "B", "B");
        agregarOpcion(respuestaCorrecta, "C", "C");
        agregarOpcion(respuestaCorrecta, "D", "D");
    }

    if (respuestaSeleccionada) {
        respuestaCorrecta.value = respuestaSeleccionada;
    }
}

function agregarOpcion(select, valor, texto) {
    var opcion = document.createElement("option");
    opcion.value = valor;
    opcion.textContent = texto;
    select.appendChild(opcion);
}

document.addEventListener("DOMContentLoaded", function() {
    mostrarOpcionesPregunta();
});
