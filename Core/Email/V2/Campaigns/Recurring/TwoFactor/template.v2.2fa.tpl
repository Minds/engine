<!--headline-->
<h1 <?= $emailStylesV2->getStyles(['m-mainContent__h1']) ?>>
    Verify your action
</h1>

<!--subhead-->
<p <?= $emailStylesV2->getStyles(['m-mainContent__paragraph']) ?>>Enter this verification code on <?php echo $vars['site_name']; ?> to complete your action.</p>

<p <?= $emailStylesV2->getStyles(['m-mainContent__code']) ?>><?php echo $vars['code']; ?></p>
