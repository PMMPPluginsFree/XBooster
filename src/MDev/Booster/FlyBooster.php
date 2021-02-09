<?php

namespace MDev\Booster;

use pocketmine\scheduler\Task;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use pocketmine\Player;
use MDev\Booster\Main;
use pocketmine\utils\Config;

class FlyBooster extends Task {
    private $plugin;
    private $player;

    public function __construct(Main $plugin, Player $player) {
        $this->plugin = $plugin;
        $this->player = $player;
    }

    public function onRun(int $currentTick) {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $players) {
            $players->setAllowFlight(false);
            $config = new Config($this->plugin->getDataFolder() . "boosters.yml", Config::YAML);
            $config->set("FlyBoosterActive", "false");
            $config->save();
            $this->plugin->getServer()->broadcastMessage(Main::PREFIX . "§cThe §6Fly §cBooster was deactivated. You cannot Fly anymore.");
        }
    }
}