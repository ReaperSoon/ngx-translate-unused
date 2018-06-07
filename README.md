# ngx-translate-unused
A php script to find orphan translation key on angular or ionic project

## Installation

* The best way is to download the php script on the root of your project (but you can download it anywhere you want)

```bash
$ git clone https://github.com/ReaperSoon/ngx-translate-unused.git
# Optional
$ mv ngx-translate-unused/ngx-translate-unused.php .
$ rm -rf ngx-translate-unused
# Execute script
$ php ngx-translate-unused.php
```

* You also can execute it directly with the command above :

```bash
$ curl -s https://raw.githubusercontent.com/ReaperSoon/ngx-translate-unused/master/ngx-translate-unused.php > ngx-translate-unused.php && php ngx-translate-unused.php
```

## Usage

Execute script
```bash
$ php ngx-translate-unused.php

Please enter your translation directory (default: src/assets/i18n): /Users/foo/Projects/Mobile/ionic/MyApp/app/src/assets/i18n/
Looking for translation files in /Users/foo/Projects/Mobile/ionic/MyApp/app/src/assets/i18n/...
2 translate files found
Keys in /Users/foo/Projects/Mobile/ionic/MyApp/app/src/assets/i18n//en.json: 157
Keys in /Users/foo/Projects/Mobile/ionic/MyApp/app/src/assets/i18n//fr.json: 157

Please enter your sources directory (default: src): /Users/foo/Projects/Mobile/ionic/MyApp/app/src
Looking for unused translation key from sources in /Users/foo/Projects/Mobile/ionic/MyApp/app/src...

Unused keys in /Users/foo/Projects/Mobile/ionic/MyApp/app/src/assets/i18n//en.json (13 found)
+-----------------------+-------+------+
| Key                   | Value | Line |
+-----------------------+-------+------+
| __TUTORIAL_PAGE__     |       | 2    |
| __WELCOME_PAGE__      |       | 13   |
| __REGISTER_PAGE__     |       | 36   |
| __LOGIN_PAGE__        |       | 41   |
| __SUBSCRIPTION_PAGE__ |       | 47   |
| __COMMON__            |       | 68   |
| __TABS_NAMES__        |       | 81   |
| __TAB_STATS__         |       | 138  |
| __USER_MENU__         |       | 142  |
| __PLAYLIST__          |       | 163  |
| __SOUNDS__            |       | 166  |
| __BOOKMARKS__         |       | 170  |
| __ABOUT__             |       | 174  |
+-----------------------+-------+------+

Unused keys in /Users/foo/Projects/Mobile/ionic/MyApp/app/src/assets/i18n//fr.json (13 found)
+-----------------------+-------+------+
| Key                   | Value | Line |
+-----------------------+-------+------+
| __TUTORIAL_PAGE__     |       | 2    |
| __WELCOME_PAGE__      |       | 13   |
| __REGISTER_PAGE__     |       | 36   |
| __LOGIN_PAGE__        |       | 41   |
| __SUBSCRIPTION_PAGE__ |       | 47   |
| __COMMON__            |       | 68   |
| __TABS_NAMES__        |       | 81   |
| __TAB_STATS__         |       | 138  |
| __USER_MENU__         |       | 142  |
| __PLAYLIST__          |       | 163  |
| __SOUNDS__            |       | 166  |
| __BOOKMARKS__         |       | 170  |
| __ABOUT__             |       | 174  |
+-----------------------+-------+------+
```

You can ignore keys matching pattern with wildcard.
Example (in my translate files I use "__TITLE__":"" to define title to my translations grouped by categories):

```bash
$ phpm ngx-translate-unused.php --ignore="__*__"

Please enter your translation directory (default: src/assets/i18n): /Users/foo/Projects/Mobile/ionic/MyApp/app/src/assets/i18n/
Looking for translation files in /Users/foo/Projects/Mobile/ionic/MyApp/app/src/assets/i18n/...
2 translate files found
Keys in /Users/foo/Projects/Mobile/ionic/MyApp/app/src/assets/i18n//en.json: 157
Keys in /Users/foo/Projects/Mobile/ionic/MyApp/app/src/assets/i18n//fr.json: 157

Please enter your sources directory (default: src): /Users/foo/Projects/Mobile/ionic/MyApp/app/src/
Looking for unused translation key from sources in /Users/foo/Projects/Mobile/ionic/MyApp/app/src/...

Unused keys in /Users/foo/Projects/Mobile/ionic/MyApp/app/src/assets/i18n//en.json (0 found | 13 ignored)

Unused keys in /Users/foo/Projects/Mobile/ionic/MyApp/app/src/assets/i18n//fr.json (0 found | 13 ignored)
```

