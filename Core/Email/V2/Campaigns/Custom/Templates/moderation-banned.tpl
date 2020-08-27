<tr>
    <td>
        <p>
            Unfortunately, your channel has been banned for violating our <a href="https://minds.com/content-policy"<?php echo $emailStyles->getStyles('m-link'); ?>>Content Policy</a>.
            <?php if($vars['reason']): ?>
                The specfic reason is: <?= $vars['reason'] ?>.
            <?php endif; ?>
        </p>
    </td>
</tr>
<tr>
    <td>
        <p>
            If you wish to appeal further, you may contact us with any supporting information at
            <a href="mailto:info@minds.com" <?php echo $emailStyles->getStyles('m-link'); ?>>info@minds.com</a>
            for our review.
        </p>
    </td>
</tr>
