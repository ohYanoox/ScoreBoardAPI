<?php

namespace yanoox;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;

class Example extends PluginBase implements Listener
{
    protected function onEnable(): void
    {
        Server::getInstance()->getPluginManager()->registerEvents($this, $this);
    }

    public function onInteract(PlayerItemUseEvent $event)
    {
        $api = ScoreBoardAPI::getInstance();
        switch ($event->getItem()->getId()) {
            case 0:
                $api->sendScore($event->getPlayer(), "The text above");
                $api->setLines(
                    $event->getPlayer(),
                    [
                        0 => "Is this api useful ?",
                        1 => "&#ffff55v&#f5df4be&#eabf40r&#dfa035y&#d5802b &#ca6020n&#bf4015i&#b5200bc&#aa0000e"
                    ]
                );
                break;
            case 1:
                $api->removeLine($event->getPlayer(), 0);
                $api->setLine($event->getPlayer(), 0, "Oh waouh !");
                $api->editLine($event->getPlayer(), 0, "waouh", "wow");
                break;
            case 2:
                $api->removeToPlayers(
                    [
                        $event->getPlayer()
                    ]
                );
                break;
        }
    }
]