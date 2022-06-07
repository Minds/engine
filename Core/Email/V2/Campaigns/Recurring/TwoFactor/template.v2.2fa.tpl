<!--headline-->
<h1 <?= $emailStylesV2->getStyles(['m-mainContent__h1']) ?>>
    Verify your action with the Two-Factor code below
</h1>

<!--subhead-->
<p <?= $emailStylesV2->getStyles(['m-mainContent__paragraph']) ?>>Enter this verification code on Minds to complete your action.</p>

<p <?= $emailStylesV2->getStyles('m-mainContent__code') ?>><?php echo $vars['code']; ?></p>
