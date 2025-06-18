<?php
/**
 * Not supported browser
 */
namespace Minds\Controllers;

use Minds\Core;
use Minds\Entities;
use Minds\Interfaces;

class notSupported extends core\page implements Interfaces\page
{
    /**
     * Get requests
     */
    public function get($pages)
    {
        echo <<< HTML
        <html>
          <head>

            <base href="/" />
            <link rel="icon" type="image/png" href="/assets/icon.png" />
            <meta name="viewport" content="width=device-width, initial-scale=1,maximum-scale=1,user-scalable=no">

            <link rel="stylesheet" crossorigin="anonymous" href="https://storage.googleapis.com/code.getmdl.io/1.0.5/material.blue_grey-amber.min.css" />
            <link rel="stylesheet" crossorigin="anonymous" integrity="sha256-84ef1bXQjXdmG+bNbSbO0Y+4fsZP2/ErjAn4GDn+D44=" href="https://fonts.googleapis.com/icon?family=Material+Icons">
            <link rel='stylesheet' crossorigin="anonymous" integrity="sha256-8MVHE8E/ZgANWGKD1HYQ9Ia4vDzIF9OculKcF1vK1JI=" href='https://fonts.googleapis.com/css?family=Roboto:400,700'>
            <link rel="stylesheet" href="stylesheets/main.css"/>
            <script crossorigin="anonymous" integrity="sha256-fLhlduqmRvw+eKNAQhNwyNFG+hm3EaOj+eBfN0Ywa10=" src="//storage.googleapis.com/code.getmdl.io/1.0.5/material.min.js"></script>
            <script crossorigin="anonymous" integrity="sha256-lvjW3BWzRRP7TD5khZrsg+SDjdafWOk+nTOM3fEfxd0=" src="//tinymce.cachefly.net/4.2/tinymce.min.js"></script>
            <!-- Google Analytics -->
            <script>
            (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
            (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
            m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
            })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
            </script>
            <!-- End Google Analytics -->

            <style>
              minds-app{
                padding:16px;
              }
              .mobile{
                display:none;
              }
              @media screen and (max-width:400px){
                .mobile{
                  display:block;
                }
                .desktop{
                  /*display:none;*/
                }
              }
            </style>

          </head>
          <body>

            <minds-app  class="">
                <img src="/assets/logos/medium.png" width="200px;" />
                <h3>Your browser is outdated.</h3>
                <p class="desktop">Please upgrade to either
                  <a href="https://www.mozilla.org/en-GB/firefox/new/" target="_blank">Firefox</a>
                  or <a href="https://www.google.co.uk/chrome/browser/desktop/">Chrome</a>.
                </p>
                <div class="mobile">
                  <p>You can also download our mobile apps too</p>
                  <div class="">
                    <a href="https://geo.itunes.apple.com/us/app/minds-com/id961771928?mt=8&amp;uo=6">
                      <img src="https://linkmaker.itunes.apple.com/images/badges/en-us/badge_appstore-lrg.png">
                    </a>
                    <a href="https://play.google.com/store/apps/details?id=com.minds.mobile" align="center">
                      <img alt="Android app on Google Play" src="https://developer.android.com/images/brand/en_app_rgb_wo_45.png">
                    </a>
                  </div>
                </div>
            </minds-app>

          </body>
        </html>

HTML;
    }

    public function post($pages)
    {
    }

    public function put($pages)
    {
    }

    public function delete($pages)
    {
    }
}
