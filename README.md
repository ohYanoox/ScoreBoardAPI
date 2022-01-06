# ScoreBoardAPI
**ScoreBoardAPI is a PocketMine-MP API that eases creating and managing scorehud**

## SETUP
**Only put the api in the src of your plugin

## Sending a scoreboard to player

```php
ScoreBoardAPI::sendScore(Player, "anyname");
```

## add lines and fill it with text
```php
ScoreBoardAPI::setScoreLine(Player, int $line, string);
```

## You can get the text that is located in the line of the scoreboard of the player you entered the function
```php
ScoreBoardAPI::getLineScore(Player, $line;
```
## To edit a line
```PHP
# https://www.php.net/manual/fr/function.str-replace.php
ScoreBoardAPI::editLineScore(Player, $mine, $replace, $subject);
```

## To delete a single ligne
```PHP
ScoreBoardAPI::removeLine(Player, $line);
```

## To remove the player's scoreboard
```PHP
ScoreBoardAPI::removeScore($player);
```

## There you go! you can now create in any circumstance and modify scoreboards to the player
have a nice day :)
