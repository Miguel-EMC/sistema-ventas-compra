// Asegurarse de que jQuery esté cargado tik tok
if (typeof jQuery == 'undefined') {
    var script = document.createElement('script');
    script.src = "https://code.jquery.com/jquery-3.6.0.min.js";
    document.head.appendChild(script);
    script.onload = startClicking;
} else {
    startClicking();
}

function startClicking() {
    // Obtener todos los elementos con el atributo data-e2e="comment-like-icon"
    let elements = $('[data-e2e="comment-like-icon"]');
    let index = 0;

    // Hacer clic sucesivamente en cada elemento con un intervalo de 2 segundos
    setInterval(function() {
        if (index < elements.length) {
            elements.eq(index).click();
            index++;
        } else {
            index = 0; // Reinicia el ciclo al llegar al final de la lista
        }
    }, 1000); // 2000 ms = 2 segundos
}
























fb parejas

////////////////////////////////////////////////////
///////////////////////////////////////////////////

// Verifica si jQuery está cargado
if (typeof jQuery === 'undefined') {
    // Inyecta jQuery
    const script = document.createElement('script');
    script.src = "https://code.jquery.com/jquery-3.6.0.min.js";
    script.onload = startClicking; // Ejecuta el script de clics después de cargar jQuery
    document.head.appendChild(script);
} else {
    // Si jQuery ya está cargado, ejecuta directamente el script de clics
    startClicking();
}

function startClicking() {
    let clickCount = 0;
    const maxClicks = 30;
    const interval = 2000; // 2000 ms = 2 segundos

    const intervalId = setInterval(() => {
        const button = $('img[src="https://static.xx.fbcdn.net/rsrc.php/v3/yy/r/e-TG7rC3HMB.png"]').closest('div.m');
        
        if (button.length) {
            button.click();
            clickCount++;
            console.log(`Clic número: ${clickCount}`);
        } else {
            console.warn("El botón no se encuentra.");
        }

        if (clickCount >= maxClicks) {
            clearInterval(intervalId); // Detener el intervalo después de 10 clics
            console.log("Proceso terminado: 10 clics completados.");
        }
    }, interval);
}











////instagram  
//like a los corazones 
// Selecciona todos los elementos con la clase específica
const elementos = document.querySelectorAll('.x1lliihq.x1n2onr6.x1cp0k07');

function clickWithDelay(elementos, delay) {
    // Verifica que haya elementos seleccionados
    if (elementos.length === 0) {
        console.error('No se encontraron elementos para hacer clic.');
        return;
    }

    console.log(`Total de clics a realizar: ${elementos.length}`); // Muestra el total de clics en la consola

    elementos.forEach((elemento, index) => {
        setTimeout(() => {
            // Desplaza la página hacia el elemento antes de hacer clic
            const offset = 100; // Aumenta este valor para desplazar más hacia abajo
            const rect = elemento.getBoundingClientRect();
            const scrollToY = window.scrollY + rect.top - offset; // Calcula la posición para scroll

            window.scrollTo({ top: scrollToY, behavior: 'smooth' });

            // Da un poco de tiempo para que se cargue el contenido
            setTimeout(() => {
                // Simula el clic en el elemento
                const event = new MouseEvent('click', {
                    bubbles: true,
                    cancelable: true,
                    view: window
                });
                elemento.dispatchEvent(event);
                
                console.log(`Clic simulado en el elemento ${index + 1}`); // Muestra el número de clic simulado
            }, 500); // Espera 500 ms después de hacer scroll para asegurarse que el contenido cargue
        }, index * delay); // El retraso aumenta con el índice
    });
}

// Llama a la función con un retraso de 2000 ms
clickWithDelay(elementos, 2000);




/////////////////////////////////
/////seguir en instagram //////  


// Selecciona todos los botones "Seguir"
const botones = document.querySelectorAll('.x9f619.xjbqb8w.x78zum5.x168nmei.x13lgxp2.x5pf9jr.xo71vjh.x150jy0e.x1e558r4.x1n2onr6.x1plvlek.xryxfnj.x1c4vz4f.x2lah0s.x1q0g3np.xqjyukv.x6s0dn4.x1oa3qoh.xl56j7k');

// Función para hacer clic en los botones con un retraso
function clickConRetraso(botones, delay) {
    // Verifica que haya botones seleccionados
    if (botones.length === 0) {
        console.error('No se encontraron botones para hacer clic.');
        return;
    }

    console.log(`Total de clics a realizar: ${botones.length}`); // Muestra el total de clics en la consola

    botones.forEach((boton, index) => {
        setTimeout(() => {
            // Desplaza la página hacia el botón antes de hacer clic
            boton.scrollIntoView({ behavior: "smooth", block: "center" });

            // Simula el clic en el botón
            const event = new MouseEvent('click', {
                bubbles: true,
                cancelable: true,
                view: window
            });
            boton.dispatchEvent(event);

            console.log(`Clic simulado en el botón ${index + 1}`); // Muestra el número de clic simulado
        }, index * delay); // El retraso aumenta con el índice
    });
}

// Llama a la función con un retraso de 2000 ms
clickConRetraso(botones, 2000);




