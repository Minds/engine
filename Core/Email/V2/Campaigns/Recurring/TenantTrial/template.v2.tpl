<!--HEADER TEXT-->
<h1 <?= $emailStylesV2->getStyles(['m-mainContent__h1']) ?>>
Your trial is ready
</h1>

<!--BODY TEXT-->

<p <?= $emailStylesV2->getStyles(['m-mainContent__paragraph']) ?> >
Your Minds Networks trial is ready to go! Click the button below to go to your network and use the username & password below to log in.
</p>

<p <?= $emailStylesV2->getStyles(['m-mainContent__paragraph']) ?> >
Your username is: <b><?= $vars['username']; ?></b> <br />
Your password is: <b><?= $vars['password']; ?></b>
</p>

<!--ACTION BUTTON-->
<p <?= $emailStylesV2->getStyles(['m-mainContent__paragraph']) ?> >
    <?php echo $vars['actionButton']; ?>
</p>
