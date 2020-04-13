<?php
/**
 * Email Styles
 * Since CSS in emails don't work properly, we are forced to use inline styles
 * This class marries the two by providing a dictionary of classes that can be applied in combination
 *
 * NOTE: Cannot be used in conjunction with hard-coded inline styles,
 * as whichever comes second will be cancelled.
 */

namespace Minds\Core\Email\V2\Common;

class EmailStyles
{
    protected $styles;

    //Define your 'css classes' here
    //In your templates, class <?php $emailStyles->getStyle('m-fonts', 'm-link', 'm-clear')
    public function __construct()
    {
        $this->styles = [
        // TEXT ///////////////////////////
            'm-fonts' => 'font-family:Roboto,Helvetica Neue,Helvetica,Arial,sans-serif;',
            'm-copy' => 'font-size:16px;line-height:22px;text-align:left;font-family:Roboto,Helvetica Neue,Helvetica,Arial,sans-serif;color: #4f4f50;',
            'm-link' => 'text-decoration: underline; color: #1b85d6 !important;',
            'm-textColor--primary' => 'color: #4f4f50;',
            'm-textColor--secondary' => 'color: #7d7d82 !important;',
            'm-textColor--white' => 'color: #FFFFFF !important;',
            'm-title' => 'font-size:26px; text-align:center;color: #4f4f50;margin-top: 0;',
            'm-subtitle' => 'font-size:22px; line-height:29px;',
            'm-signature' => 'margin: 0 0 4px 0;padding: 0 !important;',

        // SPACING & LAYOUT ///////////////////////////
            'm-maxWidth' => 'max-width:600px;',
            'm-maxWidth--copy' => 'width:80%;max-width:500px',
            'm-spacer--small' => 'padding: 22px 0;',
            'm-spacer--medium' => 'padding: 40px 0;',
            'm-spacer--large' => 'padding: 56px 0;',
            'm-borderTop' => 'border-top:1px solid #d4d4d4;',
            'm-clear' => 'margin: 0px; padding: 0px;',
        // PARTIAL : SUGGESTED CHANNELS //////////////
            'm-avatar-size' => 'height: 50px; width: 44px;',
            'm-newsfeedSidebar__header' => 'margin-bottom: 8px; font-size: 14px; color: #7d7d82; height: 18px;font-weight:400;',
            'm-suggestions__link' => 'text-decoration: none;',
            'm-suggestions__sidebar' => 'width: 540px; border: 1px solid #d4d4d4; padding: 0;',
            'm-suggestionsSidebarListItem__avatar' => 'width: 28px; height: 28px; padding: 8px',
            'm-suggestionsSidebarList__item' => 'border-bottom: 1px solid #d4d4d4;',
            'm-suggestionsSidebarListItem__description' => 'color: #7d7d82;font-size: 11px; line-height: 16px; font-family: Roboto; overflow: hidden; text-overflow:ellipsis; white-space:nowrap; width: 480px; height: 20px;',

            'm-header' => 'font-size: 24px; color: #000; margin-bottom: 10px;',
            'm-subtitle' => 'font-family:Roboto-Light; font-size: 14px; color: #4A4A4A !important; line-height: 30px;',
        ];
    }

    public function getStyles(...$styleKeys)
    {
        $styles = array_intersect_key($this->styles, array_flip($styleKeys));

        return ' style="'.implode($styles, ';').'" ';
    }
}
