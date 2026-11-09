<section class="card login-card">
    <div class="brand"><?=$brand?></div>
    <h1>Iniciar sesión</h1>
    <p class="muted">Accede al sistema de gestión comercial.</p>
    <form method="post">
        <?=csrf()?>
        <input type="hidden" name="action" value="login">
        <label>Correo <input name="email" type="email" maxlength="190" required autocomplete="username"></label>
        <label>Contraseña <input name="password" type="password" required autocomplete="current-password"></label>
        <button>Ingresar</button>
    </form>
</section>
