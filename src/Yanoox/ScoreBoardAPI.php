<?php
/*
__    __   ___   __   _   _____   _____  __    __
\ \  / /  /   | |  \ | | /  _  \ /  _  \ \ \  / /
 \ \/ /  / /| | |   \| | | | | | | | | |  \ \/ /
  \  /  / / | | | |\   | | | | | | | | |   }  {
  / /  / /  | | | | \  | | |_| | | |_| |  / /\ \
 /_/  /_/   |_| |_|  \_| \_____/ \_____/ /_/  \_\

APIs name: ScoreBoardAPI
Author: Yanoox
Plugin's api: 4.0.0
For: everybody :)

 */
namespace Yanoox\Utils\ScoreboardAPI;

use BadFunctionCallException;
use OutOfBoundsException;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\player\Player;

class ScoreBoardAPI implements Listener {

    /**
     * Contains the scoreboard of the players
     *
     * @var string[]
     */
    public static array $scoreboards;

    /**
     * Contains strings containing in each line the player's score (if he has one)
     *
     * @var string[]
     */
    public static array $lineScore;

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
        if ($player->isConnected()) {
            if (self::hasScore($player)) {
                self::removeScore($player);
            }

            $packet = new SetDisplayObjectivePacket();
            $packet->displaySlot = $displaySlot;
            $packet->objectiveName = $objectiveName;
            $packet->displayName = $displayName;
            $packet->criteriaName = $criteriaName;
            $packet->sortOrder = $slotOrder;
            $player->getNetworkSession()->sendDataPacket($packet);

            self::$scoreboards[mb_strtolower($player->getName())] = $objectiveName;
            self::$lineScore[mb_strtolower($player->getName())][0] = $objectiveName;
        }
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
        if ($player->isConnected()) {
            if (!self::hasScore($player)) {
                throw new BadFunctionCallException("Cannot set the line : the player's scoreboard has not been found");
            }

            if (self::isNotLineValid($line)) {
                throw new OutOfBoundsException("$line isn't between 1 and 15");
            }

            $entry = new ScorePacketEntry;
            $entry->objectiveName = self::$scoreboards[mb_strtolower($player->getName())] ?? "objective";
            $entry->type = $type;
            $entry->customName = $message;
            $entry->score = $line;
            $entry->scoreboardId = $line;

            $packet = new SetScorePacket();
            $packet->type = $packet::TYPE_CHANGE;
            $packet->entries[] = $entry;
            $player->getNetworkSession()->sendDataPacket($packet);

            self::$lineScore[mb_strtolower($player->getName())][$line] = $message;
        }
    }

    /**
     * Edit a line of a player
     *
     * @param Player $player
     * @param int $line
     * @return string
     */
    public static function getLineScore(Player $player, int $line) : string{
        if ($player->isConnected()) {
            if (!self::hasScore($player)) {
                throw new BadFunctionCallException("Cannot get the line : the player's scoreboard has not been found");
            }
            return self::$lineScore[mb_strtolower($player->getName())][$line];
        }
        return false;
    }

    /**
     * @param Player $player
     * @param int $line
     * @param string|float $search
     * @param string|float $replace
     * @return void
     */
    public static function editScoreLine(Player $player, int $line, string|float $search, string|float $replace)
    {
        if ($player->isConnected()) {
            if (!self::hasScore($player)) {
                throw new BadFunctionCallException("Cannot edit the line : the player's scoreboard has not been found");
            }
            if (self::isNotLineValid($line)) {
                throw new OutOfBoundsException("$line isn't between 1 and 15");
            }

            self::removeLine($player, $line);

            $entry = new ScorePacketEntry();
            $entry->objectiveName = self::$scoreboards[mb_strtolower($player->getName())] ?? "objective";
            $entry->customName = str_replace($search, $replace, self::getLineScore($player, $line));
            $entry->score = $line;
            $entry->scoreboardId = $line;
            $entry->type = $entry::TYPE_FAKE_PLAYER;

            $packet = new SetScorePacket();
            $packet->type = SetScorePacket::TYPE_CHANGE;
            $packet->entries[] = $entry;
            $player->getNetworkSession()->sendDataPacket($packet);

            self::$lineScore[mb_strtolower($player->getName())][$line] = str_replace($search, $replace, self::getLineScore($player, $line));
        }
    }

    /**
     * Edit a line of a player
     *
     * @param Player $player
     * @param int $line
     * @return void
     */
    public static function removeLine(Player $player, int $line) : void{
        if ($player->isConnected()) {
            $packet = new SetScorePacket();
            $packet->type = SetScorePacket::TYPE_REMOVE;

            $entry = new ScorePacketEntry();
            $entry->objectiveName = self::$scoreboards[mb_strtolower($player->getName())] ?? "objective";
            $entry->score = $line;
            $entry->scoreboardId = $line;
            $entry->customName = self::getLineScore($player, $line);
            $packet->entries[] = $entry;

            $player->getNetworkSession()->sendDataPacket($packet);
        }
    }


    /**
     * Remove the scoreboard from the player
     *
     * @param Player $player
     * @return void
     */
    public static function removeScore(Player $player): void{
        if ($player->isConnected() && self::hasScore($player)) {
            $objectiveName = self::$scoreboards[mb_strtolower($player->getName())] ?? "objective";

            $packet = new RemoveObjectivePacket();
            $packet->objectiveName = $objectiveName;
            $player->getNetworkSession()->sendDataPacket($packet);

            unset(self::$scoreboards[mb_strtolower($player->getName())]);
            unset(self::$lineScore[mb_strtolower($player->getName())]);
        }
    }

    /**
     * Return a boolean if the player has a score or not
     *
     * @param Player $player
     * @return bool
     */
    public static function hasScore(Player $player): bool{
        return isset(self::$scoreboards[mb_strtolower($player->getName())]);
    }

    public static function isNotLineValid(int $line): bool
    {
        return $line < 1 || $line > 15;
    }

    public function onQuit(PlayerQuitEvent $ev){
        if (self::hasScore($ev->getPlayer())){
            self::removeScore($ev->getPlayer());
        }
    }
}
