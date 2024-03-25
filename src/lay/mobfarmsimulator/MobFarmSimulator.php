<?php

declare(strict_types=1);

namespace Lay\MobFarmSimulator;

use cosmicpe\blockdata\BlockDataFactory;
use cosmicpe\blockdata\world\BlockDataWorldManager;
use Lay\MobFarmSimulator\Simulation\Mob;
use Lay\MobFarmSimulator\Simulation\Spawner;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

final class MobFarmSimulator extends PluginBase{
    public static BlockDataWorldManager $manager;

    public function onLoad(): void {
        $this->registerFarmedMobs();
    }

    public function onEnable(): void {
        if(!InvMenuHandler::isRegistered()){
            InvMenuHandler::register($this);
        }
        self::$manager = BlockDataWorldManager::create($this);
        $this->getServer()->getPluginManager()->registerEvents(new EventsHandler($this), $this);
        BlockDataFactory::register("Spawner", Spawner::class);
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if(!$sender instanceof Player) return false;
        $pos = $sender->getPosition();
        switch($command->getName()){
            case "test":
                return true;
            case "spawnsim":
                var_dump($sender->getInventory()->addItem(VanillaItems::ROTTEN_FLESH()->setCount(257)));
                return true;
            default:
                return true;
        }
    }

    private function registerFarmedMobs(){
        Mob::addMob(VanillaItems::ZOMBIE_SPAWN_EGG(), TextFormat::DARK_GREEN . "Zombie", [1, 5], [
            [VanillaItems::ROTTEN_FLESH(), 1, 5],
            [VanillaItems::IRON_INGOT(), 1, 2],
            [VanillaItems::POTATO(), 1, 2]
        ]);
        Mob::addMob(VanillaItems::SQUID_SPAWN_EGG(), TextFormat::BLACK . "Squid", [1, 5], [
            [VanillaItems::INK_SAC(), 2, 5],
            [VanillaItems::RAW_FISH(), 2, 5]
        ]);
    }
}
