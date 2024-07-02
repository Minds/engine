<!--HEADER TEXT-->
<h1 <?= $emailStylesV2->getStyles(['m-mainContent__h1']) ?>>
    <?= $vars['headerText']; ?>
</h1>

<!--BODY TEXT-->
<p <?= $emailStylesV2->getStyles(['m-mainContent__paragraph']) ?> >
    <?= $vars['bodyText']; ?>
</p>

<!--MEMBERSHIP CONTAINERS-->
<?php foreach ($vars['site_membership_containers'] as $siteMembershipContainer): ?>
    <div <?= $emailStyles->getStyles('m-tenantWelcome__membershipBox', isset($vars['color_scheme']) && $vars['color_scheme'] === 'DARK' ? 'm-tenantWelcome__membershipBox--dark' : 'm-tenantWelcome__membershipBox--light'); ?>>
        <h2 <?= $emailStyles->getStyles('m-tenantWelcome__membershipSubtitle', 'm-tenantWelcome__subtitle'); ?>><?= $siteMembershipContainer['name']; ?></h2>
        <p <?= $emailStyles->getStyles('m-tenantWelcome__membershipPrice'); ?>><?= $siteMembershipContainer['pricingLabel']; ?></p>
        <p <?= $emailStyles->getStyles('m-tenantWelcome__membershipDescription'); ?>><?= $siteMembershipContainer['description']; ?></p>
        <?= $siteMembershipContainer['actionButton']; ?>
    </div>
<?php endforeach ?>

<!--FEATURED GROUP CONTAINERS-->
<?php if(count($vars['featured_group_containers'])): ?>
    <h2 <?= $emailStyles->getStyles('m-tenantWelcome__groupsSectionSubtitle, 'm-tenantWelcome__subtitle');?>>Discuss with groups</h2>

    <table <?= $emailStyles->getStyles('m-tenantWelcome__groupsTable'); ?>>
        <tr>
            <?php foreach ($vars['featured_group_containers'] as $featuredGroupContainer): ?>
                <td class="m-welcomeEmail__groupTableCell" <?= $emailStyles->getStyles('m-tenantWelcome__groupsTableCell'); ?>>
                    <div <?= $emailStyles->getStyles('m-tenantWelcome__groupBox'); ?>>
                        <a href="<?= $featuredGroupContainer['join_url']; ?>" target="_blank">
                            <img src="<?= $featuredGroupContainer['avatar_url'] ?>" <?= $emailStyles->getStyles('m-tenantWelcome__avatar'); ?>/>
                        </a>
                        <h2 <?= $emailStyles->getStyles('m-tenantWelcome__groupName'); ?>><?= $featuredGroupContainer['name']; ?></h2>
                        <h2 <?= $emailStyles->getStyles('m-tenantWelcome__groupDescription'); ?>><?= $featuredGroupContainer['description']; ?></h2>
                        <a href=<?= $featuredGroupContainer['join_url']; ?> <?= $emailStyles->getStyles('m-tenantWelcome__groupLink'); ?> target="_blank">Join discussion</a>
                    </div>
                </td>
            <?php endforeach ?>
        </tr>
    </table>
<?php endif; ?>

<h1 <?= $emailStyles->getStyles('m-tenantWelcome__catchUpSubtitle', 'm-tenantWelcome__subtitle') ?>>
    Catch up with the latest
</h1>
<p <?= $emailStylesV2->getStyles(['m-mainContent__paragraph']) ?> >
    The newsfeed is where you'll find the latest posts. Check in regularly to see what's new and engage with the community. 
</p>
<!--ACTION BUTTON-->
<p <?= $emailStylesV2->getStyles(['m-mainContent__paragraph']) ?> >
    <?php echo $vars['actionButton']; ?>
</p>

<style type="text/css">
    @media (max-width: 600px) {
        .m-welcomeEmail__groupTableCell {
            display: block;
            width: 100% !important;
        }
    }
</style>