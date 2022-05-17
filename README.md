# ScoreBoardAPI
**ScoreBoardAPI is a PocketMine-MP  4.0.0 API that eases creating and managing scorehud**

## SETUP
**Only put the api in the src of your plugin and use it :)**

## Get an instance of your api
```php
$api = ScoreBoardAPI::getInstance();
```
## Sending a scoreboard to player

```php
$api->sendScore(Player, "anyname");
```

## add lines and fill it with text
```php
$api->setScoreLine(Player, $line, int);
//or if you've got an array you can put all your lines in this function
$lines = [
    1 => "My first line",
    2 => "My second line"
];
$api->setLines(Player, $lines, int);
```

## You can get the text that is located in the line of the scoreboard of the player you entered the function
```php
$api->getLineScore(Player, $line);
```
## To edit a line
https://www.php.net/manual/fr/function.str-replace.php
```PHP
$api->editLineScore(Player, $line, $replace, $subject);
```

## To delete a single ligne
```PHP
$api->removeLine(Player, $line);
```

## To check if the player has a scoreboard
```PHP
$api->hasScore(Player)
```

## To remove the player's scoreboard
```PHP
$api->removeScore($player);
```

## There you go! you can now create in any circumstance and modify scoreboards to the player
have a nice day ;)
