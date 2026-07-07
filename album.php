<?php
// ==========================================================================
// 1. PROCESAR LA SUBIDA DE LA FOTO (Backend en PHP)
// ==========================================================================
$carpeta_destino = 'fotosalbum/';

// Si la carpeta no existe, PHP la crea automáticamente
if (!file_exists($carpeta_destino)) {
    mkdir($carpeta_destino, 0777, true);
}

$mensaje_subida = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['foto'])) {
    $titulo = preg_replace('/[^a-zA-Z0-9_ -]/', '', $_POST['titulo']);
    $fecha = $_POST['fecha']; // Formato AAAA-MM-DD
    $descripcion = htmlspecialchars($_POST['descripcion'], ENT_QUOTES, 'UTF-8');
    
    // Conseguir la extensión del archivo original (jpg, png, etc.)
    $extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
    
    // Formamos un nombre único mezclando: FECHA__TITULO__DESCRIPCION.extension
    // Usamos doble guion bajo (__) como separador para que JavaScript lo lea fácil después
    $nombre_limpio_archivo = $fecha . "__" . $titulo . "__" . $descripcion . "." . $extension;
    $ruta_final = $carpeta_destino . $nombre_limpio_archivo;

    // Mover físicamente el archivo subido a tu carpeta
    if (move_uploaded_file($_FILES['foto']['tmp_name'], $ruta_final)) {
        // Recargar la página para que no se duplique el envío al presionar F5
        header("Location: " . $_SERVER['PHP_SELF'] . "?subido=exito");
        exit();
    } else {
        $mensaje_subida = "Hubo un error al mover la imagen a la carpeta.";
    }
}

// ==========================================================================
// 2. ESCANEAR LA CARPETA PARA ARMAR EL ARRAY AUTOMÁTICO
// ==========================================================================
$recuerdos_encontrados = [];
$archivos = array_diff(scandir($carpeta_destino), array('.', '..'));

foreach ($archivos as $archivo) {
    // Rompemos el nombre del archivo usando el separador "__"
    $partes = explode("__", $archivo);
    
    if (count($partes) >= 3) {
        $fecha = $partes[0];
        $titulo = $partes[1];
        // Quitamos la extensión del archivo al final de la descripción
        $descripcion_con_ext = $partes[2];
        $descripcion = pathinfo($descripcion_con_ext, PATHINFO_FILENAME);

        $recuerdos_encontrados[] = [
            "titulo" => str_replace('_', ' ', $titulo),
            "fecha" => $fecha,
            "descripcion" => $descripcion,
            "foto" => $carpeta_destino . $archivo
        ];
    }
}

// Convertimos el array de PHP a formato JSON para pasárselo a JavaScript de forma nativa
$json_recuerdos = json_encode($recuerdos_encontrados);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuestro Álbum de Recuerdos Mágicos</title>
    <!-- Iconos de FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght=700&family=Quicksand:wght=400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --rosa-pastel: #ffe5ec;
            --rosa-medio: #ffb3c6;
            --rosa-brillante: #ff85a1;
            --rosa-oscuro: #f72585;
            --blanco-transparente: rgba(255, 255, 255, 0.9);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Quicksand', sans-serif;
            background: linear-gradient(135deg, var(--rosa-pastel), #fff0f5);
            min-height: 100vh;
            color: #4a4a4a;
            overflow-x: hidden;
            position: relative;
            padding-bottom: 50px;
        }

        /* --- EFECTO DE ICONOS CAYENDO --- */
        .lluvia-iconos {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 999;
        }

        .corazon-cayendo {
            position: absolute;
            top: -20px;
            color: var(--rosa-brillante);
            animation: caida linear infinite;
            opacity: 0.7;
        }

        @keyframes caida {
            0% { transform: translateY(-20px) rotate(0deg); opacity: 0; }
            10% { opacity: 0.7; }
            90% { opacity: 0.7; }
            100% { transform: translateY(105vh) rotate(360deg); opacity: 0; }
        }

        .container {
            max-width: 950px;
            margin: 0 auto;
            padding: 20px;
            position: relative;
            z-index: 10;
        }

        header {
            text-align: center;
            margin-top: 30px;
            margin-bottom: 40px;
        }

        header h1 {
            font-family: 'Dancing Script', cursive;
            font-size: 3.5rem;
            color: var(--rosa-oscuro);
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }

        header p {
            font-size: 1.1rem;
            color: #7a7a7a;
            font-style: italic;
        }

        /* --- FORMULARIO DE SUBIDA --- */
        .seccion-subir {
            background: var(--blanco-transparente);
            border: 2px dashed var(--rosa-medio);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 24px rgba(255, 133, 161, 0.15);
            backdrop-filter: blur(5px);
            margin-bottom: 50px;
        }

        .seccion-subir h2 {
            color: var(--rosa-oscuro);
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .form-grupo {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        input[type="text"], input[type="date"], textarea {
            padding: 12px;
            border: 2px solid var(--rosa-pastel);
            border-radius: 12px;
            font-family: inherit;
            font-size: 1rem;
            outline: none;
        }

        input[type="text"]:focus, input[type="date"]:focus, textarea:focus {
            border-color: var(--rosa-brillante);
        }

        .file-upload-btn {
            background: #fff;
            border: 2px dashed var(--rosa-brillante);
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            color: var(--rosa-oscuro);
            font-weight: 600;
        }

        #input-foto { display: none; }

        .btn-guardar {
            background: linear-gradient(45deg, var(--rosa-brillante), var(--rosa-oscuro));
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            box-shadow: 0 4px 15px rgba(247, 37, 133, 0.3);
            margin-top: 10px;
        }

        /* --- ESTILO CALENDARIO --- */
        .bloque-mes {
            margin-bottom: 40px;
            position: relative;
        }

        .titulo-mes {
            font-family: 'Dancing Script', cursive;
            font-size: 2.5rem;
            color: var(--rosa-oscuro);
            background: rgba(255, 255, 255, 0.8);
            display: inline-block;
            padding: 5px 20px;
            border-radius: 30px;
            margin-bottom: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            border-left: 5px solid var(--rosa-brillante);
        }

        .grid-album {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }

        /* Tarjeta Polaroid */
        .tarjeta-recuerdo {
            background: white;
            padding: 15px 15px 25px 15px;
            border-radius: 4px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.06);
            position: relative;
            transform: rotate(var(--rotacion));
            transition: all 0.3s;
        }

        .tarjeta-recuerdo:hover {
            transform: scale(1.04) rotate(0deg) !important;
            z-index: 5;
            box-shadow: 0 15px 30px rgba(247, 37, 133, 0.15);
        }

        .tarjeta-recuerdo::before {
            content: '📌';
            position: absolute;
            top: -12px;
            left: 46%;
            font-size: 1.2rem;
        }

        .contenedor-foto-tarjeta {
            width: 100%;
            height: 230px;
            overflow: hidden;
            border: 1px solid #f5f5f5;
            margin-bottom: 15px;
        }

        .contenedor-foto-tarjeta img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .dia-etiqueta {
            display: inline-block;
            background: var(--rosa-pastel);
            color: var(--rosa-oscuro);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .titulo-tarjeta {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 6px;
        }

        .descripcion-tarjeta {
            font-size: 0.95rem;
            color: #666;
            line-height: 1.4;
        }
    </style>
</head>
<body>

    <div class="lluvia-iconos" id="lluvia"></div>

    <div class="container">
        <header>
            <h1>Nuestro Álbum de Recuerdos</h1>
            <p>Momentos preciosos organizados en el tiempo ✨</p>
        </header>

        <!-- Formulario que envía directo a PHP sin recargas raras -->
        <section class="seccion-subir">
            <h2><i class="fa-solid fa-heart-circle-plus"></i> Añadir un nuevo momento al calendario</h2>
            
            <?php if(!empty($mensaje_subida)): ?>
                <p style="color: red; margin-bottom: 15px;"><?php echo $mensaje_subida; ?></p>
            <?php endif; ?>

            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
                <div class="form-grupo">
                    <label for="titulo">¿Qué regalito o momento es?</label>
                    <input type="text" name="titulo" id="titulo" placeholder="Ej. El picnic sorpresa" required>
                </div>

                <div class="form-grupo">
                    <label for="fecha">Fecha del día especial:</label>
                    <input type="date" name="fecha" id="fecha" required>
                </div>

                <div class="form-grupo">
                    <label for="descripcion">Cuéntame la historia de este día:</label>
                    <textarea name="descripcion" id="descripcion" rows="3" placeholder="Detalles hermosos que no quieres olvidar..." required></textarea>
                </div>

                <div class="form-grupo">
                    <label>Selecciona la foto:</label>
                    <label class="file-upload-btn" for="input-foto">
                        <i class="fa-solid fa-camera"></i> <span id="texto-archivo">Elegir Imagen</span>
                    </label>
                    <input type="file" name="foto" id="input-foto" accept="image/*" required>
                </div>

                <button type="submit" class="btn-guardar">Guardar y Compartir en el Álbum 💞</button>
            </form>
        </section>

        <!-- El calendario dinámico -->
        <section id="calendario-recuerdos"></section>
    </div>

    <script>
        // Cambiar dinámicamente el texto del botón al elegir imagen
        const inputFoto = document.getElementById('input-foto');
        const textoArchivo = document.getElementById('texto-archivo');
        inputFoto.addEventListener('change', function() {
            if(this.files && this.files[0]) {
                textoArchivo.innerText = "Foto lista: " + this.files[0].name.substring(0, 15) + "...";
            }
        });

        // ==========================================================================
        // INCORPORAMOS EL ARRAY DE PHP DIRECTAMENTE EN JAVASCRIPT
        // ==========================================================================
        const misRecuerdos = <?php echo $json_recuerdos; ?>;

        const calendarioRecuerdos = document.getElementById('calendario-recuerdos');

        if (misRecuerdos.length === 0) {
            calendarioRecuerdos.innerHTML = `<div style="text-align:center; color:#aaa; padding:40px;">💖 El álbum está esperando su primera foto en el servidor. ¡Sube una arriba! 💖</div>`;
        } else {
            // Ordenar los recuerdos por fecha (más recientes primero)
            misRecuerdos.sort((a, b) => new Date(b.fecha) - new Date(a.fecha));

            const mesesNombre = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
            let datosAgrupados = {};

            // Agrupar dinámicamente el Array por Año y Mes
            misRecuerdos.forEach((recuerdo) => {
                const partes = recuerdo.fecha.split('-');
                if(partes.length === 3) {
                    const anio = partes[0];
                    const mesIndex = parseInt(partes[1], 10) - 1;
                    const dia = partes[2];
                    const nombreMesAnio = `${mesesNombre[mesIndex]} ${anio}`;

                    if(!datosAgrupados[nombreMesAnio]) datosAgrupados[nombreMesAnio] = [];
                    
                    datosAgrupados[nombreMesAnio].push({
                        dia: dia,
                        ...recuerdo
                    });
                }
            });

            // Pintar los bloques estilo Calendario
            for (const [mesAnio, recuerdos] of Object.entries(datosAgrupados)) {
                const bloque = document.createElement('div');
                bloque.classList.add('bloque-mes');
                bloque.innerHTML = `<h3 class="titulo-mes"><i class="fa-regular fa-calendar"></i> ${mesAnio}</h3>`;
                
                const grid = document.createElement('div');
                grid.classList.add('grid-album');

                recuerdos.forEach(recuerdo => {
                    const rotacion = (Math.random() * 6 - 3).toFixed(2) + 'deg';
                    const tarjeta = document.createElement('div');
                    tarjeta.classList.add('tarjeta-recuerdo');
                    tarjeta.style.setProperty('--rotacion', rotacion);

                    tarjeta.innerHTML = `
                        <div class="contenedor-foto-tarjeta">
                            <img src="${recuerdo.foto}" alt="Recuerdo">
                        </div>
                        <span class="dia-etiqueta">Día ${recuerdo.dia}</span>
                        <h4 class="titulo-tarjeta">${recuerdo.titulo}</h4>
                        <p class="descripcion-tarjeta">${recuerdo.descripcion}</p>
                    `;
                    grid.appendChild(tarjeta);
                });

                bloque.appendChild(grid);
                calendarioRecuerdos.appendChild(bloque);
            }
        }

        // --- ANIMACIÓN DE ICONOS CAYENDO ---
        const iconos = ['❤️', '💖', '✨', '🌸', '🎁', '💝'];
        const contenedorLluvia = document.getElementById('lluvia');

        function crearIcono() {
            const icono = document.createElement('div');
            icono.classList.add('corazon-cayendo');
            icono.innerText = iconos[Math.floor(Math.random() * iconos.length)];
            icono.style.left = Math.random() * 100 + 'vw';
            icono.style.animationDuration = Math.random() * 3 + 3 + 's';
            icono.style.fontSize = Math.random() * 15 + 12 + 'px';
            
            contenedorLluvia.appendChild(icono);
            setTimeout(() => { icono.remove(); }, 6000);
        }
        setInterval(crearIcono, 400);
    </script>
</body>
</html>
