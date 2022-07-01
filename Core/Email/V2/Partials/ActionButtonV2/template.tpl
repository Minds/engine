<div>
    <!--[if mso]>
    <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml"
                 xmlns:w="urn:schemas-microsoft-com:office:word" href="<?php echo "{$vars['href']}"?>"
                 <?php echo $emailStylesV2->getStyles(['m-button-outlook']); ?> arcsize="125%"
                 stroke="f" fillcolor="#1B85D6">
        <w:anchorlock/>
        <center>
    <![endif]-->
    <a href="<?php echo "{$vars['href']}"?>"
        <?php echo $emailStylesV2->getStyles(['m-button']); ?> >
        <?php echo "{$vars['label']}"?>
    </a>
    <!--[if mso]>
    </center>
    </v:roundrect>
    <![endif]-->
</div>
