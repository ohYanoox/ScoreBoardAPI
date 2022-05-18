<?php
/*
__    __   ___   __   _   _____   _____  __    __
\ \  / /  /   | |  \ | | /  _  \ /  _  \ \ \  / /
 \ \/ /  / /| | |   \| | | | | | | | | |  \ \/ /
  \  /  / / | | | |\   | | | | | | | | |   }  {
  / /  / /  | | | | \  | | |_| | | |_| |  / /\ \
 /_/  /_/   |_| |_|  \_| \_____/ \_____/ /_/  \_\

API name
Author: Yanoox
Plugin's api: 4.0.0
For: everybody :)
forked by: Nathan45
 */
namespace yanoox;

use BadFunctionCallException;
use OutOfBoundsException;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;

class ScoreBoardAPI
{

    use SingletonTrait;

    public const MIN_LINE = 0;
    public const MAX_LINE = 15;
    public const MAX_CHAR_LINE = 32; //Apparently it can be bypassed
    /**
     * Contains the scoreboard and each line of the player's score (if he has one)
     *
     * @var string[]
     */
    public array $scoreboards = [];


    /**
     * Add a scoreboard to player, this option is mandatory: you can't set a score to a player without a scoreboard
     *
     * @param Player $player
     * @param string $displayName
     * @param int $slotOrder
     * @param string $displaySlot
     * @param string $objectiveName
     * @param string $criteriaName
     */
    public function sendScore(Player $player, string $displayName, int $slotOrder = SetDisplayObjectivePacket::SORT_ORDER_ASCENDING, string $displaySlot = SetDisplayObjectivePacket::DISPLAY_SLOT_SIDEBAR, string $objectiveName = "objective", string $criteriaName = "dummy")
    {
        if ($player->isConnected()) {

            if ($this->hasScore($player)) {
                $this->removeToPlayer($player);
            }

            $pk = new SetDisplayObjectivePacket();
            $pk->displaySlot = $displaySlot;
            $pk->objectiveName = $objectiveName;
            $pk->displayName = $displayName;
            $pk->criteriaName = $criteriaName;
            $pk->sortOrder = $slotOrder;
            $player->getNetworkSession()->sendDataPacket($pk);

            $this->scoreboards[mb_strtolower($player->getName())][0] = $objectiveName;
        }
    }

    /**
     * Set a line to the player's score
     *
     * @param Player $player
     * @param int $line
     * @param string $message
     * @param int $type
     */
    public function setLine(Player $player, int $line, string $message, int $type = ScorePacketEntry::TYPE_FAKE_PLAYER)
    {
        if ($player->isConnected()) {
            $line++;
            if (!$this->hasScore($player)) {
                throw new BadFunctionCallException("Cannot set the line : the player's scoreboard has not been found");
            }

            if ($this->isNotLineValid($line)) {
                throw new OutOfBoundsException("$line isn't between 1 and 15");
            }

            $entry = new ScorePacketEntry;
            $entry->objectiveName = $this->scoreboards[mb_strtolower($player->getName())][0] ?? "objective";
            $entry->type = $type;
            $entry->customName = $message;
            $entry->score = $line;
            $entry->scoreboardId = $line;

            $pk = new SetScorePacket();
            $pk->type = $pk::TYPE_CHANGE;
            $pk->entries[] = $entry;
            $player->getNetworkSession()->sendDataPacket($pk);
            $this->scoreboards[mb_strtolower($player->getName())][$line] = $message;
            var_dump($this->scoreboards);
        }
    }

    /**
     * Set several lines to the player's score
     *
     * @param Player $player
     * @param array $lines
     * @param int $type
     */
    public function setLines(Player $player, array $lines, int $type = ScorePacketEntry::TYPE_FAKE_PLAYER)
    {
        foreach ($lines as $line => $message) {
            $line++;
            $this->setLine($player, $line, $message, $type);
        }
    }

    /**
     * Edit a line of a player
     *
     * @param Player $player
     * @param int $line
     * @return string
     */
    public function getLine(Player $player, int $line): string
    {
        if ($player->isConnected()) {

            if (!$this->hasScore($player)) {
                throw new BadFunctionCallException("Cannot get the line : the player's scoreboard has not been found");
            }
            $line++;
        }
        return $this->scoreboards[mb_strtolower($player->getName())][$line];
    }

    public function getLines(Player $player): string
    {
        if ($player->isConnected()) {
            if (!$this->hasScore($player)) {
                throw new BadFunctionCallException("Cannot get the line : the player's scoreboard has not been found");
            }
        }
        return $this->scoreboards[mb_strtolower($player->getName())];

    }

    /**
     * @param Player $player
     * @param int $line
     * @param string|float $search
     * @param string|float $replace
     */
    public function editLine(Player $player, int $line, string|float $search, string|float $replace)
    {
        if ($player->isConnected()) {
            if (!$this->hasScore($player)) {
                throw new BadFunctionCallException("Cannot edit the line : the player's scoreboard has not been found");
            }
            if ($this->isNotLineValid($line)) {
                throw new OutOfBoundsException("$line isn't between 1 and 15");
            }
            $line++;
            $this->removeLine($player, $line);

            $entry = new ScorePacketEntry();
            $entry->objectiveName = $this->scoreboards[mb_strtolower($player->getName())][0] ?? "objective";
            $entry->customName = str_replace($search, $replace, $this->getLine($player, $line));
            $entry->score = $line;
            $entry->scoreboardId = $line;
            $entry->type = $entry::TYPE_FAKE_PLAYER;

            $pk = new SetScorePacket();
            $pk->type = SetScorePacket::TYPE_CHANGE;
            $pk->entries[] = $entry;
            $player->getNetworkSession()->sendDataPacket($pk);

            $this->scoreboards[mb_strtolower($player->getName())][$line] = str_replace($search, $replace, $this->getLine($player, $line));
        }
    }

    /**
     * Edit a line of a player
     *
     * @param Player $player
     * @param int $line
     */
    public function removeLine(Player $player, int $line)
    {
        if ($player->isConnected()) {
            $line++;
            $pk = new SetScorePacket();
            $pk->type = SetScorePacket::TYPE_REMOVE;

            $entry = new ScorePacketEntry();
            $entry->objectiveName = $this->scoreboards[mb_strtolower($player->getName())][0] ?? "objective";
            $entry->score = $line;
            $entry->scoreboardId = $line;
            $entry->customName = $this->getLine($player, $line);
            $pk->entries[] = $entry;

            $player->getNetworkSession()->sendDataPacket($pk);
        }
    }


    /**
     * Remove the scoreboard from the player
     *
     * @param Player $player
     * @return void
     */
    public function removeToPlayer(Player $player): void
    {
        if ($player->isConnected() && $this->hasScore($player)) {
            var_dump('Test de Zummma');
            $objectiveName = $this->scoreboards[mb_strtolower($player->getName())][0] ?? "objective";

            $pk = new RemoveObjectivePacket();
            $pk->objectiveName = $objectiveName;
            $player->getNetworkSession()->sendDataPacket($pk);

            unset($this->scoreboards[mb_strtolower($player->getName())]);
        }
    }

    /**
     * Remove the scoreboard from a certain player
     *
     * @param Player[] $players
     * @return void
     */
    public function removeToPlayers(array $players): void
    {
        foreach ($players as $player) {
            $this->removeToPlayer($player);
        }
    }

    /**
     * Remove the scoreboard from all players
     *
     * @return void
     */
    public function removeToAll(): void
    {
        //TODO fix
        foreach (array_keys($this->scoreboards) as $playerName) {

            if (Server::getInstance()->getPlayerExact($playerName)->isConnected() && $this->hasScore(Server::getInstance()->getPlayerExact($playerName))) {
                $objectiveName = $this->scoreboards[mb_strtolower(Server::getInstance()->getPlayerExact($playerName)->getName())][0] ?? "objective";

                $pk = new RemoveObjectivePacket();
                $pk->objectiveName = $objectiveName;
                Server::getInstance()->getPlayerExact($playerName)->getNetworkSession()->sendDataPacket($pk);


            }
        }
        unset($this->scoreboards);
    }

    /**
     * Return a boolean if the player has a score or not
     *
     * @param Player $player
     * @return bool
     */
    public function hasScore(Player $player): bool
    {
        return isset($this->scoreboards[mb_strtolower($player->getName())]);
    }

    /**
     * Actually each scoreboard can have a title line, with up to 32 character, and 15 lines
     *
     * @param int $line
     * @return bool
     */
    public function isNotLineValid(int $line): bool
    {
        return ($line < self::MIN_LINE || $line > self::MAX_LINE) or (strlen($line) >= self::MAX_CHAR_LINE);
    }
}