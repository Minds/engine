<!--HEADER TEXT-->
<h1 <?= $emailStylesV2->getStyles(['m-mainContent__h1']) ?>>
    <?php echo $vars['headerText']; ?>
</h1>

<!--BODY TEXT-->
<p <?= $emailStylesV2->getStyles(['m-mainContent__paragraph']) ?> >
    As a Minds+ / Pro subscriber, you are eligible to receive cash Supermind offers from various brands on Minds. Baste Records is offering you $5 to answer a question for them. Give them a sub if you want more from them in the future!
</p>

<!--ACTION BUTTON-->
<?php if(isset($vars['actionButton'])){ ?>
    <?php echo $vars['actionButton']; ?>
<?php } ?>

