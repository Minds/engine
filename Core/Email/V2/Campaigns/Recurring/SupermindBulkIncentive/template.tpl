<!--HEADER TEXT-->
<h1 <?= $emailStylesV2->getStyles(['m-mainContent__h1']) ?>>
    <?php echo $vars['headerText']; ?>
</h1>

<!--BODY TEXT-->
<p <?= $emailStylesV2->getStyles(['m-mainContent__paragraph']) ?> >
    <a style="text-decoration: underline;" href="https://www.minds.com/info/blog/announcing-supermind-is-live-now-in-beta-1424069354684682242?<?php echo $vars['tracking']; ?>&utm_content=blog">We've just launched Supermind</a>, a new way for fans and creators to collaborate on Minds. Make offers to your favorite creators to send you text, video or photo replies. Get paid to reply to posts.
</p>

<p <?= $emailStylesV2->getStyles(['m-mainContent__paragraph']) ?> >
    To celebrate, we'd like to send you a 5 token Supermind offer to show how easy it is. You in?
</p>

<!--ACTION BUTTON-->
<?php if(isset($vars['actionButton'])){ ?>
    <?php echo $vars['actionButton']; ?>
<?php } ?>

