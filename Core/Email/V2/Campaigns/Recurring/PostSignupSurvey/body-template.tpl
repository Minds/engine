<!--headline-->
<h1 <?= $emailStylesV2->getStyles(['m-mainContent__h1']) ?>>
    What do you think?
</h1>

<!--subhead-->
<p <?= $emailStylesV2->getStyles(['m-mainContent__paragraph'])?>>
    Hey @<? echo $vars['username'] ?>! Thanks for joining Minds to help us elevate the discourse through Internet freedom.<br /><br />Whether positive or negative, we'd love hear your feedback about your experience on Minds so far. It will only take a few moments, and the information you provide will help improve our service.
</p>

<!--CTA-->
<?php echo $vars['actionButton']; ?>
