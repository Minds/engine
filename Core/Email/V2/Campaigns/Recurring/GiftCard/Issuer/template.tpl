<!-- HEADER -->
<h1 <?= $emailStylesV2->getStyles(['m-mainContent__h1']) ?>>
    <?php echo $vars['headerText']; ?>
</h1>

<!-- BODY TEXT -->
<?php foreach($vars['bodyContentArray'] as $bodyContentRow) { ?>
    <p <?= $emailStylesV2->getStyles(['m-mainContent__paragraph']) ?> >
        <?php echo $bodyContentRow; ?>
    </p>
<?php } ?>

<!-- CLAIM LINK -->
<?php if(isset($vars['claimLink'])){ ?>
    <div <?= $emailStylesV2->getStyles(['m-mainContent__linkBox']) ?> >
        <a href="<?php echo $vars['claimLink']; ?>" target="_blank"> <?php echo $vars['claimLink']; ?> </a>
    </div>
<?php } ?>

<!-- SUBTEXT -->
<?php if(isset($vars['footerText'])){ ?>
    <p <?= $emailStylesV2->getStyles(['m-mainContent__paragraph--subtext']) ?> >
        <?php echo $vars['footerText']; ?>
    </p>
<?php } ?>

<!-- ADD'L LINK (OPTIONAL) -->
<?php if(isset($vars['additionalCtaPath']) && isset($vars['additionalCtaText'])){ ?>
    <a <?= $emailStylesV2->getStyles(['m-mainContent__standaloneLink--noMargin']) ?> href="<?php echo $vars['additionalCtaPath']; ?>">
        <?php echo $vars['additionalCtaText']; ?>
    </a>
<?php } ?>
