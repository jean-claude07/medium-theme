<h2 style="margin-top: 0;">Bienvenue, <?php echo esc_html($args['name']); ?> !</h2>
<p>Nous sommes ravis de vous compter parmi nous. Pour commencer à écrire et interagir avec la communauté, merci d'activer votre compte en cliquant sur le bouton ci-dessous :</p>

<div style="text-align: center;">
    <a href="<?php echo esc_url($args['activation_link']); ?>" class="button">Activer mon compte</a>
</div>

<p style="margin-top: 30px; font-size: 14px; color: #666;">Si le bouton ne fonctionne pas, vous pouvez copier et coller ce lien dans votre navigateur :<br>
<a href="<?php echo esc_url($args['activation_link']); ?>"><?php echo esc_html($args['activation_link']); ?></a></p>

<p>À bientôt sur MediumClone !</p>
