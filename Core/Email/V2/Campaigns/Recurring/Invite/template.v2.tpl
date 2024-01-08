<!--HEADER TEXT-->
<h1 <?= $emailStylesV2->getStyles(['m-mainContent__h1']) ?>>
<?php echo $vars['headerText']; ?>
</h1>

<!--BODY TEXT-->
<?php if(!empty($vars['customMessage'])){ ?>
<p <?= $emailStylesV2->getStyles(['m-mainContent__paragraph']) ?> >
<?php echo $vars['customMessage']; ?>
</p>
<?php } ?>
<p <?= $emailStylesV2->getStyles(['m-mainContent__paragraph']) ?> >
<?php echo $vars['bodyText']; ?>
</p>

<!--ACTION BUTTON-->
<?php if(isset($vars['actionButton'])){ ?>
<?php echo $vars['actionButton']; ?>
<?php } ?>
