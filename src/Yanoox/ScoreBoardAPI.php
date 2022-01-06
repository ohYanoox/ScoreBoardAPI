<?php
/*
__    __   ___   __   _   _____   _____  __    __
\ \  / /  /   | |  \ | | /  _  \ /  _  \ \ \  / /
 \ \/ /  / /| | |   \| | | | | | | | | |  \ \/ /
  \  /  / / | | | |\   | | | | | | | | |   }  {
  / /  / /  | | | | \  | | |_| | | |_| |  / /\ \
 /_/  /_/   |_| |_|  \_| \_____/ \_____/ /_/  \_\

API's name: ScoreBoardAPI
Author: Yanoox
Plugin's api: 4.0.0
For: everybody :)

 */
namespace Yanoox;

use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\player\Player;

class ScoreBoardAPI{

    /**
     * Contains the scoreboard of the players
     *
     * @var string[]
     */
    private static array $scoreboards = [];

    /**
     * Contains strings containing in each line the player's score (if he has one)
     *
     * @var string[]
     */
    private static array $lineScore = [];

    /**
     * Add a scoreboard to player, this option is mandatory: you can't set a score to a player without a scoreboard
     *
     * @param Player $player
     * @param string $displayName
     * @param int $slotOrder
     * @param string $displaySlot
     * @param string $objectiveName
     * @param string $criteriaName
     * @return void
     */
    public static function sendScore(Player $player, string $displayName, int $slotOrder = SetDisplayObjectivePacket::SORT_ORDER_ASCENDING, string $displaySlot = SetDisplayObjectivePacket::DISPLAY_SLOT_SIDEBAR, string $objectiveName = "objective", string $criteriaName = "dummy"): void{
        if (!$player->isConnected()){
            return;
        }
        if(self::hasScore($player)){
            self::removeScore($player);
        }

        $pk = new SetDisplayObjectivePacket();
        $pk->displaySlot = $displaySlot;
        $pk->objectiveName = $objectiveName;
        $pk->displayName = $displayName;
        $pk->criteriaName = $criteriaName;
        $pk->sortOrder = $slotOrder;
        $player->getNetworkSession()->sendDataPacket($pk);

        self::$scoreboards[strtolower($player->getName())] = $objectiveName;
    }


    /**
     * Sets a line to the player's score
     *
     * @param Player $player
     * @param int $line
     * @param string $message
     * @param int $type
     * @return void
     */
    public static function setScoreLine(Player $player, int $line, string $message, int $type = ScorePacketEntry::TYPE_FAKE_PLAYER): void{
        if (!$player->isConnected()){
            return;
        }
        if (!self::hasScore($player)){
            throw new \BadFunctionCallException("Cannot set the line : the player's scoreboard has not been found");
        }

        if(self::isLineValid($line)){
            throw new \OutOfBoundsException("$line isn't between 1 and 15");
        }

        $entry = new ScorePacketEntry;
        $entry->objectiveName = self::$scoreboards[strtolower($player->getName())] ?? "objective";
        $entry->type = $type;
        $entry->customName = $message;
        $entry->score = $line;
        $entry->scoreboardId = $line;

        $pk = new SetScorePacket();
        $pk->type = $pk::TYPE_CHANGE;
        $pk->entries[] = $entry;
        $player->getNetworkSession()->sendDataPacket($pk);

        self::$lineScore[strtolower($player->getName())[$line]] = $message;
    }

    /**
     * Edit a line of a player
     *
     * @param Player $player
     * @param int $line
     * @return string
     */
    public static function getLineScore(Player $player, int $line) : string{
        if (!self::hasScore($player)){
            throw new \BadFunctionCallException("Cannot get the line : the player's scoreboard has not been found");
        }
        if(self::isLineValid(($line)){
            throw new \OutOfBoundsException("$line isn't between 1 and 15");
        }

        return self::$lineScore[strtolower($player->getName())[$line]];
    }

    /**
     * @param Player $player
     * @param int $line
     * @param string $replace
     * @param string|float $subject
     * @return void
     */
    public static function editLineScore(Player $player, int $line, string $replace, string|float $subject){
        if (!isset(self::$lineScore[strtolower($player->getName())[$line]]) or !self::hasScore($player)){
            throw new \BadFunctionCallException("Cannot edit the line : the player's scoreboard has not been found");
        }
        if(self::isLineCorrect($line)){
            throw new \OutOfBoundsException("$line isn't between 1 and 15");
        }

        self::removeLine($player, $line);

        $entry = new ScorePacketEntry();
        $entry->objectiveName = self::$scoreboards[strtolower($player->getName())] ?? "objective";
        $entry->customName = str_replace($replace, $subject, self::getLineScore($player, $line));
        $entry->score = $line;
        $entry->scoreboardId = $line;
        $entry->type = $entry::TYPE_FAKE_PLAYER;

        $packet = new SetScorePacket();
        $packet->type = SetScorePacket::TYPE_CHANGE;
        $packet->entries[] = $entry;

        self::$lineScore[strtolower($player->getName())[$line]] = str_replace($replace, $subject, self::getLineScore($player, $line));

        $player->getNetworkSession()->sendDataPacket($packet);
    }

    /**
     * Edit a line of a player
     *
     * @param Player $player
     * @param int $line
     * @return void
     */
    public static function removeLine(Player $player, int $line) : void{
        $pk = new SetScorePacket();
        $pk->type = SetScorePacket::TYPE_REMOVE;

        $entry = new ScorePacketEntry();
        $entry->objectiveName = self::$scoreboards[strtolower($player->getName())] ?? "objective";
        $entry->score = $line;
        $entry->scoreboardId = $line;
        $entry->customName = self::getLineScore($player, $line);
        $pk->entries[] = $entry;

        $player->getNetworkSession()->sendDataPacket($pk);

    }


    /**
     * Remove the scoreboard from the player
     *
     * @param Player $player
     * @return void
     */
    public static function removeScore(Player $player): void{
        if (!$player->isConnected()){
            return;
        }
        $objectiveName = self::$scoreboards[strtolower($player->getName())] ?? "objective";

        $pk = new RemoveObjectivePacket();
        $pk->objectiveName = $objectiveName;
        $player->getNetworkSession()->sendDataPacket($pk);

        unset(self::$scoreboards[strtolower($player->getName())]);
    }

    /**
     * Return a boolean if the player has a score or not
     *
     * @param Player $player
     * @return bool
     */
    public static function hasScore(Player $player): bool{
        return isset(self::$scoreboards[strtolower($player->getName())]);
    }

    public static function isLineValid(int $line): bool
    {
        return $line < 1 || $line > 15;
    }
}
