<!--HEADER TEXT-->
<h1 <?= $emailStylesV2->getStyles(['m-mainContent__h1']) ?>>
    <?php echo $vars['headerText']; ?>
</h1>

<!--BODY TEXT-->
<p <?= $emailStylesV2->getStyles(['m-mainContent__paragraph']) ?> >
    Supermind is a new way to crowdfund reactions and feedback from people all over the world.
</p>

<p <?= $emailStylesV2->getStyles(['m-mainContent__paragraph']) ?> >
    To show you how easy it is, we'd like to send you a 5 token Supermind offer to answer the question "What is something you've created that you want to share with the world?"
</p>

<p <?= $emailStylesV2->getStyles(['m-mainContent__paragraph']) ?> >
    You can answer with text, image or a video and use the tokens to Boost your content, or send a Supermind to someone on Minds and pay it forward.
</p>

<!--ACTION BUTTON-->
<?php if(isset($vars['actionButton'])){ ?>
    <?php echo $vars['actionButton']; ?>
<?php } ?>

