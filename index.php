
<?php
require_once __DIR__ . '/includes/db.php';

$pageTitle = 'Inicio';

// Contar las mascotas que están disponibles para adopción
$available = (int) $pdo
    ->query("SELECT COUNT(*) FROM mascotas WHERE estado = 'Disponible'")
    ->fetchColumn();

// Encabezado del sitio
require __DIR__ . '/includes/header.php';
?>

<!-- ==================================================
     PORTADA PRINCIPAL
================================================== -->

<section class="hero">

    <!-- Texto principal -->
    <div class="txt">

        <span class="k">UNA SEGUNDA OPORTUNIDAD</span>

        <h1>
            Un hogar cambia su vida.
            <span>Y ellos cambian la tuya.</span>
        </h1>

        <p class="d">
            Conoce las mascotas que buscan una familia y descubre
            cómo puedes ayudar a construir un futuro más feliz para ellas.
        </p>

        <div class="acciones">
            <a href="mascotas.php" class="btn">
                Conocer mascotas
            </a>

            <a href="nosotros.php" class="out">
                Conoce el proyecto
            </a>
        </div>

        <!-- Contador de mascotas -->
        <div class="hero-stat">
            <strong><?= $available ?></strong>
            <span>mascotas disponibles para adopción</span>
        </div>

    </div>

    <!-- Imagen de la portada -->
    <div class="foto">

        <img
            src="assets/img/perrito.jpg"
            alt="Perros y gatos que buscan un hogar"
        >

        <p>
            Adopta con amor, cuida con responsabilidad.
        </p>

    </div>

</section>


<!-- ==================================================
     SECCIÓN DE BENEFICIOS
================================================== -->

<div class="wrap">

    <section class="beneficios">

        <div class="top">

            <div>
                <span class="k">¿CÓMO AYUDAR?</span>
                <h2>Cada pequeña acción cuenta</h2>
            </div>

            <a href="mascotas.php" class="btn">
                Ver mascotas →
            </a>

        </div>

        <div class="feat">

            <!-- Beneficio 1 -->
            <div>
                <i>🏡</i>

                <h4>Adopta</h4>

                <p>
                    Brinda un hogar seguro y una familia a una mascota
                    que necesita una segunda oportunidad.
                </p>
            </div>

            <!-- Beneficio 2 -->
            <div>
                <i>💙</i>

                <h4>Cuida</h4>

                <p>
                    La adopción responsable incluye alimentación,
                    atención veterinaria, protección y cariño.
                </p>
            </div>

            <!-- Beneficio 3 -->
            <div>
                <i>📣</i>

                <h4>Comparte</h4>

                <p>
                    Difunde la información para ayudar a que más
                    mascotas encuentren una familia.
                </p>
            </div>

        </div>

    </section>

</div>


<?php
// Pie de página
require __DIR__ . '/includes/footer.php';
?>
