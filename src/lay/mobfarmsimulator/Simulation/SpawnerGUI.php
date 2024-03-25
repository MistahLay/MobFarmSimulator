<?php

namespace Lay\MobFarmSimulator\Simulation;

use Lay\MobFarmSimulator\MobFarmSimulator;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\block\Chest;
use pocketmine\block\Lever;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\ExperienceBottle;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;

final class SpawnerGUI {

    public static function openSpawner(Player $player, Position $position){
        $player->removeCurrentWindow();
        $spawner = MobFarmSimulator::$manager->get($position->world)->getBlockDataAt($position->x, $position->y, $position->z);
        if(!$spawner instanceof Spawner) return;
        $menu = InvMenu::create(InvMenu::TYPE_CHEST);
        $bar = VanillaBlocks::IRON_BARS()->getPickedItem()
            ->setCustomName(" ");
        $air = VanillaItems::AIR();
        $XP = VanillaItems::EXPERIENCE_BOTTLE()
            ->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::GREEN . "Spawner XP")
            ->setLore([
                TextFormat::RESET . "Amount: " . TextFormat::GREEN . $spawner->getXp() ."/". $spawner->getMaxXp()
            ]);
        $EGG = null;
        if($spawner->getMob() == Spawner::UNKNOWN){
            $EGG = VanillaItems::EGG()
            ->setCustomName(TextFormat::RESET . TextFormat::RED . "Unknown Mob");
        }else{
            $mob = Mob::getMob($spawner->getMob());
            $EGG = $mob->getEgg();
            $EGG->setCustomName(TextFormat::RESET . "Mob Farmed: " . $mob->getName())
                ->setLore([
                    TextFormat::RESET . "Multiplier: " . TextFormat::RED . $spawner->getMobMultiplier() . "/" . $spawner->getMaxMobMultiplier()
                ]);
        }
        $CHEST = VanillaBlocks::CHEST()->getPickedItem()
                ->setCustomName(TextFormat::RESET . TextFormat::DARK_AQUA . "Chest Pouch");
        $SWORD = VanillaItems::DIAMOND_SWORD()
            ->setCustomName(TextFormat::RESET . "Killers");
        $LEVER = VanillaBlocks::LEVER()->getPickedItem()
                ->setCustomName(TextFormat::RESET . TextFormat::GREEN . "Kill Spawned Mobs")
                ->setLore([
                    TextFormat::RESET . "Spawned Mobs: " . TextFormat::GREEN . $spawner->getSpawnedMobs() . "/" . $spawner->getMaxMobs()
                ]);
        $menu->getInventory()->setContents(
            [$bar, $bar, $bar, $bar, $CHEST, $bar, $bar, $bar, $bar,
             $bar, $air, $XP, $air, $EGG, $air, $SWORD, $air, $bar,
             $bar, $bar, $bar, $bar, $LEVER, $bar, $bar, $bar, $bar]
        );
        $menu->setName("Spawner lvl" . $spawner->getSpawnerLevel());
        $menu->setListener(InvMenu::readonly(function(DeterministicInvMenuTransaction $e) use ($position){
            $item = $e->getItemClicked();
            $spawner = MobFarmSimulator::$manager->get($position->world)->getBlockDataAt($position->x, $position->y, $position->z);
            if(!$spawner instanceof Spawner) return;
            $player = $e->getPlayer();
            if($item instanceof ExperienceBottle){
                $player->getXpManager()->addXp($spawner->getXp());
                $spawner->setXp(0);
                self::openSpawner($player, $position);
                return;
            }
            if($item->getBlock() instanceof Lever){
                $player->sendMessage("Killed " . $spawner->getSpawnedMobs());
                $spawner->generateDrops($player->getInventory());
                $player->removeCurrentWindow();
                return;
            }
            if($item->getBlock() instanceof Chest){
                self::openChestPouch($player, $position);
            }
        }));
        $menu->send($player);
    }

    public static function openChestPouch(Player $player, Position $position){
        $player->removeCurrentWindow();
        $spawner = MobFarmSimulator::$manager->get($position->world)->getBlockDataAt($position->x, $position->y, $position->z);
        if(!$spawner instanceof Spawner) return;
        $menu = InvMenu::create(InvMenu::TYPE_CHEST);
        $mobdrops = Mob::getMob($spawner->getMob())->getMobDrops();
        $items = [];
        foreach ($spawner->getChestPouch() as $key => $value) {
            $items[] = $mobdrops[$key]->setLore([
                TextFormat::RESET . TextFormat::GOLD . "Amount: " . TextFormat::AQUA . $value
            ]);
        }
        $menu->getInventory()->setContents($items);
        $menu->setListener(InvMenu::readonly(function(DeterministicInvMenuTransaction $t) use ($position, $mobdrops){
            $spawner = MobFarmSimulator::$manager->get($position->world)->getBlockDataAt($position->x, $position->y, $position->z);
            if(!$spawner instanceof Spawner) return;
            $player = $t->getPlayer();
            $slot = $t->getAction()->getSlot();
            $pouch = $spawner->getChestPouch();
            if(array_key_exists($slot, $pouch)){
                $amount = $pouch[$slot];
                if($items = $player->getInventory()->addItem((clone $mobdrops[$slot])->setCount($amount)->setLore([]))){
                    $pouch[$slot] = $items[0]->getCount();
                    $spawner->setChestPouch($pouch);
                    $player->removeCurrentWindow();
                    self::openChestPouch($player, $position);
                }else{
                    $pouch[$slot] = 0;
                    $spawner->setChestPouch($pouch);
                    $player->removeCurrentWindow();
                    self::openChestPouch($player, $position);
                }
            }
        }));
        $menu->send($player);
    }
}