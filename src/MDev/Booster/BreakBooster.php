<?php

namespace MDev\Booster;

use MDev\Booster\Main;
use pocketmine\scheduler\Task;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\utils\Config;

class BreakBooster extends Task {
    private $plugin;
    private $player;

    public function __construct(Main $plugin, Player $player) {
        $this->plugin = $plugin;
        $this->player = $player;
    }

    public function onRun(int $currentTick) {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $players) {
            $players->removeEffect(3);
            $config = new Config($this->plugin->getDataFolder() . "boosters.yml", Config::YAML);
            $config->set("BreakBoosterActive", "false");
            $config->save();
            $this->plugin->getServer()->broadcastMessage(Main::PREFIX . "§cThe §6Break §cBooster was deactivated. You cannot break blocks faster anymore.");
        }
    }
}