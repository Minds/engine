<!--HEADER TEXT-->
<h1 <?= $emailStylesV2->getStyles(['m-mainContent__h1']) ?>>
    <?php echo $vars['headerText']; ?>
</h1>

<!--BODY TEXT-->
<p <?= $emailStylesV2->getStyles(['m-mainContent__paragraph']) ?> >
    We'd like to send you a 5 token <a style="text-decoration: underline;" href="https://www.minds.com/info/blog/announcing-supermind-is-live-now-in-beta-1424069354684682242?<?php echo $vars['tracking']; ?>&utm_content=blog">Supermind</a> offer to show how easy it is.
    You can use these tokens to boost your content or tip other creators.
    You in?
</p>

<!--ACTION BUTTON-->
<?php if(isset($vars['actionButton'])){ ?>
    <?php echo $vars['actionButton']; ?>
<?php } ?>

