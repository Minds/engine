<!--headline-->
<h1 <?= $emailStylesV2->getStyles(['m-mainContent__h1']) ?>>
    Verify your email to get started
</h1>

<!--subhead-->
<p <?= $emailStylesV2->getStyles(['m-mainContent__paragraph']) ?>>Enter this verification code on Minds to verify your email.</p>

<p <?= $emailStylesV2->getStyles(['m-mainContent__code']) ?>><?php echo $vars['code']; ?></p>
