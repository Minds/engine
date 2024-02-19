@PWA
Feature: PWA
  Scenario: Successfully retrieve the dynamic web manifest
    When I make a GET call to the "v3/pwa/manifest" endpoint
    Then I get a 200 response containing
      """json
        {
          "name": "Minds Web",
          "short_name": "Minds",
          "description": "Elevate the global conversation through Internet freedom. Speak freely, protect your privacy, earn crypto, and take back control of your social media",
          "theme_color": "#ffffff",
          "categories": ["social", "news", "magazines"],
          "background_color": "#ffffff",
          "display": "standalone",
          "android_package_name": "com.minds.mobile",
          "scope": "./",
          "start_url": "/",
          "icons": [
            {
              "src": "/static/en/assets/logos/logo.png",
              "type": "image/png",
              "sizes": "192x192"
            },
            {
              "src": "/static/en/assets/logos/logo-large.png",
              "type": "image/png",
              "sizes": "512x512"
            },
            {
              "src": "/static/en/assets/logos/logo-maskable.png",
              "type": "image/png",
              "sizes": "192x192",
              "purpose": "maskable"
            },
            {
              "src": "/static/en/assets/logos/logo-maskable-large.png",
              "type": "image/png",
              "sizes": "512x512",
              "purpose": "maskable"
            }
          ],
          "prefer_related_applications": true,
          "related_applications": [
            {
              "platform": "play",
              "url": "https://play.google.com/store/apps/details?id=com.minds.mobile",
              "id": "com.minds.mobile"
            },
            {
              "platform": "itunes",
              "url": "https://apps.apple.com/app/minds-com/id961771928"
            }
          ]
        }
      """
