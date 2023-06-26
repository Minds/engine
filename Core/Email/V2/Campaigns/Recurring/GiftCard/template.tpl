<h1 <?= $emailStylesV2->getStyles(['m-mainContent__h1']) ?>>
<?php echo $vars['headerText']; ?>
</h1>

<!--BODY SUBJECT TEXT-->
<?php if(isset($vars['bodySubjectText'])){ ?>
<p <?= $emailStylesV2->getStyles(['m-mainContent__paragraphSubject']) ?> >
<?php echo $vars['bodySubjectText']; ?></p>
<?php } ?>

<!--BODY TEXT-->
<p <?= $emailStylesV2->getStyles(['m-mainContent__paragraph']) ?> >
    <?php echo $vars['bodyText']; ?>
</p>

<!--ACTION BUTTON-->
<?php if(isset($vars['actionButton'])){ ?>
<?php echo $vars['actionButton']; ?>
<?php } ?>

<p <?= $emailStylesV2->getStyles(['m-mainContent__signup_paragraph']) ?>>
    Don't have a Minds account?<br>
    <a <?= $emailStylesV2->getStyles(['m-mainContent__signup_paragraph--link']) ?> href="<?php echo $vars['signupPath']; ?>">Sign up</a> to claim
</p>

<!-- ADD'L LINK (OPTIONAL) -->
<?php if(isset($vars['additionalCtaPath']) && isset($vars['additionalCtaText'])){ ?>
<a <?= $emailStylesV2->getStyles(['m-mainContent__standaloneLink']) ?> href="<?php echo $vars['additionalCtaPath']; ?>">
<?php echo $vars['additionalCtaText']; ?>
</a>
<?php } ?>
