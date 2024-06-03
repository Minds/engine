<!--HEADER TEXT-->
<h1 <?= $emailStylesV2->getStyles(['m-mainContent__h1']) ?>>
Your trial is ready
</h1>

<!--BODY TEXT-->

<p <?= $emailStylesV2->getStyles(['m-mainContent__paragraph']) ?> >
Your Minds Network trials is ready to go! Click the button below to automatically log into your network and take it for a spin.
</p>

<p <?= $emailStylesV2->getStyles(['m-mainContent__paragraph']) ?> >
Your username is: <?= $vars['username']; ?> <br />
Your password is: <?= $vars['password']; ?>
</p>

<!--ACTION BUTTON-->
<p <?= $emailStylesV2->getStyles(['m-mainContent__paragraph']) ?> >
    <?php echo $vars['actionButton']; ?>
</p>

<br />

<p <?= $emailStylesV2->getStyles(['m-mainContent__paragraph']) ?> >
Got a question about Minds Networks? Pick an open slot to schedule a video chat with us.
</p>

<!--ACTION BUTTON-->
<?php echo $vars['helpButton']; ?>