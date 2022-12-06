<!--HEADER TEXT-->
<h1 <?= $emailStylesV2->getStyles(['m-mainContent__h1']) ?>>
    Your cash account is currently restricted
</h1>

<!--BODY TEXT-->
<p <?= $emailStylesV2->getStyles(['m-mainContent__paragraph']) ?> >
   In order to continue earning cash on Minds, additional verification information is required.
</p>

<!--ACTION BUTTON-->
<?php if(isset($vars['actionButton'])){ ?>
    <?php echo $vars['actionButton']; ?>
<?php } ?>

