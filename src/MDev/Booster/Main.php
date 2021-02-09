<?php

namespace MDev\Booster;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerGameModeChangeEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\plugin\PluginBase;
use MDev\Booster\FlyBooster;
use MDev\Booster\BreakBooster;
use pocketmine\scheduler\Task as PluginTask;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\event\player\PlayerJoinEvent;
use jojoe77777\FormAPI\SimpleForm;

class Main extends PluginBase implements Listener
{
    const PREFIX = "§6§lBooster §r§8: ";
    public $delay = [];

    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $config = new Config($this->getDataFolder() . "boosters.yml", Config::YAML);
        $this->getLogger()->info(self::PREFIX . "§aPlugin was activated.");
    }
    public function onDisable()
    {
        foreach ($this->getServer()->getOnlinePlayers() as $players) {
            $players->removeEffect(3);
            $players->setAllowFlight(false);
            $this->getLogger()->info(self::PREFIX . "§cPlugin was deactivated and all Boosters too.");
            $config = new Config($this->getDataFolder() . "boosters.yml", Config::YAML);
            $config->set("FlyBoosterActive", "false");
            $config->set("BreakBoosterActive", "false");
            $config->save();
        }
    }

    public function onJoin(PlayerJoinEvent $event){
        $config = new Config($this->getDataFolder() . "boosters.yml", Config::YAML);
        $player = $event->getPlayer();
        if($config->get("BreakBoosterActive") == "true"){
            $effect = new EffectInstance(Effect::getEffect(3), 9999999, 4, false);
            $player->addEffect($effect);
            $player->sendMessage(self::PREFIX . "§7The §6Break §7Booster is active right now. So you can break blocks faster.");
        }
        if($config->get("BreakBoosterActive") == "false"){
            $event->getPlayer()->removeEffect(3);
        }
        if($config->get("FlyBoosterActive") == "false"){
            $event->getPlayer()->setAllowFlight(false);
            if($event->getPlayer()->isOp()){
                $event->getPlayer()->setAllowFlight(true);
            }
        }
        if($config->get("FlyBoosterActive") == "true"){
            $player->setAllowFlight(true);
            $player->sendMessage(self::PREFIX . "§7The §6Fly §7Booster is active right now. So you can fly.");
        }
    }
    public function BoosterMenu(Player $player) {
        $form = new SimpleForm(function (Player $player, int $data = null) {
            $result = $data;
            if($result === null) {
                return true;
            }
            switch($result) {
                case 0;
                    $config = new Config($this->getDataFolder() . "boosters.yml", Config::YAML);
                    if(!$config->exists($player->getName())){
                        $config->set($player->getName(), 0);
                        $config->save();
                    }
                    if (!$config->get($player->getName()) >= 1) {
                        $player->sendMessage(self::PREFIX . "§cYou don't have enought boosters.");
                        return true;
                    }
                    if($config->get("FlyBoosterActive") == "true"){
                        $player->sendMessage(self::PREFIX . "§cThe §6Fly §cBooster is already activated.");
                        return true;
                    }
                    $name = $player->getName();
                    foreach ($this->getServer()->getOnlinePlayers() as $players) {
                        $players->setAllowFlight(true);
                        $this->getServer()->broadcastMessage(self::PREFIX . "§6" . $name . " §7activated the §6Fly§7 Booster! Now you can Fly for §610§7 Minutes!");
                        $this->delay[$player->getName()] = $this->getScheduler()->scheduleDelayedTask(new \MDev\Booster\FlyBooster($this, $player), 12000);
                        $new = $config->get($player->getName()) -1;
                        $config->set($player->getName(), $new);
                        $config->save();
                        $config = new Config($this->getDataFolder() . "boosters.yml", Config::YAML);
                        $config->set("FlyBoosterActive", "true");
                        $config->save();
                    }
                    break;

                case 1;
                    $config = new Config($this->getDataFolder() . "boosters.yml", Config::YAML);
                    if(!$config->exists($player->getName())){
                        $config->set($player->getName(), 0);
                        $config->save();
                    }
                    if (!$config->get($player->getName()) >= 1) {
                        $player->sendMessage(self::PREFIX . "§cYou don't have enought boosters.");
                        return true;
                    }
                    if($config->get("BreakBoosterActive") == "true"){
                        $player->sendMessage(self::PREFIX . "§cThe §6Break §cBooster is already activated.");
                        return true;
                    }
                    $name = $player->getName();
                    foreach ($this->getServer()->getOnlinePlayers() as $players) {
                        $effect = new EffectInstance(Effect::getEffect(3), 9999999, 4, false);
                        $player->addEffect($effect);
                        $this->getServer()->broadcastMessage(self::PREFIX . "§6" . $name . " §7activated the §6Break§7 Booster! Now you can Break blocks faster for §610§7 Minutes!");
                        $this->delay[$player->getName()] = $this->getScheduler()->scheduleDelayedTask(new \MDev\Booster\BreakBooster($this, $player), 12000);
                        $new = $config->get($player->getName()) -1;
                        $config->set($player->getName(), $new);
                        $config->save();
                        $config = new Config($this->getDataFolder() . "boosters.yml", Config::YAML);
                        $config->set("BreakBoosterActive", "true");
                        $config->save();
                    }
                    break;
            }
        });
        $form->setTitle("§6§lBooster§r");
        $form->setContent("§7Activate a Booster.");
        $form->addButton("§f§lFly§r§7\nEvery Player can Fly for 10 Minutes.");
        $form->addButton("§l§fBreak§r§7\nEvery Player can break blocks faster for 10 Minutes.");
        $form->sendToPlayer($player);
        return $form;
    }

    public function onCommand(CommandSender $player, Command $cmd, string $label, array $args): bool
    {
        switch ($cmd->getName()) {
            case "booster":
                if (!isset($args[0])) {
                    $this->BoosterMenu($player);
                    return true;
                }
                $options = ["fly", "break", "give", "info"];
                if (!in_array($args[0], $options)) {
                    $player->sendMessage(self::PREFIX . "§cUsage: /booster <fly:break:give:info>");
                    return true;
                }
                if ($args[0] == "fly") {
                    $config = new Config($this->getDataFolder() . "boosters.yml", Config::YAML);
                    if(!$config->exists($player->getName())){
                        $config->set($player->getName(), 0);
                        $config->save();
                    }
                    if (!$config->get($player->getName()) >= 1) {
                        $player->sendMessage(self::PREFIX . "§cYou don't have enought boosters.");
                        return true;
                    }
                    if($config->get("FlyBoosterActive") == "true"){
                        $player->sendMessage(self::PREFIX . "§cThe §6Fly §cBooster is already activated.");
                        return true;
                    }
                    $name = $player->getName();
                    foreach ($this->getServer()->getOnlinePlayers() as $players) {
                        $players->setAllowFlight(true);
                        $this->getServer()->broadcastMessage(self::PREFIX . "§6" . $name . " §7activated the §6Fly§7 Booster! Now you can Fly for §610§7 Minutes!");
                        $this->delay[$player->getName()] = $this->getScheduler()->scheduleDelayedTask(new \MDev\Booster\FlyBooster($this, $player), 12000);
                        $new = $config->get($player->getName()) -1;
                        $config->set($player->getName(), $new);
                        $config->save();
                        $config = new Config($this->getDataFolder() . "boosters.yml", Config::YAML);
                        $config->set("FlyBoosterActive", "true");
                        $config->save();
                    }
                }
                if ($args[0] == "break") {
                    $config = new Config($this->getDataFolder() . "boosters.yml", Config::YAML);
                    if(!$config->exists($player->getName())){
                        $config->set($player->getName(), 0);
                        $config->save();
                    }
                    if (!$config->get($player->getName()) >= 1) {
                        $player->sendMessage(self::PREFIX . "§cYou don't have enought boosters.");
                        return true;
                    }
                    if($config->get("BreakBoosterActive") == "true"){
                        $player->sendMessage(self::PREFIX . "§cThe §6Break §cBooster is already activated.");
                        return true;
                    }
                    $name = $player->getName();
                    foreach ($this->getServer()->getOnlinePlayers() as $players) {
                        $effect = new EffectInstance(Effect::getEffect(3), 9999999, 4, false);
                        $player->addEffect($effect);
                        $this->getServer()->broadcastMessage(self::PREFIX . "§6" . $name . " §7activated the §6Break§7 Booster! Now you can Break blocks faster for §610§7 Minutes!");
                        $this->delay[$player->getName()] = $this->getScheduler()->scheduleDelayedTask(new \MDev\Booster\BreakBooster($this, $player), 12000);
                        $new = $config->get($player->getName()) -1;
                        $config->set($player->getName(), $new);
                        $config->save();
                        $config = new Config($this->getDataFolder() . "boosters.yml", Config::YAML);
                        $config->set("BreakBoosterActive", "true");
                        $config->save();
                    }
                }
                if($args[0] == "give"){
                    if(!$player->hasPermission("booster.give")){
                        $player->sendMessage(self::PREFIX . "§cYou are not allowed to do this.");
                        return true;
                    }
                    if(!isset($args[2])){
                        $player->sendMessage(self::PREFIX . "§cUsage: /booster give <Player> <Amount>");
                        return true;
                    }
                    if(!is_numeric($args[2])){
                        $player->sendMessage(self::PREFIX . "§cThe amount must be numeric.");
                        return true;
                    }
                    $target = $this->getServer()->getPlayer($args[1]);
                    if(!$target instanceof Player){
                        $player->sendMessage(self::PREFIX . "§cThis Player is not Player!");
                    }
                    $amount = $args[2];
                    $config = new Config($this->getDataFolder() . "boosters.yml", Config::YAML);
                    $new = $config->get($target->getName()) +$amount;
                    $config->set($target->getName(), $new);
                    $config->save();
                    $player->sendMessage(self::PREFIX . "§7You gave §6" . $amount . "§7 Boosters to§6 " . $target->getName() . " §7successfully.");
                    $target->sendMessage(self::PREFIX . "§7You got §6" . $amount . " §7Boosters. You can see your Boosters with /booster info.");
                }
                if($args[0] == "info"){
                    $config = new Config($this->getDataFolder() . "boosters.yml", Config::YAML);
                    if(!$config->exists($player->getName())){
                        $config->set($player->getName(), 0);
                        $config->save();
                    }
                    $player->sendMessage(self::PREFIX . "§7You have §6" . $config->get($player->getName()) . " §7Booster(s). \n§7/booster fly §8- §7Activate the Fly Booster.\n§7/booster break §8- §7Activate the Break Booster.");
                }
        }
        return true;
    }
}
