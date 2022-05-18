# ScoreBoardAPI
**ScoreBoardAPI is a PocketMine-MP 4.0.0 API that eases creating and managing scorehud**

## SETUP
**Only put the api in the src of your plugin and use it :)**

You will find an example in \yanoox\Example.php

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
$api->setLine(Player, $line, int);
//or if you've got an array you can put all your lines in this function
$lines = [
    1 => "My first line",
    2 => "My second line"
];
$api->setLines(Player, $lines, int);
```

## You can get the text that is located in the line of the scoreboard of the player you entered the function
```php
$api->getLine(Player, $line);
```
## To edit a line
https://www.php.net/manual/fr/function.str-replace.php
```PHP
$api->editLine(Player, $line, $replace, $subject);
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
$api->removeToPlayer($player); //for a specific player
$api->removeToPlayers([$player1, $player2, $player3]); //For several specific players
$api->removeToAll(); //for all players who have a scoreboard
```

## There you go! you can now create in any circumstance and modify scoreboards to the player
have a nice day ;)
