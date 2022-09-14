<!--HEADER TEXT-->
<h1 <?= $emailStylesV2->getStyles(['m-mainContent__h1']) ?>>
    <?php echo $vars['headerText']; ?>
</h1>

<!--BODY TEXT-->
<p <?= $emailStylesV2->getStyles(['m-mainContent__paragraph']) ?> >
    <?php echo $vars['bodyText']; ?></p>

<!--ACTION BUTTON-->
<?php echo $vars['actionButton']; ?>


<!-- ADD'L LINK (OPTIONAL) -->
<?php if(isset($vars['additionalCtaPath']) && isset($vars['additionalCtaText'])){ ?>
    <a <?= $emailStylesV2->getStyles(['m-mainContent__standaloneLink']) ?> href="<?php echo $vars['additionalCtaPath']; ?>">
        <?php echo $vars['additionalCtaText']; ?>
    </a>
<?php } ?>

