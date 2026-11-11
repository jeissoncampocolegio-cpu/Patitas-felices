<?php
require_once __DIR__.'/includes/db.php';
$pageTitle='Contacto'; $ok=''; $error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $nombre=trim($_POST['nombre']??''); $correo=trim($_POST['correo']??''); $asunto=trim($_POST['asunto']??''); $mensaje=trim($_POST['mensaje']??'');
  if ($nombre==='' || !filter_var($correo,FILTER_VALIDATE_EMAIL) || $mensaje==='') $error='Completa los campos obligatorios con información válida.';
  else { $s=$pdo->prepare("INSERT INTO mensajes_contacto(nombre,correo,asunto,mensaje) VALUES(?,?,?,?)"); $s->execute([$nombre,$correo,$asunto,$mensaje]); $ok='Tu mensaje fue enviado correctamente.'; }
}
require __DIR__.'/includes/header.php';
?>
<section class="page-intro"><span class="eyebrow">HABLEMOS</span><h1>Contáctanos</h1><p>¿Tienes preguntas sobre el proyecto? Escríbenos.</p></section>
<form class="form-card" method="post"><h2>Envíanos un mensaje</h2><?php if($ok): ?><p class="alert success"><?= htmlspecialchars($ok) ?></p><?php endif; ?><?php if($error): ?><p class="alert error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<label>Nombre completo<input name="nombre" required maxlength="100"></label><label>Correo electrónico<input type="email" name="correo" required maxlength="150"></label><label>Asunto<input name="asunto" maxlength="150"></label><label>Mensaje<textarea name="mensaje" rows="5" required></textarea></label><button class="btn btn-primary" type="submit">Enviar mensaje</button></form>
<?php require __DIR__.'/includes/footer.php'; ?>
