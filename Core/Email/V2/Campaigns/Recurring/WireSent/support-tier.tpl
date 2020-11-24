<?php
    $wireDate = date('l F jS Y', ($vars['timestamp']));
    $amount = $vars['amount'];
    $receiverUsername = $vars['receiver']->get('username');
    $receiverName = $vars['receiver']->get('name');
    $tierName = $vars['supportTier']->getName();
    $tierDescription = $vars['supportTier']->getDescription();
?>
<tr>
    <td>
        <p>Thank you for becoming a member of
            <a
                href="<?php echo $vars['site_url']?><?php echo $receiverUsername ?>"
                <?php echo $emailStyles->getStyles('m-link'); ?>>
                 <?php echo $receiverName; ?>'s</a> <b><?php echo $tierName ?></b> membership.
        </p>
    </td>
</tr>

<?php if ($tierDescription): ?>
<tr style="margin-bottom: 20px; display: block">
  <td style="border-left: 5px solid #dce2e4; padding-left: 20px;">
    <p style="white-space: pre-line;"><?php echo $tierDescription; ?></p>
  </td>
</tr>
<?php endif; ?>

<tr>
    <td>
        <p>
            <div>
                <div style="display: inline-block;"><?= $vars['translator']->trans('Transfer date') ?>: </div>
                <div style="display: inline-block;"><?php echo $wireDate; ?></div>
            </div>
            <div>
                <div style="display: inline-block;"><?= $vars['translator']->trans('Amount') ?>: </div>
                <div style="display: inline-block;"><?php echo $amount; ?></div>
            </div>
        </p>
    </td>
</tr>

<tr>
    <td>
        <p>
            If you have any questions please visit <a href="<?php echo $vars['site_url']?>help" <?php echo $emailStyles->getStyles('m-link'); ?>>here</a>. You can cancel your subscription any time in your <a href="<?php echo $vars['site_url']?>settings/billing/recurring-payments" <?php echo $emailStyles->getStyles('m-link'); ?>>billing settings</a>.
        </p>
    </td>
</tr>
