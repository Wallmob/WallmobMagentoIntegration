Wallmob Magento Integration
===========================

This is the Magento Integration for the Wallmob POS. Wallmob is the leading system, and for imports that means there is a one way direction. It currently has the following features:

- Import products (and their variants).
- Import categories.
- Import stock data.
- Report orders to Wallmob.

Installation
------------

### Using [Magento Composer Installer](https://github.com/magento-hackathon/magento-composer-installer):

Add wallmob to your composer.json:

```
{
    "require": {
        "magento-hackathon/magento-composer-installer": "*",
        "wallmob/wallmob": "*"
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/magento-hackathon/magento-composer-installer"
        },
        {
            "type": "vcs",
            "url": "https://github.com/Wallmob/WallmobMagentoIntegration.git"
        }
    ],
    "extra":{
        "magento-root-dir": "htdocs/"
    }
}
```

Then, issue the ``composer install`` command.
    
### Using [modman](https://github.com/colinmollenhour/modman):

Issue the ``modman clone https://github.com/Wallmob/WallmobMagentoIntegration.git`` command.

### Manual install:

- Download the [wallmob zip file](https://github.com/Wallmob/WallmobMagentoIntegration/archive/master.zip). 
- Unpack it.
- Copy the ``app`` folder into your Magento installation.

Be sure to clear cache afterwards.

License
-------
```
Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
```

